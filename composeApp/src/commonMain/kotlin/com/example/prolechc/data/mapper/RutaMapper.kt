package com.example.prolechc.data.mapper

import com.example.prolechc.data.remote.dto.MiRutaResponseDto
import com.example.prolechc.domain.model.Coordenada
import com.example.prolechc.domain.model.Parada
import com.example.prolechc.domain.model.Ruta

fun MiRutaResponseDto.toDomain(): Ruta {
    // El backend puede devolver ganaderos (formato actual) o paradas (formato nuevo)
    val paradasDesdeGanaderos = ganaderos.orEmpty().mapIndexed { index, g ->
        Parada(
            id             = g.id,
            orden          = index + 1,
            ganaderoId     = g.id,
            ganaderoNombre = g.nombre.orEmpty(),
            ganaderoNii    = g.dni.orEmpty(),
            direccion      = g.direccion,
            geoLat         = g.geoLat,
            geoLon         = g.geoLon,
            atendido       = false,
            suspendido     = g.estaSuspendido
        )
    }

    val paradasDesdeParadas = paradas.orEmpty().map { p ->
        Parada(
            id             = p.id,
            orden          = p.orden,
            ganaderoId     = p.ganaderoId,
            ganaderoNombre = p.ganaderoNombre,
            ganaderoNii    = p.ganaderoNii,
            direccion      = p.direccion,
            geoLat         = p.geoLat,
            geoLon         = p.geoLon,
            atendido       = p.atendido,
            suspendido     = p.suspendido
        )
    }

    val paradasFinales = paradasDesdeParadas.ifEmpty { paradasDesdeGanaderos }

    return Ruta(
        asignacionId    = asignacionId ?: if (paradasFinales.isNotEmpty()) 1 else 0,
        rutaCodigo      = rutaCodigo ?: ruta ?: "",
        rutaNombre      = rutaNombre ?: ruta ?: "",
        vehiculoPlaca   = vehiculoPlaca ?: "",
        vehiculoMarca   = vehiculoMarca ?: "",
        capacidadLitros = capacidadLitros ?: 1200.0,
        fecha           = fecha ?: "",
        paradas         = paradasFinales,
        planta          = planta?.let { Coordenada(it.lat, it.lon) },
        origenRuta      = origenRuta.orEmpty(),
        rolMes          = rolMes.orEmpty()
    )
}
