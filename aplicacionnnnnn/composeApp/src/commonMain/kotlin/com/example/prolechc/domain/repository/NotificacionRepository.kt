package com.example.prolechc.domain.repository

import com.example.prolechc.domain.model.Notificacion

interface NotificacionRepository {
    suspend fun getNotificaciones(): Result<List<Notificacion>>
    suspend fun contarNoLeidas(): Result<Int>
    suspend fun marcarLeida(id: Int): Result<Unit>
    suspend fun marcarTodasLeidas(): Result<Unit>
}
