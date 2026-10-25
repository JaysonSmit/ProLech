package com.example.prolechc.domain.ruta

import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.domain.model.Parada

/** Resumen que ve el chofer al terminar la ruta del día. */
data class ResumenRuta(
    val litros: Double,
    val entregas: Int,
    val atendidos: Int,
    val totalParadas: Int,
    val noAtendidos: List<String>,
    val km: Double,
    val minutos: Int?,
    val aprobados: Int,
    val observados: Int,
    val rechazados: Int,
    val aguaPromedio: Double?
) {
    val porcentajeAprobado: Int get() = if (entregas == 0) 0 else aprobados * 100 / entregas

    companion object {
        fun calcular(
            acopiosHoy: List<Acopio>,
            paradas: List<Parada>,
            km: Double,
            inicioMs: Long?,
            ahoraMs: Long
        ): ResumenRuta {
            val visitables = paradas.filter { !it.suspendido }
            return ResumenRuta(
                litros       = acopiosHoy.sumOf { it.litros },
                entregas     = acopiosHoy.size,
                atendidos    = visitables.count { it.atendido },
                totalParadas = visitables.size,
                noAtendidos  = visitables.filter { !it.atendido }.map { it.ganaderoNombre },
                km           = km,
                minutos      = inicioMs?.let { ((ahoraMs - it) / 60_000).toInt() },
                aprobados    = acopiosHoy.count { it.estadoCalidad == EstadoCalidad.APROBADO },
                observados   = acopiosHoy.count { it.estadoCalidad == EstadoCalidad.OBSERVADO },
                rechazados   = acopiosHoy.count { it.estadoCalidad == EstadoCalidad.RECHAZADO },
                aguaPromedio = acopiosHoy.takeIf { it.isNotEmpty() }?.map { it.porcentajeAgua }?.average()
            )
        }
    }
}
