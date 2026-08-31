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
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.myapplicationvacas.web.data.EstadoInventario
import com.example.myapplicationvacas.web.data.ProductoInventario
import com.example.myapplicationvacas.web.data.WebMockData
import com.example.myapplicationvacas.web.theme.*

@Composable
fun InventarioWebScreen() {
    var tabSelected by remember { mutableStateOf(0) }
    val tabs = listOf("Quesos", "Yogurt", "Mantequilla", "Otros")
    val productos = WebMockData.inventario

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(WebBackground)
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(20.dp)
    ) {

        Row(
            modifier              = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment     = Alignment.CenterVertically
        ) {
            Text("Inventario de Producción", style = MaterialTheme.typography.headlineMedium.copy(fontWeight = FontWeight.ExtraBold))
            Button(
                onClick = {},
                shape   = RoundedCornerShape(8.dp),
                colors  = ButtonDefaults.buttonColors(containerColor = WebGreen)
            ) {
                Icon(Icons.Filled.Add, null, tint = Color.Black, modifier = Modifier.size(16.dp))
                Spacer(Modifier.width(6.dp))
                Text("+ Nueva Lote", style = MaterialTheme.typography.labelLarge.copy(color = Color.Black))
            }
        }

        // Tabs de categorías
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            tabs.forEachIndexed { index, tab ->
                val sel = tabSelected == index
                Surface(
                    shape  = RoundedCornerShape(8.dp),
                    color  = if (sel) WebGreen else WebSurface2,
                    onClick = { tabSelected = index }
                ) {
                    Text(
                        text     = tab,
                        modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp),
                        style    = MaterialTheme.typography.labelLarge.copy(
                            color      = if (sel) Color.Black else WebTextSecondary,
                            fontWeight = if (sel) FontWeight.Bold else FontWeight.Normal
                        )
                    )
                }
            }
        }

        // Grid de productos
        Row(
            modifier              = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            productos.forEach { producto ->
                ProductoCard(modifier = Modifier.weight(1f), producto = producto)
            }
        }

        // Tabla producción en curso
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
                Text("Resumen de Producción en Curso", style = MaterialTheme.typography.titleMedium)

                // Cabecera
                Row(modifier = Modifier.fillMaxWidth().background(WebSurface2, RoundedCornerShape(8.dp)).padding(horizontal = 12.dp, vertical = 8.dp)) {
                    listOf("Producto" to 2f, "Lote" to 1.2f, "Última Unidad" to 1.2f, "Cantidad Producida" to 1.5f, "Fecha" to 1f, "Responsable" to 1.5f, "Estado" to 1f).forEach { (h, w) ->
                        Text(h, modifier = Modifier.weight(w), style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary, fontWeight = FontWeight.Bold))
                    }
                }

                WebMockData.produccionEnCurso.forEach { (nombre, lote, datos) ->
                    Row(modifier = Modifier.fillMaxWidth().padding(horizontal = 12.dp, vertical = 8.dp), verticalAlignment = Alignment.CenterVertically) {
                        Text(nombre,           modifier = Modifier.weight(2f),   style = MaterialTheme.typography.bodyMedium.copy(color = WebTextPrimary))
                        Text(lote,             modifier = Modifier.weight(1.2f), style = MaterialTheme.typography.bodyMedium)
                        Text(datos["Entrada"] ?: "", modifier = Modifier.weight(1.2f), style = MaterialTheme.typography.bodyMedium)
                        Text(datos["Producido"] ?: "", modifier = Modifier.weight(1.5f),
                            style = MaterialTheme.typography.bodyMedium.copy(color = WebGreen, fontWeight = FontWeight.SemiBold))
                        Text(datos["Fecha"] ?: "", modifier = Modifier.weight(1f), style = MaterialTheme.typography.bodyMedium)
                        Text(datos["Responsable"] ?: "", modifier = Modifier.weight(1.5f), style = MaterialTheme.typography.bodyMedium)
                        val estado = datos["Estado"] ?: ""
                        Surface(
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(6.dp),
                            color = if (estado == "Completado") WebGreenBg else WebYellowBg
                        ) {
                            Text(
                                estado,
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                                style    = MaterialTheme.typography.labelSmall.copy(
                                    color      = if (estado == "Completado") WebGreen else WebYellow,
                                    fontWeight = FontWeight.Bold
                                )
                            )
                        }
                    }
                    HorizontalDivider(color = WebBorder.copy(alpha = 0.5f))
                }
            }
        }
    }
}

@Composable
private fun ProductoCard(modifier: Modifier, producto: ProductoInventario) {
    val (badgeText, badgeColor, badgeBg) = when (producto.estado) {
        EstadoInventario.EN_STOCK  -> Triple("In Stock",   WebGreen,    WebGreenBg)
        EstadoInventario.AGOTADO   -> Triple("Agotado",    WebRedLight, WebRedBg)
        EstadoInventario.EN_PROCESO -> Triple("En Proceso", WebYellow,  WebYellowBg)
    }

    Surface(
        modifier = modifier,
        shape    = RoundedCornerShape(12.dp),
        color    = WebCard
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .border(1.dp, WebBorder, RoundedCornerShape(12.dp))
        ) {
            // Imagen mock del producto
            Box(
                modifier         = Modifier
                    .fillMaxWidth()
                    .height(120.dp)
                    .background(WebSurface2),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = when {
                        producto.nombre.contains("Queso")        -> Icons.Filled.SetMeal
                        producto.nombre.contains("Yogurt")       -> Icons.Filled.LocalCafe
                        producto.nombre.contains("Mantequilla")  -> Icons.Filled.Fastfood
                        else -> Icons.Filled.Inventory
                    },
                    contentDescription = null,
                    tint     = WebTextDisabled,
                    modifier = Modifier.size(48.dp)
                )
            }

            Column(
                modifier            = Modifier.padding(14.dp),
                verticalArrangement = Arrangement.spacedBy(6.dp)
            ) {
                Row(
                    modifier              = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment     = Alignment.Top
                ) {
                    Text(
                        text  = producto.nombre,
                        style = MaterialTheme.typography.labelLarge.copy(color = WebTextPrimary, fontWeight = FontWeight.SemiBold),
                        modifier = Modifier.weight(1f)
                    )
                    Surface(shape = RoundedCornerShape(6.dp), color = badgeBg) {
                        Text(badgeText, modifier = Modifier.padding(horizontal = 6.dp, vertical = 3.dp),
                            style = MaterialTheme.typography.labelSmall.copy(color = badgeColor, fontWeight = FontWeight.Bold))
                    }
                }

                Text(
                    text  = "${producto.cantidad} ${producto.unidad}",
                    style = MaterialTheme.typography.headlineMedium.copy(
                        fontWeight = FontWeight.ExtraBold,
                        color      = if (producto.estado == EstadoInventario.AGOTADO) WebTextDisabled else WebTextPrimary
                    )
                )

                HorizontalDivider(color = WebBorder)

                InfoRow(label = "Fecha Prod.", value = producto.fechaProduccion)
                InfoRow(label = "Fecha Venc.", value = producto.fechaVencimiento)
                InfoRow(label = "Responsable", value = producto.responsable)
            }
        }
    }
}

@Composable
private fun InfoRow(label: String, value: String) {
    Row(
        modifier              = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(label, style = MaterialTheme.typography.labelSmall)
        Text(value, style = MaterialTheme.typography.labelSmall.copy(color = WebTextPrimary))
    }
}
