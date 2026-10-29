package com.example.prolechc.data.repository

import android.content.Context
import com.example.prolechc.data.api.AcopioData
import com.example.prolechc.data.api.AcopioHistorialDto
import com.example.prolechc.data.api.AcopioRequest
import com.example.prolechc.data.api.ApiResult
import com.example.prolechc.data.api.RetrofitClient
import com.example.prolechc.data.local.AcopioPendienteGuardado
import com.example.prolechc.data.local.ColaAcopiosOffline
import com.example.prolechc.data.local.TokenDataStore
import com.example.prolechc.data.services.SyncAcopiosWorker
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.first
import java.io.IOException
import java.time.LocalDate
import java.util.UUID

// ══════════════════════════════════════════════════════════════════════════════
//  AcopioRepository
//
//  POST /api/acopios  — registrar nuevo acopio con densidad
//  GET  /api/acopios/historial — cargar historial persistido en servidor
//
//  Reglas de negocio (validadas también en el backend):
//    >= 10% agua → rechazado automático
//    ~1% agua    → descuento en liquidación
//    No se permite duplicado ganadero el mismo día
//
//  Offline: si no hay red, el acopio se guarda en ColaAcopiosOffline y
//  SyncAcopiosWorker lo envía solo en cuanto vuelve la conexión.
// ══════════════════════════════════════════════════════════════════════════════

class AcopioRepository(private val context: Context) {

    private val api = RetrofitClient.getInstance(context)

    suspend fun crearAcopio(
        ganaderoId: Int,
        litros: Double,
        porcentajeAgua: Double?,
        acidezPh: Double?,
        densidad: Double? = null,
        grasa: Double? = null,
        proteina: Double? = null,
        lactosa: Double? = null,
        sng: Double? = null,
        sales: Double? = null,
        totalSolidos: Double? = null,
        puntoCongelacion: Double? = null,
        medicionBaldeConfirmada: Boolean = false,
        equipoLactoscan: Int? = null,
        capacidadTachoLitros: Double? = null,
        observaciones: String? = null,
        fotoEvidencia: String? = null,
        ganaderoNombre: String = "",
        dniGanadero: String = ""
    ): ApiResult<AcopioData> {
        val request = AcopioRequest(
            ganaderoId              = ganaderoId,
            litros                  = litros,
            porcentajeAgua          = porcentajeAgua,
            acidezPh                = acidezPh,
            densidad                = densidad,
            grasa                   = grasa,
            proteina                = proteina,
            lactosa                 = lactosa,
            sng                     = sng,
            sales                   = sales,
            totalSolidos            = totalSolidos,
            puntoCongelacion        = puntoCongelacion,
            fecha                   = LocalDate.now().toString(),
            clientUuid              = UUID.randomUUID().toString(),
            medicionBaldeConfirmada = medicionBaldeConfirmada,
            equipoLactoscan         = equipoLactoscan,
            capacidadTachoLitros    = capacidadTachoLitros,
            observaciones           = observaciones,
            fotoEvidencia           = fotoEvidencia
        )
        return try {
            val response = api.crearAcopio(request)
            if (response.isSuccessful) {
                val acopio = response.body()?.acopio
                if (acopio != null) ApiResult.Success(acopio)
                else ApiResult.Error(500, "Respuesta vacía del servidor")
            } else {
                val msg = response.errorBody()?.string() ?: "Error al registrar acopio"
                ApiResult.Error(response.code(), msg)
            }
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            // Sin red (o respuesta ilegible): guardar en el celular y reintentar solo.
            // Si el servidor sí lo recibió, el client_uuid evita el duplicado.
            ColaAcopiosOffline.encolar(context, AcopioPendienteGuardado(
                clientUuid     = request.clientUuid!!,
                usuarioId      = TokenDataStore(context).userIdFlow.first(),
                creadoEn       = System.currentTimeMillis(),
                ganaderoNombre = ganaderoNombre,
                dniGanadero    = dniGanadero,
                request        = request
            ))
            SyncAcopiosWorker.programar(context)
            ApiResult.NetworkError(e)
        }
    }

    suspend fun getHistorial(fecha: String? = null, dias: Int = 90): ApiResult<List<AcopioHistorialDto>> {
        return try {
            val response = api.getHistorial(
                dias  = if (fecha == null) dias else null,
                fecha = fecha
            )
            if (response.isSuccessful) {
                ApiResult.Success(response.body()?.acopios ?: emptyList())
            } else {
                ApiResult.Error(response.code(), "Error al cargar historial")
            }
        } catch (e: IOException) {
            ApiResult.NetworkError(e)
        } catch (e: Exception) {
            ApiResult.NetworkError(e)
        }
    }
}
