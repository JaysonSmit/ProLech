package com.example.prolechc.data.services

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.net.Uri
import android.util.Base64
import androidx.core.content.FileProvider
import java.io.ByteArrayOutputStream
import java.io.File

// ══════════════════════════════════════════════════════════════════════════════
//  FotoEvidenciaService — Foto de evidencia para leche rechazada
//
//  1. crearUri(): archivo temporal en cache/evidencias/ expuesto por FileProvider
//     para que la app de cámara escriba la foto a resolución completa.
//  2. aBase64(): reduce la foto a máx. 1280 px y la comprime a JPEG 75 %
//     (~150–300 KB) para enviarla en el JSON de POST /api/acopios.
// ══════════════════════════════════════════════════════════════════════════════

object FotoEvidenciaService {

    private const val LADO_MAXIMO = 1280
    private const val CALIDAD_JPEG = 75

    fun crearUri(context: Context): Uri {
        val dir = File(context.cacheDir, "evidencias").apply { mkdirs() }
        val archivo = File(dir, "evidencia_${System.currentTimeMillis()}.jpg")
        return FileProvider.getUriForFile(context, "${context.packageName}.provider", archivo)
    }

    /** Devuelve la foto como JPEG Base64, o null si no se pudo leer. Llamar fuera del hilo principal. */
    fun aBase64(context: Context, uri: Uri): String? {
        val resolver = context.contentResolver

        // Leer solo dimensiones para calcular el factor de reducción
        val limites = BitmapFactory.Options().apply { inJustDecodeBounds = true }
        resolver.openInputStream(uri)?.use { BitmapFactory.decodeStream(it, null, limites) }
        if (limites.outWidth <= 0 || limites.outHeight <= 0) return null

        var muestreo = 1
        while (maxOf(limites.outWidth, limites.outHeight) / (muestreo * 2) >= LADO_MAXIMO) muestreo *= 2

        val bitmap = resolver.openInputStream(uri)?.use {
            BitmapFactory.decodeStream(it, null, BitmapFactory.Options().apply { inSampleSize = muestreo })
        } ?: return null

        val escala = LADO_MAXIMO.toFloat() / maxOf(bitmap.width, bitmap.height)
        val final = if (escala < 1f) {
            Bitmap.createScaledBitmap(
                bitmap, (bitmap.width * escala).toInt(), (bitmap.height * escala).toInt(), true
            ).also { bitmap.recycle() }
        } else bitmap

        val bytes = ByteArrayOutputStream().use { out ->
            final.compress(Bitmap.CompressFormat.JPEG, CALIDAD_JPEG, out)
            final.recycle()
            out.toByteArray()
        }
        return Base64.encodeToString(bytes, Base64.NO_WRAP)
    }
}
