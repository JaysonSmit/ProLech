package com.example.prolechc

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.mutableIntStateOf
import com.example.prolechc.data.local.EstadoSesion
import com.example.prolechc.domain.usecase.VerificarSesionUseCase
import org.koin.compose.viewmodel.koinViewModel
import com.example.prolechc.presentation.PantallaPrincipal
import com.example.prolechc.presentation.login.LoginScreen
import com.example.prolechc.presentation.theme.ProLechTema
import org.koin.compose.koinInject

/**
 * Composable raíz de la aplicación.
 * Koin ya debe estar inicializado antes de llamar a esta función.
 *
 * Con token guardado entra directo a "Mi ruta" (funciona en Modo Avión);
 * sin token muestra el login.
 */
@Composable
fun App(abrirAvisos: Boolean = false) {
    val verificarSesion: VerificarSesionUseCase = koinInject()
    val estadoSesion: EstadoSesion = koinInject()
    val sesionVencida by estadoSesion.vencida.collectAsState()
    var logueado by remember { mutableStateOf<Boolean?>(null) }
    // Cada vez que se muestra el login se usa un ViewModel nuevo: el anterior quedaba
    // con exitoso = true y, tras "Salir", volvía a entrar sin pedir la contraseña.
    var intentoLogin by remember { mutableIntStateOf(0) }

    LaunchedEffect(Unit) {
        logueado = verificarSesion()
    }
    // El servidor rechazó el token (401): volver al login sin borrar los datos offline
    LaunchedEffect(sesionVencida) {
        if (sesionVencida && logueado == true) {
            intentoLogin++
            logueado = false
        }
    }

    ProLechTema {
        Surface(Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
            when (logueado) {
                null  -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
                false -> LoginScreen(
                    onLoginExitoso = { logueado = true },
                    viewModel = koinViewModel(key = "login-$intentoLogin")
                )
                true  -> PantallaPrincipal(abrirAvisos = abrirAvisos, onSesionCerrada = {
                    intentoLogin++
                    logueado = false
                })
            }
        }
    }
}
