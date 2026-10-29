package com.example.prolechc.domain.lactoscan

/** Valores leídos de la pantalla o ticket del Lactoscan / Lactomat. */
data class ResultadoLactoscan(
    val grasa: Double? = null,
    val densidad: Double? = null,
    val aguaAniadida: Double? = null,
    val sng: Double? = null,
    val proteina: Double? = null,
    val temperatura: Double? = null
) {
    val vacio: Boolean get() = grasa == null && densidad == null && aguaAniadida == null
}

/**
 * Convierte el texto reconocido por OCR en valores numéricos.
 * Es tolerante a los errores típicos del OCR: comas decimales, tildes, "O" por "0",
 * etiquetas en inglés y densidad expresada como 1.031 o 31.0 (grados lactodensímetro).
 */
object ParserLactoscan {

    fun parsear(texto: String): ResultadoLactoscan {
        val lineas = texto.lines().map { normalizar(it) }

        fun buscar(vararg claves: String): Double? {
            for ((i, linea) in lineas.withIndex()) {
                if (claves.none { linea.contains(it) }) continue
                // El número puede estar en la misma línea o, en pantallas tabuladas, en la siguiente
                (numeroEn(linea) ?: lineas.getOrNull(i + 1)?.let { numeroEn(it) })?.let { return it }
            }
            return null
        }

        val densidadCruda = buscar("densidad", "density", "dens")
        return ResultadoLactoscan(
            grasa        = buscar("grasa", "fat")?.takeIf { it in 0.0..15.0 },
            densidad     = densidadCruda?.let { normalizarDensidad(it) },
            aguaAniadida = buscar("agua anadida", "added water", "agua a", "water")?.takeIf { it in 0.0..100.0 },
            sng          = buscar("sng", "snf", "solidos no grasos"),
            proteina     = buscar("proteina", "protein", "prot"),
            temperatura  = buscar("temperatura", "temp")
        )
    }

    /** Lactodensímetro: 31.0 → 1.031 ; 1031 → 1.031 ; 1.031 se deja igual. */
    fun normalizarDensidad(valor: Double): Double? = when {
        valor in 1.0..1.1       -> valor
        valor in 20.0..40.0     -> 1 + valor / 1000
        valor in 1000.0..1100.0 -> valor / 1000
        else                    -> null
    }

    private fun normalizar(linea: String): String = linea.lowercase()
        .replace('á', 'a').replace('é', 'e').replace('í', 'i')
        .replace('ó', 'o').replace('ú', 'u').replace('ñ', 'n')

    private val NUMERO = Regex("""(\d+[.,]\d+|\d+)""")

    private fun numeroEn(linea: String): Double? {
        // El OCR suele confundir la letra O con el cero dentro de números ("1.O31")
        val corregida = linea.replace(Regex("""(?<=\d)[o](?=\d)|(?<=[.,])[o]|(?<=\d)[o](?=[.,])"""), "0")
        return NUMERO.find(corregida)?.value?.replace(',', '.')?.toDoubleOrNull()
    }
}
