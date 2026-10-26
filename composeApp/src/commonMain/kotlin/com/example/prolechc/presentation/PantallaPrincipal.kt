package com.example.prolechc.presentation

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.domain.model.GanaderoCampo
import com.example.prolechc.presentation.acopios.AcopioScreen
import com.example.prolechc.presentation.acopios.AcopioViewModel
import com.example.prolechc.presentation.ruta.MiRutaScreen
import com.example.prolechc.data.local.ConfigLocal
import com.example.prolechc.presentation.ruta.MiRutaViewModel
import org.koin.compose.koinInject
import org.koin.compose.viewmodel.koinViewModel

private enum class Pestana(val titulo: String, val icono: String) {
    RUTA("Mi ruta", "🗺️"),
    ACOPIO("Acopios", "🥛")
}

/** Contenedor de la app con sesión iniciada: pestañas Mi ruta / Acopios. */
@Composable
fun PantallaPrincipal(onSesionCerrada: () -> Unit) {
    var pestana by rememberSaveable { mutableStateOf(Pestana.RUTA) }
    // Una sola instancia: desde la ruta se preselecciona el ganadero del formulario de acopio
    val acopioViewModel: AcopioViewModel = koinViewModel()
    val acopios by acopioViewModel.uiState.collectAsStateWithLifecycle()
    val rutaViewModel: MiRutaViewModel = koinViewModel()
    val config: ConfigLocal = koinInject()
    var verCuenta by rememberSaveable { mutableStateOf(false) }

    if (verCuenta) {
        DialogoCuenta(
            config             = config,
            pendientes         = acopios.pendientes,
            sincronizando      = acopios.sincronizando,
            onSincronizar      = acopioViewModel::sincronizar,
            onServidorCambiado = { acopioViewModel.sincronizar(); rutaViewModel.refrescar(silencioso = true) },
            onCerrarSesion     = { verCuenta = false; acopioViewModel.cerrarSesion(onSesionCerrada) },
            onCerrar           = { verCuenta = false }
        )
    }

    Scaffold(
        bottomBar = {
            NavigationBar(containerColor = MaterialTheme.colorScheme.surface) {
                Pestana.entries.forEach { p ->
                    NavigationBarItem(
                        selected = pestana == p,
                        onClick = { pestana = p },
                        icon = {
                            if (p == Pestana.ACOPIO && acopios.pendientes > 0) {
                                BadgedBox(badge = { Badge { Text("${acopios.pendientes}") } }) { Text(p.icono, fontSize = 22.sp) }
                            } else {
                                Text(p.icono, fontSize = 22.sp)
                            }
                        },
                        label = { Text(p.titulo) },
                        colors = NavigationBarItemDefaults.colors(
                            indicatorColor = MaterialTheme.colorScheme.primaryContainer
                        )
                    )
                }
                // No es una pestaña: abre el diálogo de la cuenta (servidor, sincronizar, cerrar sesión)
                NavigationBarItem(
                    selected = false,
                    onClick = { verCuenta = true },
                    icon = { Text("👤", fontSize = 22.sp) },
                    label = { Text("Cuenta") }
                )
            }
        }
    ) { padding ->
        Box(Modifier.fillMaxSize().padding(padding)) {
            when (pestana) {
                Pestana.RUTA -> MiRutaScreen(
                    viewModel = rutaViewModel,
                    onRegistrarAcopio = { parada ->
                        acopioViewModel.onGanaderoSeleccionado(
                            GanaderoCampo(
                                id = parada.ganaderoId.toLong(),
                                nombre = parada.ganaderoNombre,
                                dni = parada.ganaderoNii,
                                suspendido = parada.suspendido
                            )
                        )
                        if (!parada.suspendido) pestana = Pestana.ACOPIO
                    }
                )
                Pestana.ACOPIO -> AcopioScreen(onSesionCerrada = onSesionCerrada, viewModel = acopioViewModel)
            }
        }
    }
}
