package com.example.prolechc.domain.usecase

import com.example.prolechc.domain.model.Ruta
import com.example.prolechc.domain.repository.RutaRepository

class GetMiRutaUseCase(private val repository: RutaRepository) {
    suspend operator fun invoke(): Result<Ruta> = repository.getMiRuta()
}
