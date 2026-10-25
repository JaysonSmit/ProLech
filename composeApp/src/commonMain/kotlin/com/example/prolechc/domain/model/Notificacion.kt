package com.example.prolechc.domain.model

data class Notificacion(
    val id: Int,
    val titulo: String,
    val mensaje: String,
    val tipo: String,
    val icono: String,
    val referenciaId: Int?,
    val createdAt: String
)
