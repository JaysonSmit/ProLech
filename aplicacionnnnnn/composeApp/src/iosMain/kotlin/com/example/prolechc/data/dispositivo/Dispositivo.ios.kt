package com.example.prolechc.data.dispositivo

import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import com.example.prolechc.domain.lactoscan.ResultadoLactoscan
import com.example.prolechc.domain.ticket.Ticket
import platform.UIKit.UIActivityViewController
import platform.UIKit.UIApplication

// iOS: la cámara y el OCR (Vision) quedan como siguiente paso; se comparte el ticket como texto.

@Composable
actual fun rememberTomarFoto(onFoto: (String?) -> Unit): () -> Unit = remember { { onFoto(null) } }

@Composable
actual fun rememberSolicitarPermisoBluetooth(onResultado: (Boolean) -> Unit): () -> Unit =
    remember { { onResultado(false) } }

class LectorLactoscanIos : LectorLactoscan {
    override suspend fun leer(rutaFoto: String): Result<ResultadoLactoscan> =
        Result.failure(UnsupportedOperationException("Lectura del Lactoscan aún no disponible en iOS"))
}

class AccionesTicketIos : AccionesTicket {
    override val puedeImprimir: Boolean = false

    override suspend fun compartir(ticket: Ticket): Result<Unit> = runCatching {
        val vista = UIActivityViewController(listOf(ticket.comoTexto()), null)
        UIApplication.sharedApplication.keyWindow?.rootViewController
            ?.presentViewController(vista, animated = true, completion = null)
    }

    override suspend fun imprimir(ticket: Ticket): Result<Unit> =
        Result.failure(UnsupportedOperationException("Impresión Bluetooth no disponible en iOS"))
}
