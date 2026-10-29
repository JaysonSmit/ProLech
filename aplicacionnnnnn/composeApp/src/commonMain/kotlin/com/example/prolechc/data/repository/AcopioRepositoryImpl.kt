package com.example.prolechc.data.repository

import com.example.prolechc.data.mapper.toDomain
import com.example.prolechc.data.mapper.toRequestDto
import com.example.prolechc.data.remote.datasource.AcopioRemoteDataSource
import com.example.prolechc.domain.model.AcopioCreado
import com.example.prolechc.domain.model.AcopioHistorial
import com.example.prolechc.domain.model.NuevoAcopio
import com.example.prolechc.domain.repository.AcopioRepository
import com.example.prolechc.domain.Tiempo

class AcopioRepositoryImpl(
    private val remoteDataSource: AcopioRemoteDataSource
) : AcopioRepository {

    override suspend fun crearAcopio(acopio: NuevoAcopio): Result<AcopioCreado> =
        runCatching {
            val fechaHoy = Tiempo.fechaHoy()
            val response = remoteDataSource.crearAcopio(acopio.toRequestDto(fechaHoy))
            requireNotNull(response.acopio) { "El servidor no devolvió datos del acopio" }
                .toDomain()
        }

    override suspend fun getHistorial(fecha: String?, dias: Int): Result<List<AcopioHistorial>> =
        runCatching {
            remoteDataSource.getHistorial(fecha, dias)
                .acopios
                .orEmpty()
                .map { it.toDomain() }
        }

    override suspend fun yaEntregoHoy(ganaderoId: Int): Result<Boolean> =
        runCatching {
            val fechaHoy = Tiempo.fechaHoy()
            remoteDataSource.getHistorial(fecha = fechaHoy, dias = 1)
                .acopios
                .orEmpty()
                .any { it.ganaderoId == ganaderoId }
        }
}
