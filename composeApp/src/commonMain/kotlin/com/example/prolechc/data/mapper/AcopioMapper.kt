package com.example.prolechc.data.mapper

import com.example.prolechc.data.remote.dto.AcopioDataDto
import com.example.prolechc.data.remote.dto.AcopioHistorialDto
import com.example.prolechc.data.remote.dto.AcopioRequestDto
import com.example.prolechc.domain.model.AcopioCreado
import com.example.prolechc.domain.model.AcopioHistorial
import com.example.prolechc.domain.model.NuevoAcopio

/** Convierte el modelo de dominio al DTO que espera la API. */
fun NuevoAcopio.toRequestDto(fechaHoy: String): AcopioRequestDto = AcopioRequestDto(
    ganaderoId               = ganaderoId,
    litros                   = litros,
    porcentajeAgua           = porcentajeAgua.takeIf { it >= 0 },
    acidezPh                 = acidezPh.takeIf { it > 0 },
    densidad                 = densidad,
    grasa                    = grasa,
    proteina                 = proteina,
    lactosa                  = lactosa,
    sng                      = sng,
    sales                    = sales,
    totalSolidos             = totalSolidos,
    puntoCongelacion         = puntoCongelacion,
    fecha                    = fechaHoy,
    medicionBaldeConfirmada  = medicionBaldeConfirmada,
    equipoLactoscan          = equipoLactoscan,
    capacidadTachoLitros     = capacidadTachoLitros
)

fun AcopioDataDto.toDomain(): AcopioCreado = AcopioCreado(
    id           = id,
    ticketNumero = "#PRO-$id",
    estado       = estado,
    horaAcopio   = createdAt ?: ""
)

fun AcopioHistorialDto.toDomain(): AcopioHistorial = AcopioHistorial(
    id               = id,
    ganaderoNombre   = ganadero?.nombre ?: "Ganadero",
    ganaderoNii      = ganadero?.dni ?: "",
    litros           = litros,
    porcentajeAgua   = porcentajeAgua,
    acidezPh         = acidezPh,
    densidad         = densidad,
    grasa            = grasa,
    proteina         = proteina,
    lactosa          = lactosa,
    sng              = sng,
    sales            = sales,
    totalSolidos     = totalSolidos,
    puntoCongelacion = puntoCongelacion,
    estado           = estado,
    fecha            = fecha,
    createdAt        = createdAt,
    tieneDescuento   = tieneDescuento,
    observaciones    = observaciones
)
