package com.example.prolechc.domain.model

/** Punto geográfico en grados decimales. */
data class Coordenada(val lat: Double, val lon: Double)

/** Lectura del GPS del celular. */
data class LecturaGps(
    val coordenada: Coordenada,
    val registradoEn: Long,          // epoch ms
    val precisionM: Double? = null,
    val velocidadKmh: Double? = null
)

/** Camino calculado para recorrer las paradas pendientes. */
data class PlanRuta(
    /** Paradas pendientes en el orden sugerido de visita. */
    val orden: List<Parada>,
    /** Puntos de la línea a dibujar en el mapa (por carretera o en línea recta). */
    val trazo: List<Coordenada>,
    val distanciaKm: Double,
    val duracionMin: Int,
    /** true = trazo real por carretera (OSRM); false = líneas rectas sin señal. */
    val porCarretera: Boolean
)
