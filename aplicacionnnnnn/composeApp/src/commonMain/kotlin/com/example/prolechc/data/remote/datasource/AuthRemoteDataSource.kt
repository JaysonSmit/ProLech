package com.example.prolechc.data.remote.datasource

import com.example.prolechc.data.remote.ApiConfig
import com.example.prolechc.data.remote.dto.LoginRequestDto
import com.example.prolechc.data.remote.dto.LoginResponseDto
import io.ktor.client.HttpClient
import io.ktor.client.call.body
import io.ktor.client.request.post
import io.ktor.client.request.setBody
import io.ktor.http.ContentType
import io.ktor.http.HttpStatusCode
import io.ktor.http.isSuccess
import io.ktor.http.contentType

class AuthRemoteDataSource(private val client: HttpClient) {

    suspend fun login(email: String, password: String): LoginResponseDto {
        val response = client.post("${ApiConfig.BASE_URL}${ApiConfig.LOGIN}") {
            contentType(ContentType.Application.Json)
            setBody(LoginRequestDto(email, password))
        }
        if (!response.status.isSuccess()) {
            throw ErrorHttp(
                response.status.value,
                when (response.status) {
                    HttpStatusCode.Unauthorized,
                    HttpStatusCode.UnprocessableEntity -> "Correo o contraseña incorrectos"
                    HttpStatusCode.Forbidden           -> "Tu usuario no tiene acceso a la app móvil"
                    else -> "Error del servidor (${response.status.value})"
                }
            )
        }
        return response.body()
    }

    suspend fun logout() {
        client.post("${ApiConfig.BASE_URL}${ApiConfig.LOGOUT}")
    }
}
