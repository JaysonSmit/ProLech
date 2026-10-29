package com.example.prolechc.data.remote.datasource

import com.example.prolechc.data.archivos.leerArchivo
import com.example.prolechc.data.remote.ApiConfig
import com.example.prolechc.data.sync.SubidorFotos
import com.example.prolechc.domain.model.Acopio
import io.ktor.client.HttpClient
import io.ktor.client.request.forms.formData
import io.ktor.client.request.forms.submitFormWithBinaryData
import io.ktor.http.Headers
import io.ktor.http.HttpHeaders
import io.ktor.http.isSuccess

/**
 * Sube la foto de evidencia (balde, lectura del Lactoscan, leche rechazada) de un
 * acopio ya sincronizado: POST /api/acopios-campo/{uuid}/foto (multipart).
 */
class FotoEvidenciaKtor(private val client: HttpClient) : SubidorFotos {
    override suspend fun subir(acopio: Acopio) {
        val ruta = acopio.fotoLocal ?: return
        // Si el archivo se borró del celular no hay nada que subir: se marca como resuelto
        val bytes = leerArchivo(ruta) ?: return
        val response = client.submitFormWithBinaryData(
            url = "${ApiConfig.BASE_URL}${ApiConfig.fotoAcopio(acopio.id)}",
            formData = formData {
                append("foto", bytes, Headers.build {
                    append(HttpHeaders.ContentType, "image/jpeg")
                    append(HttpHeaders.ContentDisposition, "filename=\"${acopio.id}.jpg\"")
                })
            }
        )
        if (!response.status.isSuccess()) throw ErrorHttp(response.status.value, mensajeDeError(response))
    }
}
