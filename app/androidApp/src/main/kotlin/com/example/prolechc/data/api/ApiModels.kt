package com.example.prolechc.data.api

import com.google.gson.annotations.SerializedName

// ══════════════════════════════════════════════════════════════════════════════
//  ProLech — DTOs de la API REST Laravel
//  Gson convierte snake_case del JSON a camelCase en Kotlin via @SerializedName.
// ══════════════════════════════════════════════════════════════════════════════

// ── Autenticación ─────────────────────────────────────────────────────────────

/** Cuerpo del POST /api/login */
data class LoginRequest(
    @SerializedName("email")    val email: String,
    @SerializedName("password") val password: String
)

/**
 * Respuesta del POST /api/login.
 * Laravel Sanctum devuelve el token en 'token' y los datos del usuario en 'user'.
 */
data class LoginResponse(
    @SerializedName("token") val token: String,
    @SerializedName("user")  val user: UsuarioDto
)

data class UsuarioDto(
    @SerializedName("id")        val id: Int,
    // El backend devuelve "name" como campo único de nombre completo
    @SerializedName("name")      val name: String? = null,
    // Campos alternativos por si el backend cambia a nombres separados
    @SerializedName("nombres")   val nombres: String? = null,
    @SerializedName("apellidos") val apellidos: String? = null,
    @SerializedName("email")     val email: String?,
    @SerializedName("rol")       val rol: String
) {
    /** Nombre completo unificado sin importar qué campos lleguen del backend. */
    val nombreCompleto: String get() = when {
        !name.isNullOrBlank()    -> name
        !nombres.isNullOrBlank() -> "$nombres ${apellidos.orEmpty()}".trim()
        else                     -> "Conductor"
    }
}

// ── Mi Ruta ───────────────────────────────────────────────────────────────────

/**
 * Respuesta del GET /api/mi-ruta.
 * Devuelve la asignación activa del conductor con sus paradas.
 */
data class MiRutaResponse(
    // Estructura real que devuelve el backend
    @SerializedName("ruta")      val ruta: String?,
    @SerializedName("ganaderos") val ganaderos: List<GanaderoDto>?,
    @SerializedName("total")     val total: Int?,
    // Campos opcionales por si el backend evoluciona
    @SerializedName("asignacion_id")    val asignacionId: Int?    = null,
    @SerializedName("ruta_codigo")      val rutaCodigo: String?   = null,
    @SerializedName("ruta_nombre")      val rutaNombre: String?   = null,
    @SerializedName("sector")           val sector: String?       = null,
    @SerializedName("vehiculo_placa")   val vehiculoPlaca: String? = null,
    @SerializedName("vehiculo_marca")   val vehiculoMarca: String? = null,
    @SerializedName("capacidad_litros") val capacidadLitros: Double? = null,
    @SerializedName("fecha")            val fecha: String?        = null,
    @SerializedName("paradas")          val paradas: List<ParadaDto>? = null
)

/** Ganadero tal como lo devuelve el backend actual en /api/mi-ruta */
data class GanaderoDto(
    @SerializedName("id")             val id: Int,
    @SerializedName("dni")            val dni: String?,
    @SerializedName("nombre")         val nombre: String?,
    @SerializedName("direccion")      val direccion: String?,
    @SerializedName("ruta_asignada")  val rutaAsignada: String?,
    /**
     * Estado del proveedor: "Activo" | "Suspendido"
     * Cuando es "Suspendido" la app bloquea el botón de guardar acopio
     * y muestra el nombre en rojo con "(Bloqueado por calidad)".
     */
    @SerializedName("estado")         val estado: String? = "Activo",
    /** Coordenadas GPS del predio — null si aún no están cargadas en BD */
    @SerializedName("geo_lat")        val geoLat: Double? = null,
    @SerializedName("geo_lon")        val geoLon: Double? = null
) {
    val estaSuspendido: Boolean get() = estado?.equals("Suspendido", ignoreCase = true) == true
}

data class ParadaDto(
    @SerializedName("id")               val id: Int,
    @SerializedName("orden")            val orden: Int,
    @SerializedName("ganadero_id")      val ganaderoId: Int,
    @SerializedName("ganadero_nombre")  val ganaderoNombre: String,
    @SerializedName("ganadero_dni")     val ganaderoNii: String,
    @SerializedName("direccion")        val direccion: String?,
    @SerializedName("geo_lat")          val geoLat: Double?,
    @SerializedName("geo_lon")          val geoLon: Double?,
    @SerializedName("atendido")         val atendido: Boolean
)

// ── Acopios ───────────────────────────────────────────────────────────────────

/**
 * Cuerpo del POST /api/acopios.
 * El backend Laravel valida: ganadero_id, litros, porcentaje_agua (nullable),
 * acidez_ph (nullable), densidad (nullable), fecha (required, date).
 * Reglas de negocio: >= 10% agua → rechazado automático; 1% → descuento.
 */
data class AcopioRequest(
    @SerializedName("ganadero_id")      val ganaderoId: Int,
    @SerializedName("litros")           val litros: Double,
    @SerializedName("porcentaje_agua")  val porcentajeAgua: Double?,
    @SerializedName("acidez_ph")        val acidezPh: Double?,
    /** Densidad en g/mL medida por Lactoscan (rango normal: 1.028–1.033) */
    @SerializedName("densidad")         val densidad: Double?,
    // ── Campos LACTOMAT completos ─────────────────────────────────────────────
    @SerializedName("grasa")            val grasa: Double?           = null,
    @SerializedName("proteina")         val proteina: Double?        = null,
    @SerializedName("lactosa")          val lactosa: Double?         = null,
    @SerializedName("sng")              val sng: Double?             = null,
    @SerializedName("sales")            val sales: Double?           = null,
    @SerializedName("total_solidos")    val totalSolidos: Double?    = null,
    @SerializedName("punto_congelacion") val puntoCongelacion: Double? = null,
    // ─────────────────────────────────────────────────────────────────────────
    /** Fecha en formato YYYY-MM-DD — requerido por el backend */
    @SerializedName("fecha")            val fecha: String,
    /** UUID generado en el celular: el servidor ignora reenvíos del mismo acopio */
    @SerializedName("client_uuid")      val clientUuid: String? = null,
    @SerializedName("observaciones")    val observaciones: String? = null,
    @SerializedName("medicion_balde_confirmada") val medicionBaldeConfirmada: Boolean = false,
    @SerializedName("equipo_lactoscan")           val equipoLactoscan: Int? = null,
    @SerializedName("capacidad_tacho_litros")     val capacidadTachoLitros: Double? = null,
    /** Foto de evidencia de rechazo: JPEG codificado en Base64 */
    @SerializedName("foto_evidencia")             val fotoEvidencia: String? = null
)

/**
 * Respuesta del POST /api/acopios.
 * El backend devuelve:
 *   { message, acopio: {...}, alerta_calidad?, ganadero_suspendido? }
 *
 * alerta_calidad:      aviso de strike 1 o suspensión al llegar al 2do strike
 * ganadero_suspendido: true si este acopio provocó la suspensión automática
 */
data class AcopioResponseWrapper(
    @SerializedName("message")             val message: String?,
    @SerializedName("acopio")             val acopio: AcopioData?,
    @SerializedName("alerta_calidad")     val alertaCalidad: String?    = null,
    @SerializedName("ganadero_suspendido") val ganaderoSuspendido: Boolean = false
)

data class AcopioData(
    @SerializedName("id")               val id: Int,
    @SerializedName("ganadero_id")      val ganaderoId: Int,
    @SerializedName("litros")           val litros: Double,
    @SerializedName("porcentaje_agua")  val porcentajeAgua: Double?,
    @SerializedName("acidez_ph")        val acidezPh: Double?,
    @SerializedName("densidad")         val densidad: Double?,
    // ── Campos LACTOMAT completos ─────────────────────────────────────────────
    @SerializedName("grasa")            val grasa: Double?           = null,
    @SerializedName("proteina")         val proteina: Double?        = null,
    @SerializedName("lactosa")          val lactosa: Double?         = null,
    @SerializedName("sng")              val sng: Double?             = null,
    @SerializedName("sales")            val sales: Double?           = null,
    @SerializedName("total_solidos")    val totalSolidos: Double?    = null,
    @SerializedName("punto_congelacion") val puntoCongelacion: Double? = null,
    // ─────────────────────────────────────────────────────────────────────────
    @SerializedName("estado")           val estado: String,
    @SerializedName("tiene_descuento")  val tieneDescuento: Boolean = false,
    @SerializedName("fecha")            val fecha: String,
    @SerializedName("created_at")       val createdAt: String?
) {
    /** Ticket visual: usa el ID del servidor con prefijo #PRO */
    val ticketNumero: String get() = "#PRO-$id"
}

/**
 * Respuesta del GET /api/acopios/historial
 * { acopios: [...], stats: { total_litros, total_acopios, aprobados, rechazados } }
 */
data class HistorialResponse(
    @SerializedName("acopios") val acopios: List<AcopioHistorialDto>?,
    @SerializedName("stats")   val stats: HistorialStats?
)

data class AcopioHistorialDto(
    @SerializedName("id")               val id: Int,
    @SerializedName("ganadero_id")      val ganaderoId: Int,
    @SerializedName("litros")           val litros: Double,
    @SerializedName("porcentaje_agua")  val porcentajeAgua: Double?,
    @SerializedName("acidez_ph")        val acidezPh: Double?,
    @SerializedName("densidad")         val densidad: Double?,
    // ── Campos LACTOMAT ──────────────────────────────────────────────────────
    @SerializedName("grasa")             val grasa: Double?           = null,
    @SerializedName("proteina")          val proteina: Double?        = null,
    @SerializedName("lactosa")           val lactosa: Double?         = null,
    @SerializedName("sng")               val sng: Double?             = null,
    @SerializedName("sales")             val sales: Double?           = null,
    @SerializedName("total_solidos")     val totalSolidos: Double?    = null,
    @SerializedName("punto_congelacion") val puntoCongelacion: Double? = null,
    // ─────────────────────────────────────────────────────────────────────────
    @SerializedName("estado")           val estado: String,
    @SerializedName("tiene_descuento")  val tieneDescuento: Boolean = false,
    @SerializedName("fecha")            val fecha: String,
    @SerializedName("created_at")       val createdAt: String,
    @SerializedName("observaciones")    val observaciones: String?,
    @SerializedName("ganadero")         val ganadero: GanaderoDto?
)

data class HistorialStats(
    @SerializedName("total_litros")  val totalLitros: Double,
    @SerializedName("total_acopios") val totalAcopios: Int,
    @SerializedName("aprobados")     val aprobados: Int,
    @SerializedName("rechazados")    val rechazados: Int
)

// ── Rastreo GPS ───────────────────────────────────────────────────────────────

/**
 * Cuerpo del POST /api/tracking
 * El backend acepta: latitud, longitud, estado_ruta (en_camino|finalizado).
 */
data class TrackingRequest(
    @SerializedName("latitud")      val latitud: Double,
    @SerializedName("longitud")     val longitud: Double,
    @SerializedName("estado_ruta")  val estadoRuta: String = "en_camino"
)

/** Respuesta del servidor al recibir una posición de rastreo. */
data class TrackingResponse(
    @SerializedName("message")               val message: String?,
    @SerializedName("ultima_actualizacion")  val ultimaActualizacion: String?
)

// ── Solicitud de Cambio de Ruta ───────────────────────────────────────────────

/**
 * Cuerpo del POST /api/solicitar-ruta
 * El backend Laravel valida: ruta_solicitada (string, max:100), motivo (string, max:500).
 */
data class SolicitarRutaRequest(
    /** Nombre de la ruta destino que solicita el chofer */
    @SerializedName("ruta_solicitada") val rutaSolicitada: String,
    /** Texto libre explicando el motivo del cambio */
    @SerializedName("motivo")          val motivo: String
)

/**
 * Respuesta del POST /api/solicitar-ruta.
 * El backend devuelve { message, solicitud: { id, ruta_actual, ruta_solicitada, estado, fecha_solicitud } }
 */
data class SolicitarRutaResponse(
    @SerializedName("message")    val message: String?,
    @SerializedName("solicitud")  val solicitud: SolicitudData?
)

data class SolicitudData(
    @SerializedName("id")               val id: Int,
    @SerializedName("ruta_actual")      val rutaActual: String?,
    @SerializedName("ruta_solicitada")  val rutaSolicitada: String,
    @SerializedName("estado")           val estado: String,
    @SerializedName("fecha_solicitud")  val fechaSolicitud: String?
)

// ── Respuesta genérica de error ────────────────────────────────────────────────

/**
 * Estructura estándar de errores de validación que devuelve Laravel.
 * Útil para mostrar mensajes específicos al usuario.
 */
data class ApiErrorResponse(
    @SerializedName("message") val message: String,
    @SerializedName("errors")  val errors: Map<String, List<String>>? = null
)

// ── Wrapper de resultado sellado ──────────────────────────────────────────────

/**
 * Envuelve el resultado de cualquier llamada a la API.
 * Permite distinguir éxito, error de red y error del servidor en el ViewModel.
 */
sealed class ApiResult<out T> {
    data class Success<T>(val data: T) : ApiResult<T>()
    data class Error(val code: Int, val message: String) : ApiResult<Nothing>()
    data class NetworkError(val exception: Throwable) : ApiResult<Nothing>()
}

// ── Notificaciones ────────────────────────────────────────────────────────────

/**
 * Respuesta del GET /api/notificaciones
 * { notificaciones: [...], total_no_leidas: N }
 */
data class NotificacionesResponse(
    @SerializedName("notificaciones")  val notificaciones: List<NotificacionDto>?,
    @SerializedName("total_no_leidas") val totalNoLeidas: Int = 0
)

data class NotificacionDto(
    @SerializedName("id")             val id: Int,
    @SerializedName("titulo")         val titulo: String,
    @SerializedName("mensaje")        val mensaje: String,
    @SerializedName("tipo")           val tipo: String,           // reunion | ruta_aprobada | suspension | …
    @SerializedName("referencia_id")  val referenciaId: Int?,
    @SerializedName("created_at")     val createdAt: String
) {
    /** Devuelve el emoji según el tipo de notificación */
    val icono: String get() = when (tipo) {
        "reunion"            -> "📅"
        "ruta_aprobada"      -> "✅"
        "ruta_rechazada"     -> "❌"
        "suspension"         -> "🚫"
        "adulteracion_exceso"-> "⚠️"
        else                 -> "🔔"
    }
}

// ── Estado de la solicitud de ruta ────────────────────────────────────────────

/** Respuesta del GET /api/estado-solicitud-ruta */
data class EstadoSolicitudResponse(
    @SerializedName("tiene_solicitud") val tieneSolicitud: Boolean,
    @SerializedName("solicitud")       val solicitud: EstadoSolicitudData?
)

data class EstadoSolicitudData(
    @SerializedName("id")              val id: Int,
    @SerializedName("ruta_actual")     val rutaActual: String?,
    @SerializedName("ruta_solicitada") val rutaSolicitada: String,
    @SerializedName("motivo")          val motivo: String?,
    /** pendiente | aprobado | rechazado */
    @SerializedName("estado")          val estado: String,
    @SerializedName("fecha_solicitud") val fechaSolicitud: String?,
    @SerializedName("revisado_at")     val revisadoAt: String?
)

// ── Reuniones ─────────────────────────────────────────────────────────────────

/** Respuesta del GET /api/reuniones */
data class ReunionesResponse(
    @SerializedName("reuniones") val reuniones: List<ReunionData> = emptyList(),
    @SerializedName("total")     val total: Int = 0
)

data class ReunionData(
    @SerializedName("id")          val id: Int,
    @SerializedName("titulo")      val titulo: String,
    @SerializedName("tipo_label")  val tipoLabel: String?,
    @SerializedName("fecha_hora")  val fechaHora: String,
    @SerializedName("fecha_human") val fechaHuman: String?,
    @SerializedName("enlace")      val enlace: String?,
    @SerializedName("tiene_video") val tieneVideo: Boolean = false,
    @SerializedName("lugar")       val lugar: String?,
    @SerializedName("descripcion") val descripcion: String?,
    @SerializedName("estado")      val estado: String?,
    @SerializedName("confirmado")  val confirmado: Boolean = false
)
