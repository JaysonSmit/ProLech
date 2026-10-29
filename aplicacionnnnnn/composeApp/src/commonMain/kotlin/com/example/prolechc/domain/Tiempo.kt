@file:OptIn(ExperimentalTime::class)

package com.example.prolechc.domain

import kotlinx.datetime.TimeZone
import kotlinx.datetime.atStartOfDayIn
import kotlinx.datetime.toLocalDateTime
import kotlin.time.Clock
import kotlin.time.ExperimentalTime
import kotlin.time.Instant

/**
 * Fecha y hora centralizadas.
 * kotlinx-datetime 0.7 eliminó `kotlinx.datetime.Clock`/`Instant`: ahora viven en
 * `kotlin.time`. Usar siempre estas funciones evita el NoClassDefFoundError en el celular.
 */
object Tiempo {
    fun ahoraMs(): Long = Clock.System.now().toEpochMilliseconds()

    /** Fecha local de hoy en formato ISO (2026-10-24). */
    fun fechaHoy(): String =
        Clock.System.now().toLocalDateTime(TimeZone.currentSystemDefault()).date.toString()

    /** Medianoche (hora local) del día que contiene [ms]. */
    fun inicioDelDiaMs(ms: Long): Long {
        val zona = TimeZone.currentSystemDefault()
        val fecha = Instant.fromEpochMilliseconds(ms).toLocalDateTime(zona).date
        return fecha.atStartOfDayIn(zona).toEpochMilliseconds()
    }
}
