package com.example.prolechc.domain.usecase

import com.example.prolechc.domain.model.AcopioHistorial
import com.example.prolechc.domain.repository.AcopioRepository

class GetHistorialAcopiosUseCase(private val repository: AcopioRepository) {
    suspend operator fun invoke(
        fecha: String? = null,
        dias: Int = 90
    ): Result<List<AcopioHistorial>> = repository.getHistorial(fecha, dias)
}
