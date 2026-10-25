package com.example.prolechc.domain.repository

import com.example.prolechc.domain.model.AcopioCreado
import com.example.prolechc.domain.model.AcopioHistorial
import com.example.prolechc.domain.model.NuevoAcopio

interface AcopioRepository {
    /** Registra un nuevo acopio de leche y devuelve el resultado del servidor. */
    suspend fun crearAcopio(acopio: NuevoAcopio): Result<AcopioCreado>
    /** Carga el historial de acopios del conductor (últimos [dias] días). */
    suspend fun getHistorial(fecha: String? = null, dias: Int = 90): Result<List<AcopioHistorial>>
    /** Verifica si un ganadero ya entregó leche hoy. */
    suspend fun yaEntregoHoy(ganaderoId: Int): Result<Boolean>
}
