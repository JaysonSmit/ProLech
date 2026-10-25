package com.example.prolechc.data.remote.datasource

import com.example.prolechc.data.remote.ApiConfig
import com.example.prolechc.data.remote.dto.NotificacionesResponseDto
import io.ktor.client.HttpClient
import io.ktor.client.call.body
import io.ktor.client.request.get
import io.ktor.client.request.post

class NotificacionRemoteDataSource(private val client: HttpClient) {

    suspend fun getNotificaciones(): NotificacionesResponseDto =
        client.get("${ApiConfig.BASE_URL}${ApiConfig.NOTIFICACIONES}").body()

    suspend fun marcarLeida(id: Int) {
        client.post("${ApiConfig.BASE_URL}${ApiConfig.notificacionLeer(id)}")
    }

    suspend fun marcarTodasLeidas() {
        client.post("${ApiConfig.BASE_URL}${ApiConfig.NOTIFICACIONES_LEER_TODAS}")
    }
}
