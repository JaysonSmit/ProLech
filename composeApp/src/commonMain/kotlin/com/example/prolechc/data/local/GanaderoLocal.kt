package com.example.prolechc.data.local

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import com.example.prolechc.domain.model.GanaderoCampo
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import pe.edu.upeu.prolech.db.AppDatabase
import pe.edu.upeu.prolech.db.GanaderoEntity
import kotlin.coroutines.CoroutineContext

/** Catálogo local de ganaderos (para elegir el proveedor sin red). */
interface GanaderoLocal {
    fun observarTodos(): Flow<List<GanaderoCampo>>
    /** Reemplaza el catálogo completo en una sola transacción. */
    suspend fun reemplazarTodos(ganaderos: List<GanaderoCampo>)
}

class GanaderoLocalSqlDelight(
    database: AppDatabase,
    private val dispatcher: CoroutineContext = Dispatchers.Default
) : GanaderoLocal {

    private val queries = database.ganaderoQueries

    override fun observarTodos(): Flow<List<GanaderoCampo>> =
        queries.selectAllGanaderos()
            .asFlow()
            .mapToList(dispatcher)
            .map { lista -> lista.map { it.toDomain() } }

    override suspend fun reemplazarTodos(ganaderos: List<GanaderoCampo>) {
        withContext(dispatcher) {
            queries.transaction {
                queries.deleteAllGanaderos()
                ganaderos.forEach { queries.upsertGanadero(it.toEntity()) }
            }
        }
    }
}

fun GanaderoEntity.toDomain(): GanaderoCampo = GanaderoCampo(
    id         = id,
    nombre     = nombre,
    dni        = dni,
    suspendido = suspendido == 1L
)

fun GanaderoCampo.toEntity(): GanaderoEntity = GanaderoEntity(
    id         = id,
    nombre     = nombre,
    dni        = dni,
    suspendido = if (suspendido) 1L else 0L
)
