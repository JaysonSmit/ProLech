package com.example.prolechc.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToOneOrNull
import com.example.prolechc.data.mapper.toDomain
import com.example.prolechc.data.remote.datasource.RutaRemoteDataSource
import com.example.prolechc.data.remote.dto.MiRutaResponseDto
import com.example.prolechc.domain.model.Ruta
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import com.example.prolechc.domain.Tiempo
import kotlinx.serialization.json.Json
import pe.edu.upeu.prolech.db.AppDatabase
import kotlin.coroutines.CoroutineContext

/** Ruta guardada junto a cuándo se descargó (para avisar si está desactualizada). */
data class RutaGuardada(val ruta: Ruta, val actualizadaEn: Long)

/**
 * Ruta del día offline-first: la UI lee siempre la copia en SQLDelight y
 * [refrescar] la reemplaza cuando hay señal.
 */
class RutaDiaRepositorio(
    database: AppDatabase,
    /** Descarga el JSON de /api/mi-ruta (en producción: [RutaRemoteDataSource.getMiRutaJson]). */
    private val descargarJson: suspend () -> String,
    private val dispatcher: CoroutineContext = Dispatchers.Default,
    private val reloj: () -> Long = { Tiempo.ahoraMs() }
) {
    private val queries = database.rutaCacheQueries
    private val json = Json { ignoreUnknownKeys = true; coerceInputValues = true }

    fun observar(): Flow<RutaGuardada?> =
        queries.obtenerRuta()
            .asFlow()
            .mapToOneOrNull(dispatcher)
            .map { fila -> fila?.let { RutaGuardada(decodificar(it.json), it.actualizadoEn) } }

    suspend fun refrescar(): Result<Unit> = runCatching {
        val cuerpo = descargarJson()
        decodificar(cuerpo) // valida antes de reemplazar la copia buena
        withContext(dispatcher) { queries.guardarRuta(cuerpo, reloj()) }
    }

    private fun decodificar(texto: String): Ruta =
        json.decodeFromString(MiRutaResponseDto.serializer(), texto).toDomain()
}
