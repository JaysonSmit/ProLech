package com.example.prolechc.ui.screens

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.scaleIn
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
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
fun SyncScreen(
    viewModel: ProLechViewModel,
    onBack: () -> Unit,
    onBottomDashboard: () -> Unit,
    onBottomAcopio: () -> Unit,
    onBottomRuta: () -> Unit,
    onBottomSync: () -> Unit
) {
    val state by viewModel.syncState.collectAsStateWithLifecycle()

    Scaffold(
        containerColor = BgPage,
        bottomBar = {
            ProLechBottomBar(
                current     = BottomNavRoute.SYNC,
                onDashboard = onBottomDashboard,
                onAcopio    = onBottomAcopio,
                onRuta      = onBottomRuta,
                onSync      = onBottomSync
            )
        }
    ) { innerPadding ->
        LazyColumn(
            modifier = Modifier.fillMaxSize().padding(innerPadding).systemBarsPadding(),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            // ── TopBar ─────────────────────────────────────────────────────
            item {
                Row(
                    modifier = Modifier.fillMaxWidth().background(SurfaceDark)
                        .padding(horizontal = 16.dp, vertical = 12.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment     = Alignment.CenterVertically
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text("← ", color = GreenAccent, fontSize = 18.sp,
                            modifier = Modifier.clickable(onClick = onBack))
                        Column {
                            Text("Sincronizar Datos", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold)
                            Text("Panel de Control Com Trapel", color = Color.White.copy(alpha = 0.5f), fontSize = 10.sp)
                        }
                    }
                    Text("ProLech", color = GreenAccent, fontSize = 14.sp, fontWeight = FontWeight.ExtraBold)
                }
            }

            item { Spacer(Modifier.height(4.dp)) }

            // ── Card Estado ────────────────────────────────────────────────
            item {
                Card(
                    modifier  = Modifier.fillMaxWidth().padding(horizontal = 16.dp),
                    colors    = CardDefaults.cardColors(containerColor = BgCard),
                    shape     = RoundedCornerShape(12.dp),
                    elevation = CardDefaults.cardElevation(2.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Text("ESTADO DEL DISPOSITIVO", color = TextMuted, fontSize = 10.sp, letterSpacing = 1.sp, fontWeight = FontWeight.SemiBold)
                            Box(
                                Modifier.clip(RoundedCornerShape(6.dp))
                                    .background(if (state.exitoso) GreenBg else AmberBg)
                                    .padding(horizontal = 10.dp, vertical = 4.dp)
                            ) {
                                Text(
                                    if (state.exitoso) "SINCRONIZADO" else "${state.pendientes} PENDIENTES",
                                    color = if (state.exitoso) GreenPrimary else AmberPrimary,
                                    fontSize = 10.sp, fontWeight = FontWeight.ExtraBold
                                )
                            }
                        }
                        Spacer(Modifier.height(12.dp))
                        SyncInfoRow("Última Sincronización Exitosa:", "Hoy, 06:12 AM")
                        SyncInfoRow("Registros Totales del Día:", "${mockAcopios.size} Acopios")
                        SyncInfoRow("Conexión con Central:",
                            if (state.exitoso) "Conectado" else "DESCONECTADO (Offline)",
                            valueColor = if (state.exitoso) GreenPrimary else RedPrimary)
                        Spacer(Modifier.height(12.dp))
                        HorizontalDivider(color = Divider)
                        Spacer(Modifier.height(12.dp))
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text("Sincronizando ${mockAcopios.size} de 5 registros...", color = TextMuted, fontSize = 11.sp)
                            Text(
                                "${((if (state.exitoso) 1f else state.progreso) * 100).toInt()}%",
                                color = if (state.exitoso) GreenPrimary else AmberPrimary,
                                fontSize = 11.sp, fontWeight = FontWeight.Bold
                            )
                        }
                        Spacer(Modifier.height(6.dp))
                        LinearProgressIndicator(
                            progress   = { if (state.exitoso) 1f else state.progreso },
                            modifier   = Modifier.fillMaxWidth().height(8.dp).clip(RoundedCornerShape(4.dp)),
                            color      = if (state.exitoso) GreenPrimary else AmberPrimary,
                            trackColor = Divider
                        )
                    }
                }
            }

            // ── Botón Sync ─────────────────────────────────────────────────
            item {
                Column(modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp)) {
                    AnimatedVisibility(visible = !state.sincronizando && !state.exitoso, enter = fadeIn(), exit = fadeOut()) {
                        Button(
                            onClick  = viewModel::sincronizarAhora,
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            shape    = RoundedCornerShape(12.dp),
                            colors   = ButtonDefaults.buttonColors(containerColor = GreenPrimary, contentColor = Color.White)
                        ) { Text("↑  SINCRONIZAR AHORA", fontSize = 16.sp, fontWeight = FontWeight.ExtraBold) }
                    }
                    AnimatedVisibility(visible = state.sincronizando, enter = fadeIn(), exit = fadeOut()) {
                        Row(
                            modifier = Modifier.fillMaxWidth().clip(RoundedCornerShape(12.dp))
                                .background(GreenBg).border(1.dp, GreenBorder.copy(0.4f), RoundedCornerShape(12.dp))
                                .padding(16.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.Center
                        ) {
                            CircularProgressIndicator(color = GreenPrimary, modifier = Modifier.size(24.dp), strokeWidth = 3.dp)
                            Spacer(Modifier.width(12.dp))
                            Text("Enviando registros al servidor...", color = GreenPrimary, fontSize = 14.sp, fontWeight = FontWeight.SemiBold)
                        }
                    }
                    AnimatedVisibility(visible = state.exitoso, enter = scaleIn() + fadeIn(), exit = fadeOut()) {
                        Row(
                            modifier = Modifier.fillMaxWidth().clip(RoundedCornerShape(12.dp))
                                .background(GreenBg).border(1.dp, GreenBorder.copy(0.4f), RoundedCornerShape(12.dp))
                                .padding(16.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.Center
                        ) {
                            Text("✓", color = GreenPrimary, fontSize = 22.sp, fontWeight = FontWeight.Bold)
                            Spacer(Modifier.width(10.dp))
                            Text("Sincronización Exitosa al 100%", color = GreenPrimary, fontSize = 15.sp, fontWeight = FontWeight.ExtraBold)
                        }
                    }
                }
            }

            // ── Cola de envío ──────────────────────────────────────────────
            item {
                Text("COLA DE ENVÍO LOCAL", color = TextMuted, fontSize = 10.sp,
                    letterSpacing = 1.sp, fontWeight = FontWeight.SemiBold,
                    modifier = Modifier.padding(horizontal = 16.dp))
            }

            items(mockAcopios) { acopio ->
                val (syncColor, syncBg, syncText) = when (acopio.syncEstado) {
                    EstadoSync.PENDIENTE -> Triple(AmberPrimary, AmberBg,  "Pendiente")
                    EstadoSync.ENVIANDO  -> Triple(BluePrimary,  BlueBg,   "Enviando...")
                    EstadoSync.ENVIADO   -> Triple(GreenPrimary, GreenBg,  "Enviado")
                }
                Row(
                    modifier = Modifier.fillMaxWidth()
                        .padding(horizontal = 16.dp)
                        .clip(RoundedCornerShape(10.dp))
                        .background(BgCard)
                        .border(1.dp, Divider, RoundedCornerShape(10.dp))
                        .padding(horizontal = 14.dp, vertical = 12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column(modifier = Modifier.weight(1f)) {
                        Text(acopio.ganaderoNombre, color = TextPrimary, fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                        Text("${acopio.hora}  ·  Lts: ${acopio.litros} L", color = TextMuted, fontSize = 10.sp)
                    }
                    Box(
                        Modifier.clip(RoundedCornerShape(6.dp)).background(syncBg).padding(horizontal = 10.dp, vertical = 5.dp)
                    ) { Text(syncText, color = syncColor, fontSize = 10.sp, fontWeight = FontWeight.Bold) }
                }
            }

            item { Spacer(Modifier.height(12.dp)) }
        }
    }
}

@Composable
private fun SyncInfoRow(label: String, value: String, valueColor: Color = TextPrimary) {
    Row(Modifier.fillMaxWidth().padding(vertical = 3.dp), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, color = TextMuted, fontSize = 12.sp)
        Text(value, color = valueColor, fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
    }
}
