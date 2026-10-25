package com.example.prolechc.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

// ── Request ───────────────────────────────────────────────────────────────────

@Serializable
data class AcopioRequestDto(
    @SerialName("ganadero_id")               val ganaderoId: Int,
    @SerialName("litros")                    val litros: Double,
    @SerialName("porcentaje_agua")           val porcentajeAgua: Double?,
    @SerialName("acidez_ph")                 val acidezPh: Double?,
    @SerialName("densidad")                  val densidad: Double?,
    @SerialName("grasa")                     val grasa: Double?            = null,
    @SerialName("proteina")                  val proteina: Double?         = null,
    @SerialName("lactosa")                   val lactosa: Double?          = null,
    @SerialName("sng")                       val sng: Double?              = null,
    @SerialName("sales")                     val sales: Double?            = null,
    @SerialName("total_solidos")             val totalSolidos: Double?     = null,
    @SerialName("punto_congelacion")         val puntoCongelacion: Double? = null,
    @SerialName("fecha")                     val fecha: String,
    @SerialName("observaciones")             val observaciones: String?    = null,
    @SerialName("medicion_balde_confirmada") val medicionBaldeConfirmada: Boolean = false,
    @SerialName("equipo_lactoscan")          val equipoLactoscan: Int?     = null,
    @SerialName("capacidad_tacho_litros")    val capacidadTachoLitros: Double? = null
)

// ── Response ──────────────────────────────────────────────────────────────────

@Serializable
data class AcopioResponseWrapperDto(
    @SerialName("message")              val message: String?       = null,
    @SerialName("acopio")              val acopio: AcopioDataDto? = null,
    @SerialName("alerta_calidad")      val alertaCalidad: String?  = null,
    @SerialName("ganadero_suspendido") val ganaderoSuspendido: Boolean = false
)

@Serializable
data class AcopioDataDto(
    @SerialName("id")                val id: Int,
    @SerialName("ganadero_id")       val ganaderoId: Int,
    @SerialName("litros")            val litros: Double,
    @SerialName("porcentaje_agua")   val porcentajeAgua: Double?    = null,
    @SerialName("acidez_ph")         val acidezPh: Double?          = null,
    @SerialName("densidad")          val densidad: Double?          = null,
    @SerialName("grasa")             val grasa: Double?             = null,
    @SerialName("proteina")          val proteina: Double?          = null,
    @SerialName("lactosa")           val lactosa: Double?           = null,
    @SerialName("sng")               val sng: Double?               = null,
    @SerialName("sales")             val sales: Double?             = null,
    @SerialName("total_solidos")     val totalSolidos: Double?      = null,
    @SerialName("punto_congelacion") val puntoCongelacion: Double?  = null,
    @SerialName("estado")            val estado: String,
    @SerialName("tiene_descuento")   val tieneDescuento: Boolean    = false,
    @SerialName("fecha")             val fecha: String,
    @SerialName("created_at")        val createdAt: String?         = null
)

// ── Historial ────────────────────────────────────────────────────────────────

@Serializable
data class HistorialResponseDto(
    @SerialName("acopios") val acopios: List<AcopioHistorialDto>? = null,
    @SerialName("stats")   val stats: HistorialStatsDto?         = null
)

@Serializable
data class AcopioHistorialDto(
    @SerialName("id")                val id: Int,
    @SerialName("ganadero_id")       val ganaderoId: Int,
    @SerialName("litros")            val litros: Double,
    @SerialName("porcentaje_agua")   val porcentajeAgua: Double?    = null,
    @SerialName("acidez_ph")         val acidezPh: Double?          = null,
    @SerialName("densidad")          val densidad: Double?          = null,
    @SerialName("grasa")             val grasa: Double?             = null,
    @SerialName("proteina")          val proteina: Double?          = null,
    @SerialName("lactosa")           val lactosa: Double?           = null,
    @SerialName("sng")               val sng: Double?               = null,
    @SerialName("sales")             val sales: Double?             = null,
    @SerialName("total_solidos")     val totalSolidos: Double?      = null,
    @SerialName("punto_congelacion") val puntoCongelacion: Double?  = null,
    @SerialName("estado")            val estado: String,
    @SerialName("tiene_descuento")   val tieneDescuento: Boolean    = false,
    @SerialName("fecha")             val fecha: String,
    @SerialName("created_at")        val createdAt: String,
    @SerialName("observaciones")     val observaciones: String?     = null,
    @SerialName("ganadero")          val ganadero: GanaderoDto?     = null
)

@Serializable
data class HistorialStatsDto(
    @SerialName("total_litros")  val totalLitros: Double,
    @SerialName("total_acopios") val totalAcopios: Int,
    @SerialName("aprobados")     val aprobados: Int,
    @SerialName("rechazados")    val rechazados: Int
)
