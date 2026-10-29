package com.example.prolechc.data.services

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Context
import android.content.Intent
import android.os.IBinder
import android.os.Looper
import android.util.Log
import com.example.prolechc.MainActivity
import com.example.prolechc.data.api.RetrofitClient
import com.example.prolechc.data.api.TrackingRequest
import com.google.android.gms.location.FusedLocationProviderClient
import com.google.android.gms.location.LocationCallback
import com.google.android.gms.location.LocationRequest
import com.google.android.gms.location.LocationResult
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.cancel
import kotlinx.coroutines.launch

// ══════════════════════════════════════════════════════════════════════════════
//  GpsTrackingService — Foreground Service de Rastreo GPS
//
//  Ciclo de vida:
//    ┌─ startForegroundService(intent) desde MapaChoferScreen
//    │   → onCreate():   crea CoroutineScope + FusedLocationProviderClient
//    │   → onStartCommand(): extrae asignacion_id del Intent, levanta la
//    │                       notificación persistente, inicia actualizaciones GPS
//    │   → locationCallback: cada 15 s → POST /api/tracking
//    └─ stopSelf() / stopService() desde MapaChoferScreen
//        → onDestroy(): cancela scope + elimina actualizaciones GPS
//
//  Inyección:
//    El servicio obtiene RetrofitClient vía applicationContext para no depender
//    de un contenedor DI externo — apropiado para servicios de Android donde
//    no hay ViewModelScope disponible.
// ══════════════════════════════════════════════════════════════════════════════

class GpsTrackingService : Service() {

    // ── Constantes ────────────────────────────────────────────────────────────

    companion object {
        private const val TAG = "GpsTrackingService"

        /** Nombre del canal de notificación (requerido en Android 8+). */
        const val CHANNEL_ID = "prolech_gps_tracking_channel"

        /** ID numérico de la notificación de primer plano. */
        const val NOTIFICATION_ID = 1001

        /** Clave del Extra del Intent que transporta el ID de la asignación activa. */
        const val EXTRA_ASIGNACION_ID = "asignacion_id"

        /** Intervalo deseado de actualización GPS en milisegundos (15 segundos). */
        private const val INTERVAL_MS = 15_000L

        /** Intervalo mínimo: el sistema no entregará actualizaciones más rápido que esto. */
        private const val FASTEST_INTERVAL_MS = 10_000L

        // ── Helpers de Intent ─────────────────────────────────────────────────

        /** Crea el Intent para iniciar el servicio con la asignación activa. */
        fun startIntent(context: Context, asignacionId: Int): Intent =
            Intent(context, GpsTrackingService::class.java).apply {
                putExtra(EXTRA_ASIGNACION_ID, asignacionId)
            }

        /** Crea el Intent para detener el servicio. */
        fun stopIntent(context: Context): Intent =
            Intent(context, GpsTrackingService::class.java)
    }

    // ── Estado interno ────────────────────────────────────────────────────────

    /** Scope de corrutinas vinculado al ciclo de vida del Service. */
    private val serviceScope = CoroutineScope(SupervisorJob() + Dispatchers.IO)

    /** ID de la asignación de ruta activa — recibido via Intent. */
    private var asignacionId: Int = 0

    /** Cliente de ubicación de Google Play Services. */
    private lateinit var fusedLocationClient: FusedLocationProviderClient

    /** Callback invocado por FusedLocation cada vez que llega una nueva posición. */
    private lateinit var locationCallback: LocationCallback

    // ── Ciclo de vida del Service ─────────────────────────────────────────────

    override fun onCreate() {
        super.onCreate()
        Log.d(TAG, "onCreate — inicializando FusedLocationProviderClient")

        fusedLocationClient = LocationServices.getFusedLocationProviderClient(this)
        createNotificationChannel()
        setupLocationCallback()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        asignacionId = intent?.getIntExtra(EXTRA_ASIGNACION_ID, 0) ?: 0
        Log.d(TAG, "onStartCommand — asignacion_id=$asignacionId")

        // Promover al primer plano con la notificación persistente
        startForeground(NOTIFICATION_ID, buildNotification())

        // Iniciar actualizaciones de ubicación
        requestLocationUpdates()

        // START_STICKY: el sistema reinicia el servicio si lo mata por memoria,
        // re-entregando el último Intent (mantiene el rastreo activo).
        return START_STICKY
    }

    override fun onDestroy() {
        super.onDestroy()
        Log.d(TAG, "onDestroy — deteniendo actualizaciones GPS")
        fusedLocationClient.removeLocationUpdates(locationCallback)
        serviceScope.cancel()
    }

    /** Este servicio no ofrece binding — siempre devuelve null. */
    override fun onBind(intent: Intent?): IBinder? = null

    // ── Notificación persistente ──────────────────────────────────────────────

    /**
     * Crea el [NotificationChannel] requerido en Android 8+ (API 26).
     * Es idempotente: llamadas adicionales no duplican el canal.
     */
    private fun createNotificationChannel() {
        val channel = NotificationChannel(
            CHANNEL_ID,
            "Rastreo GPS ProLech",
            NotificationManager.IMPORTANCE_LOW       // Silencioso pero visible
        ).apply {
            description = "Rastrea la posición del conductor durante la jornada de acopio"
            setShowBadge(false)
        }

        val manager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        manager.createNotificationChannel(channel)
    }

    /**
     * Construye la [Notification] persistente del Foreground Service.
     * Incluye un PendingIntent que lleva al usuario de vuelta a la app al tocarla.
     */
    private fun buildNotification(): Notification {
        val tapIntent = PendingIntent.getActivity(
            this,
            0,
            Intent(this, MainActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_SINGLE_TOP
            },
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        return Notification.Builder(this, CHANNEL_ID)
            .setContentTitle("ProLech — Jornada Activa")
            .setContentText("Rastreando tu ubicación cada 15 s")
            .setSmallIcon(android.R.drawable.ic_menu_mylocation)
            .setContentIntent(tapIntent)
            .setOngoing(true)           // No se puede deslizar para descartar
            .setForegroundServiceBehavior(Notification.FOREGROUND_SERVICE_IMMEDIATE)
            .build()
    }

    // ── Ubicación GPS ─────────────────────────────────────────────────────────

    /**
     * Configura [locationCallback] para procesar cada actualización de posición.
     * Por cada [LocationResult] recibido lanza una corrutina que envía la
     * posición al servidor vía POST /api/tracking.
     */
    private fun setupLocationCallback() {
        locationCallback = object : LocationCallback() {
            override fun onLocationResult(result: LocationResult) {
                // Tomamos la última ubicación del lote (la más reciente)
                val location = result.lastLocation ?: return
                Log.d(TAG, "Nueva posición: lat=${location.latitude}, lng=${location.longitude}")

                serviceScope.launch {
                    enviarPosicion(
                        lat        = location.latitude,
                        lng        = location.longitude,
                        precisionM = if (location.hasAccuracy()) location.accuracy else null,
                        velocidadMs = if (location.hasSpeed()) location.speed else null
                    )
                }
            }
        }
    }

    /**
     * Registra las actualizaciones de ubicación con FusedLocationProviderClient.
     *
     * [Priority.PRIORITY_HIGH_ACCURACY]: usa GPS + red para máxima precisión.
     * Para un contexto real de campo rural (Puno) con baja cobertura de red,
     * esto garantiza que se use el chip GPS físico.
     *
     * @throws SecurityException si el permiso ACCESS_FINE_LOCATION fue revocado.
     */
    @Suppress("MissingPermission")   // El permiso se verifica en MapaChoferScreen antes de iniciar
    private fun requestLocationUpdates() {
        val request = LocationRequest.Builder(
            Priority.PRIORITY_HIGH_ACCURACY,
            INTERVAL_MS
        ).apply {
            setMinUpdateIntervalMillis(FASTEST_INTERVAL_MS)
            setWaitForAccurateLocation(false)   // No bloquear si la primera lectura es imprecisa
        }.build()

        fusedLocationClient.requestLocationUpdates(
            request,
            locationCallback,
            Looper.getMainLooper()   // El callback se entrega en el hilo principal
        )
    }

    // ── Comunicación con la API ───────────────────────────────────────────────

    /**
     * Envía la posición actual al endpoint POST /api/tracking.
     * El backend acepta: latitud, longitud, estado_ruta.
     * Los errores se registran en logcat pero NO detienen el servicio.
     */
    private suspend fun enviarPosicion(
        lat: Double,
        lng: Double,
        precisionM: Float?,
        velocidadMs: Float?
    ) {
        try {
            val request = TrackingRequest(
                latitud    = lat,
                longitud   = lng,
                estadoRuta = "en_camino"
            )

            val response = RetrofitClient.getInstance(applicationContext)
                .enviarPosicion(request)

            if (response.isSuccessful) {
                Log.d(TAG, "Posición enviada OK — ${response.body()?.ultimaActualizacion}")
            } else {
                Log.w(TAG, "Error del servidor al enviar posición: HTTP ${response.code()}")
            }
        } catch (e: Exception) {
            Log.e(TAG, "Fallo al enviar posición (sin red?): ${e.message}")
        }
    }
}
