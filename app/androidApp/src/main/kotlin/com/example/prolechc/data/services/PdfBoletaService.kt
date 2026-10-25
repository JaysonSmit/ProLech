package com.example.prolechc.data.services

import android.content.Context
import android.content.Intent
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.graphics.pdf.PdfDocument
import android.net.Uri
import androidx.core.content.FileProvider
import com.example.prolechc.data.AcopioRecord
import com.example.prolechc.data.EstadoAcopio
import java.io.File
import java.io.FileOutputStream
import java.time.LocalDateTime
import java.time.format.DateTimeFormatter

// ══════════════════════════════════════════════════════════════════════════════
//  PdfBoletaService
//
//  Genera una boleta PDF con todos los datos del acopio (incluidos parámetros
//  LACTOMAT) y la comparte usando el selector nativo de Android (Share Sheet).
//  No requiere permisos de almacenamiento — usa el directorio de caché interno.
// ══════════════════════════════════════════════════════════════════════════════

object PdfBoletaService {

    private const val PAGE_WIDTH  = 595   // A4 puntos (72 ppp)
    private const val PAGE_HEIGHT = 842
    private const val MARGIN      = 40f
    private const val LINE_H      = 22f

    fun generarYCompartir(context: Context, acopio: AcopioRecord) {
        val pdfFile = generarPdf(context, acopio)
        compartirPdf(context, pdfFile)
    }

    // ── Genera el archivo PDF ─────────────────────────────────────────────────

    private fun generarPdf(context: Context, acopio: AcopioRecord): File {
        val doc  = PdfDocument()
        val page = doc.startPage(
            PdfDocument.PageInfo.Builder(PAGE_WIDTH, PAGE_HEIGHT, 1).create()
        )
        val canvas = page.canvas

        dibujarBoleta(canvas, acopio)

        doc.finishPage(page)

        val dir  = File(context.cacheDir, "boletas")
        dir.mkdirs()
        val file = File(dir, "boleta_${acopio.ticketNumero.replace("#", "").replace("-", "_")}.pdf")
        FileOutputStream(file).use { doc.writeTo(it) }
        doc.close()
        return file
    }

    // ── Dibuja el contenido del PDF ───────────────────────────────────────────

    private fun dibujarBoleta(canvas: Canvas, acopio: AcopioRecord) {
        val paintTitle = Paint().apply {
            color     = Color.rgb(27, 94, 32)   // verde oscuro
            textSize  = 20f
            isFakeBoldText = true
        }
        val paintSubtitle = Paint().apply {
            color    = Color.rgb(100, 100, 100)
            textSize = 12f
        }
        val paintLabel = Paint().apply {
            color    = Color.rgb(80, 80, 80)
            textSize = 11f
        }
        val paintValue = Paint().apply {
            color    = Color.BLACK
            textSize = 12f
            isFakeBoldText = true
        }
        val paintSection = Paint().apply {
            color          = Color.rgb(27, 94, 32)
            textSize       = 11f
            isFakeBoldText = true
        }
        val paintLine = Paint().apply {
            color       = Color.rgb(200, 200, 200)
            strokeWidth = 1f
        }
        val paintStatusOk  = Paint().apply { color = Color.rgb(27, 94, 32);  textSize = 13f; isFakeBoldText = true }
        val paintStatusWarn = Paint().apply { color = Color.rgb(230, 81, 0);  textSize = 13f; isFakeBoldText = true }
        val paintStatusErr = Paint().apply { color = Color.rgb(198, 40, 40); textSize = 13f; isFakeBoldText = true }

        var y = MARGIN + 10f

        // ── Logo / Encabezado ─────────────────────────────────────────────────
        canvas.drawText("🐄  ProLech", MARGIN, y, paintTitle)
        y += 20f
        canvas.drawText("BOLETA DE ACOPIO DE LECHE", MARGIN, y, paintSubtitle)
        y += 14f
        canvas.drawText(
            "Generado: ${LocalDateTime.now().format(DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm"))}",
            MARGIN, y, paintSubtitle
        )
        y += 6f
        canvas.drawLine(MARGIN, y, PAGE_WIDTH - MARGIN, y, paintLine)
        y += 20f

        // ── Estado ────────────────────────────────────────────────────────────
        val statusPaint = when (acopio.estado) {
            EstadoAcopio.APROBADO  -> paintStatusOk
            EstadoAcopio.OBSERVADO -> paintStatusWarn
            EstadoAcopio.RECHAZADO -> paintStatusErr
        }
        val statusText = when (acopio.estado) {
            EstadoAcopio.APROBADO  -> "✓  APROBADO"
            EstadoAcopio.OBSERVADO -> "!  OBSERVADO"
            EstadoAcopio.RECHAZADO -> "✗  RECHAZADO"
        }
        canvas.drawText(statusText, MARGIN, y, statusPaint)
        canvas.drawText(acopio.ticketNumero, PAGE_WIDTH - MARGIN - 120f, y, paintValue)
        y += 20f
        canvas.drawLine(MARGIN, y, PAGE_WIDTH - MARGIN, y, paintLine)
        y += 20f

        // ── Sección: Productor ────────────────────────────────────────────────
        y = dibujarSeccion(canvas, "PRODUCTOR", y, paintSection, paintLine)
        y = dibujarFila(canvas, "Nombre",   acopio.ganaderoNombre.ifBlank { "—" }, y, paintLabel, paintValue)
        if (acopio.dniGanadero.isNotBlank())
            y = dibujarFila(canvas, "DNI", acopio.dniGanadero, y, paintLabel, paintValue)

        // ── Sección: Registro ─────────────────────────────────────────────────
        y = dibujarSeccion(canvas, "REGISTRO", y, paintSection, paintLine)
        y = dibujarFila(canvas, "Litros Recibidos", "${"%.1f".format(acopio.litros)} L", y, paintLabel, paintValue)
        y = dibujarFila(canvas, "Hora de Acopio",   acopio.hora, y, paintLabel, paintValue)
        y = dibujarFila(canvas, "Ticket",            acopio.ticketNumero, y, paintLabel, paintValue)
        if (acopio.tieneDescuento)
            y = dibujarFila(canvas, "Descuento", "Sí — ~1% agua", y, paintLabel, paintStatusWarn)

        // ── Sección: Análisis LACTOMAT ────────────────────────────────────────
        y = dibujarSeccion(canvas, "ANÁLISIS LACTOMAT", y, paintSection, paintLine)

        // Orden igual al ticket físico del analizador
        acopio.grasa?.takeIf { it >= 0 }?.let {
            y = dibujarFila(canvas, "Grasa", "${"%.2f".format(it)}%", y, paintLabel, paintValue)
        }
        acopio.sng?.takeIf { it >= 0 }?.let {
            y = dibujarFila(canvas, "SNG", "${"%.2f".format(it)}%", y, paintLabel, paintValue)
        }
        acopio.densidad?.takeIf { it > 0 }?.let {
            y = dibujarFila(canvas, "Densidad", "${"%.3f".format(it)} g/mL", y, paintLabel,
                if (it < 1.028 || it > 1.033) paintStatusWarn else paintValue)
        }
        acopio.proteina?.takeIf { it >= 0 }?.let {
            y = dibujarFila(canvas, "Proteína", "${"%.2f".format(it)}%", y, paintLabel, paintValue)
        }
        acopio.lactosa?.takeIf { it >= 0 }?.let {
            y = dibujarFila(canvas, "Lactosa", "${"%.2f".format(it)}%", y, paintLabel, paintValue)
        }
        acopio.sales?.takeIf { it >= 0 }?.let {
            y = dibujarFila(canvas, "Sales", "${"%.2f".format(it)}%", y, paintLabel, paintValue)
        }
        acopio.totalSolidos?.takeIf { it >= 0 }?.let {
            y = dibujarFila(canvas, "Total Sólidos", "${"%.2f".format(it)}%", y, paintLabel, paintValue)
        }
        if (acopio.porcentajeAgua > 0)
            y = dibujarFila(canvas, "Agua Añadida",
                "${"%.2f".format(acopio.porcentajeAgua)}%", y, paintLabel,
                if (acopio.porcentajeAgua >= 10.0) paintStatusErr
                else if (acopio.porcentajeAgua >= 5.0) paintStatusWarn else paintValue)
        acopio.puntoCongelacion?.let {
            y = dibujarFila(canvas, "Punto Cong.", "${"%.3f".format(it)} °C", y, paintLabel, paintValue)
        }
        if (acopio.acidezPh > 0)
            y = dibujarFila(canvas, "pH", "${"%.2f".format(acopio.acidezPh)}", y, paintLabel,
                if (acopio.acidezPh < 6.4 || acopio.acidezPh > 6.8) paintStatusWarn else paintValue)

        // ── GPS ───────────────────────────────────────────────────────────────
        if (acopio.gpsLat != null && acopio.gpsLon != null) {
            y = dibujarSeccion(canvas, "UBICACIÓN GPS", y, paintSection, paintLine)
            y = dibujarFila(canvas, "Coordenadas",
                "${"%.5f".format(acopio.gpsLat)}, ${"%.5f".format(acopio.gpsLon)}",
                y, paintLabel, paintValue)
        }

        // ── Pie de página ─────────────────────────────────────────────────────
        y += 10f
        canvas.drawLine(MARGIN, y, PAGE_WIDTH - MARGIN, y, paintLine)
        y += 16f
        canvas.drawText(
            "Este documento es un comprobante oficial de recepción de leche — ProLech Huata, Puno.",
            MARGIN, y, paintSubtitle
        )
    }

    private fun dibujarSeccion(
        canvas: Canvas, titulo: String, y: Float,
        paintSection: Paint, paintLine: Paint
    ): Float {
        var cy = y + 10f
        canvas.drawText(titulo, MARGIN, cy, paintSection)
        cy += 6f
        canvas.drawLine(MARGIN, cy, PAGE_WIDTH - MARGIN, cy, paintLine)
        return cy + 14f
    }

    private fun dibujarFila(
        canvas: Canvas,
        label: String, value: String,
        y: Float,
        paintLabel: Paint, paintValue: Paint
    ): Float {
        canvas.drawText(label, MARGIN, y, paintLabel)
        canvas.drawText(value, PAGE_WIDTH / 2f, y, paintValue)
        return y + LINE_H
    }

    // ── Comparte el PDF via Intent ────────────────────────────────────────────

    private fun compartirPdf(context: Context, file: File) {
        val uri: Uri = FileProvider.getUriForFile(
            context,
            "${context.packageName}.provider",
            file
        )
        val intent = Intent(Intent.ACTION_SEND).apply {
            type     = "application/pdf"
            putExtra(Intent.EXTRA_STREAM, uri)
            putExtra(Intent.EXTRA_SUBJECT, "Boleta de Acopio — ProLech")
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        }
        context.startActivity(Intent.createChooser(intent, "Compartir boleta PDF")
            .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
    }
}
