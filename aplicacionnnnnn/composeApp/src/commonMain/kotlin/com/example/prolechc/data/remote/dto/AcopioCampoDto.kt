package com.example.prolechc.data.remote.dto

import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.domain.model.EstadoSync
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/** Representación JSON de un acopio de campo en la API Laravel. */
@Serializable
data class AcopioCampoDto(
    @SerialName("uuid")            val uuid: String,
    @SerialName("ganadero_id")     val ganaderoId: Long,
    @SerialName("ganadero_nombre") val ganaderoNombre: String,
    @SerialName("litros")          val litros: Double,
    @SerialName("porcentaje_agua") val porcentajeAgua: Double,
    @SerialName("densidad")        val densidad: Double,
    @SerialName("estado_calidad")  val estadoCalidad: String,
    @SerialName("actualizado_en")  val actualizadoEn: Long,
    @SerialName("grasa")           val grasa: Double? = null,
    @SerialName("foto_url")        val fotoUrl: String? = null,
    @SerialName("observacion")      val observacion: String? = null,
    @SerialName("observacion_nota") val observacionNota: String? = null,
    @SerialName("muestra_codigo")   val muestraCodigo: String? = null
)

@Serializable
data class AcopiosCampoResponseDto(
    @SerialName("data") val data: List<AcopioCampoDto> = emptyList()
)

fun Acopio.toDto(): AcopioCampoDto = AcopioCampoDto(
    uuid           = id,
    ganaderoId     = ganaderoId,
    ganaderoNombre = ganaderoNombre,
    litros         = litros,
    porcentajeAgua = porcentajeAgua,
    densidad       = densidad,
    estadoCalidad  = estadoCalidad.name,
    actualizadoEn  = actualizadoEn,
    grasa          = grasa,
    observacion     = observacion,
    observacionNota = observacionNota,
    muestraCodigo   = muestraCodigo
)

/** Lo que llega del servidor siempre se guarda como SINCRONIZADO. */
fun AcopioCampoDto.toDomain(): Acopio = Acopio(
    id             = uuid,
    ganaderoId     = ganaderoId,
    ganaderoNombre = ganaderoNombre,
    litros         = litros,
    porcentajeAgua = porcentajeAgua,
    densidad       = densidad,
    estadoCalidad  = EstadoCalidad.entries.firstOrNull { it.name == estadoCalidad.uppercase() }
        ?: EstadoCalidad.evaluar(porcentajeAgua, densidad),
    actualizadoEn  = actualizadoEn,
    estadoSync     = EstadoSync.SINCRONIZADO,
    grasa          = grasa,
    fotoSubida     = fotoUrl != null,
    observacion     = observacion,
    observacionNota = observacionNota,
    muestraCodigo   = muestraCodigo
)
