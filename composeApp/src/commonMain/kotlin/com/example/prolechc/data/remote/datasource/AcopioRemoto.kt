package com.example.prolechc.data.remote.datasource

import com.example.prolechc.data.remote.ApiConfig
import com.example.prolechc.data.remote.dto.AcopiosCampoResponseDto
import com.example.prolechc.data.remote.dto.toDomain
import com.example.prolechc.data.remote.dto.toDto
import com.example.prolechc.domain.model.Acopio
import io.ktor.client.HttpClient
import io.ktor.client.call.body
import io.ktor.client.request.delete
import io.ktor.client.request.get
import io.ktor.client.request.post
import io.ktor.client.request.put
import io.ktor.client.request.setBody
import io.ktor.client.statement.HttpResponse
import io.ktor.http.ContentType
import io.ktor.http.HttpStatusCode
import io.ktor.http.contentType
import io.ktor.http.isSuccess

/** Fuente de datos remota de acopios de campo (API REST Laravel). */
interface AcopioRemoto {
    suspend fun crear(acopio: Acopio)
    suspend fun actualizar(acopio: Acopio)
    suspend fun eliminar(id: String)
    suspend fun obtenerTodos(): List<Acopio>
}

class AcopioRemotoKtor(private val client: HttpClient) : AcopioRemoto {

    override suspend fun crear(acopio: Acopio) {
        client.post("${ApiConfig.BASE_URL}${ApiConfig.ACOPIOS_CAMPO}") {
            contentType(ContentType.Application.Json)
            setBody(acopio.toDto())
        }.validar()
    }

    override suspend fun actualizar(acopio: Acopio) {
        client.put("${ApiConfig.BASE_URL}${ApiConfig.acopioCampo(acopio.id)}") {
            contentType(ContentType.Application.Json)
            setBody(acopio.toDto())
        }.validar()
    }

    override suspend fun eliminar(id: String) {
        val response = client.delete("${ApiConfig.BASE_URL}${ApiConfig.acopioCampo(id)}")
        // 404 = ya no existe en el servidor: el objetivo del borrado se cumplió
        if (response.status != HttpStatusCode.NotFound) response.validar()
    }

    override suspend fun obtenerTodos(): List<Acopio> =
        client.get("${ApiConfig.BASE_URL}${ApiConfig.ACOPIOS_CAMPO}")
            .validar()
            .body<AcopiosCampoResponseDto>()
            .data
            .map { it.toDomain() }

    private fun HttpResponse.validar(): HttpResponse {
        if (!status.isSuccess()) throw ErrorHttp(status.value, status.description)
        return this
    }
}

class ErrorHttp(val codigo: Int, val detalle: String) : Exception("HTTP $codigo: $detalle")
