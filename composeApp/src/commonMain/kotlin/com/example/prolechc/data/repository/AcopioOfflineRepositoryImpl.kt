package com.example.prolechc.data.repository

import com.example.prolechc.data.local.AcopioLocal
import com.example.prolechc.data.local.GanaderoLocal
import com.example.prolechc.data.sync.SyncManager
import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.domain.model.EstadoSync
import com.example.prolechc.domain.model.GanaderoCampo
import com.example.prolechc.domain.model.ResultadoSync
import com.example.prolechc.domain.repository.AcopioOfflineRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOf
import com.example.prolechc.domain.Tiempo
import kotlin.uuid.ExperimentalUuidApi
import kotlin.uuid.Uuid

@OptIn(ExperimentalUuidApi::class)
class AcopioOfflineRepositoryImpl(
    private val local: AcopioLocal,
    private val syncManager: SyncManager,
    private val ganaderoLocal: GanaderoLocal? = null,
    private val reloj: () -> Long = { Tiempo.ahoraMs() },
    private val generarId: () -> String = { Uuid.random().toString() }
) : AcopioOfflineRepository {

    override fun observarAcopios(): Flow<List<Acopio>> = local.observarTodos()

    override fun observarGanaderos(): Flow<List<GanaderoCampo>> =
        ganaderoLocal?.observarTodos() ?: flowOf(emptyList())

    override suspend fun registrar(
        ganaderoId: Long,
        ganaderoNombre: String,
        litros: Double,
        porcentajeAgua: Double,
        densidad: Double,
        grasa: Double?,
        fotoLocal: String?,
        observacion: String?,
        observacionNota: String?,
        muestraCodigo: String?
    ): Acopio {
        val acopio = Acopio(
            id             = generarId(),
            ganaderoId     = ganaderoId,
            ganaderoNombre = ganaderoNombre,
            litros         = litros,
            porcentajeAgua = porcentajeAgua,
            densidad       = densidad,
            estadoCalidad  = calidadDeCampo(porcentajeAgua, densidad),
            actualizadoEn  = reloj(),
            estadoSync     = EstadoSync.PENDIENTE_CREAR,
            grasa          = grasa,
            fotoLocal      = fotoLocal,
            observacion     = observacion,
            observacionNota = observacionNota?.takeIf { it.isNotBlank() },
            muestraCodigo   = muestraCodigo
        )
        local.guardar(acopio)
        syncManager.solicitarSincronizacion()
        return acopio
    }

    override suspend fun actualizar(acopio: Acopio): Acopio {
        val previo = local.obtenerPorId(acopio.id)
        // Si aún no se subió, sigue siendo una creación pendiente
        val estado = if (previo?.estadoSync == EstadoSync.PENDIENTE_CREAR) {
            EstadoSync.PENDIENTE_CREAR
        } else {
            EstadoSync.PENDIENTE_ACTUALIZAR
        }
        val actualizado = acopio.copy(
            estadoCalidad = calidadDeCampo(acopio.porcentajeAgua, acopio.densidad),
            actualizadoEn = reloj(),
            estadoSync    = estado,
            // Al corregirlo, el registro rechazado vuelve a intentarse
            errorSync     = null,
            // Foto nueva → hay que volver a subirla
            fotoSubida    = acopio.fotoSubida && acopio.fotoLocal == previo?.fotoLocal
        )
        local.guardar(actualizado)
        syncManager.solicitarSincronizacion()
        return actualizado
    }

    override suspend fun eliminar(id: String) {
        local.eliminar(id, reloj())
        syncManager.solicitarSincronizacion()
    }

    override suspend fun sincronizar(): ResultadoSync = syncManager.sincronizar()
}

/**
 * El chofer ya no mide la calidad (la miden las muestras sorpresa de la planta): sin medidas
 * el acopio queda APROBADO. Si llegan medidas (versiones anteriores), se usa la regla de campo.
 */
internal fun calidadDeCampo(porcentajeAgua: Double, densidad: Double): EstadoCalidad =
    if (porcentajeAgua == 0.0 && densidad == 0.0) EstadoCalidad.APROBADO else EstadoCalidad.evaluar(porcentajeAgua, densidad)
