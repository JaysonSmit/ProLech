package com.example.prolechc.data.repository

import com.example.prolechc.data.mapper.toDomain
import com.example.prolechc.data.remote.datasource.NotificacionRemoteDataSource
import com.example.prolechc.domain.model.Notificacion
import com.example.prolechc.domain.repository.NotificacionRepository

class NotificacionRepositoryImpl(
    private val remoteDataSource: NotificacionRemoteDataSource
) : NotificacionRepository {

    override suspend fun getNotificaciones(): Result<List<Notificacion>> =
        runCatching {
            remoteDataSource.getNotificaciones(todas = true)
                .notificaciones
                .orEmpty()
                .map { it.toDomain() }
        }

    override suspend fun contarNoLeidas(): Result<Int> =
        runCatching {
            remoteDataSource.getNotificaciones().totalNoLeidas
        }

    override suspend fun marcarLeida(id: Int): Result<Unit> =
        runCatching { remoteDataSource.marcarLeida(id) }

    override suspend fun marcarTodasLeidas(): Result<Unit> =
        runCatching { remoteDataSource.marcarTodasLeidas() }
}
