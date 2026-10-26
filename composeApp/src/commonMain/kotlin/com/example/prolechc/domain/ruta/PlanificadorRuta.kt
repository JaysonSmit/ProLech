package com.example.prolechc.domain.ruta

import com.example.prolechc.domain.model.Coordenada
import com.example.prolechc.domain.model.Parada
import com.example.prolechc.domain.model.PlanRuta
import kotlin.math.asin
import kotlin.math.cos
import kotlin.math.min
import kotlin.math.roundToInt
import kotlin.math.sin
import kotlin.math.sqrt

object Geo {
    private const val RADIO_TIERRA_KM = 6371.0

    /** Distancia en línea recta (fórmula de Haversine). */
    fun distanciaKm(a: Coordenada, b: Coordenada): Double {
        val dLat = rad(b.lat - a.lat)
        val dLon = rad(b.lon - a.lon)
        val h = sin(dLat / 2).let { it * it } +
            cos(rad(a.lat)) * cos(rad(b.lat)) * sin(dLon / 2).let { it * it }
        return 2 * RADIO_TIERRA_KM * asin(min(1.0, sqrt(h)))
    }

    fun distanciaTotalKm(puntos: List<Coordenada>): Double =
        puntos.zipWithNext { a, b -> distanciaKm(a, b) }.sum()

    private fun rad(grados: Double) = grados * kotlin.math.PI / 180.0
}

val Parada.coordenada: Coordenada?
    get() = if (geoLat != null && geoLon != null) Coordenada(geoLat, geoLon) else null

/**
 * Ordena las paradas pendientes con la heurística del vecino más cercano:
 * desde donde está el chofer, siempre va al ganadero más próximo que falte.
 * Sin señal funciona igual (no depende de servicios externos).
 */
class PlanificadorRuta(
    /** Velocidad promedio en trochas rurales de Huata, para estimar el tiempo. */
    private val velocidadPromedioKmh: Double = 25.0,
    /** Minutos que toma recoger y medir la leche en cada parada. */
    private val minutosPorParada: Int = 6
) {

    fun pendientes(paradas: List<Parada>): List<Parada> =
        paradas.filter { !it.atendido && !it.suspendido }

    fun ordenar(desde: Coordenada?, paradas: List<Parada>): List<Parada> {
        val pendientes = pendientes(paradas)
        val (conGps, sinGps) = pendientes.partition { it.coordenada != null }
        if (desde == null) return conGps.sortedBy { it.orden } + sinGps

        val restantes = conGps.toMutableList()
        val orden = mutableListOf<Parada>()
        var actual: Coordenada = desde
        while (restantes.isNotEmpty()) {
            val siguiente = restantes.minBy { Geo.distanciaKm(actual, it.coordenada!!) }
            restantes.remove(siguiente)
            orden += siguiente
            actual = siguiente.coordenada!!
        }
        return orden + sinGps
    }

    /** Plan con líneas rectas: se usa sin señal o si el servicio de rutas falla. */
    fun planEnLineaRecta(desde: Coordenada?, paradas: List<Parada>, regresoA: Coordenada?): PlanRuta {
        val orden = ordenar(desde, paradas)
        val trazo = puntosDelRecorrido(desde, orden, regresoA)
        val km = Geo.distanciaTotalKm(trazo) * 1.3   // las trochas no van en línea recta
        return PlanRuta(
            orden        = orden,
            trazo        = trazo,
            distanciaKm  = km,
            duracionMin  = duracionMin(km, orden.size),
            porCarretera = false
        )
    }

    /**
     * Plan con el camino que trazó el administrador: se respeta el orden de visita que
     * calculó el servidor a lo largo del trazo y se dibuja ese trazo (funciona sin señal).
     */
    fun planTrazado(trazado: List<Coordenada>, paradas: List<Parada>): PlanRuta {
        val orden = pendientes(paradas).sortedBy { it.orden }
        val km = Geo.distanciaTotalKm(trazado)
        return PlanRuta(
            orden            = orden,
            trazo            = trazado,
            distanciaKm      = km,
            duracionMin      = duracionMin(km, orden.size),
            porCarretera     = true,
            trazadoPorPlanta = true
        )
    }

    fun puntosDelRecorrido(desde: Coordenada?, orden: List<Parada>, regresoA: Coordenada?): List<Coordenada> =
        listOfNotNull(desde) + orden.mapNotNull { it.coordenada } + listOfNotNull(regresoA)

    fun duracionMin(km: Double, paradas: Int): Int =
        (km / velocidadPromedioKmh * 60).roundToInt() + paradas * minutosPorParada
}
