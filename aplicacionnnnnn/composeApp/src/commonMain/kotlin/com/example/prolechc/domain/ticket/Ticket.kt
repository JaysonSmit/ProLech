package com.example.prolechc.domain.ticket

import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.presentation.formato
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import kotlin.time.ExperimentalTime
import kotlin.time.Instant

/** Ticket de acopio que se entrega al ganadero (impreso o por WhatsApp). */
data class Ticket(
    val numero: String,
    val fechaHora: String,
    val chofer: String,
    val ruta: String,
    val ganadero: String,
    val litros: Double,
    val porcentajeAgua: Double,
    val densidad: Double,
    val grasa: Double?,
    val calidad: EstadoCalidad,
    val sincronizado: Boolean
) {
    /** Líneas de 32 columnas: el ancho de una impresora térmica de 58 mm. */
    fun lineas(): List<String> = buildList {
        add(centrar("PROLECH"))
        add(centrar("Planta de lacteos - Huata"))
        add(centrar("TICKET DE ACOPIO"))
        add(SEPARADOR)
        add(par("Ticket", numero))
        add(par("Fecha", fechaHora))
        add(par("Chofer", chofer))
        add(par("Ruta", ruta))
        add(SEPARADOR)
        add("Productor:")
        add(ganadero.take(ANCHO))
        add(SEPARADOR)
        add(par("Litros", "${formato(litros, 2)} L"))
        add(par("Agua anadida", "${formato(porcentajeAgua, 1)} %"))
        add(par("Densidad", formato(densidad, 3)))
        grasa?.let { add(par("Grasa", "${formato(it, 1)} %")) }
        add(SEPARADOR)
        add(centrar(when (calidad) {
            EstadoCalidad.APROBADO  -> "CALIDAD: APROBADO"
            EstadoCalidad.OBSERVADO -> "** CALIDAD: OBSERVADO **"
            EstadoCalidad.RECHAZADO -> "*** LECHE RECHAZADA ***"
        }))
        if (!sincronizado) add(centrar("(registrado sin senal)"))
        add("")
        add(centrar("Firma del productor"))
        add("")
        add(centrar("______________________"))
        add(centrar("Gracias por su entrega"))
    }

    fun comoTexto(): String = lineas().joinToString("\n")

    companion object {
        const val ANCHO = 32
        private val SEPARADOR = "-".repeat(ANCHO)

        private fun centrar(texto: String): String {
            val t = texto.take(ANCHO)
            return " ".repeat((ANCHO - t.length) / 2) + t
        }

        private fun par(etiqueta: String, valor: String): String {
            val espacio = ANCHO - etiqueta.length - valor.length
            return if (espacio >= 1) etiqueta + " ".repeat(espacio) + valor
            else "$etiqueta: $valor".take(ANCHO)
        }

        @OptIn(ExperimentalTime::class)
        fun de(acopio: Acopio, chofer: String, ruta: String): Ticket {
            val fecha = Instant.fromEpochMilliseconds(acopio.actualizadoEn)
                .toLocalDateTime(TimeZone.currentSystemDefault())
            val dosDig = { n: Int -> n.toString().padStart(2, '0') }
            return Ticket(
                numero        = "AC-" + acopio.id.replace("-", "").take(8).uppercase(),
                fechaHora     = "${dosDig(fecha.day)}/${dosDig(fecha.month.ordinal + 1)}/${fecha.year} " +
                    "${dosDig(fecha.hour)}:${dosDig(fecha.minute)}",
                chofer        = chofer,
                ruta          = ruta,
                ganadero      = acopio.ganaderoNombre,
                litros        = acopio.litros,
                porcentajeAgua = acopio.porcentajeAgua,
                densidad      = acopio.densidad,
                grasa         = acopio.grasa,
                calidad       = acopio.estadoCalidad,
                sincronizado  = acopio.estadoSync.name == "SINCRONIZADO"
            )
        }
    }
}
