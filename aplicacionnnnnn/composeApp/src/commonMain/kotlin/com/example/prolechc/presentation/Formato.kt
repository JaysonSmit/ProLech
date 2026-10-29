package com.example.prolechc.presentation

import kotlin.math.abs
import kotlin.math.roundToLong

/** Formatea con [decimales] fijos. String.format solo existe en JVM, no en iOS. */
fun formato(valor: Double, decimales: Int): String {
    var factor = 1L
    repeat(decimales) { factor *= 10 }
    val redondeado = (valor * factor).roundToLong()
    val signo = if (redondeado < 0) "-" else ""
    val entero = abs(redondeado) / factor
    val fraccion = (abs(redondeado) % factor).toString().padStart(decimales, '0')
    return if (decimales == 0) "$signo$entero" else "$signo$entero.$fraccion"
}
