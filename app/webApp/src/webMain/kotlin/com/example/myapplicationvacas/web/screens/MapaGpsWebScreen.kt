package com.example.myapplicationvacas.web.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.myapplicationvacas.web.data.WebMockData
import com.example.myapplicationvacas.web.theme.*

@Composable
fun MapaGpsWebScreen() {
    val ruta = WebMockData.rutaMonitoreo

    Row(
        modifier = Modifier
            .fillMaxSize()
            .background(WebBackground)
            .padding(24.dp),
        horizontalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        // ── Panel principal (Mapa mock) ────────────────────────────────────────
        Column(
            modifier = Modifier.weight(2f).fillMaxHeight(),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Filtros
            Row(
                modifier              = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(10.dp),
                verticalAlignment     = Alignment.CenterVertically
            ) {
                Text("FILTRAR MAPA:", style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary))

                FilterChipMap(label = "Todas las Rutas", selected = true, onClick = {})
                FilterChipMap(label = "Calidad: Aprobada", selected = false, onClick = {})

                Spacer(Modifier.weight(1f))

                Row(
                    verticalAlignment     = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                    modifier              = Modifier
                        .background(WebSurface2, RoundedCornerShape(8.dp))
                        .padding(horizontal = 12.dp, vertical = 6.dp)
                ) {
                    Icon(Icons.Filled.DateRange, null, tint = WebTextSecondary, modifier = Modifier.size(14.dp))
                    Text("Hoy, 07 de Febrero", style = MaterialTheme.typography.bodyMedium)
                }
            }

            // Mapa visual mock
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .weight(1f)
                    .clip(RoundedCornerShape(12.dp))
                    .border(1.dp, WebBorder, RoundedCornerShape(12.dp))
                    .background(
                        Brush.radialGradient(
                            colors = listOf(
                                Color(0xFF1A2E1A),
                                Color(0xFF2E4A1A),
                                Color(0xFF3A5A20),
                                Color(0xFF1A2E10),
                                Color(0xFF0A1A08)
                            )
                        )
                    )
            ) {
                // Puntos de calor simulados
                val puntos = WebMockData.puntosMapa
                Box(modifier = Modifier.fillMaxSize()) {
                    // Zonas de calor (círculos con opacidad)
                    Box(
                        modifier = Modifier
                            .size((puntos[0].intensidad * 200).dp)
                            .align(Alignment.Center)
                            .offset(x = (-20).dp, y = (-30).dp)
                            .clip(CircleShape)
                            .background(
                                Brush.radialGradient(
                                    colors = listOf(
                                        Color(0xFFFF0000).copy(alpha = 0.6f),
                                        Color(0xFFFF6600).copy(alpha = 0.3f),
                                        Color(0x00FF0000)
                                    )
                                )
                            )
                    )
                    Box(
                        modifier = Modifier
                            .size(120.dp)
                            .align(Alignment.Center)
                            .offset(x = 40.dp, y = 20.dp)
                            .clip(CircleShape)
                            .background(
                                Brush.radialGradient(
                                    colors = listOf(
                                        Color(0xFFFFAA00).copy(alpha = 0.5f),
                                        Color(0x00FFAA00)
                                    )
                                )
                            )
                    )

                    // Pin del camión
                    Box(
                        modifier = Modifier
                            .align(Alignment.Center)
                            .offset(x = (-10).dp, y = (-10).dp)
                    ) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Surface(shape = RoundedCornerShape(8.dp), color = WebGreen) {
                                Text(
                                    "EO-492",
                                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                                    style    = MaterialTheme.typography.labelSmall.copy(color = Color.Black, fontWeight = FontWeight.Bold)
                                )
                            }
                            Box(
                                modifier         = Modifier.size(32.dp).clip(CircleShape).background(WebGreen),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(Icons.Filled.LocalShipping, null, tint = Color.Black, modifier = Modifier.size(18.dp))
                            }
                        }
                    }

                    // Escala de intensidad
                    Column(
                        modifier = Modifier
                            .align(Alignment.BottomStart)
                            .padding(16.dp)
                            .background(WebSurface.copy(alpha = 0.85f), RoundedCornerShape(8.dp))
                            .padding(12.dp),
                        verticalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        Text("Escala de Intensidad", style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary))
                        Row(
                            modifier              = Modifier.width(160.dp).height(10.dp).clip(RoundedCornerShape(5.dp))
                                .background(
                                    Brush.horizontalGradient(listOf(Color(0xFF00FF00), Color(0xFFFFAA00), Color(0xFFFF0000)))
                                ),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {}
                        Row(
                            modifier              = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text("Bajo",  style = MaterialTheme.typography.labelSmall.copy(color = WebGreen,   fontSize = 9.sp))
                            Text("Medio", style = MaterialTheme.typography.labelSmall.copy(color = WebOrange,  fontSize = 9.sp))
                            Text("Alto",  style = MaterialTheme.typography.labelSmall.copy(color = WebRedLight,fontSize = 9.sp))
                        }
                    }
                }
            }
        }

        // ── Panel lateral: Monitoreo de rutas ─────────────────────────────────
        Column(
            modifier            = Modifier.width(280.dp).fillMaxHeight().verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text("Monitoreo de Rutas", style = MaterialTheme.typography.titleMedium)

            // Tarjeta de ruta
            Surface(
                modifier = Modifier.fillMaxWidth(),
                shape    = RoundedCornerShape(12.dp),
                color    = WebCard
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .border(1.dp, WebBorder, RoundedCornerShape(12.dp))
                        .padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Row(
                        modifier              = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text(ruta.nombre, style = MaterialTheme.typography.labelLarge.copy(color = WebTextPrimary, fontWeight = FontWeight.SemiBold))
                        Surface(shape = RoundedCornerShape(5.dp), color = WebGreenBg) {
                            Text(
                                "${ruta.paradas}/${ruta.totalParadas}",
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 3.dp),
                                style    = MaterialTheme.typography.labelSmall.copy(color = WebGreen, fontWeight = FontWeight.Bold)
                            )
                        }
                    }

                    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                        Icon(Icons.Filled.Person, null, tint = WebTextSecondary, modifier = Modifier.size(13.dp))
                        Text(ruta.conductor, style = MaterialTheme.typography.bodyMedium)
                    }
                    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                        Icon(Icons.Filled.LocalShipping, null, tint = WebTextSecondary, modifier = Modifier.size(13.dp))
                        Text("Flota: ${ruta.camion}", style = MaterialTheme.typography.bodyMedium)
                    }

                    HorizontalDivider(color = WebBorder)

                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceEvenly) {
                        RutaStat(value = "${ruta.litros}L", label = "Litros")
                        RutaStat(value = "${ruta.paradas} / ${ruta.totalParadas}", label = "Paradas")
                        RutaStat(value = "${ruta.km} km", label = "Distancia")
                    }

                    LinearProgressIndicator(
                        progress   = { ruta.paradas.toFloat() / ruta.totalParadas },
                        modifier   = Modifier.fillMaxWidth().height(6.dp),
                        color      = WebGreen,
                        trackColor = WebBorder
                    )
                    Text(
                        "Progreso: ${(ruta.paradas.toFloat() / ruta.totalParadas * 100).toInt()}%",
                        style = MaterialTheme.typography.labelSmall
                    )
                }
            }

            // Stats en tiempo real
            Text("Estadísticas en tiempo real", style = MaterialTheme.typography.labelLarge.copy(color = WebTextSecondary))

            listOf(
                Triple(Icons.Filled.Water,       "Total Litros Hoy",    "3,480 L"),
                Triple(Icons.Filled.AssignmentTurnedIn, "Acopios Completados", "48"),
                Triple(Icons.Filled.Speed,        "Velocidad Promedio",  "42 km/h"),
                Triple(Icons.Filled.Timer,        "Tiempo en Ruta",      "4h 22m")
            ).forEach { (icon, label, value) ->
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    shape    = RoundedCornerShape(10.dp),
                    color    = WebSurface2
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        Icon(icon, null, tint = WebGreen, modifier = Modifier.size(16.dp))
                        Text(label, modifier = Modifier.weight(1f), style = MaterialTheme.typography.bodyMedium)
                        Text(value, style = MaterialTheme.typography.labelLarge.copy(color = WebGreen, fontWeight = FontWeight.Bold))
                    }
                }
            }
        }
    }
}

@Composable
private fun RutaStat(value: String, label: String) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(value, style = MaterialTheme.typography.titleMedium.copy(color = WebGreen, fontWeight = FontWeight.Bold))
        Text(label, style = MaterialTheme.typography.labelSmall)
    }
}

@Composable
private fun FilterChipMap(label: String, selected: Boolean, onClick: () -> Unit) {
    Surface(
        shape  = RoundedCornerShape(20.dp),
        color  = if (selected) WebGreenBg else WebSurface2,
        onClick = onClick
    ) {
        Row(
            modifier = Modifier
                .border(1.dp, if (selected) WebGreen.copy(0.5f) else WebBorder, RoundedCornerShape(20.dp))
                .padding(horizontal = 12.dp, vertical = 6.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            if (selected) Icon(Icons.Filled.Check, null, tint = WebGreen, modifier = Modifier.size(12.dp))
            Text(label, style = MaterialTheme.typography.labelSmall.copy(color = if (selected) WebGreen else WebTextSecondary))
        }
    }
}
