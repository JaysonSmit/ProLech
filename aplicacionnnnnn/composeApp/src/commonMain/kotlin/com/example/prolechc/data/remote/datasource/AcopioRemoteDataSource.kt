package com.example.prolechc.data.remote.datasource

import com.example.prolechc.data.remote.ApiConfig
import com.example.prolechc.data.remote.dto.AcopioRequestDto
import com.example.prolechc.data.remote.dto.AcopioResponseWrapperDto
import com.example.prolechc.data.remote.dto.HistorialResponseDto
import io.ktor.client.HttpClient
import io.ktor.client.call.body
import io.ktor.client.request.get
import io.ktor.client.request.parameter
import io.ktor.client.request.post
import io.ktor.client.request.setBody
import io.ktor.http.ContentType
import io.ktor.http.contentType

class AcopioRemoteDataSource(private val client: HttpClient) {

    suspend fun crearAcopio(request: AcopioRequestDto): AcopioResponseWrapperDto =
        client.post("${ApiConfig.BASE_URL}${ApiConfig.ACOPIOS}") {
            contentType(ContentType.Application.Json)
            setBody(request)
        }.body()

    suspend fun getHistorial(fecha: String?, dias: Int): HistorialResponseDto =
        client.get("${ApiConfig.BASE_URL}${ApiConfig.HISTORIAL}") {
            fecha?.let { parameter("fecha", it) }
            parameter("dias", dias)
        }.body()
}
