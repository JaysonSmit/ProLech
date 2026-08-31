package com.example.myapplicationvacas.web.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
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
import com.example.myapplicationvacas.web.components.KpiCard
import com.example.myapplicationvacas.web.components.SimpleBarChart
import com.example.myapplicationvacas.web.data.WebMockData
import com.example.myapplicationvacas.web.theme.*

@Composable
fun ReportesWebScreen() {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(WebBackground)
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(20.dp)
    ) {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Column {
                Text("Reportes y Analítica Avanzada", style = MaterialTheme.typography.headlineMedium.copy(fontWeight = FontWeight.ExtraBold))
                Text("Análisis de producción, calidad y tendencias del período", style = MaterialTheme.typography.bodyMedium)
            }
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedButton(onClick = {}, shape = RoundedCornerShape(8.dp), colors = ButtonDefaults.outlinedButtonColors(contentColor = WebTextSecondary)) {
                    Icon(Icons.Filled.PictureAsPdf, null, modifier = Modifier.size(14.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("Exportar PDF")
                }
                Button(onClick = {}, shape = RoundedCornerShape(8.dp), colors = ButtonDefaults.buttonColors(containerColor = WebGreen)) {
                    Icon(Icons.Filled.Refresh, null, tint = Color.Black, modifier = Modifier.size(14.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("Actualizar", style = MaterialTheme.typography.labelLarge.copy(color = Color.Black))
                }
            }
        }

        // KPIs
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
            KpiCard(modifier = Modifier.weight(1f), label = "Producción Total Período",   value = "18,450 L", change = "+11% vs ant.", changePositive = true, icon = Icons.Filled.Water, iconColor = WebGreen)
            KpiCard(modifier = Modifier.weight(1f), label = "Calidad Promedio Período",   value = "94.8%",    change = "+1.2% vs ant.", changePositive = true, icon = Icons.Filled.Star, iconColor = WebYellow)
            KpiCard(modifier = Modifier.weight(1f), label = "Ganaderos Participando",     value = "112",      change = "−2 vs ant.", changePositive = false, icon = Icons.Filled.People, iconColor = WebOrange)
            KpiCard(modifier = Modifier.weight(1f), label = "Tendencia de Rendimiento",   value = "+4.2%",    change = "Semana favorable", changePositive = true, icon = Icons.Filled.TrendingUp, iconColor = WebBlue)
        }

        // Gráficas
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
            // Producción por período
            Surface(modifier = Modifier.weight(1f), shape = RoundedCornerShape(12.dp), color = WebCard) {
                Column(
                    modifier = Modifier.fillMaxWidth().border(1.dp, WebBorder, RoundedCornerShape(12.dp)).padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Text("Producción por Período y Conductor", style = MaterialTheme.typography.titleMedium)
                    Text("Comparativa semanal de litros recogidos", style = MaterialTheme.typography.bodyMedium)
                    val datos = WebMockData.produccionSemanal.map { it.dia to it.rutaA }
                    SimpleBarChart(
                        modifier = Modifier.fillMaxWidth().height(160.dp),
                        values   = datos,
                        color    = WebGreen
                    )
                }
            }

            // Calidad promedio por ruta
            Surface(modifier = Modifier.weight(1f), shape = RoundedCornerShape(12.dp), color = WebCard) {
                Column(
                    modifier = Modifier.fillMaxWidth().border(1.dp, WebBorder, RoundedCornerShape(12.dp)).padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Text("Calidad Promedio por Ruta", style = MaterialTheme.typography.titleMedium)
                    Text("Porcentaje de aprobación por zona", style = MaterialTheme.typography.bodyMedium)
                    val datos = listOf("Ruta A-1" to 96.0, "Ruta B-2" to 93.5, "Ruta C-3" to 91.2, "Ruta D-1" to 88.0)
                    SimpleBarChart(
                        modifier = Modifier.fillMaxWidth().height(160.dp),
                        values   = datos,
                        color    = WebBlue.copy(alpha = 0.8f)
                    )
                }
            }

            // Tendencias de rendimiento
            Surface(modifier = Modifier.weight(1f), shape = RoundedCornerShape(12.dp), color = WebCard) {
                Column(
                    modifier = Modifier.fillMaxWidth().border(1.dp, WebBorder, RoundedCornerShape(12.dp)).padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Text("Tendencias de Rendimiento", style = MaterialTheme.typography.titleMedium)
                    Text("Evolución semanal del acopio", style = MaterialTheme.typography.bodyMedium)
                    // Barras de tendencia
                    val datos = listOf("Sem 1" to 3200.0, "Sem 2" to 3480.0, "Sem 3" to 3100.0, "Sem 4" to 3750.0)
                    SimpleBarChart(
                        modifier = Modifier.fillMaxWidth().height(160.dp),
                        values   = datos,
                        color    = WebOrange
                    )
                }
            }
        }

        // Tabla resumen por ganadero
        Surface(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(12.dp), color = WebCard) {
            Column(
                modifier = Modifier.fillMaxWidth().border(1.dp, WebBorder, RoundedCornerShape(12.dp)).padding(20.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text("Resumen por Ganadero — Período Actual", style = MaterialTheme.typography.titleMedium)

                Row(
                    modifier = Modifier.fillMaxWidth().background(WebSurface2, RoundedCornerShape(8.dp)).padding(horizontal = 16.dp, vertical = 10.dp)
                ) {
                    listOf("Ganadero" to 2f, "Sector" to 1.5f, "Litros" to 1f, "Calidad" to 1f, "Variación" to 1f, "Tendencia" to 1f).forEach { (h, w) ->
                        Text(h, modifier = Modifier.weight(w), style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary, fontWeight = FontWeight.Bold))
                    }
                }

                WebMockData.ganaderos.forEachIndexed { i, g ->
                    val calidad = 88.0 + (i * 2.3)
                    val variacion = if (i % 2 == 0) "+${(i + 1) * 2.1}%" else "-${i * 0.8}%"
                    val positiva = variacion.startsWith("+")
                    Row(modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 10.dp), verticalAlignment = Alignment.CenterVertically) {
                        Text(g.nombre, modifier = Modifier.weight(2f), style = MaterialTheme.typography.bodyMedium.copy(color = WebTextPrimary))
                        Text(g.sector, modifier = Modifier.weight(1.5f), style = MaterialTheme.typography.bodyMedium)
                        Text("%.0f L".format(g.litrosMes), modifier = Modifier.weight(1f), style = MaterialTheme.typography.bodyMedium.copy(color = WebGreen, fontWeight = FontWeight.SemiBold))
                        Text("%.1f%%".format(calidad), modifier = Modifier.weight(1f), style = MaterialTheme.typography.bodyMedium)
                        Text(variacion, modifier = Modifier.weight(1f),
                            style = MaterialTheme.typography.bodyMedium.copy(color = if (positiva) WebGreen else WebRedLight, fontWeight = FontWeight.SemiBold))
                        Row(modifier = Modifier.weight(1f), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                            Icon(
                                if (positiva) Icons.Filled.TrendingUp else Icons.Filled.TrendingDown,
                                null,
                                tint     = if (positiva) WebGreen else WebRedLight,
                                modifier = Modifier.size(14.dp)
                            )
                            Text(if (positiva) "Subiendo" else "Bajando", style = MaterialTheme.typography.labelSmall.copy(color = if (positiva) WebGreen else WebRedLight))
                        }
                    }
                    if (i < WebMockData.ganaderos.lastIndex) HorizontalDivider(color = WebBorder.copy(alpha = 0.4f))
                }
            }
        }
    }
}
