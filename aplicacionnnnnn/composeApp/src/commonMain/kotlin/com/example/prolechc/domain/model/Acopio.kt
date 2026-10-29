package com.example.prolechc.domain.model

/** Resultado inmediato tras crear un acopio en el servidor. */
data class AcopioCreado(
    val id: Int,
    val ticketNumero: String,
    val estado: String,
    val horaAcopio: String
)

/** Registro histórico de un acopio ya procesado. */
data class AcopioHistorial(
    val id: Int,
    val ganaderoNombre: String,
    val ganaderoNii: String,
    val litros: Double,
    val porcentajeAgua: Double?,
    val acidezPh: Double?,
    val densidad: Double?,
    // ── Parámetros LACTOMAT ─────────────────────────────────────────────────
    val grasa: Double?            = null,
    val proteina: Double?         = null,
    val lactosa: Double?          = null,
    val sng: Double?              = null,
    val sales: Double?            = null,
    val totalSolidos: Double?     = null,
    val puntoCongelacion: Double? = null,
    // ───────────────────────────────────────────────────────────────────────
    val estado: String,
    val fecha: String,
    val createdAt: String,
    val tieneDescuento: Boolean = false,
    val observaciones: String?  = null
)

/** Parámetros completos para registrar un nuevo acopio. */
data class NuevoAcopio(
    val asignacionId: Int,
    val ganaderoId: Int,
    val litros: Double,
    val porcentajeAgua: Double,
    val acidezPh: Double,
    val densidad: Double?,
    val grasa: Double?            = null,
    val proteina: Double?         = null,
    val lactosa: Double?          = null,
    val sng: Double?              = null,
    val sales: Double?            = null,
    val totalSolidos: Double?     = null,
    val puntoCongelacion: Double? = null,
    val gpsLat: Double?,
    val gpsLon: Double?,
    val litrosTina: Double?,
    val medicionBaldeConfirmada: Boolean = true,
    val equipoLactoscan: Int?     = null,
    val capacidadTachoLitros: Double? = null
)
