package com.example.prolechc.data.repository

import android.content.Context
import com.example.prolechc.data.repositories.AcopioCreado
import com.example.prolechc.data.repositories.AcopioPendiente
import com.example.prolechc.data.repositories.ResultadoSyncPendientes
import com.example.prolechc.data.local.ColaAcopiosOffline
import com.example.prolechc.data.services.SyncAcopiosWorker
import com.example.prolechc.data.repositories.AcopioHistorial
import com.example.prolechc.data.repositories.IAcopioRepository
import com.example.prolechc.data.repositories.IAuthRepository
import com.example.prolechc.data.repositories.IMiRutaRepository
import com.example.prolechc.data.repositories.INotificacionesRepository
import com.example.prolechc.data.repositories.NotificacionInfo
import com.example.prolechc.data.repositories.ParadaInfo
import com.example.prolechc.data.repositories.RepoResult
import com.example.prolechc.data.repositories.RutaInfo
import com.example.prolechc.data.repositories.UsuarioInfo
import com.example.prolechc.data.api.ApiResult

// ══════════════════════════════════════════════════════════════════════════════
//  RepositoryAdapters
//
//  Adaptan los repositorios Android (que usan ApiResult con DTOs de Retrofit)
//  a las interfaces de commonMain (que usan RepoResult con DTOs compartidos).
//
//  Esto desacopla completamente la capa de red del ViewModel KMP.
// ══════════════════════════════════════════════════════════════════════════════

// ── Auth ──────────────────────────────────────────────────────────────────────

class AuthRepositoryAdapter(context: Context) : IAuthRepository {

    private val context = context.applicationContext
    private val repo = AuthRepository(this.context)

    override suspend fun login(email: String, password: String): RepoResult<UsuarioInfo> {
        return when (val result = repo.login(email, password)) {
            is ApiResult.Success -> {
                // Acopios offline que esperaban una sesión válida para enviarse
                if (ColaAcopiosOffline.hayPendientes(context)) SyncAcopiosWorker.programar(context)
                RepoResult.Success(
                    UsuarioInfo(
                        id             = result.data.user.id,
                        // Usa el campo unificado nombreCompleto del DTO
                        nombreCompleto = result.data.user.nombreCompleto,
                        rol            = result.data.user.rol
                    )
                )
            }
            is ApiResult.Error        -> RepoResult.Error(result.code, result.message)
            is ApiResult.NetworkError -> RepoResult.NetworkError(
                result.exception.message ?: "Error de red"
            )
        }
    }

    override suspend fun logout() = repo.logout()

    override suspend fun isLoggedIn() = repo.isLoggedIn()
}

// ── Mi Ruta ───────────────────────────────────────────────────────────────────

class MiRutaRepositoryAdapter(context: Context) : IMiRutaRepository {

    private val repo = MiRutaRepository(context)

    override suspend fun getMiRuta(): RepoResult<RutaInfo> {
        return when (val result = repo.getMiRuta()) {
            is ApiResult.Success -> {
                val data = result.data

                // El backend actual devuelve { ruta, ganaderos, total }
                // Mapeamos ganaderos → paradas para que la UI funcione igual
                val paradas = data.ganaderos.orEmpty().mapIndexed { index, g ->
                    ParadaInfo(
                        id             = g.id,
                        orden          = index + 1,
                        ganaderoId     = g.id,
                        ganaderoNombre = g.nombre.orEmpty(),
                        ganaderoNii    = g.dni.orEmpty(),
                        direccion      = g.direccion,
                        geoLat         = g.geoLat,   // coordenadas reales del predio
                        geoLon         = g.geoLon,
                        atendido       = false,
                        suspendido     = g.estaSuspendido   // FASE 1: estado del proveedor
                    )
                }

                // También soporta el formato nuevo si el backend lo agrega
                val paradaFormato2 = data.paradas.orEmpty().map { p ->
                    ParadaInfo(
                        id             = p.id,
                        orden          = p.orden,
                        ganaderoId     = p.ganaderoId,
                        ganaderoNombre = p.ganaderoNombre,
                        ganaderoNii    = p.ganaderoNii,
                        direccion      = p.direccion,
                        geoLat         = p.geoLat,
                        geoLon         = p.geoLon,
                        atendido       = p.atendido,
                        suspendido     = false   // formato antiguo no incluye estado
                    )
                }

                val paradasFinales = if (paradaFormato2.isNotEmpty()) paradaFormato2 else paradas

                RepoResult.Success(
                    RutaInfo(
                        // El backend no devuelve asignacion_id — usamos 1 como valor válido
                        // para que guardarAcopio() no caiga en modo mock
                        asignacionId    = data.asignacionId ?: if (paradasFinales.isNotEmpty()) 1 else 0,
                        rutaCodigo      = data.rutaCodigo ?: data.ruta ?: "",
                        rutaNombre      = data.rutaNombre ?: data.ruta ?: "",
                        vehiculoPlaca   = data.vehiculoPlaca ?: "",
                        vehiculoMarca   = data.vehiculoMarca ?: "",
                        capacidadLitros = data.capacidadLitros ?: 1200.0,
                        fecha           = data.fecha ?: "",
                        paradas         = paradasFinales
                    )
                )
            }
            is ApiResult.Error        -> RepoResult.Error(result.code, result.message)
            is ApiResult.NetworkError -> RepoResult.NetworkError(
                result.exception.message ?: "Error de red"
            )
        }
    }
}

// ── Acopio ────────────────────────────────────────────────────────────────────

class AcopioRepositoryAdapter(context: Context) : IAcopioRepository {

    private val context = context.applicationContext
    private val repo = AcopioRepository(this.context)

    override suspend fun crearAcopio(
        asignacionId: Int,
        ganaderoId: Int,
        litros: Double,
        porcentajeAgua: Double,
        acidezPh: Double,
        densidad: Double?,
        grasa: Double?,
        proteina: Double?,
        lactosa: Double?,
        sng: Double?,
        sales: Double?,
        totalSolidos: Double?,
        puntoCongelacion: Double?,
        gpsLat: Double?,
        gpsLon: Double?,
        litrosTina: Double?,
        medicionBaldeConfirmada: Boolean,
        equipoLactoscan: Int?,
        capacidadTachoLitros: Double?,
        observaciones: String?,
        fotoEvidencia: String?,
        ganaderoNombre: String,
        dniGanadero: String
    ): RepoResult<AcopioCreado> {
        return when (val result = repo.crearAcopio(
            ganaderoId              = ganaderoId,
            litros                  = litros,
            porcentajeAgua          = porcentajeAgua.takeIf { it >= 0 },
            acidezPh                = acidezPh.takeIf { it > 0 },
            densidad                = densidad,
            grasa                   = grasa,
            proteina                = proteina,
            lactosa                 = lactosa,
            sng                     = sng,
            sales                   = sales,
            totalSolidos            = totalSolidos,
            puntoCongelacion        = puntoCongelacion,
            medicionBaldeConfirmada = medicionBaldeConfirmada,
            equipoLactoscan         = equipoLactoscan,
            capacidadTachoLitros    = capacidadTachoLitros,
            observaciones           = observaciones,
            fotoEvidencia           = fotoEvidencia,
            ganaderoNombre          = ganaderoNombre,
            dniGanadero             = dniGanadero
        )) {
            is ApiResult.Success -> {
                val acopio = result.data
                RepoResult.Success(
                    AcopioCreado(
                        id           = acopio.id,
                        ticketNumero = acopio.ticketNumero,
                        estado       = acopio.estado,
                        horaAcopio   = acopio.createdAt ?: ""
                    )
                )
            }
            is ApiResult.Error        -> RepoResult.Error(result.code, result.message)
            is ApiResult.NetworkError -> RepoResult.NetworkError(result.exception.message ?: "Error de red")
        }
    }

    override suspend fun getHistorial(fecha: String?, dias: Int): RepoResult<List<AcopioHistorial>> {
        return when (val result = repo.getHistorial(fecha, dias)) {
            is ApiResult.Success -> RepoResult.Success(
                result.data.map { dto ->
                    AcopioHistorial(
                        id               = dto.id,
                        ganaderoNombre   = dto.ganadero?.nombre ?: "Ganadero",
                        ganaderoNii      = dto.ganadero?.dni ?: "",
                        litros           = dto.litros,
                        porcentajeAgua   = dto.porcentajeAgua,
                        acidezPh         = dto.acidezPh,
                        densidad         = dto.densidad,
                        grasa            = dto.grasa,
                        proteina         = dto.proteina,
                        lactosa          = dto.lactosa,
                        sng              = dto.sng,
                        sales            = dto.sales,
                        totalSolidos     = dto.totalSolidos,
                        puntoCongelacion = dto.puntoCongelacion,
                        estado           = dto.estado,
                        fecha            = dto.fecha,
                        createdAt        = dto.createdAt,
                        tieneDescuento   = dto.tieneDescuento,
                        observaciones    = dto.observaciones
                    )
                }
            )
            is ApiResult.Error        -> RepoResult.Error(result.code, result.message)
            is ApiResult.NetworkError -> RepoResult.NetworkError(result.exception.message ?: "Error de red")
        }
    }

    override suspend fun obtenerPendientes(): List<AcopioPendiente> {
        val formato = java.time.format.DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm")
        return ColaAcopiosOffline.listarDelUsuario(context).map { p ->
            AcopioPendiente(
                clientUuid     = p.clientUuid,
                ganaderoId     = p.request.ganaderoId,
                ganaderoNombre = p.ganaderoNombre.ifBlank { "Ganadero #${p.request.ganaderoId}" },
                dniGanadero    = p.dniGanadero,
                litros         = p.request.litros,
                porcentajeAgua = p.request.porcentajeAgua ?: 0.0,
                acidezPh       = p.request.acidezPh ?: 0.0,
                densidad       = p.request.densidad,
                fechaHora      = java.time.Instant.ofEpochMilli(p.creadoEn)
                    .atZone(java.time.ZoneId.systemDefault()).format(formato),
                intentos       = p.intentos,
                error          = p.error,
                rechazado      = p.rechazado
            )
        }
    }

    override suspend fun sincronizarPendientes(): ResultadoSyncPendientes {
        val r = ColaAcopiosOffline.enviarPendientes(context)
        // Si quedó algo por la red o el servidor, el worker sigue intentando solo
        if (r.sinRed || r.restantes > 0) SyncAcopiosWorker.programar(context)
        return ResultadoSyncPendientes(
            enviados       = r.enviados,
            restantes      = r.restantes,
            rechazados     = r.rechazados,
            sinRed         = r.sinRed,
            sesionExpirada = r.sesionExpirada
        )
    }

    override suspend fun descartarPendiente(clientUuid: String) {
        ColaAcopiosOffline.eliminar(context, clientUuid)
    }

    override suspend fun yaEntregoHoy(ganaderoId: Int): RepoResult<Boolean> {
        return when (val result = repo.getHistorial(fecha = java.time.LocalDate.now().toString())) {
            is ApiResult.Success -> RepoResult.Success(
                result.data.any { it.ganadero?.id == ganaderoId }
            )
            is ApiResult.Error        -> RepoResult.Error(result.code, result.message)
            is ApiResult.NetworkError -> RepoResult.NetworkError(result.exception.message ?: "Error de red")
        }
    }
}

// ── Notificaciones ────────────────────────────────────────────────────────────

class NotificacionesRepositoryAdapter(context: Context) : INotificacionesRepository {

    private val api = com.example.prolechc.data.api.RetrofitClient.getInstance(context)

    override suspend fun getNotificaciones(): RepoResult<List<NotificacionInfo>> {
        return try {
            val response = api.getNotificaciones()
            if (response.isSuccessful) {
                val lista = response.body()?.notificaciones.orEmpty().map { dto ->
                    NotificacionInfo(
                        id           = dto.id,
                        titulo       = dto.titulo,
                        mensaje      = dto.mensaje,
                        tipo         = dto.tipo,
                        icono        = dto.icono,
                        referenciaId = dto.referenciaId,
                        createdAt    = dto.createdAt
                    )
                }
                RepoResult.Success(lista)
            } else {
                RepoResult.Error(response.code(), "Error al cargar notificaciones")
            }
        } catch (e: java.io.IOException) {
            RepoResult.NetworkError(e.message ?: "Error de red")
        } catch (e: Exception) {
            RepoResult.NetworkError(e.message ?: "Error inesperado")
        }
    }

    override suspend fun contarNoLeidas(): RepoResult<Int> {
        return try {
            val response = api.getNotificaciones()
            if (response.isSuccessful) {
                RepoResult.Success(response.body()?.totalNoLeidas ?: 0)
            } else {
                RepoResult.Error(response.code(), "Error")
            }
        } catch (e: Exception) {
            RepoResult.NetworkError(e.message ?: "Error de red")
        }
    }

    override suspend fun marcarLeida(id: Int): RepoResult<Unit> {
        return try {
            val response = api.marcarNotificacionLeida(id)
            if (response.isSuccessful) RepoResult.Success(Unit)
            else RepoResult.Error(response.code(), "Error al marcar leída")
        } catch (e: Exception) {
            RepoResult.NetworkError(e.message ?: "Error de red")
        }
    }

    override suspend fun marcarTodasLeidas(): RepoResult<Unit> {
        return try {
            val response = api.marcarTodasLeidas()
            if (response.isSuccessful) RepoResult.Success(Unit)
            else RepoResult.Error(response.code(), "Error")
        } catch (e: Exception) {
            RepoResult.NetworkError(e.message ?: "Error de red")
        }
    }
}
