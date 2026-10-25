package com.example.prolechc.data.repositories

// ══════════════════════════════════════════════════════════════════════════════
//  Interfaces de repositorios — definidas en commonMain
//
//  El ViewModel (commonMain) depende solo de estas interfaces.
//  Las implementaciones reales con Retrofit viven en androidApp y se inyectan
//  desde MainActivity usando una factory del ViewModel.
//
//  Resultado: la UI Compose no cambia nada y el ViewModel puede ejecutarse
//  en cualquier plataforma KMP (con mocks o implementaciones reales).
// ══════════════════════════════════════════════════════════════════════════════

// ── Resultados sellados ───────────────────────────────────────────────────────

/** Envuelve el resultado de cualquier operación de repositorio. */
sealed class RepoResult<out T> {
    data class Success<T>(val data: T) : RepoResult<T>()
    data class Error(val code: Int, val message: String) : RepoResult<Nothing>()
    data class NetworkError(val message: String) : RepoResult<Nothing>()
}

// ── DTOs mínimos compartidos ──────────────────────────────────────────────────
// Solo los campos que el ViewModel necesita para actualizar la UI.

data class UsuarioInfo(
    val id: Int,
    val nombreCompleto: String,
    val rol: String
)

data class RutaInfo(
    val asignacionId: Int,
    val rutaCodigo: String,
    val rutaNombre: String,
    val vehiculoPlaca: String,
    val vehiculoMarca: String,
    val capacidadLitros: Double,
    val fecha: String,
    val paradas: List<ParadaInfo>
)

data class ParadaInfo(
    val id: Int,
    val orden: Int,
    val ganaderoId: Int,
    val ganaderoNombre: String,
    val ganaderoNii: String,
    val direccion: String?,
    val geoLat: Double?,
    val geoLon: Double?,
    val atendido: Boolean,
    /** true cuando el backend reporta estado = "Suspendido" — bloquea el registro de acopio */
    val suspendido: Boolean = false
)

data class AcopioCreado(
    val id: Int,
    val ticketNumero: String,
    val estado: String,
    val horaAcopio: String
)

data class AcopioHistorial(
    val id: Int,
    val ganaderoNombre: String,
    val ganaderoNii: String,
    val litros: Double,
    val porcentajeAgua: Double?,
    val acidezPh: Double?,
    val densidad: Double?,
    // ── Campos LACTOMAT completos ─────────────────────────────────────────────
    val grasa: Double?           = null,
    val proteina: Double?        = null,
    val lactosa: Double?         = null,
    val sng: Double?             = null,
    val sales: Double?           = null,
    val totalSolidos: Double?    = null,
    val puntoCongelacion: Double? = null,
    // ─────────────────────────────────────────────────────────────────────────
    val estado: String,
    val fecha: String,
    val createdAt: String,
    val tieneDescuento: Boolean = false,
    val observaciones: String? = null
)

// ── Interfaces ────────────────────────────────────────────────────────────────

interface IAuthRepository {
    /** Autentica al usuario y persiste el token localmente. */
    suspend fun login(email: String, password: String): RepoResult<UsuarioInfo>

    /** Invalida el token en el servidor y borra la sesión local. */
    suspend fun logout()

    /** Devuelve true si hay token guardado localmente. */
    suspend fun isLoggedIn(): Boolean
}

interface IMiRutaRepository {
    /** Obtiene la asignación activa del conductor autenticado. */
    suspend fun getMiRuta(): RepoResult<RutaInfo>
}

interface IAcopioRepository {
    /** Envía un acopio de leche al servidor y devuelve el registro creado. */
    suspend fun crearAcopio(
        asignacionId: Int,
        ganaderoId: Int,
        litros: Double,
        porcentajeAgua: Double,
        acidezPh: Double,
        densidad: Double?,
        grasa: Double?           = null,
        proteina: Double?        = null,
        lactosa: Double?         = null,
        sng: Double?             = null,
        sales: Double?           = null,
        totalSolidos: Double?    = null,
        puntoCongelacion: Double? = null,
        gpsLat: Double?,
        gpsLon: Double?,
        litrosTina: Double?,
        medicionBaldeConfirmada: Boolean = true,
        equipoLactoscan: Int? = null,
        capacidadTachoLitros: Double? = null,
        /** Observaciones del rechazo (solo leche RECHAZADA/OBSERVADA) */
        observaciones: String? = null,
        /** Foto de evidencia JPEG en Base64 */
        fotoEvidencia: String? = null,
        /** Para mostrar el acopio si queda pendiente offline */
        ganaderoNombre: String = "",
        dniGanadero: String = ""
    ): RepoResult<AcopioCreado>

    /** Carga el historial de acopios del chofer desde el servidor. */
    suspend fun getHistorial(fecha: String? = null, dias: Int = 90): RepoResult<List<AcopioHistorial>>

    /** Verifica si un ganadero ya entregó leche hoy (duplicado). */
    suspend fun yaEntregoHoy(ganaderoId: Int): RepoResult<Boolean>

    // ── Offline ───────────────────────────────────────────────────────────────

    /** Acopios guardados en el celular que aún no llegan al servidor. */
    suspend fun obtenerPendientes(): List<AcopioPendiente> = emptyList()

    /** Envía ya los pendientes (botón "Sincronizar"). */
    suspend fun sincronizarPendientes(): ResultadoSyncPendientes = ResultadoSyncPendientes()

    /** Elimina un pendiente que el servidor rechazó y el chofer decide descartar. */
    suspend fun descartarPendiente(clientUuid: String) {}
}

data class AcopioPendiente(
    val clientUuid: String,
    val ganaderoId: Int,
    val ganaderoNombre: String,
    val dniGanadero: String,
    val litros: Double,
    val porcentajeAgua: Double,
    val acidezPh: Double,
    val densidad: Double?,
    /** "YYYY-MM-DD HH:mm" del registro en el celular */
    val fechaHora: String,
    val intentos: Int,
    /** Último error al enviarlo (null = aún no se intentó o fue falta de red) */
    val error: String?,
    /** true = el servidor lo rechazó y no se reintentará solo */
    val rechazado: Boolean
)

data class ResultadoSyncPendientes(
    val enviados: Int       = 0,
    val restantes: Int      = 0,
    val rechazados: Int     = 0,
    val sinRed: Boolean     = false,
    val sesionExpirada: Boolean = false
)

// ── Notificaciones ─────────────────────────────────────────────────────────────

data class NotificacionInfo(
    val id: Int,
    val titulo: String,
    val mensaje: String,
    val tipo: String,
    val icono: String,        // emoji calculado por el DTO
    val referenciaId: Int?,
    val createdAt: String
)

interface INotificacionesRepository {
    /** Carga las notificaciones no leídas del usuario autenticado. */
    suspend fun getNotificaciones(): RepoResult<List<NotificacionInfo>>

    /** Cuenta las notificaciones no leídas (para el badge de la campana). */
    suspend fun contarNoLeidas(): RepoResult<Int>

    /** Marca una notificación como leída. */
    suspend fun marcarLeida(id: Int): RepoResult<Unit>

    /** Marca todas las notificaciones como leídas. */
    suspend fun marcarTodasLeidas(): RepoResult<Unit>
}
