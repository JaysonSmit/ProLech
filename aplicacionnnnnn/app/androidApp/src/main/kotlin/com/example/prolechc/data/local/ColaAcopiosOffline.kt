package com.example.prolechc.data.local

import android.content.Context
import com.example.prolechc.data.api.AcopioRequest
import com.example.prolechc.data.api.RetrofitClient
import com.google.gson.Gson
import com.google.gson.JsonParser
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.coroutines.withContext
import java.io.File
import java.io.IOException

// ══════════════════════════════════════════════════════════════════════════════
//  ColaAcopiosOffline — Acopios guardados sin red
//
//  Cada acopio pendiente es un archivo JSON en filesDir/acopios_pendientes/,
//  así sobrevive al cierre de la app, a que Android mate el proceso y a
//  reinicios del celular. Incluye la foto de evidencia (Base64) si la hay.
//
//  El envío lo hacen SyncAcopiosWorker (automático al volver la red) y el
//  botón "Sincronizar". Cada request lleva un client_uuid: si el servidor ya
//  lo recibió en un intento anterior responde 200 sin duplicarlo.
// ══════════════════════════════════════════════════════════════════════════════

data class AcopioPendienteGuardado(
    val clientUuid: String,
    /** Chofer que lo registró: solo se envía con su propia sesión */
    val usuarioId: Int?,
    /** Momento del registro en el celular (ms) */
    val creadoEn: Long,
    val ganaderoNombre: String,
    val dniGanadero: String,
    val request: AcopioRequest,
    val intentos: Int     = 0,
    /** Último error recibido al intentar enviarlo */
    val error: String?    = null,
    /** true = el servidor lo rechazó (4xx); no se reintenta solo, el chofer decide */
    val rechazado: Boolean = false
)

data class ResultadoEnvioCola(
    val enviados: Int,
    /** Pendientes que se reintentarán (no incluye los rechazados) */
    val restantes: Int,
    val rechazados: Int,
    val sinRed: Boolean,
    val sesionExpirada: Boolean
)

object ColaAcopiosOffline {

    private val mutex = Mutex()
    private val gson  = Gson()

    private fun dir(context: Context): File =
        File(context.applicationContext.filesDir, "acopios_pendientes").apply { mkdirs() }

    private fun archivo(context: Context, uuid: String) = File(dir(context), "$uuid.json")

    /** Escritura atómica: si la app muere a mitad de camino no queda un JSON corrupto. */
    private fun escribir(context: Context, pendiente: AcopioPendienteGuardado) {
        val tmp = File(dir(context), "${pendiente.clientUuid}.tmp")
        tmp.writeText(gson.toJson(pendiente))
        if (!tmp.renameTo(archivo(context, pendiente.clientUuid))) {
            archivo(context, pendiente.clientUuid).delete()
            tmp.renameTo(archivo(context, pendiente.clientUuid))
        }
    }

    suspend fun encolar(context: Context, pendiente: AcopioPendienteGuardado) =
        withContext(Dispatchers.IO) { mutex.withLock { escribir(context, pendiente) } }

    /** Todos los pendientes, del más antiguo al más reciente. */
    suspend fun listar(context: Context): List<AcopioPendienteGuardado> = withContext(Dispatchers.IO) {
        dir(context).listFiles { f -> f.extension == "json" }.orEmpty()
            .mapNotNull { f ->
                runCatching { gson.fromJson(f.readText(), AcopioPendienteGuardado::class.java) }.getOrNull()
            }
            .sortedBy { it.creadoEn }
    }

    /** Pendientes del chofer con sesión activa. */
    suspend fun listarDelUsuario(context: Context): List<AcopioPendienteGuardado> {
        val usuarioId = TokenDataStore(context).userIdFlow.first()
        return listar(context).filter { it.usuarioId == null || it.usuarioId == usuarioId }
    }

    suspend fun eliminar(context: Context, uuid: String) = withContext(Dispatchers.IO) {
        mutex.withLock { archivo(context, uuid).delete() }
    }

    suspend fun hayPendientes(context: Context): Boolean = listarDelUsuario(context).any { !it.rechazado }

    /**
     * Envía los pendientes del chofer actual, en orden de registro.
     * Nunca lanza excepción; el resultado indica si conviene reintentar.
     */
    suspend fun enviarPendientes(context: Context): ResultadoEnvioCola = withContext(Dispatchers.IO) {
        mutex.withLock {
            val usuarioId = TokenDataStore(context).userIdFlow.first()
            if (usuarioId == null) {
                // Sin sesión no hay token: se enviarán al volver a iniciar sesión
                return@withLock ResultadoEnvioCola(0, contarReintentables(context, null), 0, false, true)
            }

            val api = RetrofitClient.getInstance(context)
            var enviados = 0
            var rechazados = 0
            var sinRed = false
            var sesionExpirada = false

            val cola = listar(context).filter {
                !it.rechazado && (it.usuarioId == null || it.usuarioId == usuarioId)
            }
            for (p in cola) {
                try {
                    val response = api.crearAcopio(p.request)
                    when {
                        response.isSuccessful -> {
                            archivo(context, p.clientUuid).delete()
                            enviados++
                        }
                        response.code() == 401 -> {
                            sesionExpirada = true
                            break
                        }
                        response.code() in 400..499 -> {
                            escribir(context, p.copy(
                                intentos  = p.intentos + 1,
                                rechazado = true,
                                error     = mensajeError(response.errorBody()?.string(), response.code())
                            ))
                            rechazados++
                        }
                        else -> escribir(context, p.copy(
                            intentos = p.intentos + 1,
                            error    = "Error del servidor (${response.code()})"
                        ))
                    }
                } catch (e: IOException) {
                    // Se cayó la red a mitad del envío: el resto espera al próximo intento
                    sinRed = true
                    break
                } catch (e: CancellationException) {
                    throw e
                } catch (e: Exception) {
                    escribir(context, p.copy(intentos = p.intentos + 1, error = e.message ?: "Error inesperado"))
                }
            }

            ResultadoEnvioCola(
                enviados       = enviados,
                restantes      = contarReintentables(context, usuarioId),
                rechazados     = rechazados,
                sinRed         = sinRed,
                sesionExpirada = sesionExpirada
            )
        }
    }

    private suspend fun contarReintentables(context: Context, usuarioId: Int?): Int =
        listar(context).count { !it.rechazado && (it.usuarioId == null || it.usuarioId == usuarioId) }

    /** Extrae el "message" de Laravel; si no hay, un texto genérico. */
    private fun mensajeError(body: String?, code: Int): String =
        runCatching { JsonParser.parseString(body).asJsonObject.get("message").asString }
            .getOrNull()
            ?.takeIf { it.isNotBlank() }
            ?: "Rechazado por el servidor ($code)"
}
