package com.example.prolechc.data.sync

import com.example.prolechc.data.local.AcopioLocal
import com.example.prolechc.data.local.GanaderoLocal
import com.example.prolechc.data.network.MonitorConectividad
import com.example.prolechc.data.remote.datasource.AcopioRemoto
import com.example.prolechc.data.remote.datasource.ErrorHttp
import com.example.prolechc.data.remote.datasource.GanaderoRemoto
import com.example.prolechc.data.tracking.SubidorGps
import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.EstadoSync
import com.example.prolechc.domain.model.ResultadoSync
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.filter
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import kotlinx.coroutines.launch
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock

/** Sube la foto de evidencia de un acopio ya sincronizado. */
fun interface SubidorFotos {
    suspend fun subir(acopio: Acopio)
}

/** El servidor respondió 401 en medio de la sincronización. */
private class SesionVencida : Exception()

/**
 * Orquesta la sincronización entre SQLDelight (fuente de verdad) y la API.
 *
 * 1. PUSH: sube los registros pendientes (crear / actualizar / eliminar).
 * 2. PULL: baja el estado del servidor y lo fusiona con Last-Write-Wins
 *    usando `actualizadoEn`.
 * 3. CATÁLOGO, FOTOS, GPS y RUTA: tareas secundarias para trabajar sin red.
 *
 * Errores:
 *  - 4xx (dato inválido) → el registro queda "Con error" con el motivo y no se reintenta
 *    hasta que el chofer lo corrija.
 *  - 401 → se detiene todo y se avisa con [alSesionVencida]; los datos offline se conservan.
 *  - Sin red / 5xx → queda pendiente y se reintenta en la próxima sincronización.
 *
 * Con [iniciar] se suscribe al [MonitorConectividad] y sincroniza
 * automáticamente cada vez que el dispositivo recupera la red.
 */
class SyncManager(
    private val local: AcopioLocal,
    private val remoto: AcopioRemoto,
    private val monitor: MonitorConectividad,
    private val scope: CoroutineScope,
    private val ganaderoLocal: GanaderoLocal? = null,
    private val ganaderoRemoto: GanaderoRemoto? = null,
    /** Sin token Sanctum la API responde 401: no tiene sentido intentar. */
    private val sesionActiva: suspend () -> Boolean = { true },
    /** Puntos GPS del recorrido guardados sin señal. */
    private val subidorGps: SubidorGps? = null,
    /** Ruta del día (paradas y mapa) para tenerla offline. */
    private val refrescarRuta: (suspend () -> Unit)? = null,
    /** Fotos de evidencia tomadas sin señal. */
    private val subidorFotos: SubidorFotos? = null,
    /** Se llama cuando el servidor rechaza el token (la app pide volver a ingresar). */
    private val alSesionVencida: () -> Unit = {}
) {
    private val mutex = Mutex()
    private var observador: Job? = null

    private val _sincronizando = MutableStateFlow(false)
    val sincronizando: StateFlow<Boolean> = _sincronizando.asStateFlow()

    private val _ultimoResultado = MutableStateFlow<ResultadoSync?>(null)
    val ultimoResultado: StateFlow<ResultadoSync?> = _ultimoResultado.asStateFlow()

    /** Sincroniza automáticamente en cada transición offline -> online (StateFlow ya es distinct). */
    fun iniciar() {
        if (observador != null) return
        observador = monitor.enLinea
            .filter { it }
            .onEach { sincronizar() }
            .launchIn(scope)
    }

    fun detener() {
        observador?.cancel()
        observador = null
    }

    /** Lanza una sincronización en segundo plano sin bloquear al llamador. */
    fun solicitarSincronizacion() {
        if (monitor.hayConexion()) scope.launch { sincronizar() }
    }

    /** Push + Pull. Nunca lanza excepción: los problemas se reportan en [ResultadoSync]. */
    suspend fun sincronizar(): ResultadoSync = mutex.withLock {
        if (!monitor.hayConexion()) {
            return ResultadoSync(sinConexion = true).also { _ultimoResultado.value = it }
        }
        if (!sesionActiva()) {
            return ResultadoSync(sinSesion = true).also { _ultimoResultado.value = it }
        }
        _sincronizando.value = true
        try {
            val push = push()
            val (bajados, fallidosPull) = pull()
            refrescarGanaderos()
            opcional { subirFotos() }
            opcional { subidorGps?.subirPendientes() }
            opcional { refrescarRuta?.invoke() }
            ResultadoSync(
                subidos  = push.subidos,
                bajados  = bajados,
                fallidos = push.fallidos + fallidosPull,
                conError = push.conError
            ).also { _ultimoResultado.value = it }
        } catch (e: SesionVencida) {
            alSesionVencida()
            ResultadoSync(sinSesion = true, sesionVencida = true).also { _ultimoResultado.value = it }
        } finally {
            _sincronizando.value = false
        }
    }

    /** Tareas secundarias: si fallan no afectan el resultado de los acopios. */
    private suspend fun opcional(tarea: suspend () -> Unit) {
        try {
            tarea()
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            // Se reintenta en la próxima sincronización
        }
    }

    // ── PUSH ──────────────────────────────────────────────────────────────────

    private data class ResultadoPush(val subidos: Int, val fallidos: Int, val conError: Int)

    private suspend fun push(): ResultadoPush {
        var subidos = 0
        var fallidos = 0
        var conError = 0
        for (acopio in local.obtenerPendientes()) {
            // Rechazado antes: espera a que el chofer lo corrija (al editarlo se limpia el error)
            if (acopio.errorSync != null) {
                conError++
                continue
            }
            try {
                when (acopio.estadoSync) {
                    EstadoSync.PENDIENTE_CREAR -> {
                        remoto.crear(acopio)
                        local.marcarSincronizado(acopio.id)
                    }
                    EstadoSync.PENDIENTE_ACTUALIZAR -> {
                        remoto.actualizar(acopio)
                        local.marcarSincronizado(acopio.id)
                    }
                    EstadoSync.PENDIENTE_ELIMINAR -> {
                        remoto.eliminar(acopio.id)
                        local.borrarFisico(acopio.id)
                    }
                    EstadoSync.SINCRONIZADO -> continue
                }
                subidos++
            } catch (e: CancellationException) {
                throw e
            } catch (e: ErrorHttp) {
                when {
                    e.codigo == 401 -> throw SesionVencida()
                    esRechazoDefinitivo(e.codigo) -> {
                        local.marcarError(acopio.id, e.detalle)
                        conError++
                    }
                    else -> fallidos++
                }
            } catch (e: Exception) {
                // Sin red / timeout: queda pendiente y se reintenta en la próxima sincronización
                fallidos++
            }
        }
        return ResultadoPush(subidos, fallidos, conError)
    }

    // ── PULL + Last-Write-Wins ───────────────────────────────────────────────

    private suspend fun pull(): Pair<Int, Int> {
        val remotos = try {
            remoto.obtenerTodos()
        } catch (e: CancellationException) {
            throw e
        } catch (e: ErrorHttp) {
            if (e.codigo == 401) throw SesionVencida()
            return 0 to 1
        } catch (e: Exception) {
            return 0 to 1
        }

        var bajados = 0
        for (servidor in remotos) {
            val enLocal = local.obtenerPorId(servidor.id)
            if (debeAplicarServidor(enLocal, servidor)) {
                // La foto vive en el celular: se conserva al adoptar la versión del servidor
                local.guardar(
                    servidor.copy(
                        estadoSync = EstadoSync.SINCRONIZADO,
                        fotoLocal  = enLocal?.fotoLocal,
                        fotoSubida = servidor.fotoSubida || (enLocal?.fotoSubida ?: false)
                    )
                )
                bajados++
            }
        }

        // Registros ya sincronizados que el servidor ya no tiene: se borraron en otro equipo
        val idsServidor = remotos.mapTo(HashSet()) { it.id }
        local.obtenerTodos()
            .filter { it.estadoSync == EstadoSync.SINCRONIZADO && it.id !in idsServidor }
            .forEach { local.borrarFisico(it.id) }

        return bajados to 0
    }

    // ── Fotos de evidencia ───────────────────────────────────────────────────

    private suspend fun subirFotos() {
        val subidor = subidorFotos ?: return
        for (acopio in local.fotosPendientes()) {
            try {
                subidor.subir(acopio)
                local.marcarFotoSubida(acopio.id)
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                // Se reintenta en la próxima sincronización
            }
        }
    }

    // ── Catálogo de ganaderos ────────────────────────────────────────────────

    private suspend fun refrescarGanaderos() {
        val local = ganaderoLocal ?: return
        val remoto = ganaderoRemoto ?: return
        try {
            val ganaderos = remoto.obtenerGanaderos()
            // Si el chofer no tiene ruta hoy, se conserva el último catálogo descargado
            if (ganaderos.isNotEmpty()) local.reemplazarTodos(ganaderos)
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            // El catálogo es opcional: el último descargado sigue disponible offline
        }
    }

    companion object {
        /**
         * Regla Last-Write-Wins:
         * - No existe localmente -> se inserta.
         * - Local SINCRONIZADO -> el servidor es la verdad (si cambió).
         * - Local con cambios pendientes -> gana el `actualizadoEn` más reciente.
         */
        fun debeAplicarServidor(local: Acopio?, servidor: Acopio): Boolean = when {
            local == null                               -> true
            local.estadoSync == EstadoSync.SINCRONIZADO -> !local.mismosDatosQue(servidor)
            else                                        -> servidor.actualizadoEn > local.actualizadoEn
        }

        /** 4xx que no se arreglan reintentando (sí se reintentan 401, 408 y 429). */
        fun esRechazoDefinitivo(codigo: Int): Boolean = codigo in 400..499 && codigo !in setOf(401, 408, 429)
    }
}
