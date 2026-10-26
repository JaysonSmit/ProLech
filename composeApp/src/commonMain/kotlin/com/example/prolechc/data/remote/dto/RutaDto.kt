package com.example.prolechc.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class MiRutaResponseDto(
    @SerialName("ruta")              val ruta: String?              = null,
    @SerialName("ganaderos")         val ganaderos: List<GanaderoDto>? = null,
    @SerialName("total")             val total: Int?                = null,
    @SerialName("asignacion_id")     val asignacionId: Int?         = null,
    @SerialName("ruta_codigo")       val rutaCodigo: String?        = null,
    @SerialName("ruta_nombre")       val rutaNombre: String?        = null,
    @SerialName("vehiculo_placa")    val vehiculoPlaca: String?     = null,
    @SerialName("vehiculo_marca")    val vehiculoMarca: String?     = null,
    @SerialName("capacidad_litros")  val capacidadLitros: Double?   = null,
    @SerialName("fecha")             val fecha: String?             = null,
    @SerialName("paradas")           val paradas: List<ParadaDto>?  = null,
    @SerialName("planta")            val planta: PlantaDto?         = null,
    @SerialName("origen_ruta")       val origenRuta: String?        = null,
    @SerialName("rol_mes")           val rolMes: String?            = null,
    /** Camino trazado por el administrador en el panel: [[lat, lon], ...] */
    @SerialName("trazado")           val trazado: List<List<Double>>? = null
)

@Serializable
data class PlantaDto(
    @SerialName("nombre") val nombre: String? = null,
    @SerialName("lat")    val lat: Double,
    @SerialName("lon")    val lon: Double
)

@Serializable
data class GanaderoDto(
    @SerialName("id")            val id: Int,
    @SerialName("dni")           val dni: String?       = null,
    @SerialName("nombre")        val nombre: String?    = null,
    @SerialName("direccion")     val direccion: String? = null,
    @SerialName("ruta_asignada") val rutaAsignada: String? = null,
    @SerialName("estado")        val estado: String?    = "Activo",
    @SerialName("geo_lat")       val geoLat: Double?    = null,
    @SerialName("geo_lon")       val geoLon: Double?    = null
) {
    val estaSuspendido: Boolean
        get() = estado?.equals("Suspendido", ignoreCase = true) == true
}

@Serializable
data class ParadaDto(
    @SerialName("id")              val id: Int,
    @SerialName("orden")           val orden: Int,
    @SerialName("ganadero_id")     val ganaderoId: Int,
    @SerialName("ganadero_nombre") val ganaderoNombre: String,
    @SerialName("ganadero_dni")    val ganaderoNii: String,
    @SerialName("direccion")       val direccion: String?  = null,
    @SerialName("geo_lat")         val geoLat: Double?     = null,
    @SerialName("geo_lon")         val geoLon: Double?     = null,
    @SerialName("atendido")        val atendido: Boolean   = false,
    @SerialName("suspendido")      val suspendido: Boolean = false
)
