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
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.myapplicationvacas.web.theme.*

@Composable
fun ConfiguracionWebScreen() {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(WebBackground)
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(20.dp)
    ) {
        Text("Configuración del Sistema", style = MaterialTheme.typography.headlineMedium.copy(fontWeight = FontWeight.ExtraBold))
        Text("Gestión de usuarios, parámetros de calidad y configuración general", style = MaterialTheme.typography.bodyMedium)

        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
            // Gestión de usuarios
            ConfigSection(
                modifier = Modifier.weight(1f),
                title    = "Gestión de Usuarios",
                icon     = Icons.Filled.People,
                items    = listOf(
                    "Agregar conductores y choferes",
                    "Gestionar roles y permisos",
                    "Panel de acceso por usuario",
                    "Historial de actividad"
                )
            )
            // Parámetros de calidad
            ConfigSection(
                modifier = Modifier.weight(1f),
                title    = "Parámetros de Calidad",
                icon     = Icons.Filled.Science,
                items    = listOf(
                    "Límite % Agua máximo (actual: 5.0%)",
                    "Rango de Acidez pH (4.0 – 5.5)",
                    "Temperatura máxima aceptada: 6°C",
                    "Parámetros Lactoscan"
                )
            )
            // Rutas y camiones
            ConfigSection(
                modifier = Modifier.weight(1f),
                title    = "Rutas y Camiones",
                icon     = Icons.Filled.LocalShipping,
                items    = listOf(
                    "Gestionar rutas activas",
                    "Flota de vehículos",
                    "Asignación conductor-ruta",
                    "Mantenimiento programado"
                )
            )
        }

        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
            // Parámetros globales de calidad con toggles
            Surface(modifier = Modifier.weight(1f), shape = RoundedCornerShape(12.dp), color = WebCard) {
                Column(
                    modifier = Modifier.fillMaxWidth().border(1.dp, WebBorder, RoundedCornerShape(12.dp)).padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        Icon(Icons.Filled.Tune, null, tint = WebGreen, modifier = Modifier.size(18.dp))
                        Text("Parámetros Globales de Calidad", style = MaterialTheme.typography.titleMedium)
                    }

                    var fotoObligatoria by remember { mutableStateOf(true) }
                    var gpsAutomatico   by remember { mutableStateOf(true) }
                    var notifRechazo    by remember { mutableStateOf(true) }
                    var sincAutomatic   by remember { mutableStateOf(false) }

                    ConfigToggle("Foto obligatoria en rechazo", fotoObligatoria) { fotoObligatoria = it }
                    ConfigToggle("GPS automático en acopio",    gpsAutomatico)   { gpsAutomatico = it }
                    ConfigToggle("Notificación en rechazo",     notifRechazo)    { notifRechazo = it }
                    ConfigToggle("Sincronización automática",   sincAutomatic)   { sincAutomatic = it }

                    HorizontalDivider(color = WebBorder)

                    // Límites numéricos
                    listOf("% Agua Máximo" to "5.0", "pH Mínimo" to "4.0", "pH Máximo" to "5.5", "Temp. Máxima (°C)" to "6.0").forEach { (label, valor) ->
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Text(label, style = MaterialTheme.typography.bodyMedium)
                            Surface(shape = RoundedCornerShape(6.dp), color = WebSurface2) {
                                Text(valor, modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp),
                                    style = MaterialTheme.typography.labelLarge.copy(color = WebGreen, fontWeight = FontWeight.Bold))
                            }
                        }
                    }

                    Button(onClick = {}, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(8.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = WebGreen)) {
                        Text("Guardar Cambios", style = MaterialTheme.typography.labelLarge.copy(color = Color.Black, fontWeight = FontWeight.Bold))
                    }
                }
            }

            // Info del sistema
            Surface(modifier = Modifier.weight(1f), shape = RoundedCornerShape(12.dp), color = WebCard) {
                Column(
                    modifier = Modifier.fillMaxWidth().border(1.dp, WebBorder, RoundedCornerShape(12.dp)).padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        Icon(Icons.Filled.Info, null, tint = WebBlue, modifier = Modifier.size(18.dp))
                        Text("Información del Sistema", style = MaterialTheme.typography.titleMedium)
                    }
                    listOf(
                        "Versión" to "ProLech v1.0.0",
                        "Módulo"  to "Web Admin",
                        "Servidor" to "Central Online · Huata",
                        "API"     to "REST + JSON",
                        "Sincronización" to "Offline-First",
                        "Base de datos" to "PostgreSQL / Local SQLite",
                        "Última actualización" to "07/02/2026"
                    ).forEach { (label, valor) ->
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text(label, style = MaterialTheme.typography.bodyMedium)
                            Text(valor, style = MaterialTheme.typography.bodyMedium.copy(color = WebTextPrimary, fontWeight = FontWeight.SemiBold))
                        }
                        HorizontalDivider(color = WebBorder.copy(alpha = 0.3f))
                    }
                }
            }
        }
    }
}

@Composable
private fun ConfigSection(modifier: Modifier, title: String, icon: ImageVector, items: List<String>) {
    Surface(modifier = modifier, shape = RoundedCornerShape(12.dp), color = WebCard) {
        Column(
            modifier = Modifier.fillMaxWidth().border(1.dp, WebBorder, RoundedCornerShape(12.dp)).padding(20.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Icon(icon, null, tint = WebGreen, modifier = Modifier.size(18.dp))
                Text(title, style = MaterialTheme.typography.titleMedium)
            }
            HorizontalDivider(color = WebBorder)
            items.forEach { item ->
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Icon(Icons.Filled.ChevronRight, null, tint = WebTextSecondary, modifier = Modifier.size(14.dp))
                    Text(item, style = MaterialTheme.typography.bodyMedium)
                }
            }
            OutlinedButton(onClick = {}, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(8.dp),
                colors = ButtonDefaults.outlinedButtonColors(contentColor = WebGreen),
                border = ButtonDefaults.outlinedButtonBorder.copy(width = 1.dp)
            ) {
                Text("Gestionar", style = MaterialTheme.typography.labelLarge)
            }
        }
    }
}

@Composable
private fun ConfigToggle(label: String, checked: Boolean, onCheckedChange: (Boolean) -> Unit) {
    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
        Text(label, style = MaterialTheme.typography.bodyMedium)
        Switch(checked = checked, onCheckedChange = onCheckedChange,
            colors = SwitchDefaults.colors(checkedThumbColor = WebGreen, checkedTrackColor = WebGreenBg))
    }
}
