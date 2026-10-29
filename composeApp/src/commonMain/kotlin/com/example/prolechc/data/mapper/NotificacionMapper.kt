package com.example.prolechc.data.mapper

import com.example.prolechc.data.remote.dto.NotificacionDto
import com.example.prolechc.domain.model.Notificacion

fun NotificacionDto.toDomain(): Notificacion = Notificacion(
    id          = id,
    titulo      = titulo,
    mensaje     = mensaje,
    tipo        = tipo,
    icono       = icono,
    referenciaId = referenciaId,
    createdAt   = createdAt,
    leida       = leida
)
