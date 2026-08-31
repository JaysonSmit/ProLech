package com.example.prolechc.ui.screens

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.ui.components.BottomNavRoute
import com.example.prolechc.ui.components.ProLechBottomBar
import com.example.prolechc.ui.theme.*
import com.example.prolechc.viewmodel.ProLechViewModel

@Composable
fun AcopioScreen(
    viewModel: ProLechViewModel,
    onBack: () -> Unit,
    onGuardadoExitoso: () -> Unit,
    onBottomDashboard: () -> Unit,
    onBottomAcopio: () -> Unit,
    onBottomRuta: () -> Unit,
    onBottomSync: () -> Unit
) {
    val state      by viewModel.formState.collectAsStateWithLifecycle()

    LaunchedEffect(state.guardadoExitoso) {
        if (state.guardadoExitoso) onGuardadoExitoso()
    }

    Scaffold(
        containerColor = BgPage,
        bottomBar = {
            ProLechBottomBar(
                current     = BottomNavRoute.ACOPIO,
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
                .systemBarsPadding()
        ) {
            // ── TopBar ─────────────────────────────────────────────────────
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(SurfaceDark)
                    .padding(horizontal = 14.dp, vertical = 12.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text("← ", color = GreenAccent, fontSize = 20.sp,
                    modifier = Modifier.clickable(onClick = onBack))
                Text("Nuevo Acopio", color = Color.White, fontSize = 17.sp, fontWeight = FontWeight.Bold)
                Spacer(Modifier.weight(1f))
                Column(horizontalAlignment = Alignment.End) {
                    Text("Cisterna: ${viewModel.cisternaActual.toInt()}/${viewModel.cisternaTotal.toInt()}L",
                        color = Color.White.copy(alpha = 0.6f), fontSize = 10.sp)
                    LinearProgressIndicator(
                        progress   = { viewModel.cisternaProgress },
                        modifier   = Modifier.width(72.dp).height(5.dp).clip(RoundedCornerShape(3.dp)),
                        color      = GreenAccent,
                        trackColor = Color.White.copy(alpha = 0.2f)
                    )
                }
            }

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(horizontal = 16.dp)
            ) {
                Spacer(Modifier.height(14.dp))

                // ── DNI + Buscar ───────────────────────────────────────────
                AcopioSectionLabel("NÚMERO DE DNI DEL GANADERO")
                Spacer(Modifier.height(6.dp))
                Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value         = state.dni,
                        onValueChange = viewModel::onDniChange,
                        placeholder   = { Text("Ej. 40201653", color = TextDim) },
                        modifier      = Modifier.weight(1f),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        singleLine    = true,
                        colors        = lightFieldColors()
                    )
                    Button(
                        onClick  = {},
                        modifier = Modifier.height(56.dp),
                        shape    = RoundedCornerShape(10.dp),
                        colors   = ButtonDefaults.buttonColors(containerColor = GreenPrimary, contentColor = Color.White)
                    ) { Text("BUSCAR", fontSize = 12.sp, fontWeight = FontWeight.Bold) }
                }

                // Nombre autocompletado
                AnimatedVisibility(visible = state.nombreAutocompletado.isNotEmpty(), enter = fadeIn(), exit = fadeOut()) {
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                            .clip(RoundedCornerShape(10.dp))
                            .background(GreenBg)
                            .border(1.dp, GreenBorder.copy(alpha = 0.5f), RoundedCornerShape(10.dp))
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Box(
                            modifier = Modifier.size(38.dp).clip(RoundedCornerShape(8.dp)).background(GreenPrimary),
                            contentAlignment = Alignment.Center
                        ) { Text("QC", color = Color.White, fontSize = 12.sp, fontWeight = FontWeight.Bold) }
                        Spacer(Modifier.width(10.dp))
                        Column {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Text(state.nombreAutocompletado, color = GreenDark, fontSize = 14.sp, fontWeight = FontWeight.Bold)
                                Spacer(Modifier.width(8.dp))
                                Box(Modifier.clip(RoundedCornerShape(4.dp)).background(GreenPrimary).padding(horizontal = 6.dp, vertical = 2.dp)) {
                                    Text("Base Activo", color = Color.White, fontSize = 9.sp)
                                }
                            }
                            Text("DNI: ${state.dni}  ·  Fundo: La Rinconada (Huata)", color = TextSecondary, fontSize = 10.sp)
                        }
                    }
                }

                Spacer(Modifier.height(16.dp))

                // ── Litros ─────────────────────────────────────────────────
                AcopioSectionLabel("LITROS DE LECHE (MEDIDOS)")
                Spacer(Modifier.height(8.dp))
                Row(
                    Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Box(
                        modifier = Modifier.size(52.dp).clip(RoundedCornerShape(10.dp))
                            .background(BgCard).border(1.5.dp, Divider, RoundedCornerShape(10.dp))
                            .clickable { viewModel.decrementarLitros() },
                        contentAlignment = Alignment.Center
                    ) { Text("–", color = TextPrimary, fontSize = 26.sp, fontWeight = FontWeight.Bold) }

                    Box(
                        modifier = Modifier.weight(1f).clip(RoundedCornerShape(10.dp))
                            .background(GreenBg).border(1.5.dp, GreenBorder, RoundedCornerShape(10.dp))
                            .padding(vertical = 14.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(state.litrosTexto, color = GreenPrimary, fontSize = 40.sp,
                            fontWeight = FontWeight.ExtraBold, textAlign = TextAlign.Center)
                    }

                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("TEMP.", color = TextMuted, fontSize = 9.sp)
                        Box(
                            modifier = Modifier.width(70.dp).clip(RoundedCornerShape(8.dp))
                                .background(BgCard).border(1.dp, Divider, RoundedCornerShape(8.dp))
                                .padding(vertical = 10.dp),
                            contentAlignment = Alignment.Center
                        ) { Text("4.2 °C", color = TextPrimary, fontSize = 13.sp, fontWeight = FontWeight.Bold) }
                    }

                    Box(
                        modifier = Modifier.size(52.dp).clip(RoundedCornerShape(10.dp))
                            .background(GreenBg).border(1.5.dp, GreenBorder, RoundedCornerShape(10.dp))
                            .clickable { viewModel.incrementarLitros() },
                        contentAlignment = Alignment.Center
                    ) { Text("+", color = GreenPrimary, fontSize = 26.sp, fontWeight = FontWeight.Bold) }
                }

                Spacer(Modifier.height(16.dp))

                // ── Lactoscan ──────────────────────────────────────────────
                AcopioSectionLabel("PRUEBAS DE CALIDAD (LACTOSCAN)")
                Spacer(Modifier.height(8.dp))
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    Column(Modifier.weight(1f)) {
                        Text("% AGUA (Máx. 5%)", color = if (state.lechRechazada) RedPrimary else TextMuted, fontSize = 10.sp)
                        Spacer(Modifier.height(4.dp))
                        OutlinedTextField(
                            value         = state.porcentajeAgua,
                            onValueChange = viewModel::onPorcentajeAguaChange,
                            placeholder   = { Text("0.0") },
                            modifier      = Modifier.fillMaxWidth(),
                            singleLine    = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            suffix        = { Text("%") },
                            colors        = if (state.lechRechazada) lightRedFieldColors() else lightFieldColors()
                        )
                    }
                    Column(Modifier.weight(1f)) {
                        Text("ACIDEZ (pH 6.4 – 6.8)", color = TextMuted, fontSize = 10.sp)
                        Spacer(Modifier.height(4.dp))
                        OutlinedTextField(
                            value         = state.acidezPh,
                            onValueChange = viewModel::onAcidezPhChange,
                            placeholder   = { Text("6.5") },
                            modifier      = Modifier.fillMaxWidth(),
                            singleLine    = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            suffix        = { Text("pH") },
                            colors        = lightFieldColors()
                        )
                    }
                }

                Spacer(Modifier.height(10.dp))

                // ── Estado calidad ─────────────────────────────────────────
                AcopioSectionLabel("ESTADO / CALIDAD DE LA LECHE")
                Spacer(Modifier.height(6.dp))
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    QualityChip("APROBADA",  GreenPrimary, GreenBg,   !state.lechRechazada && state.porcentajeAgua.isNotEmpty(), Modifier.weight(1f))
                    QualityChip("OBSERVADA", AmberPrimary, AmberBg,   false, Modifier.weight(1f))
                    QualityChip("RECHAZADA", RedPrimary,   RedBg,     state.lechRechazada, Modifier.weight(1f))
                }

                // Alerta rechazo
                AnimatedVisibility(visible = state.lechRechazada, enter = fadeIn(), exit = fadeOut()) {
                    Column {
                        Spacer(Modifier.height(10.dp))
                        Row(
                            modifier = Modifier.fillMaxWidth()
                                .clip(RoundedCornerShape(8.dp))
                                .background(RedBg)
                                .border(1.dp, RedBorder.copy(alpha = 0.4f), RoundedCornerShape(8.dp))
                                .padding(12.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text("✗  ", color = RedPrimary, fontSize = 16.sp, fontWeight = FontWeight.Bold)
                            Column {
                                Text("⚠ ALERTA ACIDEZ / RECHAZADA", color = RedPrimary, fontSize = 12.sp, fontWeight = FontWeight.ExtraBold)
                                Text("% de Agua supera el límite permitido (5%). Se requiere foto.", color = RedText, fontSize = 10.sp)
                            }
                        }
                        Spacer(Modifier.height(8.dp))
                        // Evidencia foto
                        Row(
                            modifier = Modifier.fillMaxWidth()
                                .clip(RoundedCornerShape(8.dp))
                                .background(RedBg)
                                .border(1.dp, RedBorder.copy(alpha = 0.4f), RoundedCornerShape(8.dp))
                                .padding(12.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text("EVIDENCIA OBLIGATORIA (FOTO)", color = RedPrimary, fontSize = 12.sp, fontWeight = FontWeight.Bold)
                            Box(Modifier.clip(RoundedCornerShape(4.dp)).background(RedPrimary).padding(horizontal = 8.dp, vertical = 4.dp)) {
                                Text("FALTA ADJUNTAR", color = Color.White, fontSize = 9.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                        Spacer(Modifier.height(8.dp))
                        Button(
                            onClick  = {},
                            modifier = Modifier.fillMaxWidth().height(50.dp),
                            shape    = RoundedCornerShape(10.dp),
                            colors   = ButtonDefaults.buttonColors(containerColor = RedPrimary, contentColor = Color.White)
                        ) { Text("📷  TOMAR FOTO", fontSize = 14.sp, fontWeight = FontWeight.Bold) }
                        Spacer(Modifier.height(8.dp))
                        OutlinedTextField(
                            value = "Presencia de grumos y acidez elevada. No apto para consumo.",
                            onValueChange = {},
                            label = { Text("OBSERVACIONES / DETALLE RECHAZO") },
                            modifier = Modifier.fillMaxWidth(),
                            minLines = 2,
                            colors = lightRedFieldColors()
                        )
                    }
                }

                Spacer(Modifier.height(20.dp))

                // ── Guardar ────────────────────────────────────────────────
                Button(
                    onClick  = viewModel::guardarAcopio,
                    modifier = Modifier.fillMaxWidth().height(56.dp),
                    shape    = RoundedCornerShape(12.dp),
                    colors   = ButtonDefaults.buttonColors(containerColor = GreenPrimary, contentColor = Color.White)
                ) { Text("◎  GUARDAR ACOPIO (GPS)", fontSize = 16.sp, fontWeight = FontWeight.ExtraBold) }

                Spacer(Modifier.height(24.dp))
            }
        }
    }
}

@Composable
private fun AcopioSectionLabel(text: String) {
    Text(text, color = TextMuted, fontSize = 10.sp, fontWeight = FontWeight.SemiBold, letterSpacing = 1.sp)
}

@Composable
private fun QualityChip(label: String, textColor: Color, bg: Color, active: Boolean, modifier: Modifier) {
    Box(
        modifier = modifier.clip(RoundedCornerShape(8.dp))
            .background(if (active) bg else BgCardAlt)
            .border(1.5.dp, if (active) textColor.copy(alpha = 0.5f) else Divider, RoundedCornerShape(8.dp))
            .padding(vertical = 8.dp),
        contentAlignment = Alignment.Center
    ) { Text(label, color = if (active) textColor else TextDim, fontSize = 11.sp, fontWeight = FontWeight.Bold) }
}

@Composable
private fun lightFieldColors() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor      = GreenBorder,
    unfocusedBorderColor    = Divider,
    focusedLabelColor       = GreenPrimary,
    unfocusedLabelColor     = TextMuted,
    focusedContainerColor   = BgCard,
    unfocusedContainerColor = BgCard,
    focusedTextColor        = TextPrimary,
    unfocusedTextColor      = TextPrimary,
    cursorColor             = GreenPrimary
)

@Composable
private fun lightRedFieldColors() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor      = RedBorder,
    unfocusedBorderColor    = RedBorder,
    focusedLabelColor       = RedPrimary,
    unfocusedLabelColor     = RedPrimary,
    focusedContainerColor   = RedBg,
    unfocusedContainerColor = RedBg,
    focusedTextColor        = TextPrimary,
    unfocusedTextColor      = TextPrimary,
    cursorColor             = RedPrimary
)
