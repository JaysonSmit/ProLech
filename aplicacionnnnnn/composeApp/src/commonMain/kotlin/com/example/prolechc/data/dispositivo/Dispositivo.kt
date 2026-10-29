package com.example.prolechc.data.dispositivo

import androidx.compose.runtime.Composable
import com.example.prolechc.domain.lactoscan.ResultadoLactoscan
import com.example.prolechc.domain.ticket.Ticket

/** Lee los valores del Lactoscan en una foto (OCR del celular, funciona sin señal). */
fun interface LectorLactoscan {
    suspend fun leer(rutaFoto: String): Result<ResultadoLactoscan>
}

/** Entrega del ticket al ganadero: PDF por WhatsApp o impresora térmica Bluetooth. */
interface AccionesTicket {
    /** Genera el PDF y abre "Compartir" (WhatsApp, correo...). */
    suspend fun compartir(ticket: Ticket): Result<Unit>
    /** Imprime en la primera impresora Bluetooth emparejada (ESC/POS, 58 mm). */
    suspend fun imprimir(ticket: Ticket): Result<Unit>
    val puedeImprimir: Boolean
}

/**
 * Devuelve una función que abre la cámara. [onFoto] recibe la ruta del JPEG
 * (ya reducido para no gastar datos) o null si el chofer canceló.
 */
@Composable
expect fun rememberTomarFoto(onFoto: (String?) -> Unit): () -> Unit

/** Pide el permiso de Bluetooth (Android 12+) antes de imprimir. */
@Composable
expect fun rememberSolicitarPermisoBluetooth(onResultado: (Boolean) -> Unit): () -> Unit
