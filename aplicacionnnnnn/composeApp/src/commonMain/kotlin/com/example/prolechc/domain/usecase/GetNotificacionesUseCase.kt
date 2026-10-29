package com.example.prolechc.domain.usecase

import com.example.prolechc.domain.model.Notificacion
import com.example.prolechc.domain.repository.NotificacionRepository

class GetNotificacionesUseCase(private val repository: NotificacionRepository) {
    suspend operator fun invoke(): Result<List<Notificacion>> =
        repository.getNotificaciones()
}
