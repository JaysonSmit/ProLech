package com.example.prolechc.data.repository

import com.example.prolechc.data.mapper.toDomain
import com.example.prolechc.data.remote.datasource.RutaRemoteDataSource
import com.example.prolechc.domain.model.Ruta
import com.example.prolechc.domain.repository.RutaRepository

class RutaRepositoryImpl(
    private val remoteDataSource: RutaRemoteDataSource
) : RutaRepository {

    override suspend fun getMiRuta(): Result<Ruta> =
        runCatching { remoteDataSource.getMiRuta().toDomain() }
}
