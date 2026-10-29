package com.example.prolechc.data.remote.datasource

import com.example.prolechc.data.remote.ApiConfig
import com.example.prolechc.data.remote.dto.MiRutaResponseDto
import io.ktor.client.HttpClient
import io.ktor.client.call.body
import io.ktor.client.request.get
import io.ktor.client.statement.bodyAsText
import io.ktor.http.isSuccess
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive

class RutaRemoteDataSource(private val client: HttpClient) {

    /** Texto JSON crudo: se guarda tal cual en la caché offline. */
    suspend fun getMiRutaJson(): String {
        val response = client.get("${ApiConfig.BASE_URL}${ApiConfig.MI_RUTA}")
        val cuerpo = response.bodyAsText()
        if (!response.status.isSuccess()) {
            // 422 = sin zona asignada, 401 = sesión vencida: se muestra el mensaje del backend
            val mensaje = runCatching {
                Json.parseToJsonElement(cuerpo).jsonObject["message"]?.jsonPrimitive?.content
            }.getOrNull()
            throw ErrorHttp(response.status.value, mensaje ?: response.status.description)
        }
        return cuerpo
    }

    suspend fun getMiRuta(): MiRutaResponseDto {
        val response = client.get("${ApiConfig.BASE_URL}${ApiConfig.MI_RUTA}")
        if (!response.status.isSuccess()) throw ErrorHttp(response.status.value, response.status.description)
        return response.body()
    }
}
