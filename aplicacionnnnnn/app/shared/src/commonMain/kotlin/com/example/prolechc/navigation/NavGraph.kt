package com.example.prolechc.navigation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import kotlinx.coroutines.launch
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.example.prolechc.ui.screens.*
import com.example.prolechc.viewmodel.ProLechViewModel

object Routes {
    const val LOGIN              = "login"
    const val DASHBOARD          = "dashboard"
    const val ACOPIO             = "acopio"
    const val REGISTRO_EXITOSO   = "registro_exitoso"
    const val HISTORIAL          = "historial"
    const val SYNC               = "sync"
    const val MI_RUTA            = "mi_ruta"
    const val MAPA_CHOFER        = "mapa_chofer"
    const val SOLICITAR_RUTA     = "solicitar_ruta"
    const val NOTIFICACIONES     = "notificaciones"
    const val REUNIONES          = "reuniones"
    const val DETALLE_ACOPIO     = "detalle_acopio/{acopioId}"
    const val ESCANEAR_TICKET    = "escanear_ticket"
    const val MODO_BATCH         = "modo_batch"

    fun detalleAcopio(id: Int) = "detalle_acopio/$id"
}

private fun NavHostController.navigateBottomNav(route: String) {
    navigate(route) {
        popUpTo(Routes.DASHBOARD) { saveState = true }
        launchSingleTop = true
        restoreState    = true
    }
}

@Composable
fun ProLechNavGraph(
    navController: NavHostController        = rememberNavController(),
    vm: ProLechViewModel                    = viewModel { ProLechViewModel() },
    mapaChoferScreen: (@Composable (onBack: () -> Unit) -> Unit)?    = null,
    solicitarRutaScreen: (@Composable (onBack: () -> Unit) -> Unit)? = null,
    notificacionesScreen: (@Composable (onBack: () -> Unit) -> Unit)? = null,
    reunionesScreen: (@Composable (onBack: () -> Unit) -> Unit)? = null,
    onImprimirBoleta: (suspend (
        ticketNumero: String,
        conductorNombre: String,
        ganaderoNombre: String,
        dniGanadero: String,
        litros: Double,
        porcentajeAgua: String,
        acidezPh: String,
        densidad: String,
        estado: String,
        tieneDescuento: Boolean,
        rutaNombre: String
    ) -> String)? = null,
    onExportarPdf: ((acopioId: Int) -> Unit)? = null,
    escanearTicketScreen: (@Composable (onResultado: (Any) -> Unit, onCancelar: () -> Unit) -> Unit)? = null,
    /** Abre la cámara para la foto de evidencia; el resultado llega por vm.onFotoEvidencia() */
    onTomarFotoEvidencia: (() -> Unit)? = null,
    /** Conteo actual de notificaciones no leídas para el badge de la campana */
    notificacionesCount: Int = 0
) {
    NavHost(navController = navController, startDestination = Routes.LOGIN) {

        composable(Routes.LOGIN) {
            LoginScreen(
                viewModel     = vm,
                onIniciarRuta = {
                    navController.navigate(Routes.DASHBOARD) {
                        popUpTo(Routes.LOGIN) { inclusive = true }
                    }
                }
            )
        }

        composable(Routes.DASHBOARD) {
            DashboardScreen(
                viewModel            = vm,
                onNuevoAcopio        = { navController.navigate(Routes.ACOPIO) },
                onModoBatch          = { navController.navigate(Routes.MODO_BATCH) },
                onHistorial          = { navController.navigateBottomNav(Routes.HISTORIAL) },
                onSincronizar        = { navController.navigateBottomNav(Routes.SYNC) },
                onMiRuta             = { navController.navigateBottomNav(Routes.MI_RUTA) },
                onNotificaciones     = { navController.navigate(Routes.NOTIFICACIONES) },
                onReuniones          = if (reunionesScreen != null) {
                    { navController.navigate(Routes.REUNIONES) }
                } else null,
                notificacionesCount  = notificacionesCount,
                onBottomDashboard    = { },
                onBottomAcopio       = { navController.navigate(Routes.ACOPIO) },
                onBottomRuta         = { navController.navigateBottomNav(Routes.MI_RUTA) },
                onBottomSync         = { navController.navigateBottomNav(Routes.SYNC) }
            )
        }

        composable(Routes.ACOPIO) {
            AcopioScreen(
                viewModel         = vm,
                onBack            = { vm.resetForm(); navController.popBackStack() },
                onGuardadoExitoso = {
                    navController.navigate(Routes.REGISTRO_EXITOSO) {
                        popUpTo(Routes.ACOPIO) { inclusive = true }
                    }
                },
                onEscanearTicket  = if (escanearTicketScreen != null) {
                    { navController.navigate(Routes.ESCANEAR_TICKET) }
                } else null,
                onTomarFotoEvidencia = onTomarFotoEvidencia,
                onBottomDashboard = {
                    navController.navigate(Routes.DASHBOARD) {
                        popUpTo(Routes.DASHBOARD) { inclusive = true }
                    }
                },
                onBottomAcopio    = { },
                onBottomRuta      = { navController.navigateBottomNav(Routes.MI_RUTA) },
                onBottomSync      = { navController.navigateBottomNav(Routes.SYNC) }
            )
        }

        composable(Routes.MODO_BATCH) {
            ModoBatchScreen(
                viewModel         = vm,
                onBack            = { vm.resetBatch(); navController.popBackStack() },
                onGuardadoExitoso = {
                    navController.navigate(Routes.HISTORIAL) {
                        popUpTo(Routes.DASHBOARD)
                    }
                }
            )
        }

        composable(Routes.REGISTRO_EXITOSO) {
            val coroutineScope    = androidx.compose.runtime.rememberCoroutineScope()
            val snackbarHostState = remember { androidx.compose.material3.SnackbarHostState() }

            RegistroExitosoScreen(
                viewModel      = vm,
                onNuevoAcopio  = {
                    vm.resetForm()
                    navController.navigate(Routes.ACOPIO) { popUpTo(Routes.DASHBOARD) }
                },
                onVerHistorial = {
                    navController.navigate(Routes.HISTORIAL) { popUpTo(Routes.DASHBOARD) }
                },
                onImprimirBoleta = if (onImprimirBoleta != null) {
                    {
                        coroutineScope.launch {
                            val form  = vm.formState.value
                            val ruta  = vm.miRutaState.value
                            val login = vm.loginState.value
                            val msg = onImprimirBoleta(
                                form.ticketNumero,
                                login.conductorNombre,
                                form.ganaderoNombre,
                                form.dniGanadero,
                                form.litros,
                                form.porcentajeAgua,
                                form.acidezPh,
                                form.densidad,
                                if (form.lechRechazada) "RECHAZADA" else "APROBADA",
                                form.tieneDescuento,
                                ruta.rutaNombre.ifBlank { login.ruta }
                            )
                            snackbarHostState.showSnackbar(msg)
                        }
                    }
                } else null
            )
        }

        composable(Routes.HISTORIAL) {
            HistorialScreen(
                onBack            = { navController.popBackStack() },
                onNuevoAcopio     = { navController.navigate(Routes.ACOPIO) },
                onVerDetalle      = { acopioId -> navController.navigate(Routes.detalleAcopio(acopioId)) },
                onBottomDashboard = {
                    navController.navigate(Routes.DASHBOARD) {
                        popUpTo(Routes.DASHBOARD) { inclusive = true }
                    }
                },
                onBottomAcopio    = { navController.navigate(Routes.ACOPIO) },
                onBottomRuta      = { navController.navigateBottomNav(Routes.MI_RUTA) },
                onBottomSync      = { navController.navigateBottomNav(Routes.SYNC) },
                viewModel         = vm
            )
        }

        composable(Routes.SYNC) {
            SyncScreen(
                viewModel         = vm,
                onBack            = { vm.resetSync(); navController.popBackStack() },
                onBottomDashboard = {
                    navController.navigate(Routes.DASHBOARD) {
                        popUpTo(Routes.DASHBOARD) { inclusive = true }
                    }
                },
                onBottomAcopio    = { navController.navigate(Routes.ACOPIO) },
                onBottomRuta      = { navController.navigateBottomNav(Routes.MI_RUTA) },
                onBottomSync      = { }
            )
        }

        composable(Routes.MI_RUTA) {
            MiRutaScreen(
                viewModel         = vm,
                onBack            = { navController.popBackStack() },
                onVerMapa         = if (mapaChoferScreen != null) {
                    { navController.navigate(Routes.MAPA_CHOFER) }
                } else null,
                onSolicitarRuta   = if (solicitarRutaScreen != null) {
                    { navController.navigate(Routes.SOLICITAR_RUTA) }
                } else null,
                onBottomDashboard = {
                    navController.navigate(Routes.DASHBOARD) {
                        popUpTo(Routes.DASHBOARD) { inclusive = true }
                    }
                },
                onBottomAcopio    = { navController.navigate(Routes.ACOPIO) },
                onBottomRuta      = { },
                onBottomSync      = { navController.navigateBottomNav(Routes.SYNC) }
            )
        }

        if (mapaChoferScreen != null) {
            composable(Routes.MAPA_CHOFER) {
                mapaChoferScreen { navController.popBackStack() }
            }
        }

        if (solicitarRutaScreen != null) {
            composable(Routes.SOLICITAR_RUTA) {
                solicitarRutaScreen { navController.popBackStack() }
            }
        }

        if (reunionesScreen != null) {
            composable(Routes.REUNIONES) {
                reunionesScreen { navController.popBackStack() }
            }
        }

        // ── Escanear ticket LACTOMAT (OCR) ───────────────────────────────────
        if (escanearTicketScreen != null) {
            composable(Routes.ESCANEAR_TICKET) {
                escanearTicketScreen(
                    { resultado -> navController.popBackStack() },
                    { navController.popBackStack() }
                )
            }
        }

        // ── Detalle de acopio ─────────────────────────────────────────────
        composable(
            route     = Routes.DETALLE_ACOPIO,
            arguments = listOf(navArgument("acopioId") { type = NavType.IntType })
        ) { backStackEntry ->
            val acopioId = backStackEntry.arguments?.getInt("acopioId") ?: return@composable
            val acopios  = vm.historialCompleto
            val acopio   = acopios.find { it.id == acopioId } ?: return@composable

            DetalleAcopioScreen(
                acopio        = acopio,
                onBack        = { navController.popBackStack() },
                onExportarPdf = if (onExportarPdf != null) {
                    { onExportarPdf(acopioId) }
                } else null
            )
        }
        if (notificacionesScreen != null) {
            composable(Routes.NOTIFICACIONES) {
                notificacionesScreen { navController.popBackStack() }
            }
        } else {
            // Ruta por defecto usando la pantalla compartida
            composable(Routes.NOTIFICACIONES) {
                // La pantalla está en androidApp — no accesible desde commonMain
                // Se inyecta desde MainActivity vía notificacionesScreen
            }
        }
    }
}
