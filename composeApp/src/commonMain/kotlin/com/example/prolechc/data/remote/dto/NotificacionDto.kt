package com.example.prolechc.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class NotificacionesResponseDto(
    @SerialName("notificaciones")  val notificaciones: List<NotificacionDto>? = null,
    @SerialName("total_no_leidas") val totalNoLeidas: Int = 0
)

@Serializable
data class NotificacionDto(
    @SerialName("id")            val id: Int,
    @SerialName("titulo")        val titulo: String,
    @SerialName("mensaje")       val mensaje: String,
    @SerialName("tipo")          val tipo: String,
    @SerialName("icono")         val icono: String,
    @SerialName("referencia_id") val referenciaId: Int?  = null,
    @SerialName("created_at")    val createdAt: String
)
