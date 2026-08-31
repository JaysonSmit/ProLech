package com.example.myapplicationvacas.navigation

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.example.myapplicationvacas.ui.screens.*
import com.example.myapplicationvacas.ui.theme.*
import com.example.myapplicationvacas.viewmodel.ProLechViewModel

// ── Rutas ─────────────────────────────────────────────────────────────────────
object Routes {
    const val LOGIN          = "login"
    const val MAIN           = "main"
    const val DASHBOARD      = "dashboard"
    const val ACOPIO_FORM    = "acopio_form"
    const val HISTORIAL      = "historial"
    const val SINCRONIZACION = "sincronizacion"
}

data class BottomNavItem(val route: String, val label: String, val icon: ImageVector)

private val bottomItems = listOf(
    BottomNavItem(Routes.DASHBOARD,      "Inicio",    Icons.Filled.Home),
    BottomNavItem(Routes.ACOPIO_FORM,    "Acopio",    Icons.Filled.Add),
    BottomNavItem(Routes.HISTORIAL,      "Historial", Icons.Filled.History),
    BottomNavItem(Routes.SINCRONIZACION, "Sync",      Icons.Filled.Sync)
)

// ── Grafo raíz ────────────────────────────────────────────────────────────────
@Composable
fun ProLechNavGraph(navController: NavHostController) {
    val viewModel: ProLechViewModel = viewModel()

    NavHost(navController = navController, startDestination = Routes.LOGIN) {
        composable(Routes.LOGIN) {
            LoginScreen(
                viewModel      = viewModel,
                onRutaIniciada = {
                    navController.navigate(Routes.MAIN) {
                        popUpTo(Routes.LOGIN) { inclusive = true }
                    }
                }
            )
        }
        composable(Routes.MAIN) {
            MainShell(viewModel = viewModel)
        }
    }
}

// ── Shell con BottomBar ───────────────────────────────────────────────────────
@Composable
fun MainShell(viewModel: ProLechViewModel) {
    val innerNav = rememberNavController()
    val backStack by innerNav.currentBackStackEntryAsState()
    val currentRoute = backStack?.destination?.route

    Scaffold(
        containerColor = BackgroundDark,
        bottomBar = {
            NavigationBar(containerColor = SurfaceDark) {
                bottomItems.forEach { item ->
                    val selected = currentRoute == item.route
                    NavigationBarItem(
                        selected = selected,
                        onClick  = {
                            innerNav.navigate(item.route) {
                                popUpTo(innerNav.graph.findStartDestination().id) {
                                    saveState = true
                                }
                                launchSingleTop = true
                                restoreState    = true
                            }
                        },
                        icon  = { Icon(item.icon, contentDescription = item.label) },
                        label = { Text(item.label) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor   = GreenPrimary,
                            selectedTextColor   = GreenPrimary,
                            unselectedIconColor = TextSecondary,
                            unselectedTextColor = TextSecondary,
                            indicatorColor      = GreenContainer
                        )
                    )
                }
            }
        }
    ) { innerPadding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            NavHost(navController = innerNav, startDestination = Routes.DASHBOARD) {
                composable(Routes.DASHBOARD) {
                    DashboardScreen(
                        viewModel             = viewModel,
                        onNavigateToAcopio    = { innerNav.navigate(Routes.ACOPIO_FORM) },
                        onNavigateToHistorial = { innerNav.navigate(Routes.HISTORIAL) },
                        onNavigateToSync      = { innerNav.navigate(Routes.SINCRONIZACION) }
                    )
                }
                composable(Routes.ACOPIO_FORM) {
                    AcopioFormScreen(
                        viewModel      = viewModel,
                        onNavigateBack = {
                            viewModel.resetForm()
                            innerNav.popBackStack()
                        },
                        onGuardadoExitoso = {
                            innerNav.navigate(Routes.DASHBOARD) {
                                popUpTo(Routes.DASHBOARD) { inclusive = true }
                            }
                        }
                    )
                }
                composable(Routes.HISTORIAL) {
                    HistorialScreen(
                        viewModel      = viewModel,
                        onNavigateBack = { innerNav.popBackStack() }
                    )
                }
                composable(Routes.SINCRONIZACION) {
                    SincronizacionScreen(
                        viewModel      = viewModel,
                        onNavigateBack = { innerNav.popBackStack() }
                    )
                }
            }
        }
    }
}
