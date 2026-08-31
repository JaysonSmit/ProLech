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
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.myapplicationvacas.web.components.CalidadBadgeWeb
import com.example.myapplicationvacas.web.components.KpiCard
import com.example.myapplicationvacas.web.data.WebMockData
import com.example.myapplicationvacas.web.data.EstadoAcopio
import com.example.myapplicationvacas.web.theme.*

@Composable
fun AcopiosWebScreen() {
    var filtroCalidad by remember { mutableStateOf<EstadoAcopio?>(null) }
    val acopios = WebMockData.acopiosRecientes.filter {
        filtroCalidad == null || it.calidad == filtroCalidad
    }
    val totalLitros   = acopios.sumOf { it.litros }
    val aprobados     = WebMockData.acopiosRecientes.count { it.calidad == EstadoAcopio.APROBADO }
    val rechazados    = WebMockData.acopiosRecientes.count { it.calidad == EstadoAcopio.RECHAZADO }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(WebBackground)
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(20.dp)
    ) {

        // ── Cabecera ──────────────────────────────────────────────────────────
        Row(
            modifier              = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment     = Alignment.CenterVertically
        ) {
            Column {
                Text("Registros de Acopio", style = MaterialTheme.typography.headlineMedium.copy(fontWeight = FontWeight.ExtraBold))
                Text("Listado completo de acopios registrados hoy", style = MaterialTheme.typography.bodyMedium)
            }
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedButton(onClick = {}, shape = RoundedCornerShape(8.dp), colors = ButtonDefaults.outlinedButtonColors(contentColor = WebTextSecondary)) {
                    Icon(Icons.Filled.PictureAsPdf, null, modifier = Modifier.size(14.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("Exportar PDF", style = MaterialTheme.typography.labelLarge)
                }
                OutlinedButton(onClick = {}, shape = RoundedCornerShape(8.dp), colors = ButtonDefaults.outlinedButtonColors(contentColor = WebTextSecondary)) {
                    Icon(Icons.Filled.GridOn, null, modifier = Modifier.size(14.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("Exportar Excel", style = MaterialTheme.typography.labelLarge)
                }
            }
        }

        // ── KPIs ──────────────────────────────────────────────────────────────
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
            KpiCard(modifier = Modifier.weight(1f), label = "Total Litros Hoy", value = "%.0f L".format(WebMockData.acopiosRecientes.sumOf { it.litros }),
                change = "+13.4% vs ayer", changePositive = true, icon = Icons.Filled.Water, iconColor = WebGreen)
            KpiCard(modifier = Modifier.weight(1f), label = "Acopios Totales",  value = "${WebMockData.acopiosRecientes.size}",
                change = "+2 vs ayer", changePositive = true, icon = Icons.Filled.AssignmentTurnedIn, iconColor = WebBlue)
            KpiCard(modifier = Modifier.weight(1f), label = "Aprobados",        value = "$aprobados",
                change = "${(aprobados * 100 / WebMockData.acopiosRecientes.size)}% del total", changePositive = true, icon = Icons.Filled.CheckCircle, iconColor = WebGreen)
            KpiCard(modifier = Modifier.weight(1f), label = "Rechazados",       value = "$rechazados",
                change = "${(rechazados * 100 / WebMockData.acopiosRecientes.size)}% del total", changePositive = false, icon = Icons.Filled.Cancel, iconColor = WebRedLight)
        }

        // ── Filtros ───────────────────────────────────────────────────────────
        Row(
            modifier              = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            verticalAlignment     = Alignment.CenterVertically
        ) {
            Text("Filtrar:", style = MaterialTheme.typography.labelLarge.copy(color = WebTextSecondary))
            FiltroChip(label = "Todos",      selected = filtroCalidad == null,                 onClick = { filtroCalidad = null })
            FiltroChip(label = "Aprobados",  selected = filtroCalidad == EstadoAcopio.APROBADO,  onClick = { filtroCalidad = EstadoAcopio.APROBADO }, color = WebGreen)
            FiltroChip(label = "Observados", selected = filtroCalidad == EstadoAcopio.OBSERVADO, onClick = { filtroCalidad = EstadoAcopio.OBSERVADO }, color = WebYellow)
            FiltroChip(label = "Rechazados", selected = filtroCalidad == EstadoAcopio.RECHAZADO, onClick = { filtroCalidad = EstadoAcopio.RECHAZADO }, color = WebRedLight)
        }

        // ── Tabla completa ────────────────────────────────────────────────────
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
                verticalArrangement = Arrangement.spacedBy(0.dp)
            ) {
                // Cabecera
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(WebSurface2, RoundedCornerShape(8.dp))
                        .padding(horizontal = 16.dp, vertical = 10.dp)
                ) {
                    listOf("#" to 0.4f, "Hora" to 0.7f, "Ganadero" to 2f, "Ruta" to 1.5f, "Litros" to 0.8f, "Calidad" to 1f, "Sector GPS" to 1.3f, "Acciones" to 0.7f).forEach { (h, w) ->
                        Text(h, modifier = Modifier.weight(w), style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary, fontWeight = FontWeight.Bold))
                    }
                }

                acopios.forEachIndexed { index, acopio ->
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("${index + 1}", modifier = Modifier.weight(0.4f), style = MaterialTheme.typography.bodyMedium.copy(color = WebTextDisabled))
                        Text(acopio.hora, modifier = Modifier.weight(0.7f), style = MaterialTheme.typography.bodyMedium)
                        Text(acopio.ganadero, modifier = Modifier.weight(2f), style = MaterialTheme.typography.bodyMedium.copy(color = WebTextPrimary, fontWeight = FontWeight.SemiBold))
                        Text(acopio.ruta, modifier = Modifier.weight(1.5f), style = MaterialTheme.typography.bodyMedium)
                        Text("${acopio.litros} L", modifier = Modifier.weight(0.8f), style = MaterialTheme.typography.bodyMedium.copy(color = WebGreen, fontWeight = FontWeight.SemiBold))
                        Box(modifier = Modifier.weight(1f)) { CalidadBadgeWeb(acopio.calidad) }
                        Row(modifier = Modifier.weight(1.3f), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                            Icon(Icons.Filled.LocationOn, null, tint = WebTextSecondary, modifier = Modifier.size(13.dp))
                            Text(acopio.sector, style = MaterialTheme.typography.bodyMedium)
                        }
                        Row(modifier = Modifier.weight(0.7f), horizontalArrangement = Arrangement.spacedBy(2.dp)) {
                            IconButton(onClick = {}, modifier = Modifier.size(28.dp)) {
                                Icon(Icons.Filled.Visibility, null, tint = WebTextSecondary, modifier = Modifier.size(14.dp))
                            }
                            IconButton(onClick = {}, modifier = Modifier.size(28.dp)) {
                                Icon(Icons.Filled.Print, null, tint = WebTextSecondary, modifier = Modifier.size(14.dp))
                            }
                        }
                    }
                    if (index < acopios.lastIndex) HorizontalDivider(color = WebBorder.copy(alpha = 0.4f))
                }
            }
        }
    }
}

@Composable
private fun FiltroChip(label: String, selected: Boolean, onClick: () -> Unit, color: Color = WebGreen) {
    Surface(
        shape  = RoundedCornerShape(20.dp),
        color  = if (selected) color.copy(alpha = 0.15f) else WebSurface2,
        onClick = onClick
    ) {
        Text(
            text     = label,
            modifier = Modifier
                .border(1.dp, if (selected) color else WebBorder, RoundedCornerShape(20.dp))
                .padding(horizontal = 14.dp, vertical = 6.dp),
            style    = MaterialTheme.typography.labelSmall.copy(
                color      = if (selected) color else WebTextSecondary,
                fontWeight = if (selected) FontWeight.Bold else FontWeight.Normal
            )
        )
    }
}
