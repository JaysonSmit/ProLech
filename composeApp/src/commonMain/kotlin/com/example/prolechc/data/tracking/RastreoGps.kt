package com.example.prolechc.data.tracking

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import com.example.prolechc.data.location.ProveedorUbicacion
import com.example.prolechc.data.network.MonitorConectividad
import com.example.prolechc.data.remote.datasource.TrackingRemoto
import com.example.prolechc.domain.model.Coordenada
import com.example.prolechc.domain.model.LecturaGps
import com.example.prolechc.domain.ruta.Geo
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.catch
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.coroutines.withContext
import com.example.prolechc.domain.Tiempo
import pe.edu.upeu.prolech.db.AppDatabase
import kotlin.coroutines.CoroutineContext

/** Sube los puntos GPS pendientes (lo usa el SyncManager al recuperar señal). */
fun interface SubidorGps {
    suspend fun subirPendientes(): Int
}

/**
 * Rastreo del recorrido del chofer, offline-first:
 *  1. Cada lectura útil del GPS se guarda en SQLDelight (aunque no haya señal).
 *  2. Mientras dure la ruta y haya red, sube lotes cada [intervaloSubidaMs].
 *  3. Lo que quede pendiente lo sube el SyncManager cuando vuelva la señal.
 */
class RastreoGps(
    database: AppDatabase,
    private val proveedor: ProveedorUbicacion,
    private val remoto: TrackingRemoto,
    private val monitor: MonitorConectividad,
    private val scope: CoroutineScope,
    private val dispatcher: CoroutineContext = Dispatchers.Default,
    private val reloj: () -> Long = { Tiempo.ahoraMs() },
    private val intervaloSubidaMs: Long = 60_000,
    /** Se descartan lecturas con un error mayor a esto (GPS sin fix). */
    private val precisionMaximaM: Double = 60.0,
    /** No guarda un punto nuevo si el chofer se movió menos que esto... */
    private val distanciaMinimaM: Double = 15.0,
    /** ...salvo que haya pasado este tiempo (para saber que sigue detenido ahí). */
    private val tiempoMaximoSinPuntoMs: Long = 120_000
) : SubidorGps {

    private val queries = database.puntoGpsQueries
    private val mutexSubida = Mutex()
    private var trabajo: Job? = null
    private var ultimoGuardado: LecturaGps? = null

    private val _activo = MutableStateFlow(false)
    val activo: StateFlow<Boolean> = _activo.asStateFlow()

    private val _ultimaLectura = MutableStateFlow<LecturaGps?>(null)
    val ultimaLectura: StateFlow<LecturaGps?> = _ultimaLectura.asStateFlow()

    private val _error = MutableStateFlow<String?>(null)
    val error: StateFlow<String?> = _error.asStateFlow()

    /** Trazo recorrido desde [desdeMs] (para dibujarlo en el mapa). */
    fun recorrido(desdeMs: Long): Flow<List<Coordenada>> =
        queries.puntosDesde(desdeMs)
            .asFlow()
            .mapToList(dispatcher)
            .map { filas -> filas.map { Coordenada(it.lat, it.lon) } }

    fun iniciar() {
        if (trabajo?.isActive == true) return
        _error.value = null
        _activo.value = true
        trabajo = scope.launch {
            launch {
                proveedor.lecturas()
                    .catch { e -> _error.value = e.message ?: "No se pudo leer el GPS" }
                    .collect { registrar(it) }
            }
            launch {
                while (isActive) {
                    delay(intervaloSubidaMs)
                    subirPendientes()
                }
            }
        }
    }

    /** Detiene el GPS e intenta subir lo último marcando la ruta como finalizada. */
    fun detener() {
        detenerSinSubir()
        scope.launch { subirPendientes(finalizado = true) }
    }

    /** Apaga el GPS sin lanzar la subida (quien llama la hace y la espera). */
    fun detenerSinSubir() {
        trabajo?.cancel()
        trabajo = null
        _activo.value = false
        ultimoGuardado = null
    }

    /** Guarda la lectura si aporta información al trazo. */
    suspend fun registrar(lectura: LecturaGps) {
        _ultimaLectura.value = lectura
        if ((lectura.precisionM ?: 0.0) > precisionMaximaM) return

        val previo = ultimoGuardado
        if (previo != null) {
            val metros = Geo.distanciaKm(previo.coordenada, lectura.coordenada) * 1000
            val pasoMucho = lectura.registradoEn - previo.registradoEn >= tiempoMaximoSinPuntoMs
            if (metros < distanciaMinimaM && !pasoMucho) return
        }
        withContext(dispatcher) {
            queries.insertarPunto(
                lectura.coordenada.lat, lectura.coordenada.lon,
                lectura.precisionM, lectura.velocidadKmh, lectura.registradoEn
            )
        }
        ultimoGuardado = lectura
    }

    override suspend fun subirPendientes(): Int = subirPendientes(finalizado = false)

    suspend fun subirPendientes(finalizado: Boolean): Int = mutexSubida.withLock {
        if (!monitor.hayConexion()) return 0
        var subidos = 0
        try {
            while (true) {
                val lote = withContext(dispatcher) { queries.pendientesDeEnvio(LOTE).executeAsList() }
                if (lote.isEmpty()) break
                val esUltimo = lote.size < LOTE
                remoto.enviarLote(
                    lote.map { LecturaGps(Coordenada(it.lat, it.lon), it.registradoEn, it.precisionM, it.velocidadKmh) },
                    finalizado = finalizado && esUltimo
                )
                withContext(dispatcher) { queries.marcarEnviados(lote.map { it.id }) }
                subidos += lote.size
                if (esUltimo) break
            }
            // Los puntos ya subidos solo se conservan unos días en el celular
            withContext(dispatcher) { queries.borrarEnviadosAntesDe(reloj() - RETENCION_MS) }
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            // Quedan pendientes y se reintenta luego
        }
        subidos
    }

    suspend fun pendientes(): Long = withContext(dispatcher) { queries.contarPendientes().executeAsOne() }

    companion object {
        const val LOTE = 200L
        private const val RETENCION_MS = 3L * 24 * 60 * 60 * 1000
    }
}
