package com.example.prolechc.navigation

import androidx.compose.runtime.Composable
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.example.prolechc.ui.screens.*
import com.example.prolechc.viewmodel.ProLechViewModel

object Routes {
    const val LOGIN            = "login"
    const val DASHBOARD        = "dashboard"
    const val ACOPIO           = "acopio"
    const val REGISTRO_EXITOSO = "registro_exitoso"
    const val HISTORIAL        = "historial"
    const val SYNC             = "sync"
    const val MI_RUTA          = "mi_ruta"
}

// Helper para navegar sin acumular el backstack del BottomNav
private fun NavHostController.navigateBottomNav(route: String) {
    navigate(route) {
        popUpTo(Routes.DASHBOARD) { saveState = true }
        launchSingleTop = true
        restoreState    = true
    }
}

@Composable
fun ProLechNavGraph(
    navController: NavHostController = rememberNavController(),
    vm: ProLechViewModel = viewModel { ProLechViewModel() }
) {
    NavHost(navController = navController, startDestination = Routes.LOGIN) {

        composable(Routes.LOGIN) {
            LoginScreen(
                viewModel     = vm,
                onIniciarRuta = { navController.navigate(Routes.DASHBOARD) }
            )
        }

        composable(Routes.DASHBOARD) {
            DashboardScreen(
                viewModel     = vm,
                onNuevoAcopio = { navController.navigate(Routes.ACOPIO) },
                onHistorial   = { navController.navigateBottomNav(Routes.HISTORIAL) },
                onSincronizar = { navController.navigateBottomNav(Routes.SYNC) },
                onMiRuta      = { navController.navigateBottomNav(Routes.MI_RUTA) },
                // Bottom nav callbacks
                onBottomDashboard = { /* ya estamos aquí */ },
                onBottomAcopio    = { navController.navigate(Routes.ACOPIO) },
                onBottomRuta      = { navController.navigateBottomNav(Routes.MI_RUTA) },
                onBottomSync      = { navController.navigateBottomNav(Routes.SYNC) }
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
                onBottomDashboard = { navController.navigate(Routes.DASHBOARD) { popUpTo(Routes.DASHBOARD) { inclusive = true } } },
                onBottomAcopio    = { /* ya estamos */ },
                onBottomRuta      = { navController.navigateBottomNav(Routes.MI_RUTA) },
                onBottomSync      = { navController.navigateBottomNav(Routes.SYNC) }
            )
        }

        composable(Routes.REGISTRO_EXITOSO) {
            RegistroExitosoScreen(
                viewModel      = vm,
                onNuevoAcopio  = {
                    vm.resetForm()
                    navController.navigate(Routes.ACOPIO) { popUpTo(Routes.DASHBOARD) }
                },
                onVerHistorial = {
                    navController.navigate(Routes.HISTORIAL) { popUpTo(Routes.DASHBOARD) }
                }
            )
        }

        composable(Routes.HISTORIAL) {
            HistorialScreen(
                onBack            = { navController.popBackStack() },
                onNuevoAcopio     = { navController.navigate(Routes.ACOPIO) },
                onBottomDashboard = { navController.navigate(Routes.DASHBOARD) { popUpTo(Routes.DASHBOARD) { inclusive = true } } },
                onBottomAcopio    = { navController.navigate(Routes.ACOPIO) },
                onBottomRuta      = { navController.navigateBottomNav(Routes.MI_RUTA) },
                onBottomSync      = { navController.navigateBottomNav(Routes.SYNC) }
            )
        }

        composable(Routes.SYNC) {
            SyncScreen(
                viewModel         = vm,
                onBack            = { vm.resetSync(); navController.popBackStack() },
                onBottomDashboard = { navController.navigate(Routes.DASHBOARD) { popUpTo(Routes.DASHBOARD) { inclusive = true } } },
                onBottomAcopio    = { navController.navigate(Routes.ACOPIO) },
                onBottomRuta      = { navController.navigateBottomNav(Routes.MI_RUTA) },
                onBottomSync      = { /* ya estamos */ }
            )
        }

        composable(Routes.MI_RUTA) {
            MiRutaScreen(
                onBack            = { navController.popBackStack() },
                onBottomDashboard = { navController.navigate(Routes.DASHBOARD) { popUpTo(Routes.DASHBOARD) { inclusive = true } } },
                onBottomAcopio    = { navController.navigate(Routes.ACOPIO) },
                onBottomRuta      = { /* ya estamos */ },
                onBottomSync      = { navController.navigateBottomNav(Routes.SYNC) }
            )
        }
    }
}
