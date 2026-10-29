package com.example.prolechc.data.location

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Context
import android.content.Intent
import android.content.pm.ServiceInfo
import android.os.Build
import android.os.IBinder
import androidx.core.app.NotificationCompat
import androidx.core.app.ServiceCompat
import androidx.core.content.ContextCompat
import com.example.prolechc.MainActivity
import com.example.prolechc.data.tracking.RastreoGps
import org.koin.android.ext.android.inject

/**
 * Servicio en primer plano: mantiene el GPS activo con la pantalla apagada
 * mientras el chofer recorre su ruta (Android lo exige desde la versión 10).
 */
class ServicioRastreo : Service() {

    private val rastreo: RastreoGps by inject()

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        if (intent?.action == ACCION_DETENER) {
            rastreo.detener()
            ServiceCompat.stopForeground(this, ServiceCompat.STOP_FOREGROUND_REMOVE)
            stopSelf()
            return START_NOT_STICKY
        }

        crearCanal()
        val abrirApp = PendingIntent.getActivity(
            this, 0, Intent(this, MainActivity::class.java),
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT
        )
        val notificacion = NotificationCompat.Builder(this, CANAL)
            .setSmallIcon(android.R.drawable.ic_menu_mylocation)
            .setContentTitle("Ruta de acopio en curso")
            .setContentText("ProLech está registrando tu recorrido")
            .setContentIntent(abrirApp)
            .setOngoing(true)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .build()

        val tipo = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) ServiceInfo.FOREGROUND_SERVICE_TYPE_LOCATION else 0
        ServiceCompat.startForeground(this, NOTIFICACION_ID, notificacion, tipo)
        rastreo.iniciar()
        return START_STICKY
    }

    private fun crearCanal() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        val nm = getSystemService(NotificationManager::class.java)
        nm.createNotificationChannel(
            NotificationChannel(CANAL, "Rastreo de ruta", NotificationManager.IMPORTANCE_LOW)
        )
    }

    companion object {
        private const val CANAL = "rastreo_ruta"
        private const val NOTIFICACION_ID = 4210
        const val ACCION_DETENER = "com.example.prolechc.DETENER_RASTREO"
    }
}

class ControlRastreoAndroid(private val context: Context) : ControlRastreoSegundoPlano {
    override fun iniciar() {
        ContextCompat.startForegroundService(context, Intent(context, ServicioRastreo::class.java))
    }

    override fun detener() {
        context.startService(
            Intent(context, ServicioRastreo::class.java).setAction(ServicioRastreo.ACCION_DETENER)
        )
    }
}
