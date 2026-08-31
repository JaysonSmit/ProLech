package com.example.myapplicationvacas.web

import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import com.example.myapplicationvacas.web.components.WebSidebar
import com.example.myapplicationvacas.web.components.WebTopBar
import com.example.myapplicationvacas.web.screens.*
import com.example.myapplicationvacas.web.theme.ProLechWebTheme
import androidx.compose.foundation.layout.Column

@Composable
fun ProLechWebApp() {
    ProLechWebTheme {
        var currentRoute by remember { mutableStateOf("dashboard") }

        Row(modifier = Modifier.fillMaxSize()) {
            // Sidebar de navegación
            WebSidebar(
                currentRoute = currentRoute,
                onNavigate   = { currentRoute = it }
            )

            // Área de contenido principal
            Column(modifier = Modifier.weight(1f).fillMaxSize()) {
                // TopBar compartido
                WebTopBar(
                    title = when (currentRoute) {
                        "dashboard"    -> "Dashboard General"
                        "acopios"      -> "Registros de Acopio"
                        "ganaderos"    -> "Ganaderos"
                        "inventario"   -> "Inventario de Producción"
                        "liquidacion"  -> "Liquidación de Pagos"
                        "mapa"         -> "Mapa de Calor GPS"
                        "reuniones"    -> "Gestión de Reuniones"
                        "reportes"     -> "Reportes y Analítica"
                        "configuracion" -> "Configuración"
                        else           -> "ProLech"
                    }
                )

                // Pantalla activa
                when (currentRoute) {
                    "dashboard"    -> DashboardWebScreen()
                    "acopios"      -> AcopiosWebScreen()
                    "ganaderos"    -> GanaderosWebScreen()
                    "inventario"   -> InventarioWebScreen()
                    "liquidacion"  -> LiquidacionWebScreen()
                    "mapa"         -> MapaGpsWebScreen()
                    "reuniones"    -> ReunionesWebScreen()
                    "reportes"     -> ReportesWebScreen()
                    "configuracion" -> ConfiguracionWebScreen()
                    else           -> DashboardWebScreen()
                }
            }
        }
    }
}
