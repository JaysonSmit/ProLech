package com.example.prolechc.data.remote.datasource

import com.example.prolechc.data.remote.ApiConfig
import com.example.prolechc.domain.model.LecturaGps
import io.ktor.client.HttpClient
import io.ktor.client.request.post
import io.ktor.client.request.setBody
import io.ktor.client.statement.bodyAsText
import io.ktor.http.ContentType
import io.ktor.http.contentType
import io.ktor.http.isSuccess
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive

/** Guarda en el servidor la ubicación GPS del predio de un ganadero. */
fun interface GanaderoUbicacionRemoto {
    suspend fun fijar(ganaderoId: Int, lectura: LecturaGps)
}

@Serializable
private data class UbicacionDto(
    @SerialName("latitud")     val latitud: Double,
    @SerialName("longitud")    val longitud: Double,
    @SerialName("precision_m") val precisionM: Double? = null
)

class GanaderoUbicacionKtor(private val client: HttpClient) : GanaderoUbicacionRemoto {
    override suspend fun fijar(ganaderoId: Int, lectura: LecturaGps) {
        val response = client.post("${ApiConfig.BASE_URL}${ApiConfig.ubicacionGanadero(ganaderoId)}") {
            contentType(ContentType.Application.Json)
            setBody(UbicacionDto(lectura.coordenada.lat, lectura.coordenada.lon, lectura.precisionM))
        }
        if (!response.status.isSuccess()) {
            val mensaje = runCatching {
                Json.parseToJsonElement(response.bodyAsText()).jsonObject["message"]?.jsonPrimitive?.content
            }.getOrNull()
            throw ErrorHttp(response.status.value, mensaje ?: response.status.description)
        }
    }
}
