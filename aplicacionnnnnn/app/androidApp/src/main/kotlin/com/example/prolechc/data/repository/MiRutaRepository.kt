package com.example.prolechc.data.repository

import android.content.Context
import com.example.prolechc.data.api.ApiResult
import com.example.prolechc.data.api.MiRutaResponse
import com.example.prolechc.data.api.RetrofitClient
import java.io.IOException

// ══════════════════════════════════════════════════════════════════════════════
//  MiRutaRepository
//
//  Responsabilidad: obtener la asignación activa del conductor (GET /api/mi-ruta).
//
//  La respuesta incluye:
//    - Datos de la ruta (código, nombre, sector)
//    - Datos del vehículo (placa, marca, capacidad)
//    - Lista de paradas ordenadas (ganadero, dirección, coordenadas GPS, atendido)
//
//  El ViewModel usa estos datos para:
//    - Mostrar la info del conductor en LoginScreen y DashboardScreen
//    - Renderizar la lista de paradas en MiRutaScreen
// ══════════════════════════════════════════════════════════════════════════════

class MiRutaRepository(context: Context) {

    private val api = RetrofitClient.getInstance(context)

    /**
     * Obtiene la ruta activa del conductor autenticado.
     *
     * @return [ApiResult.Success] con [MiRutaResponse] si el servidor responde 200.
     *         [ApiResult.Error] con código 401 si el token expiró o es inválido.
     *         [ApiResult.NetworkError] si no hay conexión.
     */
    suspend fun getMiRuta(): ApiResult<MiRutaResponse> {
        return try {
            val response = api.getMiRuta()

            if (response.isSuccessful) {
                val body = response.body()!!
                ApiResult.Success(body)
            } else {
                val msg = response.errorBody()?.string() ?: "Sin ruta asignada"
                ApiResult.Error(code = response.code(), message = msg)
            }
        } catch (e: IOException) {
            ApiResult.NetworkError(e)
        } catch (e: Exception) {
            ApiResult.NetworkError(e)
        }
    }
}
