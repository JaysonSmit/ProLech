package com.example.prolechc.data.local

import com.example.prolechc.domain.model.Acopio
import kotlinx.coroutines.flow.Flow

/** Fuente de datos local (SQLDelight). Es la única fuente de verdad para la UI. */
interface AcopioLocal {
    /** Emite la lista visible (excluye PENDIENTE_ELIMINAR) cada vez que la tabla cambia. */
    fun observarTodos(): Flow<List<Acopio>>
    suspend fun obtenerTodos(): List<Acopio>
    suspend fun obtenerPorId(id: String): Acopio?
    suspend fun obtenerPendientes(): List<Acopio>
    suspend fun guardar(acopio: Acopio)
    /**
     * Borrado lógico/físico según el estado:
     * PENDIENTE_CREAR -> se borra físicamente (nunca llegó al servidor);
     * en otro caso -> PENDIENTE_ELIMINAR hasta que el servidor confirme.
     */
    suspend fun eliminar(id: String, ahora: Long)
    suspend fun marcarSincronizado(id: String)
    suspend fun borrarFisico(id: String)
}
