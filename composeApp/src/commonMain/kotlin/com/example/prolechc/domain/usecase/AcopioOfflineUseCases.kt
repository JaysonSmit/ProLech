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
        porcentajeAgua: Double = 0.0,
        densidad: Double = 0.0,
        grasa: Double? = null,
        fotoLocal: String? = null,
        observacion: String? = null,
        observacionNota: String? = null,
        muestraCodigo: String? = null
    ): Result<Acopio> = runCatching {
        require(ganaderoId > 0) { "Selecciona un ganadero válido" }
        require(ganaderoNombre.isNotBlank()) { "El nombre del ganadero es obligatorio" }
        require(litros > 0) { "Los litros deben ser mayores a 0" }
        require(porcentajeAgua in 0.0..100.0) { "El % de agua debe estar entre 0 y 100" }
        // 0 = no medida en campo (la calidad la mide la planta con muestras sorpresa)
        require(densidad == 0.0 || densidad in 1.0..1.1) { "La densidad debe estar entre 1.000 y 1.100" }
        require(grasa == null || grasa in 0.0..15.0) { "La grasa debe estar entre 0 y 15 %" }
        repository.registrar(
            ganaderoId, ganaderoNombre.trim(), litros, porcentajeAgua, densidad, grasa, fotoLocal,
            observacion, observacionNota, muestraCodigo
        )
    }
}

class ActualizarAcopioUseCase(private val repository: AcopioOfflineRepository) {
    suspend operator fun invoke(acopio: Acopio): Result<Acopio> = runCatching {
        require(acopio.ganaderoId > 0) { "Selecciona un ganadero válido" }
        require(acopio.ganaderoNombre.isNotBlank()) { "El nombre del ganadero es obligatorio" }
        require(acopio.litros > 0) { "Los litros deben ser mayores a 0" }
        require(acopio.porcentajeAgua in 0.0..100.0) { "El % de agua debe estar entre 0 y 100" }
        require(acopio.densidad == 0.0 || acopio.densidad in 1.0..1.1) { "La densidad debe estar entre 1.000 y 1.100" }
        repository.actualizar(acopio)
    }
}

class EliminarAcopioUseCase(private val repository: AcopioOfflineRepository) {
    suspend operator fun invoke(id: String): Result<Unit> = runCatching { repository.eliminar(id) }
}

class SincronizarAcopiosUseCase(private val repository: AcopioOfflineRepository) {
    suspend operator fun invoke(): ResultadoSync = repository.sincronizar()
}
