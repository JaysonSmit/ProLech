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
import com.example.myapplicationvacas.web.data.Reunion
import com.example.myapplicationvacas.web.data.WebMockData
import com.example.myapplicationvacas.web.theme.*

@Composable
fun ReunionesWebScreen() {
    var showForm by remember { mutableStateOf(false) }

    Row(
        modifier = Modifier
            .fillMaxSize()
            .background(WebBackground)
            .padding(24.dp),
        horizontalArrangement = Arrangement.spacedBy(20.dp)
    ) {
        // ── Panel izquierdo: Calendario + lista ───────────────────────────────
        Column(
            modifier            = Modifier.weight(1.2f).fillMaxHeight().verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            Row(
                modifier              = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment     = Alignment.CenterVertically
            ) {
                Column {
                    Text("Gestión de Reuniones", style = MaterialTheme.typography.headlineMedium.copy(fontWeight = FontWeight.ExtraBold))
                    Text("Municipalidad Distrital de Huata · Puno", style = MaterialTheme.typography.bodyMedium)
                }
                Button(
                    onClick = { showForm = true },
                    shape   = RoundedCornerShape(8.dp),
                    colors  = ButtonDefaults.buttonColors(containerColor = WebGreen)
                ) {
                    Icon(Icons.Filled.Add, null, tint = Color.Black, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("+ Nueva Reunión", style = MaterialTheme.typography.labelLarge.copy(color = Color.Black))
                }
            }

            // Calendario semanal
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
                    Text("Calendario de Actividades Rurales", style = MaterialTheme.typography.titleMedium)
                    Text("Semana del 9 al 15 de Febrero", style = MaterialTheme.typography.bodyMedium)

                    // Días de semana
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                        val dias = listOf("Lunes 09" to null, "Martes 10" to "cap", "Miércoles 11" to null, "Jueves 12" to "ord", "Viernes 13" to null, "Sábado 14" to "ext", "Domingo 15" to null)
                        dias.forEach { (dia, tipo) ->
                            Column(
                                modifier            = Modifier.weight(1f)
                                    .clip(RoundedCornerShape(8.dp))
                                    .background(if (tipo != null) WebSurface2 else Color.Transparent)
                                    .border(if (tipo != null) 1.dp else 0.dp, WebBorder, RoundedCornerShape(8.dp))
                                    .padding(6.dp),
                                horizontalAlignment = Alignment.CenterHorizontally,
                                verticalArrangement = Arrangement.spacedBy(4.dp)
                            ) {
                                Text(dia.take(3), style = MaterialTheme.typography.labelSmall.copy(fontSize = 10.sp))
                                Text(dia.drop(4), style = MaterialTheme.typography.titleMedium.copy(fontWeight = FontWeight.Bold))
                                if (tipo != null) {
                                    val (label, color) = when (tipo) {
                                        "cap"  -> Pair("Capacitación", WebBlue)
                                        "ord"  -> Pair("Ordinaria",    WebGreen)
                                        "ext"  -> Pair("Extraordinaria", WebRedLight)
                                        else   -> Pair("", WebTextSecondary)
                                    }
                                    Surface(shape = RoundedCornerShape(4.dp), color = color.copy(alpha = 0.15f)) {
                                        Text(label, modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp),
                                            style = MaterialTheme.typography.labelSmall.copy(color = color, fontSize = 8.sp, fontWeight = FontWeight.Bold))
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Lista de próximas reuniones
            Text("Próximas Videoconferencias y Eventos", style = MaterialTheme.typography.titleMedium)

            WebMockData.reuniones.forEach { reunion ->
                ReunionCard(reunion = reunion)
            }
        }

        // ── Panel derecho: Formulario nueva reunión ────────────────────────────
        Column(
            modifier            = Modifier.weight(0.9f).fillMaxHeight().verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            Text("Programar Nuevo Reunión", style = MaterialTheme.typography.titleMedium)
            Text("TIPO DE REUNIÓN", style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary))

            // Tipo de reunión
            var tipoReunion by remember { mutableStateOf("Asamblea de coordinación de rutas") }
            OutlinedTextField(
                value         = tipoReunion,
                onValueChange = { tipoReunion = it },
                modifier      = Modifier.fillMaxWidth(),
                shape         = RoundedCornerShape(8.dp),
                colors        = webTextFieldColors()
            )

            // Tipo y Fecha/Hora
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Text("TIPO", style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary))
                    var tipo by remember { mutableStateOf("Capacitación") }
                    OutlinedTextField(value = tipo, onValueChange = { tipo = it },
                        modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(8.dp), singleLine = true,
                        colors = webTextFieldColors())
                }
                Column(modifier = Modifier.weight(1.2f), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Text("FECHA Y HORA", style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary))
                    OutlinedTextField(value = "14/02/2026 — 08:38", onValueChange = {},
                        modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(8.dp), singleLine = true,
                        colors = webTextFieldColors())
                }
            }

            // Participantes
            Text("PARTICIPANTES (MULTI-SELECCIÓN)", style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary))
            Surface(
                modifier = Modifier.fillMaxWidth().border(1.dp, WebBorder, RoundedCornerShape(8.dp)),
                shape    = RoundedCornerShape(8.dp),
                color    = WebSurface2
            ) {
                Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text("Todos los ganaderos (Ruta A-1)", style = MaterialTheme.typography.bodyMedium.copy(color = WebTextPrimary))
                    Text("+ Agregar más participantes", style = MaterialTheme.typography.bodyMedium.copy(color = WebGreen))
                }
            }

            // Agenda
            Text("AGENDA / DETALLES", style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary))
            var agenda by remember { mutableStateOf("Reunirse periódicamente para discutir la calidad y\ncondiciones del período.") }
            OutlinedTextField(
                value         = agenda,
                onValueChange = { agenda = it },
                modifier      = Modifier.fillMaxWidth().height(90.dp),
                shape         = RoundedCornerShape(8.dp),
                colors        = webTextFieldColors()
            )

            // Habilitar videollamada
            Row(
                modifier              = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment     = Alignment.CenterVertically
            ) {
                Column {
                    Text("Habilitar Videollamada", style = MaterialTheme.typography.bodyMedium.copy(color = WebTextPrimary))
                    Text("Integración con plataforma externa", style = MaterialTheme.typography.labelSmall)
                }
                var videoEnabled by remember { mutableStateOf(true) }
                Switch(
                    checked         = videoEnabled,
                    onCheckedChange = { videoEnabled = it },
                    colors          = SwitchDefaults.colors(checkedThumbColor = WebGreen, checkedTrackColor = WebGreenBg)
                )
            }

            Button(
                onClick  = {},
                modifier = Modifier.fillMaxWidth().height(52.dp),
                shape    = RoundedCornerShape(8.dp),
                colors   = ButtonDefaults.buttonColors(containerColor = WebGreen)
            ) {
                Text("Confirmar Reunión", style = MaterialTheme.typography.titleMedium.copy(color = Color.Black, fontWeight = FontWeight.Bold))
            }
        }
    }
}

@Composable
private fun ReunionCard(reunion: Reunion) {
    val borderColor = when {
        reunion.esExtraordinaria -> WebRedLight
        reunion.tieneVideoconferencia -> WebBlue
        else -> WebBorder
    }
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape    = RoundedCornerShape(12.dp),
        color    = WebCard
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .border(1.dp, borderColor, RoundedCornerShape(12.dp))
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            Row(
                modifier              = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment     = Alignment.Top
            ) {
                Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    if (reunion.esExtraordinaria) {
                        Surface(shape = RoundedCornerShape(4.dp), color = WebRedBg) {
                            Text("Extraordinaria", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                style = MaterialTheme.typography.labelSmall.copy(color = WebRedLight, fontWeight = FontWeight.Bold, fontSize = 9.sp))
                        }
                    }
                    Text(reunion.titulo, style = MaterialTheme.typography.labelLarge.copy(color = WebTextPrimary, fontWeight = FontWeight.SemiBold))
                    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                        Icon(Icons.Filled.CalendarToday, null, tint = WebTextSecondary, modifier = Modifier.size(12.dp))
                        Text("${reunion.fecha} · ${reunion.hora}", style = MaterialTheme.typography.bodyMedium)
                    }
                    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                        Icon(Icons.Filled.LocationOn, null, tint = WebTextSecondary, modifier = Modifier.size(12.dp))
                        Text(reunion.lugar, style = MaterialTheme.typography.bodyMedium)
                    }
                }
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    repeat(minOf(reunion.participantes, 3)) {
                        Box(
                            modifier         = Modifier.size(24.dp).clip(CircleShape)
                                .background(listOf(WebGreen, WebBlue, WebOrange)[it].copy(alpha = 0.7f)),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(listOf("A","B","C")[it], style = MaterialTheme.typography.labelSmall.copy(color = Color.White, fontSize = 9.sp, fontWeight = FontWeight.Bold))
                        }
                    }
                    if (reunion.participantes > 3) {
                        Text("+${reunion.participantes - 3}", style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary))
                    }
                }
            }

            if (reunion.tieneVideoconferencia) {
                Button(
                    onClick = {},
                    shape   = RoundedCornerShape(8.dp),
                    colors  = ButtonDefaults.buttonColors(containerColor = WebBlue),
                    contentPadding = PaddingValues(horizontal = 14.dp, vertical = 8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Icon(Icons.Filled.VideoCall, null, tint = Color.White, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("UNIRSE A VIDEOCONFERENCIA", style = MaterialTheme.typography.labelLarge.copy(color = Color.White, fontSize = 11.sp))
                }
            }
        }
    }
}

@Composable
private fun webTextFieldColors() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor        = WebGreen,
    unfocusedBorderColor      = WebBorder,
    focusedLabelColor         = WebGreen,
    unfocusedLabelColor       = WebTextSecondary,
    focusedTextColor          = WebTextPrimary,
    unfocusedTextColor        = WebTextPrimary,
    focusedContainerColor     = WebSurface2,
    unfocusedContainerColor   = WebSurface2,
    focusedPlaceholderColor   = WebTextDisabled,
    unfocusedPlaceholderColor = WebTextDisabled
)
