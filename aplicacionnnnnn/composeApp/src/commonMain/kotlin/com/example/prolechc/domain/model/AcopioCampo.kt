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
    val estadoSync: EstadoSync = EstadoSync.SINCRONIZADO,
    /** Motivo del rechazo del servidor; mientras exista no se reintenta automáticamente. */
    val errorSync: String? = null,
    /** % de grasa leído del Lactoscan. */
    val grasa: Double? = null,
    /** Ruta de la foto de evidencia en el celular (solo local). */
    val fotoLocal: String? = null,
    val fotoSubida: Boolean = false,
    /** Lo que el chofer vio a simple vista (ver [Observacion]); la calidad la mide la planta. */
    val observacion: String? = null,
    val observacionNota: String? = null,
    /** Frasco de muestra sorpresa tomado en esta entrega. */
    val muestraCodigo: String? = null
) {
    /** Compara solo lo que viaja al servidor (ignora estado de sync y datos locales de la foto). */
    fun mismosDatosQue(otro: Acopio): Boolean =
        id == otro.id && ganaderoId == otro.ganaderoId && ganaderoNombre == otro.ganaderoNombre &&
            litros == otro.litros && porcentajeAgua == otro.porcentajeAgua && densidad == otro.densidad &&
            estadoCalidad == otro.estadoCalidad && actualizadoEn == otro.actualizadoEn && grasa == otro.grasa &&
            observacion == otro.observacion && observacionNota == otro.observacionNota && muestraCodigo == otro.muestraCodigo
}

/** Resumen de una corrida de sincronización (push + pull). */
data class ResultadoSync(
    val subidos: Int   = 0,
    val bajados: Int   = 0,
    val fallidos: Int  = 0,
    val sinSesion: Boolean = false,
    val sinConexion: Boolean = false,
    /** Registros rechazados por el servidor que el chofer debe corregir. */
    val conError: Int  = 0,
    /** El servidor respondió 401: hay que volver a iniciar sesión (lo offline se conserva). */
    val sesionVencida: Boolean = false
)

/** Ganadero de la ruta del chofer, disponible offline. */
data class GanaderoCampo(
    val id: Long,
    val nombre: String,
    val dni: String,
    val suspendido: Boolean = false,
    /** Entregas que aún no puede hacer por una sanción de calidad (0 = puede entregar). */
    val bloqueado: Int = 0,
    /** Frasco a llenar si hoy le toca muestra sorpresa. */
    val muestra: String? = null
) {
    val puedeEntregar: Boolean get() = !suspendido && bloqueado == 0
}

/** Observación a simple vista del chofer al recibir la leche. */
enum class Observacion(val clave: String, val etiqueta: String) {
    NORMAL("normal", "✓ Normal"),
    OLOR("olor", "👃 Olor raro"),
    COLOR("color", "🎨 Color raro"),
    GRUMOS("grumos", "🧀 Grumos"),
    CALIENTE("caliente", "🌡️ Muy caliente"),
    OTRO("otro", "✏️ Otro");

    companion object {
        fun de(clave: String?): Observacion? = entries.firstOrNull { it.clave == clave }
    }
}
