package com.example.prolechc.domain.repository

import com.example.prolechc.domain.model.Ruta

interface RutaRepository {
    /** Obtiene la asignación activa del conductor autenticado. */
    suspend fun getMiRuta(): Result<Ruta>
}
