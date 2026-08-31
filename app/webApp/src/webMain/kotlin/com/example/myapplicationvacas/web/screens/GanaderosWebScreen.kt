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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.myapplicationvacas.web.data.GanaderoWeb
import com.example.myapplicationvacas.web.data.WebMockData
import com.example.myapplicationvacas.web.theme.*

@Composable
fun GanaderosWebScreen() {
    var busqueda by remember { mutableStateOf("") }
    val ganaderos = WebMockData.ganaderos.filter {
        busqueda.isBlank() || it.nombre.contains(busqueda, ignoreCase = true) || it.dni.contains(busqueda)
    }

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
            Column {
                Text("Ganaderos", style = MaterialTheme.typography.headlineMedium.copy(fontWeight = FontWeight.ExtraBold))
                Text("${WebMockData.ganaderos.size} productores activos registrados", style = MaterialTheme.typography.bodyMedium)
            }
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(
                    value         = busqueda,
                    onValueChange = { busqueda = it },
                    placeholder   = { Text("Buscar por nombre o DNI...") },
                    leadingIcon   = { Icon(Icons.Filled.Search, null, tint = WebTextSecondary, modifier = Modifier.size(16.dp)) },
                    modifier      = Modifier.width(240.dp).height(44.dp),
                    singleLine    = true,
                    shape         = RoundedCornerShape(8.dp),
                    colors        = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = WebGreen, unfocusedBorderColor = WebBorder,
                        focusedTextColor = WebTextPrimary, unfocusedTextColor = WebTextPrimary,
                        focusedContainerColor = WebSurface2, unfocusedContainerColor = WebSurface2,
                        focusedPlaceholderColor = WebTextSecondary, unfocusedPlaceholderColor = WebTextSecondary
                    )
                )
                Button(onClick = {}, shape = RoundedCornerShape(8.dp), colors = ButtonDefaults.buttonColors(containerColor = WebGreen)) {
                    Icon(Icons.Filled.Add, null, tint = Color.Black, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("Nuevo Ganadero", style = MaterialTheme.typography.labelLarge.copy(color = Color.Black))
                }
            }
        }

        // Tabla
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
                    listOf("Ganadero" to 2.5f, "DNI" to 1.2f, "Sector" to 1.5f, "Litros Mes" to 1f, "Estado" to 0.8f, "Código QR" to 0.8f, "Acciones" to 0.8f).forEach { (h, w) ->
                        Text(h, modifier = Modifier.weight(w), style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary, fontWeight = FontWeight.Bold))
                    }
                }

                ganaderos.forEachIndexed { index, ganadero ->
                    GanaderoRow(ganadero = ganadero)
                    if (index < ganaderos.lastIndex) HorizontalDivider(color = WebBorder.copy(alpha = 0.4f))
                }
            }
        }
    }
}

@Composable
private fun GanaderoRow(ganadero: GanaderoWeb) {
    Row(
        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        // Avatar + nombre
        Row(modifier = Modifier.weight(2.5f), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            Box(
                modifier         = Modifier.size(34.dp).clip(CircleShape).background(WebGreenBg),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    ganadero.nombre.split(" ").take(2).map { it.firstOrNull() ?: "" }.joinToString(""),
                    style = MaterialTheme.typography.labelSmall.copy(color = WebGreen, fontWeight = FontWeight.Bold, fontSize = 11.sp)
                )
            }
            Column {
                Text(ganadero.nombre, style = MaterialTheme.typography.bodyMedium.copy(color = WebTextPrimary, fontWeight = FontWeight.SemiBold), maxLines = 1)
                Text(ganadero.codigo, style = MaterialTheme.typography.labelSmall.copy(fontSize = 10.sp))
            }
        }
        Text(ganadero.dni, modifier = Modifier.weight(1.2f), style = MaterialTheme.typography.bodyMedium)
        Row(modifier = Modifier.weight(1.5f), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(4.dp)) {
            Icon(Icons.Filled.LocationOn, null, tint = WebTextSecondary, modifier = Modifier.size(12.dp))
            Text(ganadero.sector, style = MaterialTheme.typography.bodyMedium)
        }
        Text("%.0f L".format(ganadero.litrosMes), modifier = Modifier.weight(1f),
            style = MaterialTheme.typography.bodyMedium.copy(color = WebGreen, fontWeight = FontWeight.SemiBold))
        Box(modifier = Modifier.weight(0.8f)) {
            Surface(shape = RoundedCornerShape(6.dp), color = WebGreenBg) {
                Text(ganadero.estado, modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                    style = MaterialTheme.typography.labelSmall.copy(color = WebGreen, fontWeight = FontWeight.Bold))
            }
        }
        // QR mock
        Box(modifier = Modifier.weight(0.8f)) {
            Surface(shape = RoundedCornerShape(6.dp), color = WebSurface2, onClick = {}) {
                Row(modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    Icon(Icons.Filled.QrCode, null, tint = WebTextSecondary, modifier = Modifier.size(14.dp))
                    Text("Ver QR", style = MaterialTheme.typography.labelSmall)
                }
            }
        }
        Row(modifier = Modifier.weight(0.8f), horizontalArrangement = Arrangement.spacedBy(4.dp)) {
            IconButton(onClick = {}, modifier = Modifier.size(28.dp)) {
                Icon(Icons.Filled.Visibility, null, tint = WebTextSecondary, modifier = Modifier.size(14.dp))
            }
            IconButton(onClick = {}, modifier = Modifier.size(28.dp)) {
                Icon(Icons.Filled.Edit, null, tint = WebTextSecondary, modifier = Modifier.size(14.dp))
            }
        }
    }
}
