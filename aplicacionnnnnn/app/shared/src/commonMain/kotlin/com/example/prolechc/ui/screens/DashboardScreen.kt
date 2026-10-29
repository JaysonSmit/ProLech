package com.example.prolechc.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.data.*
import com.example.prolechc.ui.components.BottomNavRoute
import com.example.prolechc.ui.components.ProLechBottomBar
import com.example.prolechc.ui.theme.*
import com.example.prolechc.viewmodel.ProLechViewModel

@Composable
fun DashboardScreen(
    viewModel: ProLechViewModel,
    onNuevoAcopio: () -> Unit,
    onModoBatch: () -> Unit = {},
    onHistorial: () -> Unit,
    onSincronizar: () -> Unit,
    onMiRuta: () -> Unit,
    onNotificaciones: () -> Unit = {},
    onReuniones: (() -> Unit)? = null,
    notificacionesCount: Int = 0,
    onBottomDashboard: () -> Unit,
    onBottomAcopio: () -> Unit,
    onBottomRuta: () -> Unit,
    onBottomSync: () -> Unit
) {
    val loginState by viewModel.loginState.collectAsStateWithLifecycle()

    Scaffold(
        containerColor = BgPage,
        bottomBar = {
            ProLechBottomBar(
                current     = BottomNavRoute.DASHBOARD,
                onDashboard = onBottomDashboard,
                onAcopio    = onBottomAcopio,
                onRuta      = onBottomRuta,
                onSync      = onBottomSync
            )
        }
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            // ── TopBar ─────────────────────────────────────────────────────
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(SurfaceDark)
                    .padding(horizontal = 16.dp, vertical = 12.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Box(
                            modifier = Modifier
                                .clip(RoundedCornerShape(4.dp))
                                .background(GreenPrimary.copy(alpha = 0.3f))
                                .padding(horizontal = 7.dp, vertical = 3.dp)
                        ) { Text("● GPS Activo", color = GreenAccent, fontSize = 10.sp, fontWeight = FontWeight.Bold) }
                        Spacer(Modifier.width(6.dp))
                        Text("·15.632, -70.104", color = Color.White.copy(alpha = 0.5f), fontSize = 10.sp)
                    }
                }

                // Título + campana con badge
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    Text("ProLech", color = GreenAccent, fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)

                    // ── Campana de notificaciones con badge ─────────────────
                    Box(
                        modifier = Modifier
                            .size(38.dp)
                            .clip(androidx.compose.foundation.shape.RoundedCornerShape(10.dp))
                            .background(Color.White.copy(alpha = 0.10f))
                            .clickable(onClick = onNotificaciones),
                        contentAlignment = Alignment.Center
                    ) {
                        Text("🔔", fontSize = 18.sp)
                        // Badge rojo cuando hay notificaciones no leídas
                        if (notificacionesCount > 0) {
                            Box(
                                modifier = Modifier
                                    .align(Alignment.TopEnd)
                                    .offset(x = 2.dp, y = (-2).dp)
                                    .size(if (notificacionesCount > 9) 18.dp else 14.dp)
                                    .clip(androidx.compose.foundation.shape.CircleShape)
                                    .background(Color(0xFFE53935)),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    if (notificacionesCount > 9) "9+" else notificacionesCount.toString(),
                                    color      = Color.White,
                                    fontSize   = 8.sp,
                                    fontWeight = FontWeight.ExtraBold
                                )
                            }
                        }
                    }
                }
            }

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(horizontal = 16.dp)
            ) {
                Spacer(Modifier.height(16.dp))

                // ── Ruta del día ───────────────────────────────────────────
                Text("RUTA DE HOY", color = TextMuted, fontSize = 10.sp, letterSpacing = 1.sp)
                Spacer(Modifier.height(2.dp))
                Text("Huata Centro – Sectores", color = TextPrimary, fontSize = 22.sp, fontWeight = FontWeight.ExtraBold)
                Text("Sábado, 7 de Febrero de 2026", color = TextSecondary, fontSize = 12.sp)

                Spacer(Modifier.height(14.dp))

                // ── Cisterna ───────────────────────────────────────────────
                Card(
                    modifier  = Modifier.fillMaxWidth(),
                    colors    = CardDefaults.cardColors(containerColor = BgCard),
                    shape     = RoundedCornerShape(12.dp),
                    elevation = CardDefaults.cardElevation(2.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(
                            Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment     = Alignment.CenterVertically
                        ) {
                            Text("Cisterna de Recolección", color = TextSecondary, fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                            Text(
                                "${viewModel.cisternaActual.toInt()}L / ${viewModel.cisternaTotal.toInt()}L",
                                color = GreenPrimary, fontSize = 13.sp, fontWeight = FontWeight.Bold
                            )
                        }
                        Spacer(Modifier.height(8.dp))
                        LinearProgressIndicator(
                            progress   = { viewModel.cisternaProgress },
                            modifier   = Modifier.fillMaxWidth().height(10.dp).clip(RoundedCornerShape(5.dp)),
                            color      = GreenPrimary,
                            trackColor = Divider
                        )
                        Spacer(Modifier.height(6.dp))
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text("Capacidad ${(viewModel.cisternaProgress * 100).toInt()}%", color = TextMuted, fontSize = 11.sp)
                            Text("Huata – Puno", color = TextMuted, fontSize = 11.sp)
                        }
                    }
                }

                Spacer(Modifier.height(18.dp))

                // ── 4 Botones ──────────────────────────────────────────────
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    DashButton(
                        modifier    = Modifier.weight(1f),
                        icon        = "+",
                        label       = "Nuevo Acopio",
                        sub         = "Registrar leche",
                        bg          = GreenBg,
                        border      = GreenBorder,
                        iconColor   = GreenPrimary,
                        onClick     = onNuevoAcopio
                    )
                    DashButton(
                        modifier    = Modifier.weight(1f),
                        icon        = "⚡",
                        label       = "Modo Rápido",
                        sub         = "Varios a la vez",
                        bg          = Color(0xFFFFF3E0),
                        border      = Color(0xFFF57F17),
                        iconColor   = Color(0xFFF57F17),
                        onClick     = onModoBatch
                    )
                }
                Spacer(Modifier.height(10.dp))
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    val totalAcopios by viewModel.acopiosSession.collectAsStateWithLifecycle()
                    DashButton(
                        modifier    = Modifier.weight(1f),
                        icon        = "☰",
                        label       = "Historial Hoy",
                        sub         = "Acopios realizados",
                        bg          = BlueBg,
                        border      = BluePrimary,
                        iconColor   = BluePrimary,
                        onClick     = onHistorial,
                        badge       = if (totalAcopios.isNotEmpty()) "${totalAcopios.size}" else null
                    )
                    if (onReuniones != null) {
                        DashButton(
                            modifier    = Modifier.weight(1f),
                            icon        = "📅",
                            label       = "Reuniones",
                            sub         = "Capacitaciones",
                            bg          = GreenBg,
                            border      = GreenBorder,
                            iconColor   = GreenPrimary,
                            onClick     = onReuniones
                        )
                    }
                }
                Spacer(Modifier.height(10.dp))
                val syncState by viewModel.syncState.collectAsStateWithLifecycle()
                val sessionAcopios by viewModel.acopiosSession.collectAsStateWithLifecycle()
                val pendientesReal = sessionAcopios.count { it.syncEstado == com.example.prolechc.data.EstadoSync.PENDIENTE }

                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    DashButton(
                        modifier    = Modifier.weight(1f),
                        icon        = "↑",
                        label       = "Sincronizar",
                        sub         = "Envía a la central",
                        bg          = AmberBg,
                        border      = AmberPrimary,
                        iconColor   = AmberPrimary,
                        onClick     = onSincronizar,
                        badge       = if (pendientesReal > 0) "$pendientesReal Pend." else null
                    )
                    DashButton(
                        modifier    = Modifier.weight(1f),
                        icon        = "◎",
                        label       = "Mi Ruta",
                        sub         = "Ver productores",
                        bg          = CyanBg,
                        border      = CyanAccent,
                        iconColor   = CyanAccent,
                        onClick     = onMiRuta
                    )
                }

                Spacer(Modifier.height(18.dp))

                // ── Últimos acopios rápidos ────────────────────────────────
                Row(
                    Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment     = Alignment.CenterVertically
                ) {
                    Text("ÚLTIMOS ACOPIOS", color = TextMuted, fontSize = 10.sp, letterSpacing = 1.sp, fontWeight = FontWeight.SemiBold)
                    Text("Ver todos →", color = GreenPrimary, fontSize = 11.sp, fontWeight = FontWeight.SemiBold,
                        modifier = Modifier.clickable(onClick = onHistorial))
                }
                Spacer(Modifier.height(8.dp))

                val ultimosAcopios by viewModel.acopiosSession.collectAsStateWithLifecycle()
                val acopiosMostrar = if (ultimosAcopios.isEmpty()) emptyList() else ultimosAcopios.take(3)

                if (acopiosMostrar.isEmpty()) {
                    Box(
                        modifier = Modifier.fillMaxWidth()
                            .clip(RoundedCornerShape(10.dp))
                            .background(BgCard)
                            .padding(24.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Text("Sin acopios registrados hoy", color = TextMuted, fontSize = 12.sp)
                    }
                } else {
                    acopiosMostrar.forEach { acopio ->
                        val (color, bg, label) = when (acopio.estado) {
                            EstadoAcopio.APROBADO  -> Triple(GreenPrimary, GreenBg,  "APROBADO")
                            EstadoAcopio.RECHAZADO -> Triple(RedPrimary,   RedBg,    "RECHAZADO")
                            EstadoAcopio.OBSERVADO -> Triple(AmberPrimary, AmberBg,  "OBSERVADO")
                        }
                        Card(
                            modifier  = Modifier.fillMaxWidth().padding(vertical = 4.dp),
                            colors    = CardDefaults.cardColors(containerColor = BgCard),
                            shape     = RoundedCornerShape(10.dp),
                            elevation = CardDefaults.cardElevation(1.dp)
                        ) {
                            Row(
                                modifier = Modifier.fillMaxWidth().padding(12.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Box(
                                    modifier = Modifier.size(36.dp).clip(RoundedCornerShape(8.dp)).background(bg),
                                    contentAlignment = Alignment.Center
                                ) { Text(if (acopio.estado == EstadoAcopio.APROBADO) "✓" else if (acopio.estado == EstadoAcopio.RECHAZADO) "✗" else "!",
                                    color = color, fontSize = 16.sp, fontWeight = FontWeight.ExtraBold) }
                                Spacer(Modifier.width(10.dp))
                                Column(Modifier.weight(1f)) {
                                    Text(acopio.ganaderoNombre, color = TextPrimary, fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                                    Row {
                                        Text(acopio.hora, color = TextMuted, fontSize = 10.sp)
                                        if (acopio.dniGanadero.isNotBlank()) {
                                            Text("  ·  DNI: ${acopio.dniGanadero}", color = TextMuted, fontSize = 10.sp)
                                        }
                                    }
                                }
                                Column(horizontalAlignment = Alignment.End) {
                                    Text("${acopio.litros} L", color = GreenPrimary, fontSize = 15.sp, fontWeight = FontWeight.ExtraBold)
                                    Box(Modifier.clip(RoundedCornerShape(4.dp)).background(bg).padding(horizontal = 6.dp, vertical = 2.dp)) {
                                        Text(label, color = color, fontSize = 9.sp, fontWeight = FontWeight.Bold)
                                    }
                                }
                            }
                        }
                    }
                }

                Spacer(Modifier.height(18.dp))

                // ── Info conductor ─────────────────────────────────────────
                Card(
                    modifier  = Modifier.fillMaxWidth(),
                    colors    = CardDefaults.cardColors(containerColor = BgCard),
                    shape     = RoundedCornerShape(12.dp),
                    elevation = CardDefaults.cardElevation(1.dp)
                ) {
                    Row(modifier = Modifier.fillMaxWidth().padding(14.dp)) {
                        Column(Modifier.weight(1f)) {
                            Text("CHOFER", color = TextMuted, fontSize = 9.sp, letterSpacing = 1.sp)
                            Text(loginState.conductorNombre, color = TextPrimary, fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                        }
                        Column(Modifier.weight(1f)) {
                            Text("CAMIÓN", color = TextMuted, fontSize = 9.sp, letterSpacing = 1.sp)
                            Text(loginState.vehiculo.take(22), color = TextSecondary, fontSize = 12.sp)
                        }
                    }
                    Row(modifier = Modifier.fillMaxWidth().padding(start = 14.dp, end = 14.dp, bottom = 14.dp)) {
                        Column(Modifier.weight(1f)) {
                            Text("RUTA", color = TextMuted, fontSize = 9.sp, letterSpacing = 1.sp)
                            Text(loginState.ruta, color = TextSecondary, fontSize = 12.sp)
                        }
                    }
                }

                Spacer(Modifier.height(14.dp))

                // ── Alerta offline ─────────────────────────────────────────
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(10.dp))
                        .background(AmberBg)
                        .border(1.dp, AmberPrimary.copy(alpha = 0.3f), RoundedCornerShape(10.dp))
                        .padding(12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("⚠  ", color = AmberPrimary, fontSize = 14.sp)
                    Column {
                        Text("Modo Offline Activo: Puedes registrar acopios sin internet.\nSincroniza antes de salir.",
                            color = AmberText, fontSize = 11.sp)
                        Spacer(Modifier.height(2.dp))
                        Text("Última Sincronización: 07/02/2026 06:12 AM",
                            color = AmberText.copy(alpha = 0.6f), fontSize = 10.sp)
                    }
                }

                Spacer(Modifier.height(16.dp))
            }
        }
    }
}

@Composable
private fun DashButton(
    modifier: Modifier, icon: String, label: String, sub: String,
    bg: Color, border: Color, iconColor: Color, onClick: () -> Unit, badge: String? = null
) {
    Box(
        modifier = modifier
            .height(96.dp)
            .clip(RoundedCornerShape(12.dp))
            .background(bg)
            .border(1.5.dp, border.copy(alpha = 0.5f), RoundedCornerShape(12.dp))
            .clickable(onClick = onClick)
            .padding(12.dp)
    ) {
        Column {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(icon, color = iconColor, fontSize = 20.sp, fontWeight = FontWeight.ExtraBold)
                if (badge != null) {
                    Spacer(Modifier.weight(1f))
                    Box(
                        Modifier.clip(RoundedCornerShape(4.dp)).background(iconColor.copy(alpha = 0.15f))
                            .padding(horizontal = 5.dp, vertical = 2.dp)
                    ) { Text(badge, color = iconColor, fontSize = 9.sp, fontWeight = FontWeight.Bold) }
                }
            }
            Spacer(Modifier.height(4.dp))
            Text(label, color = iconColor,                  fontSize = 13.sp, fontWeight = FontWeight.Bold)
            Text(sub,   color = iconColor.copy(alpha = 0.65f), fontSize = 10.sp)
        }
    }
}
