package com.example.prolechc.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class LoginRequestDto(
    @SerialName("email")    val email: String,
    @SerialName("password") val password: String
)

@Serializable
data class LoginResponseDto(
    @SerialName("token") val token: String,
    @SerialName("user")  val user: UsuarioDto
)

@Serializable
data class UsuarioDto(
    @SerialName("id")        val id: Int,
    @SerialName("name")      val name: String?      = null,
    @SerialName("nombres")   val nombres: String?   = null,
    @SerialName("apellidos") val apellidos: String? = null,
    @SerialName("email")     val email: String?     = null,
    @SerialName("rol")       val rol: String
) {
    val nombreCompleto: String
        get() = when {
            !name.isNullOrBlank()    -> name
            !nombres.isNullOrBlank() -> "$nombres ${apellidos.orEmpty()}".trim()
            else                     -> "Conductor"
        }
}
