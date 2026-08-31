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
import androidx.compose.ui.unit.sp
import com.example.myapplicationvacas.web.components.KpiCard
import com.example.myapplicationvacas.web.data.LiquidacionGanadero
import com.example.myapplicationvacas.web.data.WebMockData
import com.example.myapplicationvacas.web.theme.*

@Composable
fun LiquidacionWebScreen() {
    var periodo by remember { mutableStateOf(0) } // 0 = Quincenal, 1 = Mensual
    val datos = WebMockData.liquidacion
    val totalPagar   = datos.sumOf { it.montoLiquidar }
    val totalLitros  = datos.sumOf { it.totalLitros }
    val precioPromedio = if (totalLitros > 0) totalPagar / totalLitros else 0.0

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
                Text("Liquidación de Pagos", style = MaterialTheme.typography.headlineMedium.copy(fontWeight = FontWeight.ExtraBold))
                Text("Municipalidad Distrital de Huata · Cusco", style = MaterialTheme.typography.bodyMedium)
            }
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                // Tabs Quincenal/Mensual
                listOf("Quincenal", "Mensual").forEachIndexed { i, label ->
                    Surface(
                        shape  = RoundedCornerShape(8.dp),
                        color  = if (periodo == i) WebGreen else WebSurface2,
                        onClick = { periodo = i }
                    ) {
                        Text(
                            label,
                            modifier = Modifier.padding(horizontal = 14.dp, vertical = 7.dp),
                            style = MaterialTheme.typography.labelLarge.copy(
                                color      = if (periodo == i) Color.Black else WebTextSecondary,
                                fontWeight = if (periodo == i) FontWeight.Bold else FontWeight.Normal
                            )
                        )
                    }
                }
                // Rango de fechas
                Surface(shape = RoundedCornerShape(8.dp), color = WebSurface2) {
                    Row(
                        modifier = Modifier.padding(horizontal = 12.dp, vertical = 7.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        Icon(Icons.Filled.DateRange, null, tint = WebTextSecondary, modifier = Modifier.size(14.dp))
                        Text("01/02/2026 — 15/02/2026", style = MaterialTheme.typography.bodyMedium)
                    }
                }
            }
        }

        // ── Botones de exportación ────────────────────────────────────────────
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            OutlinedButton(
                onClick = {},
                shape   = RoundedCornerShape(8.dp),
                colors  = ButtonDefaults.outlinedButtonColors(contentColor = WebTextSecondary),
                border  = ButtonDefaults.outlinedButtonBorder.copy(width = 1.dp)
            ) {
                Icon(Icons.Filled.PictureAsPdf, null, modifier = Modifier.size(15.dp))
                Spacer(Modifier.width(6.dp))
                Text("Exportar PDF", style = MaterialTheme.typography.labelLarge)
            }
            OutlinedButton(
                onClick = {},
                shape   = RoundedCornerShape(8.dp),
                colors  = ButtonDefaults.outlinedButtonColors(contentColor = WebTextSecondary),
                border  = ButtonDefaults.outlinedButtonBorder.copy(width = 1.dp)
            ) {
                Icon(Icons.Filled.GridOn, null, modifier = Modifier.size(15.dp))
                Spacer(Modifier.width(6.dp))
                Text("Exportar Excel", style = MaterialTheme.typography.labelLarge)
            }
            Spacer(Modifier.weight(1f))
            Button(
                onClick = {},
                shape   = RoundedCornerShape(8.dp),
                colors  = ButtonDefaults.buttonColors(containerColor = WebGreen)
            ) {
                Icon(Icons.Filled.Payments, null, tint = Color.Black, modifier = Modifier.size(15.dp))
                Spacer(Modifier.width(6.dp))
                Text("Generar Liquidación", style = MaterialTheme.typography.labelLarge.copy(color = Color.Black, fontWeight = FontWeight.Bold))
            }
        }

        // ── KPIs ──────────────────────────────────────────────────────────────
        Row(
            modifier              = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            KpiCard(
                modifier       = Modifier.weight(1f),
                label          = "Total a Pagar",
                value          = "S/. %.2f".format(totalPagar),
                change         = "−S/. 1,388 vs ant.",
                changePositive = false,
                icon           = Icons.Filled.AttachMoney,
                iconColor      = WebGreen
            )
            KpiCard(
                modifier       = Modifier.weight(1f),
                label          = "Ganaderos a Liquidar",
                value          = "${datos.count { it.estado == "Pendiente" }}",
                change         = "Período Activo · ${datos.size} total",
                changePositive = true,
                icon           = Icons.Filled.People,
                iconColor      = WebOrange
            )
            KpiCard(
                modifier       = Modifier.weight(1f),
                label          = "Litros del Período",
                value          = "%.0f L".format(totalLitros),
                change         = "+%,1.0f vs ant.".format(3011.0),
                changePositive = true,
                icon           = Icons.Filled.Water,
                iconColor      = WebBlue
            )
            KpiCard(
                modifier       = Modifier.weight(1f),
                label          = "Precio Promedio/Litro",
                value          = "S/. %.2f".format(precioPromedio),
                change         = "Estable vs ant.",
                changePositive = true,
                icon           = Icons.Filled.TrendingUp,
                iconColor      = WebGreen
            )
        }

        // ── Tabla de pagos ────────────────────────────────────────────────────
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
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text("Pagos por Ganadero en este Período", style = MaterialTheme.typography.titleMedium)
                Text("Liquidación para el período actual de entregas.", style = MaterialTheme.typography.bodyMedium)

                // Cabecera
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(WebSurface2, RoundedCornerShape(8.dp))
                        .padding(horizontal = 16.dp, vertical = 10.dp)
                ) {
                    listOf("Ganad." to 2f, "DNI" to 1.2f, "Total Litros" to 1.2f, "Calidad Prom." to 1.2f, "Bonificación" to 1.2f, "Deducción" to 1.2f, "Neto a Pagar" to 1.2f, "Estado" to 1f, "Acciones" to 0.8f).forEach { (h, w) ->
                        Text(h, modifier = Modifier.weight(w), style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary, fontWeight = FontWeight.Bold, fontSize = 11.sp))
                    }
                }

                datos.forEach { item ->
                    LiquidacionRow(item = item)
                    HorizontalDivider(color = WebBorder.copy(alpha = 0.4f))
                }
            }
        }
    }
}

@Composable
private fun LiquidacionRow(item: LiquidacionGanadero) {
    val isPendiente = item.estado == "Pendiente"
    Row(
        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(2f)) {
            Text(item.ganadero, style = MaterialTheme.typography.bodyMedium.copy(color = WebTextPrimary, fontWeight = FontWeight.SemiBold), maxLines = 1)
        }
        Text(item.codigo, modifier = Modifier.weight(1.2f), style = MaterialTheme.typography.bodyMedium)
        Text("%.0f L".format(item.totalLitros), modifier = Modifier.weight(1.2f), style = MaterialTheme.typography.bodyMedium)
        Text("S/. %.2f".format(item.precioPorLitro), modifier = Modifier.weight(1.2f), style = MaterialTheme.typography.bodyMedium)
        Text(
            text     = if (item.bonificacion > 0) "S/. %.2f".format(item.bonificacion) else "S/. 0.00",
            modifier = Modifier.weight(1.2f),
            style    = MaterialTheme.typography.bodyMedium.copy(color = if (item.bonificacion > 0) WebGreen else WebTextSecondary)
        )
        Text(
            text     = if (item.descuento > 0) "−S/. %.2f".format(item.descuento) else "S/. 0.00",
            modifier = Modifier.weight(1.2f),
            style    = MaterialTheme.typography.bodyMedium.copy(color = if (item.descuento > 0) WebRedLight else WebTextSecondary)
        )
        Text(
            text     = "S/. %.2f".format(item.montoLiquidar),
            modifier = Modifier.weight(1.2f),
            style    = MaterialTheme.typography.bodyMedium.copy(color = WebGreen, fontWeight = FontWeight.Bold)
        )
        Box(modifier = Modifier.weight(1f)) {
            Surface(
                shape = RoundedCornerShape(6.dp),
                color = if (isPendiente) WebYellowBg else WebGreenBg
            ) {
                Text(
                    text     = item.estado,
                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                    style    = MaterialTheme.typography.labelSmall.copy(
                        color      = if (isPendiente) WebYellow else WebGreen,
                        fontWeight = FontWeight.Bold
                    )
                )
            }
        }
        Box(modifier = Modifier.weight(0.8f)) {
            if (isPendiente) {
                Button(
                    onClick = {},
                    shape   = RoundedCornerShape(6.dp),
                    colors  = ButtonDefaults.buttonColors(containerColor = WebGreen),
                    contentPadding = PaddingValues(horizontal = 10.dp, vertical = 5.dp)
                ) {
                    Text("Pagar", style = MaterialTheme.typography.labelSmall.copy(color = Color.Black, fontWeight = FontWeight.Bold, fontSize = 11.sp))
                }
            }
        }
    }
}
