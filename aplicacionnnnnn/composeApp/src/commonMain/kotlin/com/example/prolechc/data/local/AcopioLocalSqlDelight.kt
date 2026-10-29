package com.example.prolechc.data.local

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.domain.model.EstadoSync
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import pe.edu.upeu.prolech.db.AcopioEntity
import pe.edu.upeu.prolech.db.AppDatabase
import kotlin.coroutines.CoroutineContext

class AcopioLocalSqlDelight(
    database: AppDatabase,
    private val dispatcher: CoroutineContext = Dispatchers.Default
) : AcopioLocal {

    private val queries = database.acopioQueries

    override fun observarTodos(): Flow<List<Acopio>> =
        queries.selectAll()
            .asFlow()
            .mapToList(dispatcher)
            .map { lista -> lista.map { it.toDomain() } }

    override suspend fun obtenerTodos(): List<Acopio> = withContext(dispatcher) {
        queries.selectAll().executeAsList().map { it.toDomain() }
    }

    override suspend fun obtenerPorId(id: String): Acopio? = withContext(dispatcher) {
        queries.selectById(id).executeAsOneOrNull()?.toDomain()
    }

    override suspend fun obtenerPendientes(): List<Acopio> = withContext(dispatcher) {
        queries.selectPendientes().executeAsList().map { it.toDomain() }
    }

    override suspend fun guardar(acopio: Acopio) {
        withContext(dispatcher) { queries.upsert(acopio.toEntity()) }
    }

    override suspend fun eliminar(id: String, ahora: Long) {
        withContext(dispatcher) {
            queries.transaction {
                val actual = queries.selectById(id).executeAsOneOrNull() ?: return@transaction
                if (actual.estadoSync == EstadoSync.PENDIENTE_CREAR.name) {
                    queries.deleteById(id)
                } else {
                    queries.marcarPendienteEliminar(ahora, id)
                }
            }
        }
    }

    override suspend fun marcarSincronizado(id: String) {
        withContext(dispatcher) { queries.marcarSincronizado(id) }
    }

    override suspend fun borrarFisico(id: String) {
        withContext(dispatcher) { queries.deleteById(id) }
    }

    override suspend fun marcarError(id: String, motivo: String) {
        withContext(dispatcher) { queries.marcarError(motivo, id) }
    }

    override suspend fun fotosPendientes(): List<Acopio> = withContext(dispatcher) {
        queries.selectFotosPendientes().executeAsList().filter { it.fotoLocal != null }.map { it.toDomain() }
    }

    override suspend fun marcarFotoSubida(id: String) {
        withContext(dispatcher) { queries.marcarFotoSubida(id) }
    }
}

// ── Mappers Entity <-> Dominio ───────────────────────────────────────────────

fun AcopioEntity.toDomain(): Acopio = Acopio(
    id             = id,
    ganaderoId     = ganaderoId,
    ganaderoNombre = ganaderoNombre,
    litros         = litros,
    porcentajeAgua = porcentajeAgua,
    densidad       = densidad,
    estadoCalidad  = EstadoCalidad.valueOf(estadoCalidad),
    actualizadoEn  = actualizadoEn,
    estadoSync     = EstadoSync.valueOf(estadoSync),
    errorSync      = errorSync,
    grasa          = grasa,
    fotoLocal      = fotoLocal,
    fotoSubida     = fotoSubida == 1L,
    observacion     = observacion,
    observacionNota = observacionNota,
    muestraCodigo   = muestraCodigo
)

fun Acopio.toEntity(): AcopioEntity = AcopioEntity(
    id             = id,
    ganaderoId     = ganaderoId,
    ganaderoNombre = ganaderoNombre,
    litros         = litros,
    porcentajeAgua = porcentajeAgua,
    densidad       = densidad,
    estadoCalidad  = estadoCalidad.name,
    actualizadoEn  = actualizadoEn,
    estadoSync     = estadoSync.name,
    errorSync      = errorSync,
    grasa          = grasa,
    fotoLocal      = fotoLocal,
    fotoSubida     = if (fotoSubida) 1L else 0L,
    observacion     = observacion,
    observacionNota = observacionNota,
    muestraCodigo   = muestraCodigo
)
