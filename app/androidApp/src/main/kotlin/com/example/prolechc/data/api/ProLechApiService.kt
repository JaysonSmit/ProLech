package com.example.prolechc.data.api

import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path

// ══════════════════════════════════════════════════════════════════════════════
//  ProLechApiService — Contrato de la API REST Laravel
//
//  Base URL: http://10.0.2.2:8000/  (emulador → localhost del PC)
//  Autenticación: Bearer Token inyectado por AuthInterceptor en RetrofitClient.
//
//  Todos los métodos devuelven Response<T> para inspeccionar el código HTTP
//  antes de deserializar el cuerpo (permite manejar 401, 422, 500, etc.).
// ══════════════════════════════════════════════════════════════════════════════

interface ProLechApiService {

    // ── Autenticación ─────────────────────────────────────────────────────────

    /**
     * POST /api/login
     * NO requiere token — es el endpoint que lo genera.
     *
     * Request:  { "email": "...", "password": "..." }
     * Response: { "token": "...", "user": { ... } }
     */
    @POST("api/login")
    suspend fun login(
        @Body body: LoginRequest
    ): Response<LoginResponse>

    /**
     * POST /api/logout
     * Invalida el token actual en el servidor (tabla personal_access_tokens).
     * Requiere Bearer Token en cabecera.
     */
    @POST("api/logout")
    suspend fun logout(): Response<Unit>

    // ── Conductor: Mi Ruta ────────────────────────────────────────────────────

    /**
     * GET /api/mi-ruta
     * Devuelve la asignación activa del conductor autenticado:
     * ruta, vehículo, fecha y lista de paradas (ganaderos con orden).
     *
     * Requiere Bearer Token.
     */
    @GET("api/mi-ruta")
    suspend fun getMiRuta(): Response<MiRutaResponse>

    // ── Acopios ───────────────────────────────────────────────────────────────

    /**
     * POST /api/acopios
     * Registra un nuevo acopio de leche en el servidor.
     * El backend devuelve { message, acopio: { id, ganadero_id, litros, estado, fecha, created_at } }
     *
     * Requiere Bearer Token.
     */
    @POST("api/acopios")
    suspend fun crearAcopio(
        @Body body: AcopioRequest
    ): Response<AcopioResponseWrapper>

    /**
     * GET /api/acopios/historial
     * Devuelve los acopios del chofer autenticado.
     * Query: ?dias=90 (default) o ?fecha=YYYY-MM-DD
     */
    @GET("api/acopios/historial")
    suspend fun getHistorial(
        @retrofit2.http.Query("dias")  dias: Int?    = null,
        @retrofit2.http.Query("fecha") fecha: String? = null
    ): Response<HistorialResponse>

    // ── Rastreo GPS ───────────────────────────────────────────────────────────

    /**
     * POST /api/tracking
     * El backend acepta: latitud, longitud, estado_ruta (en_camino|finalizado).
     * Enviado por GpsTrackingService cada ~30 segundos.
     *
     * Requiere Bearer Token.
     */
    @POST("api/tracking")
    suspend fun enviarPosicion(
        @Body body: TrackingRequest
    ): Response<TrackingResponse>

    // ── Solicitud de Cambio de Ruta ───────────────────────────────────────────

    /**
     * POST /api/solicitar-ruta
     * El conductor solicita ser reasignado a otra ruta dando un motivo.
     * El estado inicial de la solicitud es PENDIENTE; un admin la aprueba/rechaza.
     *
     * Requiere Bearer Token.
     *
     * Request:  { "ruta_codigo": "A-2", "ruta_nombre": "Huata Sur – Capachica", "motivo": "..." }
     * Response: { "id": 7, "estado": "PENDIENTE", "mensaje": "Solicitud registrada" }
     */
    @POST("api/solicitar-ruta")
    suspend fun solicitarRuta(
        @Body body: SolicitarRutaRequest
    ): Response<SolicitarRutaResponse>

    // ── Notificaciones ────────────────────────────────────────────────────────

    /**
     * GET /api/notificaciones
     * Devuelve las notificaciones no leídas del usuario autenticado.
     * Response: { notificaciones: [...], total_no_leidas: N }
     */
    @GET("api/notificaciones")
    suspend fun getNotificaciones(): Response<NotificacionesResponse>

    /**
     * POST /api/notificaciones/{id}/leer
     * Marca una notificación específica como leída.
     */
    @POST("api/notificaciones/{id}/leer")
    suspend fun marcarNotificacionLeida(
        @Path("id") id: Int
    ): Response<Unit>

    /**
     * POST /api/notificaciones/leer-todas
     * Marca todas las notificaciones del usuario como leídas.
     */
    @POST("api/notificaciones/leer-todas")
    suspend fun marcarTodasLeidas(): Response<Unit>

    // ── Estado de solicitud de ruta ───────────────────────────────────────────

    /**
     * GET /api/estado-solicitud-ruta
     * Última solicitud de cambio de ruta del chofer (pendiente / aprobado / rechazado).
     */
    @GET("api/estado-solicitud-ruta")
    suspend fun getEstadoSolicitudRuta(): Response<EstadoSolicitudResponse>

    // ── Reuniones ─────────────────────────────────────────────────────────────

    /**
     * GET /api/reuniones
     * Reuniones a las que fue invitado el usuario (desde ayer en adelante).
     */
    @GET("api/reuniones")
    suspend fun getReuniones(): Response<ReunionesResponse>
}
