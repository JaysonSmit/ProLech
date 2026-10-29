package com.example.prolechc.data.dispositivo

import android.Manifest
import android.annotation.SuppressLint
import android.bluetooth.BluetoothManager
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Paint
import android.graphics.Typeface
import android.graphics.pdf.PdfDocument
import android.net.Uri
import android.os.Build
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.platform.LocalContext
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import com.example.prolechc.domain.lactoscan.ParserLactoscan
import com.example.prolechc.domain.lactoscan.ResultadoLactoscan
import com.example.prolechc.domain.ticket.Ticket
import com.google.mlkit.vision.common.InputImage
import com.google.mlkit.vision.text.TextRecognition
import com.google.mlkit.vision.text.latin.TextRecognizerOptions
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.suspendCancellableCoroutine
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream
import java.text.Normalizer
import java.util.UUID
import kotlin.coroutines.resume
import kotlin.coroutines.resumeWithException

private fun autoridad(context: Context) = "${context.packageName}.archivos"

// ── Cámara ────────────────────────────────────────────────────────────────────

@Composable
actual fun rememberTomarFoto(onFoto: (String?) -> Unit): () -> Unit {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    // Carpeta privada y persistente: la foto debe sobrevivir hasta que haya señal para subirla
    val archivo = remember { mutableListOf<File>() }
    val launcher = rememberLauncherForActivityResult(ActivityResultContracts.TakePicture()) { ok ->
        val foto = archivo.lastOrNull()
        if (!ok || foto == null || !foto.exists() || foto.length() == 0L) {
            foto?.delete()
            onFoto(null)
        } else {
            scope.launch {
                withContext(Dispatchers.IO) { reducirJpeg(foto) }
                onFoto(foto.absolutePath)
            }
        }
    }
    return remember(launcher) {
        {
            val dir = File(context.filesDir, "evidencias").apply { mkdirs() }
            val nuevo = File(dir, "acopio_${System.currentTimeMillis()}.jpg")
            archivo.clear()
            archivo += nuevo
            launcher.launch(FileProvider.getUriForFile(context, autoridad(context), nuevo))
        }
    }
}

/** Máx. 1280 px y JPEG 75 %: ~200 KB en vez de 4 MB (se sube con datos móviles). */
private fun reducirJpeg(archivo: File, ladoMaximo: Int = 1280) {
    val limites = BitmapFactory.Options().apply { inJustDecodeBounds = true }
    BitmapFactory.decodeFile(archivo.absolutePath, limites)
    if (limites.outWidth <= 0) return
    var muestreo = 1
    while (maxOf(limites.outWidth, limites.outHeight) / (muestreo * 2) >= ladoMaximo) muestreo *= 2
    val bitmap = BitmapFactory.decodeFile(archivo.absolutePath, BitmapFactory.Options().apply { inSampleSize = muestreo })
        ?: return
    val escala = ladoMaximo.toFloat() / maxOf(bitmap.width, bitmap.height)
    val final = if (escala < 1f) {
        Bitmap.createScaledBitmap(bitmap, (bitmap.width * escala).toInt(), (bitmap.height * escala).toInt(), true)
            .also { bitmap.recycle() }
    } else bitmap
    FileOutputStream(archivo).use { final.compress(Bitmap.CompressFormat.JPEG, 75, it) }
    final.recycle()
}

// ── OCR del Lactoscan (ML Kit, en el dispositivo, sin internet) ───────────────

class LectorLactoscanAndroid(private val context: Context) : LectorLactoscan {
    override suspend fun leer(rutaFoto: String): Result<ResultadoLactoscan> = runCatching {
        val imagen = InputImage.fromFilePath(context, Uri.fromFile(File(rutaFoto)))
        val reconocedor = TextRecognition.getClient(TextRecognizerOptions.DEFAULT_OPTIONS)
        val texto = suspendCancellableCoroutine { cont ->
            reconocedor.process(imagen)
                .addOnSuccessListener { cont.resume(it.text) }
                .addOnFailureListener { cont.resumeWithException(it) }
        }
        reconocedor.close()
        ParserLactoscan.parsear(texto)
    }
}

// ── Ticket: PDF para compartir + impresora térmica Bluetooth ──────────────────

class AccionesTicketAndroid(private val context: Context) : AccionesTicket {

    override val puedeImprimir: Boolean
        get() = context.getSystemService(BluetoothManager::class.java)?.adapter != null

    override suspend fun compartir(ticket: Ticket): Result<Unit> = runCatching {
        val pdf = withContext(Dispatchers.IO) { generarPdf(ticket) }
        val uri = FileProvider.getUriForFile(context, autoridad(context), pdf)
        val enviar = Intent(Intent.ACTION_SEND).apply {
            type = "application/pdf"
            putExtra(Intent.EXTRA_STREAM, uri)
            putExtra(Intent.EXTRA_TEXT, "Ticket de acopio ${ticket.numero} — ${ticket.litros} L. ProLech Huata.")
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }
        context.startActivity(
            Intent.createChooser(enviar, "Enviar ticket ${ticket.numero}")
                .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_GRANT_READ_URI_PERMISSION)
        )
    }

    private fun generarPdf(ticket: Ticket): File {
        val lineas = ticket.lineas()
        val tamFuente = 9f
        val alto = (40 + lineas.size * (tamFuente + 4)).toInt()
        val documento = PdfDocument()
        // Página angosta tipo ticket (~80 mm)
        val pagina = documento.startPage(PdfDocument.PageInfo.Builder(226, alto, 1).create())
        val pintura = Paint().apply {
            typeface = Typeface.MONOSPACE
            textSize = tamFuente
            isAntiAlias = true
        }
        lineas.forEachIndexed { i, linea ->
            pintura.isFakeBoldText = i < 3 || linea.contains("CALIDAD") || linea.contains("RECHAZADA")
            pagina.canvas.drawText(linea, 14f, 24f + i * (tamFuente + 4), pintura)
        }
        documento.finishPage(pagina)
        val dir = File(context.cacheDir, "tickets").apply { mkdirs() }
        val archivo = File(dir, "${ticket.numero}.pdf")
        FileOutputStream(archivo).use { documento.writeTo(it) }
        documento.close()
        return archivo
    }

    @SuppressLint("MissingPermission")
    override suspend fun imprimir(ticket: Ticket): Result<Unit> = withContext(Dispatchers.IO) {
        runCatching {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S &&
                ContextCompat.checkSelfPermission(context, Manifest.permission.BLUETOOTH_CONNECT) !=
                PackageManager.PERMISSION_GRANTED
            ) error("Falta el permiso de Bluetooth")

            val adaptador = context.getSystemService(BluetoothManager::class.java)?.adapter
                ?: error("Este celular no tiene Bluetooth")
            if (!adaptador.isEnabled) error("Activa el Bluetooth del celular")

            val dispositivos = adaptador.bondedDevices.orEmpty()
            val impresora = dispositivos.firstOrNull { d ->
                val n = d.name.orEmpty().lowercase()
                listOf("print", "pos", "xprinter", "rongta", "epson", "thermal", "btp", "mtp").any { n.contains(it) }
            } ?: dispositivos.firstOrNull()
                ?: error("Empareja la impresora térmica en Ajustes > Bluetooth")

            val socket = impresora.createRfcommSocketToServiceRecord(SPP)
            adaptador.cancelDiscovery()
            socket.use {
                it.connect()
                val out = it.outputStream
                out.write(byteArrayOf(0x1B, 0x40))              // inicializar
                out.write(byteArrayOf(0x1B, 0x74, 0x02))        // tabla de caracteres CP850
                ticket.lineas().forEach { linea -> out.write((sinTildes(linea) + "\n").toByteArray(Charsets.US_ASCII)) }
                out.write("\n\n\n".toByteArray())
                out.write(byteArrayOf(0x1D, 0x56, 0x41, 0x03))  // cortar papel
                out.flush()
            }
        }
    }

    /** Las térmicas baratas no imprimen bien tildes ni ñ en UTF-8. */
    private fun sinTildes(texto: String): String =
        Normalizer.normalize(texto, Normalizer.Form.NFD).replace(Regex("\\p{M}"), "")

    private companion object {
        val SPP: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")
    }
}

@Composable
actual fun rememberSolicitarPermisoBluetooth(onResultado: (Boolean) -> Unit): () -> Unit {
    val launcher = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission(), onResultado)
    return remember(launcher) {
        {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) launcher.launch(Manifest.permission.BLUETOOTH_CONNECT)
            else onResultado(true)
        }
    }
}
