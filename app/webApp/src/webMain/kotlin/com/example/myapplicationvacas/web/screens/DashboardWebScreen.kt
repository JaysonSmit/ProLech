package com.example.myapplicationvacas.web.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.myapplicationvacas.web.components.*
import com.example.myapplicationvacas.web.data.WebMockData
import com.example.myapplicationvacas.web.theme.*

@Composable
fun DashboardWebScreen() {
    val acopios = WebMockData.acopiosRecientes
    val totalLitros = acopios.sumOf { it.litros }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(WebBackground)
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(20.dp)
    ) {

        Text(
            text  = "Dashboard General",
            style = MaterialTheme.typography.headlineMedium.copy(fontWeight = FontWeight.ExtraBold)
        )
        Text(
            text  = "Resumen en tiempo real del sistema de acopio registrado",
            style = MaterialTheme.typography.bodyMedium
        )

        // ── KPIs ──────────────────────────────────────────────────────────────
        Row(
            modifier              = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            KpiCard(
                modifier       = Modifier.weight(1f),
                label          = "Litros Hoy",
                value          = "%.0f L".format(totalLitros),
                change         = "+13.4% vs ayer",
                changePositive = true,
                icon           = Icons.Filled.Water,
                iconColor      = WebGreen
            )
            KpiCard(
                modifier       = Modifier.weight(1f),
                label          = "Acopios Hoy",
                value          = "${acopios.size}",
                change         = "+4 Acopios hoy",
                changePositive = true,
                icon           = Icons.Filled.AssignmentTurnedIn,
                iconColor      = WebBlue
            )
            KpiCard(
                modifier       = Modifier.weight(1f),
                label          = "Ganaderos Activos",
                value          = "112",
                change         = "−2 activos ayer",
                changePositive = false,
                icon           = Icons.Filled.People,
                iconColor      = WebOrange
            )
            KpiCard(
                modifier       = Modifier.weight(1f),
                label          = "Calidad Promedio",
                value          = "94.8%",
                change         = "+1.2% esta semana",
                changePositive = true,
                icon           = Icons.Filled.Star,
                iconColor      = WebYellow
            )
        }

        // ── Gráfica producción semanal + Distribución calidad ─────────────────
        Row(
            modifier              = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Gráfica semanal
            Surface(
                modifier = Modifier.weight(1.5f),
                shape    = RoundedCornerShape(12.dp),
                color    = WebCard
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .border(1.dp, WebBorder, RoundedCornerShape(12.dp))
                        .padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Row(
                        modifier              = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment     = Alignment.CenterVertically
                    ) {
                        Column {
                            Text("Producción Semanal (Litros)", style = MaterialTheme.typography.titleMedium)
                            Text("Comparativa de recolección por ruta registrado", style = MaterialTheme.typography.bodyMedium)
                        }
                        // Leyenda
                        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                            LegendDot(color = WebGreen,  label = "Ruta A-1 / Costa")
                            LegendDot(color = WebBlue.copy(alpha = 0.8f), label = "Ruta B-9 / Capachica")
                        }
                    }
                    // Barras dobles
                    val semana = WebMockData.produccionSemanal
                    Row(
                        modifier              = Modifier.fillMaxWidth().height(140.dp),
                        horizontalArrangement = Arrangement.spacedBy(6.dp),
                        verticalAlignment     = Alignment.Bottom
                    ) {
                        val maxVal = semana.maxOf { maxOf(it.rutaA, it.rutaB) }
                        semana.forEach { punto ->
                            Column(
                                modifier            = Modifier.weight(1f),
                                horizontalAlignment = Alignment.CenterHorizontally,
                                verticalArrangement = Arrangement.Bottom
                            ) {
                                Row(
                                    modifier              = Modifier.weight(1f),
                                    horizontalArrangement = Arrangement.spacedBy(2.dp),
                                    verticalAlignment     = Alignment.Bottom
                                ) {
                                    Box(
                                        modifier = Modifier
                                            .width(14.dp)
                                            .fillMaxHeight(fraction = (punto.rutaA / maxVal).toFloat())
                                            .background(WebGreen, RoundedCornerShape(topStart = 3.dp, topEnd = 3.dp))
                                    )
                                    Box(
                                        modifier = Modifier
                                            .width(14.dp)
                                            .fillMaxHeight(fraction = (punto.rutaB / maxVal).toFloat())
                                            .background(WebBlue.copy(alpha = 0.7f), RoundedCornerShape(topStart = 3.dp, topEnd = 3.dp))
                                    )
                                }
                                Spacer(Modifier.height(4.dp))
                                Text(punto.dia, style = MaterialTheme.typography.labelSmall.copy(fontSize = 10.sp))
                            }
                        }
                    }
                }
            }

            // Distribución por calidad
            Surface(
                modifier = Modifier.weight(1f),
                shape    = RoundedCornerShape(12.dp),
                color    = WebCard
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .border(1.dp, WebBorder, RoundedCornerShape(12.dp))
                        .padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Column {
                        Text("Distribución por Calidad", style = MaterialTheme.typography.titleMedium)
                        Text("Categoría de leche por % observado", style = MaterialTheme.typography.bodyMedium)
                    }

                    // Círculo central grande (mock visual)
                    Box(
                        modifier         = Modifier
                            .size(140.dp)
                            .background(WebSurface2, androidx.compose.foundation.shape.CircleShape)
                            .border(12.dp, WebGreen, androidx.compose.foundation.shape.CircleShape),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text("94.8%", style = MaterialTheme.typography.headlineMedium.copy(color = WebGreen, fontWeight = FontWeight.ExtraBold))
                            Text("Calidad", style = MaterialTheme.typography.labelSmall)
                        }
                    }

                    DonutLegend(
                        listOf(
                            Triple("Aprobada (85%)", 85.0, WebGreen),
                            Triple("Observada (12%)", 12.0, WebYellow),
                            Triple("Rechazada (3%)", 3.0, WebRedLight)
                        )
                    )
                }
            }
        }

        // ── Últimos acopios ───────────────────────────────────────────────────
        Surface(
            modifier = Modifier.fillMaxWidth(),
            shape    = RoundedCornerShape(12.dp),
            color    = WebCard
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .border(1.dp, WebBorder, RoundedCornerShape(12.dp))
                    .padding(20.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Row(
                    modifier              = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment     = Alignment.CenterVertically
                ) {
                    Column {
                        Text("Últimas Acopios Registrados", style = MaterialTheme.typography.titleMedium)
                        Text("Registros en las últimas horas del siguiente fecha-hora", style = MaterialTheme.typography.bodyMedium)
                    }
                    Button(
                        onClick = {},
                        shape   = RoundedCornerShape(8.dp),
                        colors  = ButtonDefaults.buttonColors(containerColor = WebGreenBg),
                        contentPadding = PaddingValues(horizontal = 14.dp, vertical = 8.dp)
                    ) {
                        Text("Ver Todos los Acopios →", style = MaterialTheme.typography.labelMedium.copy(color = WebGreen))
                    }
                }

                // Cabecera de tabla
                AcopiosTableHeader()
                HorizontalDivider(color = WebBorder)

                // Filas
                acopios.forEach { acopio ->
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(vertical = 10.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(acopio.hora,     modifier = Modifier.weight(0.8f), style = MaterialTheme.typography.bodyMedium)
                        Text(acopio.ganadero, modifier = Modifier.weight(2f),   style = MaterialTheme.typography.bodyMedium.copy(color = WebTextPrimary))
                        Text(acopio.ruta,     modifier = Modifier.weight(1.5f), style = MaterialTheme.typography.bodyMedium)
                        Text("${acopio.litros} L", modifier = Modifier.weight(0.8f), style = MaterialTheme.typography.bodyMedium.copy(color = WebGreen, fontWeight = FontWeight.SemiBold))
                        Box(modifier = Modifier.weight(1f)) { CalidadBadgeWeb(acopio.calidad) }
                        Row(modifier = Modifier.weight(1.5f), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                            Icon(Icons.Filled.LocationOn, null, tint = WebTextSecondary, modifier = Modifier.size(13.dp))
                            Text(acopio.sector, style = MaterialTheme.typography.bodyMedium)
                        }
                        Row(modifier = Modifier.weight(0.6f), horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                            IconButton(onClick = {}, modifier = Modifier.size(28.dp)) {
                                Icon(Icons.Filled.Visibility, null, tint = WebTextSecondary, modifier = Modifier.size(14.dp))
                            }
                        }
                    }
                    HorizontalDivider(color = WebBorder.copy(alpha = 0.5f))
                }
            }
        }
    }
}

@Composable
private fun AcopiosTableHeader() {
    Row(modifier = Modifier.fillMaxWidth()) {
        val headers = listOf("Hora" to 0.8f, "Nombre" to 2f, "Ruta" to 1.5f, "Litros" to 0.8f, "Calidad" to 1f, "GPS / Sector" to 1.5f, "Acciones" to 0.6f)
        headers.forEach { (h, w) ->
            Text(
                text     = h,
                modifier = Modifier.weight(w),
                style    = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary, fontWeight = FontWeight.Bold)
            )
        }
    }
}

@Composable
private fun LegendDot(color: Color, label: String) {
    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(4.dp)) {
        Box(modifier = Modifier.size(8.dp).background(color, androidx.compose.foundation.shape.CircleShape))
        Text(label, style = MaterialTheme.typography.labelSmall.copy(fontSize = 10.sp))
    }
}
