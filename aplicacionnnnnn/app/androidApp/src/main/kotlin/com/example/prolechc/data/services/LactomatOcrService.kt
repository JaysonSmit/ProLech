package com.example.prolechc.data.services

import android.graphics.Bitmap
import com.google.mlkit.vision.common.InputImage
import com.google.mlkit.vision.text.TextRecognition
import com.google.mlkit.vision.text.latin.TextRecognizerOptions
import kotlinx.coroutines.suspendCancellableCoroutine
import kotlin.coroutines.resume
import kotlin.coroutines.resumeWithException

// ══════════════════════════════════════════════════════════════════════════════
//  LactomatOcrService
//
//  Lee el ticket impreso del analizador LACTOMAT usando ML Kit Text Recognition.
//  Extrae automáticamente: Grasa, SNG, Densidad, Proteína, Lactosa, Sales,
//  Total Sólidos, Agua Añadida, Punto de Congelación y pH.
//
//  El conductor solo saca una foto al ticket y todos los valores se rellenan solos.
// ══════════════════════════════════════════════════════════════════════════════

data class ResultadoLactomat(
    val grasa: Double?            = null,
    val sng: Double?              = null,
    val densidad: Double?         = null,
    val proteina: Double?         = null,
    val lactosa: Double?          = null,
    val sales: Double?            = null,
    val totalSolidos: Double?     = null,
    val aguaAniadida: Double?     = null,   // → porcentajeAgua
    val puntoCongelacion: Double? = null,
    val ph: Double?               = null,
    val temperatura: Double?      = null,
    val textoRaw: String          = ""      // texto completo para depuración
)

object LactomatOcrService {

    /**
     * Analiza un [Bitmap] con ML Kit y extrae los parámetros del ticket LACTOMAT.
     * Se ejecuta de forma suspendida — seguro llamar desde una coroutine.
     */
    suspend fun leerTicket(bitmap: Bitmap): Result<ResultadoLactomat> {
        return try {
            val texto = extraerTexto(bitmap)
            Result.success(parsearTexto(texto))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    // ── ML Kit: extrae texto del bitmap ──────────────────────────────────────

    private suspend fun extraerTexto(bitmap: Bitmap): String =
        suspendCancellableCoroutine { cont ->
            val image      = InputImage.fromBitmap(bitmap, 0)
            val recognizer = TextRecognition.getClient(TextRecognizerOptions.DEFAULT_OPTIONS)

            recognizer.process(image)
                .addOnSuccessListener { visionText ->
                    cont.resume(visionText.text)
                }
                .addOnFailureListener { e ->
                    cont.resumeWithException(e)
                }
        }

    // ── Parser: convierte el texto en valores numéricos ───────────────────────

    fun parsearTexto(texto: String): ResultadoLactomat {
        val lineas = texto.lines()

        // Busca un número decimal en una línea que contenga la clave
        fun buscar(vararg claves: String): Double? {
            for (linea in lineas) {
                val lineaNorm = linea.lowercase()
                    .replace("á", "a").replace("é", "e").replace("í", "i")
                    .replace("ó", "o").replace("ú", "u").replace("ñ", "n")
                if (claves.any { lineaNorm.contains(it.lowercase()) }) {
                    // Extrae el primer número decimal de la línea
                    val match = Regex("""[-–]?\d+[.,]\d+""").find(linea)
                        ?: Regex("""\d+""").find(linea)
                    match?.value
                        ?.replace(",", ".")
                        ?.replace("–", "-")
                        ?.toDoubleOrNull()
                        ?.let { return it }
                }
            }
            return null
        }

        return ResultadoLactomat(
            grasa            = buscar("grasa", "fat"),
            sng              = buscar("sng", "snf", "solidos no grasos", "solidos no"),
            densidad         = buscar("densidad", "density", "dens"),
            proteina         = buscar("proteina", "protein", "prot"),
            lactosa          = buscar("lactosa", "lactose", "lact"),
            sales            = buscar("sales", "salt"),
            totalSolidos     = buscar("total solid", "total sol", "ts"),
            aguaAniadida     = buscar("agua anadida", "agua añadida", "added water", "agua a"),
            puntoCongelacion = buscar("punto cong", "punto de cong", "freezing", "cong"),
            ph               = buscar("ph"),
            temperatura      = buscar("temp", "temperatura"),
            textoRaw         = texto
        )
    }
}
