package com.example.prolechc.data.dispositivo

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import com.example.prolechc.MainActivity

/** Notificaciones con sonido en el canal «Avisos de la planta». */
class AvisadorAndroid(private val context: Context) : AvisadorSistema {

    override fun mostrar(id: Int, titulo: String, mensaje: String) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) return
        crearCanal()
        val abrir = PendingIntent.getActivity(
            context, id,
            Intent(context, MainActivity::class.java)
                .putExtra(EXTRA_ABRIR_AVISOS, true)
                .addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP or Intent.FLAG_ACTIVITY_CLEAR_TOP),
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT
        )
        val texto = mensaje.replace("*", "")
        val notificacion = NotificationCompat.Builder(context, CANAL)
            .setSmallIcon(android.R.drawable.ic_popup_reminder)
            .setContentTitle(titulo)
            .setContentText(texto)
            .setStyle(NotificationCompat.BigTextStyle().bigText(texto))
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setCategory(NotificationCompat.CATEGORY_EVENT)
            .setAutoCancel(true)
            .setContentIntent(abrir)
            .build()
        NotificationManagerCompat.from(context).notify(BASE_ID + id, notificacion)
    }

    private fun crearCanal() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        context.getSystemService(NotificationManager::class.java).createNotificationChannel(
            NotificationChannel(CANAL, "Avisos de la planta", NotificationManager.IMPORTANCE_HIGH).apply {
                description = "Reuniones, capacitaciones y comunicados del administrador"
            }
        )
    }

    companion object {
        private const val CANAL = "avisos_planta"
        private const val BASE_ID = 7000
        const val EXTRA_ABRIR_AVISOS = "abrir_avisos"
    }
}
