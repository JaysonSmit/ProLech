package com.example.prolechc.domain.model

/** Estado de sincronización de un registro local respecto al servidor. */
enum class EstadoSync {
    SINCRONIZADO,
    PENDIENTE_CREAR,
    PENDIENTE_ACTUALIZAR,
    PENDIENTE_ELIMINAR
}

/** Resultado de la evaluación de calidad de la leche en campo. */
enum class EstadoCalidad {
    APROBADO,
    OBSERVADO,
    RECHAZADO;

    companion object {
        /**
         * Regla de calidad en campo:
         * - Sin agua añadida y densidad dentro de rango -> APROBADO
         * - Hasta 3 % de agua o densidad levemente fuera de rango -> OBSERVADO
         * - Más de 3 % de agua -> RECHAZADO
         */
        fun evaluar(porcentajeAgua: Double, densidad: Double): EstadoCalidad = when {
            porcentajeAgua > 3.0                                   -> RECHAZADO
            porcentajeAgua > 0.0 || densidad !in 1.028..1.034      -> OBSERVADO
            else                                                   -> APROBADO
        }
    }
}

/** Acopio de leche registrado en campo (funciona offline, id = UUID local). */
data class Acopio(
    val id: String,
    val ganaderoId: Long,
    val ganaderoNombre: String,
    val litros: Double,
    val porcentajeAgua: Double,
    val densidad: Double,
    val estadoCalidad: EstadoCalidad,
    val actualizadoEn: Long,
    val estadoSync: EstadoSync = EstadoSync.SINCRONIZADO
)

/** Resumen de una corrida de sincronización (push + pull). */
data class ResultadoSync(
    val subidos: Int   = 0,
    val bajados: Int   = 0,
    val fallidos: Int  = 0,
    val sinSesion: Boolean = false,
    val sinConexion: Boolean = false
)

/** Ganadero de la ruta del chofer, disponible offline. */
data class GanaderoCampo(
    val id: Long,
    val nombre: String,
    val dni: String,
    val suspendido: Boolean = false
)
