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
import com.example.prolechc.domain.usecase.VerificarSesionUseCase
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
fun App() {
    val verificarSesion: VerificarSesionUseCase = koinInject()
    var logueado by remember { mutableStateOf<Boolean?>(null) }

    LaunchedEffect(Unit) {
        logueado = verificarSesion()
    }

    ProLechTema {
        Surface(Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
            when (logueado) {
                null  -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
                false -> LoginScreen(onLoginExitoso = { logueado = true })
                true  -> PantallaPrincipal(onSesionCerrada = { logueado = false })
            }
        }
    }
}
