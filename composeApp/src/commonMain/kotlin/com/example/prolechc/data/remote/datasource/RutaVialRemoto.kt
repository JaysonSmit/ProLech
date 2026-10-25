package com.example.prolechc.data.remote.datasource

import com.example.prolechc.domain.model.Coordenada
import io.ktor.client.HttpClient
import io.ktor.client.request.get
import io.ktor.client.statement.bodyAsText
import io.ktor.http.isSuccess
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.double
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive

/** Trazo por carretera entre varios puntos. */
data class TrazoVial(
    val puntos: List<Coordenada>,
    val distanciaKm: Double,
    val duracionMin: Int
)

/** Calcula el camino real por las vías (no en línea recta). */
fun interface RutaVialRemoto {
    suspend fun trazar(puntos: List<Coordenada>): TrazoVial?
}

/**
 * Implementación con OSRM (OpenStreetMap, sin API key).
 * Usa un HttpClient SIN el token de la API: nunca se envían credenciales a terceros.
 * El servidor público de OSRM es para uso moderado; en producción conviene uno propio.
 */
class RutaVialOsrm(
    private val client: HttpClient,
    private val baseUrl: String = "https://router.project-osrm.org"
) : RutaVialRemoto {

    override suspend fun trazar(puntos: List<Coordenada>): TrazoVial? {
        if (puntos.size < 2) return null
        // OSRM admite hasta ~100 puntos por consulta; una ruta de acopio tiene bastante menos
        val coords = puntos.take(90).joinToString(";") { "${it.lon},${it.lat}" }
        val response = client.get("$baseUrl/route/v1/driving/$coords?overview=full&geometries=geojson")
        if (!response.status.isSuccess()) return null
        return parsearOsrm(response.bodyAsText())
    }

    companion object {
        private val json = Json { ignoreUnknownKeys = true }

        /** Lee la respuesta de OSRM. Público para poder probarlo sin red. */
        fun parsearOsrm(cuerpo: String): TrazoVial? {
            val raiz = json.parseToJsonElement(cuerpo).jsonObject
            if (raiz["code"]?.jsonPrimitive?.content != "Ok") return null
            val ruta = raiz["routes"]?.jsonArray?.firstOrNull()?.jsonObject ?: return null
            val coordenadas = ruta["geometry"]!!.jsonObject["coordinates"]!!.jsonArray.map { par ->
                val p = par.jsonArray
                // GeoJSON viene como [lon, lat]
                Coordenada(lat = p[1].jsonPrimitive.double, lon = p[0].jsonPrimitive.double)
            }
            return TrazoVial(
                puntos      = coordenadas,
                distanciaKm = ruta["distance"]!!.jsonPrimitive.double / 1000.0,
                duracionMin = (ruta["duration"]!!.jsonPrimitive.double / 60.0).toInt()
            )
        }
    }
}
