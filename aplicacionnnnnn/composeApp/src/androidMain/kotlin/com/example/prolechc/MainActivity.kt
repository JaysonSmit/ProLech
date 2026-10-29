package com.example.prolechc

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.runtime.mutableStateOf
import androidx.core.content.ContextCompat
import com.example.prolechc.data.dispositivo.AvisadorAndroid

class MainActivity : ComponentActivity() {

    /** true cuando la app se abrió tocando la notificación de un aviso. */
    private val abrirAvisos = mutableStateOf(false)

    private val pedirPermisoNotificaciones =
        registerForActivityResult(ActivityResultContracts.RequestPermission()) { }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        abrirAvisos.value = intent.getBooleanExtra(AvisadorAndroid.EXTRA_ABRIR_AVISOS, false)
        // Android 13+: sin este permiso no se ven los avisos de reuniones en la barra del celular
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) {
            pedirPermisoNotificaciones.launch(Manifest.permission.POST_NOTIFICATIONS)
        }
        setContent {
            App(abrirAvisos = abrirAvisos.value)
        }
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        if (intent.getBooleanExtra(AvisadorAndroid.EXTRA_ABRIR_AVISOS, false)) abrirAvisos.value = true
    }
}
