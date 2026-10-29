package com.example.prolechc.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.plugins.HttpTimeout
import io.ktor.client.plugins.contentnegotiation.ContentNegotiation
import io.ktor.client.HttpClientConfig
import io.ktor.client.engine.HttpClientEngine
import io.ktor.client.plugins.HttpResponseValidator
import io.ktor.client.plugins.defaultRequest
import io.ktor.client.plugins.logging.LogLevel
import io.ktor.client.plugins.logging.Logging
import io.ktor.client.request.header
import io.ktor.http.HttpHeaders
import io.ktor.serialization.kotlinx.json.json
import kotlinx.serialization.json.Json

/**
 * Fábrica del HttpClient de Ktor.
 *
 * El motor concreto (Android/Darwin) se provee desde cada plataforma a través
 * de [PlatformModule]. Esta fábrica sólo configura plugins comunes.
 *
 * [tokenProvider] devuelve el Bearer token activo, o null si no hay sesión.
 * [alSesionVencida] se llama cuando el servidor rechaza el token (401) en CUALQUIER
 * pantalla (ruta, sync, GPS...), para volver al login sin borrar los datos offline.
 */
object HttpClientFactory {

    fun create(
        tokenProvider: () -> String?,
        alSesionVencida: () -> Unit = {},
        motor: HttpClientEngine? = null
    ): HttpClient =
        if (motor != null) HttpClient(motor) { configurar(tokenProvider, alSesionVencida) }
        else HttpClient { configurar(tokenProvider, alSesionVencida) }

    private fun HttpClientConfig<*>.configurar(tokenProvider: () -> String?, alSesionVencida: () -> Unit) {
        install(ContentNegotiation) {
            json(Json {
                ignoreUnknownKeys = true
                isLenient         = true
                coerceInputValues = true
            })
        }
        // Señal débil en campo: mejor fallar rápido y dejar el registro pendiente
        install(HttpTimeout) {
            connectTimeoutMillis = 10_000
            requestTimeoutMillis = 20_000
            socketTimeoutMillis  = 20_000
        }
        install(Logging) {
            level = LogLevel.HEADERS
            // No imprimir el Bearer token en Logcat
            sanitizeHeader { it == HttpHeaders.Authorization }
        }
        // Se evalúa en cada petición: adjunta el token vigente si existe
        defaultRequest {
            tokenProvider()?.let { token ->
                header(HttpHeaders.Authorization, "Bearer $token")
            }
            header(HttpHeaders.Accept, "application/json")
        }
        // Token vencido o revocado (p. ej. el chofer inició sesión en otro celular)
        HttpResponseValidator {
            validateResponse { respuesta ->
                val conToken = respuesta.call.request.headers[HttpHeaders.Authorization] != null
                if (respuesta.status.value == 401 && conToken &&
                    !respuesta.call.request.url.encodedPath.endsWith(ApiConfig.LOGIN)) {
                    alSesionVencida()
                }
            }
        }
    }
}
