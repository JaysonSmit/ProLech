package com.example.prolechc.domain.usecase

import com.example.prolechc.domain.model.AcopioCreado
import com.example.prolechc.domain.model.NuevoAcopio
import com.example.prolechc.domain.repository.AcopioRepository

class CrearAcopioUseCase(private val repository: AcopioRepository) {
    suspend operator fun invoke(acopio: NuevoAcopio): Result<AcopioCreado> =
        repository.crearAcopio(acopio)
}
