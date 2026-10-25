package com.example.prolechc.domain.repository

import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.GanaderoCampo
import com.example.prolechc.domain.model.ResultadoSync
import kotlinx.coroutines.flow.Flow

/** Repositorio Offline-First: toda escritura va primero a SQLDelight. */
interface AcopioOfflineRepository {
    fun observarAcopios(): Flow<List<Acopio>>
    /** Catálogo de ganaderos de la ruta guardado en el celular. */
    fun observarGanaderos(): Flow<List<GanaderoCampo>>
    suspend fun registrar(
        ganaderoId: Long,
        ganaderoNombre: String,
        litros: Double,
        porcentajeAgua: Double,
        densidad: Double
    ): Acopio
    suspend fun actualizar(acopio: Acopio): Acopio
    suspend fun eliminar(id: String)
    suspend fun sincronizar(): ResultadoSync
}
