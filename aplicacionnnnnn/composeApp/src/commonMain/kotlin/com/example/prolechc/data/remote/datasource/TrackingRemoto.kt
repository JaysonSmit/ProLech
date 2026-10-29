package com.example.prolechc.data.remote.datasource

import com.example.prolechc.data.remote.ApiConfig
import com.example.prolechc.domain.model.LecturaGps
import io.ktor.client.HttpClient
import io.ktor.client.request.post
import io.ktor.client.request.setBody
import io.ktor.http.ContentType
import io.ktor.http.contentType
import io.ktor.http.isSuccess
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class PuntoGpsDto(
    @SerialName("latitud")       val latitud: Double,
    @SerialName("longitud")      val longitud: Double,
    @SerialName("registrado_en") val registradoEn: Long,
    @SerialName("precision_m")   val precisionM: Double? = null,
    @SerialName("velocidad_kmh") val velocidadKmh: Double? = null
)

@Serializable
data class LoteGpsDto(
    @SerialName("puntos")      val puntos: List<PuntoGpsDto>,
    @SerialName("estado_ruta") val estadoRuta: String = "en_camino"
)

/** Sube el recorrido del chofer al panel de monitoreo. */
fun interface TrackingRemoto {
    suspend fun enviarLote(puntos: List<LecturaGps>, finalizado: Boolean)
}

class TrackingRemotoKtor(private val client: HttpClient) : TrackingRemoto {
    override suspend fun enviarLote(puntos: List<LecturaGps>, finalizado: Boolean) {
        val response = client.post("${ApiConfig.BASE_URL}${ApiConfig.TRACKING_LOTE}") {
            contentType(ContentType.Application.Json)
            setBody(
                LoteGpsDto(
                    puntos = puntos.map {
                        PuntoGpsDto(it.coordenada.lat, it.coordenada.lon, it.registradoEn, it.precisionM, it.velocidadKmh)
                    },
                    estadoRuta = if (finalizado) "finalizado" else "en_camino"
                )
            )
        }
        if (!response.status.isSuccess()) throw ErrorHttp(response.status.value, response.status.description)
    }
}
