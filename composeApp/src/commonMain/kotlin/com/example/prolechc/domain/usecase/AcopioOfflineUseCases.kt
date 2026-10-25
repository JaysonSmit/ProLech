package com.example.prolechc.domain.usecase

import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.GanaderoCampo
import com.example.prolechc.domain.model.ResultadoSync
import com.example.prolechc.domain.repository.AcopioOfflineRepository
import kotlinx.coroutines.flow.Flow

class ObservarAcopiosUseCase(private val repository: AcopioOfflineRepository) {
    operator fun invoke(): Flow<List<Acopio>> = repository.observarAcopios()
}

class ObservarGanaderosUseCase(private val repository: AcopioOfflineRepository) {
    operator fun invoke(): Flow<List<GanaderoCampo>> = repository.observarGanaderos()
}

class RegistrarAcopioUseCase(private val repository: AcopioOfflineRepository) {
    suspend operator fun invoke(
        ganaderoId: Long,
        ganaderoNombre: String,
        litros: Double,
        porcentajeAgua: Double,
        densidad: Double
    ): Result<Acopio> = runCatching {
        require(ganaderoId > 0) { "Selecciona un ganadero válido" }
        require(ganaderoNombre.isNotBlank()) { "El nombre del ganadero es obligatorio" }
        require(litros > 0) { "Los litros deben ser mayores a 0" }
        require(porcentajeAgua in 0.0..100.0) { "El % de agua debe estar entre 0 y 100" }
        require(densidad in 1.0..1.1) { "La densidad debe estar entre 1.000 y 1.100" }
        repository.registrar(ganaderoId, ganaderoNombre.trim(), litros, porcentajeAgua, densidad)
    }
}

class ActualizarAcopioUseCase(private val repository: AcopioOfflineRepository) {
    suspend operator fun invoke(acopio: Acopio): Result<Acopio> = runCatching {
        require(acopio.ganaderoId > 0) { "Selecciona un ganadero válido" }
        require(acopio.ganaderoNombre.isNotBlank()) { "El nombre del ganadero es obligatorio" }
        require(acopio.litros > 0) { "Los litros deben ser mayores a 0" }
        require(acopio.porcentajeAgua in 0.0..100.0) { "El % de agua debe estar entre 0 y 100" }
        require(acopio.densidad in 1.0..1.1) { "La densidad debe estar entre 1.000 y 1.100" }
        repository.actualizar(acopio)
    }
}

class EliminarAcopioUseCase(private val repository: AcopioOfflineRepository) {
    suspend operator fun invoke(id: String): Result<Unit> = runCatching { repository.eliminar(id) }
}

class SincronizarAcopiosUseCase(private val repository: AcopioOfflineRepository) {
    suspend operator fun invoke(): ResultadoSync = repository.sincronizar()
}
