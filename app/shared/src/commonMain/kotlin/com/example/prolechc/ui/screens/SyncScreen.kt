package com.example.prolechc.ui.screens

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.tween
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
import androidx.compose.runtime.*
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
    val syncState    by viewModel.syncState.collectAsStateWithLifecycle()
    val acopios      by viewModel.acopiosSession.collectAsStateWithLifecycle()

    val pendientes  = acopios.filter { it.syncEstado == EstadoSync.PENDIENTE }
    val enviados    = acopios.filter { it.syncEstado == EstadoSync.ENVIADO }
    val total       = acopios.size

    // Progreso animado
    val progresoAnimado by animateFloatAsState(
        targetValue  = if (syncState.exitoso) 1f else syncState.progreso,
        animationSpec = tween(600),
        label        = "progreso"
    )

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
            modifier            = Modifier.fillMaxSize().padding(innerPadding),
            verticalArrangement = Arrangement.spacedBy(0.dp)
        ) {

            // ── TopBar ─────────────────────────────────────────────────────
            item {
                Row(
                    modifier = Modifier.fillMaxWidth()
                        .background(SurfaceDark)
                        .padding(horizontal = 16.dp, vertical = 14.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment     = Alignment.CenterVertically
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text("← ", color = GreenAccent, fontSize = 20.sp,
                            modifier = Modifier.clickable(onClick = onBack))
                        Spacer(Modifier.width(6.dp))
                        Column {
                            Text("Sincronizar Datos",
                                color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold)
                            Text("Envía los acopios al servidor",
                                color = Color.White.copy(alpha = 0.5f), fontSize = 10.sp)
                        }
                    }
                    Text("ProLech", color = GreenAccent, fontSize = 14.sp, fontWeight = FontWeight.ExtraBold)
                }
            }

            // ── Tarjetas de resumen ────────────────────────────────────────
            item {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(BgCard)
                        .padding(horizontal = 16.dp, vertical = 14.dp),
                    horizontalArrangement = Arrangement.SpaceEvenly
                ) {
                    SyncStatItem(
                        value     = "$total",
                        label     = "Total",
                        color     = TextPrimary
                    )
                    Box(Modifier.width(1.dp).height(36.dp).background(Divider))
                    SyncStatItem(
                        value = "${enviados.size}",
                        label = "Enviados",
                        color = GreenPrimary
                    )
                    Box(Modifier.width(1.dp).height(36.dp).background(Divider))
                    SyncStatItem(
                        value = "${pendientes.size}",
                        label = "Pendientes",
                        color = if (pendientes.isEmpty()) GreenPrimary else AmberPrimary
                    )
                    Box(Modifier.width(1.dp).height(36.dp).background(Divider))
                    SyncStatItem(
                        value = "${acopios.sumOf { it.litros }.let { "%.1f".format(it) }} L",
                        label = "Total litros",
                        color = GreenPrimary
                    )
                }
            }

            item { Spacer(Modifier.height(16.dp)) }

            // ── Card de estado principal ───────────────────────────────────
            item {
                Card(
                    modifier  = Modifier.fillMaxWidth().padding(horizontal = 16.dp),
                    colors    = CardDefaults.cardColors(containerColor = BgCard),
                    shape     = RoundedCornerShape(14.dp),
                    elevation = CardDefaults.cardElevation(2.dp)
                ) {
                    Column(modifier = Modifier.padding(18.dp)) {

                        // Estado badge
                        Row(
                            Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment     = Alignment.CenterVertically
                        ) {
                            Text("ESTADO DE SINCRONIZACIÓN",
                                color = TextMuted, fontSize = 10.sp,
                                letterSpacing = 1.sp, fontWeight = FontWeight.Bold)

                            val (badgeText, badgeBg, badgeColor) = when {
                                syncState.sincronizando -> Triple("ENVIANDO…", AmberBg, AmberPrimary)
                                syncState.exitoso       -> Triple("✓ SINCRONIZADO", GreenBg, GreenPrimary)
                                pendientes.isEmpty()    -> Triple("AL DÍA", GreenBg, GreenPrimary)
                                else                    -> Triple("${pendientes.size} PENDIENTES", AmberBg, AmberPrimary)
                            }
                            Box(
                                Modifier.clip(RoundedCornerShape(20.dp))
                                    .background(badgeBg)
                                    .padding(horizontal = 12.dp, vertical = 5.dp)
                            ) {
                                Text(badgeText, color = badgeColor,
                                    fontSize = 10.sp, fontWeight = FontWeight.ExtraBold)
                            }
                        }

                        Spacer(Modifier.height(16.dp))

                        // Barra de progreso
                        Row(
                            Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment     = Alignment.CenterVertically
                        ) {
                            Text(
                                if (total > 0)
                                    "${enviados.size} de $total acopios enviados"
                                else
                                    "Sin acopios registrados hoy",
                                color = TextMuted, fontSize = 12.sp
                            )
                            Text(
                                "${(progresoAnimado * 100).toInt()}%",
                                color = if (syncState.exitoso || pendientes.isEmpty()) GreenPrimary else AmberPrimary,
                                fontSize = 13.sp, fontWeight = FontWeight.Bold
                            )
                        }
                        Spacer(Modifier.height(8.dp))
                        LinearProgressIndicator(
                            progress   = { if (total == 0) 1f else progresoAnimado },
                            modifier   = Modifier.fillMaxWidth().height(10.dp)
                                .clip(RoundedCornerShape(5.dp)),
                            color      = if (syncState.exitoso || pendientes.isEmpty()) GreenPrimary else AmberPrimary,
                            trackColor = Divider
                        )

                        Spacer(Modifier.height(14.dp))
                        HorizontalDivider(color = Divider)
                        Spacer(Modifier.height(12.dp))

                        // Info rows
                        SyncInfoRow("Acopios enviados hoy:", "${enviados.size}")
                        SyncInfoRow("Acopios pendientes:", "${pendientes.size}",
                            valueColor = if (pendientes.isEmpty()) GreenPrimary else AmberPrimary)
                        SyncInfoRow("Total litros acopiados:",
                            "${"%.1f".format(acopios.sumOf { it.litros })} L")
                    }
                }
            }

            item { Spacer(Modifier.height(12.dp)) }

            // ── Botón de sincronización ────────────────────────────────────
            item {
                Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                    syncState.mensaje?.takeIf { !syncState.sincronizando }?.let { msg ->
                        Text(
                            msg,
                            color      = if (msg.startsWith("✓")) GreenPrimary else AmberPrimary,
                            fontSize   = 12.sp,
                            fontWeight = FontWeight.SemiBold,
                            modifier   = Modifier.fillMaxWidth().padding(bottom = 8.dp)
                        )
                    }
                    if (pendientes.isNotEmpty() && !syncState.sincronizando) {
                        Text(
                            "Los pendientes están guardados en el celular y se envían solos al volver la señal.",
                            color    = TextMuted,
                            fontSize = 11.sp,
                            modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp)
                        )
                    }
                    AnimatedVisibility(
                        visible = !syncState.sincronizando && pendientes.isNotEmpty(),
                        enter = fadeIn(), exit = fadeOut()
                    ) {
                        Button(
                            onClick  = viewModel::sincronizarAhora,
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            shape    = RoundedCornerShape(12.dp),
                            colors   = ButtonDefaults.buttonColors(
                                containerColor = GreenPrimary,
                                contentColor   = Color.White
                            )
                        ) {
                            Text("↑  SINCRONIZAR ${pendientes.size} ACOPIO${if (pendientes.size != 1) "S" else ""}",
                                fontSize = 15.sp, fontWeight = FontWeight.ExtraBold)
                        }
                    }

                    AnimatedVisibility(visible = syncState.sincronizando, enter = fadeIn(), exit = fadeOut()) {
                        Row(
                            modifier = Modifier.fillMaxWidth()
                                .clip(RoundedCornerShape(12.dp))
                                .background(GreenBg)
                                .border(1.dp, GreenBorder.copy(0.3f), RoundedCornerShape(12.dp))
                                .padding(16.dp),
                            verticalAlignment     = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.Center
                        ) {
                            CircularProgressIndicator(
                                color       = GreenPrimary,
                                modifier    = Modifier.size(22.dp),
                                strokeWidth = 2.5.dp
                            )
                            Spacer(Modifier.width(12.dp))
                            Text("Enviando acopios al servidor…",
                                color = GreenPrimary, fontSize = 14.sp, fontWeight = FontWeight.SemiBold)
                        }
                    }

                    AnimatedVisibility(
                        visible = (syncState.exitoso || pendientes.isEmpty()) && !syncState.sincronizando && total > 0,
                        enter = scaleIn() + fadeIn(), exit = fadeOut()
                    ) {
                        Row(
                            modifier = Modifier.fillMaxWidth()
                                .clip(RoundedCornerShape(12.dp))
                                .background(GreenBg)
                                .border(1.dp, GreenBorder.copy(0.3f), RoundedCornerShape(12.dp))
                                .padding(16.dp),
                            verticalAlignment     = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.Center
                        ) {
                            Text("✓", color = GreenPrimary, fontSize = 24.sp, fontWeight = FontWeight.Bold)
                            Spacer(Modifier.width(10.dp))
                            Column {
                                Text("Todo sincronizado",
                                    color = GreenPrimary, fontSize = 15.sp, fontWeight = FontWeight.ExtraBold)
                                Text("Los datos están en el servidor",
                                    color = GreenPrimary.copy(alpha = 0.7f), fontSize = 11.sp)
                            }
                        }
                    }
                }
            }

            // ── Lista de acopios de sesión ─────────────────────────────────
            if (acopios.isNotEmpty()) {
                item {
                    Text(
                        "ACOPIOS DE ESTA SESIÓN",
                        color = TextMuted, fontSize = 10.sp,
                        letterSpacing = 1.sp, fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(horizontal = 16.dp, vertical = 10.dp)
                    )
                }

                items(acopios, key = { it.ticketNumero }) { acopio ->
                    val (syncColor, syncBg, syncText) = when {
                        acopio.errorSync != null                 -> Triple(RedPrimary,   RedBg,    "✗ Rechazado")
                        acopio.syncEstado == EstadoSync.PENDIENTE -> Triple(AmberPrimary, AmberBg,  "⏳ Pendiente")
                        acopio.syncEstado == EstadoSync.ENVIANDO  -> Triple(CyanAccent,   CyanBg,   "↑ Enviando…")
                        else                                      -> Triple(GreenPrimary, GreenBg,  "✓ Enviado")
                    }
                    val estadoColor = when (acopio.estado) {
                        EstadoAcopio.APROBADO  -> GreenPrimary
                        EstadoAcopio.RECHAZADO -> RedPrimary
                        EstadoAcopio.OBSERVADO -> AmberPrimary
                    }

                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp, vertical = 3.dp)
                            .clip(RoundedCornerShape(10.dp))
                            .background(BgCard)
                            .border(1.dp, Divider, RoundedCornerShape(10.dp))
                            .padding(horizontal = 14.dp, vertical = 11.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        // Indicador de estado calidad
                        Box(
                            modifier = Modifier
                                .size(8.dp)
                                .clip(RoundedCornerShape(4.dp))
                                .background(estadoColor)
                        )
                        Spacer(Modifier.width(10.dp))

                        Column(modifier = Modifier.weight(1f)) {
                            Text(acopio.ganaderoNombre,
                                color = TextPrimary, fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                            Row(verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                Text(acopio.hora, color = TextMuted, fontSize = 10.sp)
                                Text("·", color = TextDim, fontSize = 10.sp)
                                Text("${acopio.litros} L", color = GreenPrimary,
                                    fontSize = 10.sp, fontWeight = FontWeight.Bold)
                                if (acopio.ticketNumero.isNotBlank()) {
                                    Text("·", color = TextDim, fontSize = 10.sp)
                                    Text(acopio.ticketNumero, color = TextDim, fontSize = 10.sp)
                                }
                            }
                            acopio.errorSync?.let { error ->
                                Text(error, color = RedPrimary, fontSize = 10.sp)
                                Text(
                                    "Descartar",
                                    color      = RedPrimary,
                                    fontSize   = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    modifier   = Modifier
                                        .padding(top = 4.dp)
                                        .clickable { viewModel.descartarPendiente(acopio) }
                                )
                            }
                        }

                        Box(
                            Modifier.clip(RoundedCornerShape(6.dp))
                                .background(syncBg)
                                .padding(horizontal = 9.dp, vertical = 4.dp)
                        ) {
                            Text(syncText, color = syncColor,
                                fontSize = 10.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                }
            } else {
                item {
                    Box(
                        modifier         = Modifier.fillMaxWidth().padding(40.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text("📭", fontSize = 40.sp)
                            Spacer(Modifier.height(10.dp))
                            Text("Sin acopios en esta sesión",
                                color = TextMuted, fontSize = 14.sp, fontWeight = FontWeight.SemiBold)
                            Text("Registra acopios y aparecerán aquí",
                                color = TextDim, fontSize = 12.sp)
                        }
                    }
                }
            }

            item { Spacer(Modifier.height(16.dp)) }
        }
    }
}

@Composable
private fun SyncStatItem(value: String, label: String, color: Color) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(value, color = color, fontSize = 18.sp, fontWeight = FontWeight.ExtraBold)
        Text(label, color = TextMuted, fontSize = 9.sp, letterSpacing = 0.5.sp)
    }
}

@Composable
private fun SyncInfoRow(label: String, value: String, valueColor: Color = TextPrimary) {
    Row(
        Modifier.fillMaxWidth().padding(vertical = 3.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment     = Alignment.CenterVertically
    ) {
        Text(label, color = TextMuted, fontSize = 12.sp)
        Text(value, color = valueColor, fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
    }
}
