package com.example.prolechc.data.mapper

import com.example.prolechc.data.remote.dto.LoginResponseDto
import com.example.prolechc.domain.model.Usuario

fun LoginResponseDto.toDomain(): Usuario = Usuario(
    id             = user.id,
    nombreCompleto = user.nombreCompleto,
    rol            = user.rol
)
