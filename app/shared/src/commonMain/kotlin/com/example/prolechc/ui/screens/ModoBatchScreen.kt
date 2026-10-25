package com.example.prolechc.ui.screens

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.ui.theme.*
import com.example.prolechc.viewmodel.EntradaBatch
import com.example.prolechc.viewmodel.ProLechViewModel

// ══════════════════════════════════════════════════════════════════════════════
//  ModoBatchScreen — Modo Rápido
//
//  Permite registrar litros de 5-6 proveedores al mismo tiempo en un punto
//  de acopio donde varios están esperando juntos (RF-ACO-04).
//
//  El conductor ingresa solo los litros de cada uno — sin necesidad de ir
//  uno por uno al formulario completo.
// ══════════════════════════════════════════════════════════════════════════════

@Composable
fun ModoBatchScreen(
    viewModel: ProLechViewModel,
    onBack: () -> Unit,
    onGuardadoExitoso: () -> Unit
) {
    val batchState by viewModel.batchState.collectAsStateWithLifecycle()
    val miRuta     by viewModel.miRutaState.collectAsStateWithLifecycle()

    // Inicializar con ganaderos de la ruta al entrar
    LaunchedEffect(Unit) {
        viewModel.iniciarBatch()
    }

    // Cuando todos los que tenían litros ya están guardados
    val todosGuardados = batchState.entradas.isNotEmpty() &&
        batchState.entradas.filter { it.litrosTexto.toDoubleOrNull() != null &&
            (it.litrosTexto.toDoubleOrNull() ?: 0.0) > 0 }
            .all { it.guardado }

    Scaffold(containerColor = BgPage) { innerPadding ->
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
                    .padding(horizontal = 14.dp, vertical = 12.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    "← ",
                    color = GreenAccent,
                    fontSize = 20.sp,
                    modifier = Modifier.clickable(
                        enabled = !batchState.isLoading,
                        onClick = onBack
                    )
                )
                Column(Modifier.weight(1f)) {
                    Text(
                        "⚡  Modo Rápido",
                        color = Color.White, fontSize = 17.sp, fontWeight = FontWeight.Bold
                    )
                    Text(
                        "Registra varios productores a la vez",
                        color = Color.White.copy(alpha = 0.5f), fontSize = 10.sp
                    )
                }
                Text("ProLech", color = GreenAccent, fontSize = 13.sp, fontWeight = FontWeight.ExtraBold)
            }

            // ── Instrucción ────────────────────────────────────────────────
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(Color(0xFF1565C0).copy(alpha = 0.1f))
                    .padding(horizontal = 16.dp, vertical = 10.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text("ℹ  ", color = Color(0xFF1565C0), fontSize = 14.sp)
                Text(
                    "Ingresa solo los litros de cada productor presente. Deja en blanco los que no están.",
                    color    = Color(0xFF1565C0),
                    fontSize = 11.sp
                )
            }

            // ── Lista de ganaderos ─────────────────────────────────────────
            if (batchState.entradas.isEmpty()) {
                Box(
                    modifier         = Modifier.weight(1f).fillMaxWidth(),
                    contentAlignment = Alignment.Center
                ) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("🐄", fontSize = 40.sp)
                        Spacer(Modifier.height(8.dp))
                        Text("Sin productores en la ruta", color = TextMuted, fontSize = 14.sp)
                    }
                }
            } else {
                LazyColumn(
                    modifier            = Modifier.weight(1f).padding(horizontal = 14.dp, vertical = 8.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(batchState.entradas, key = { it.ganaderoId }) { entrada ->
                        BatchEntradaCard(
                            entrada     = entrada,
                            onLitrosChange = { viewModel.onBatchLitrosChange(entrada.ganaderoId, it) },
                            enabled     = !batchState.isLoading && !entrada.guardado
                        )
                    }
                    item { Spacer(Modifier.height(8.dp)) }
                }
            }

            // ── Resumen y botón guardar ────────────────────────────────────
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(BgCard)
                    .padding(horizontal = 16.dp, vertical = 14.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                val conLitros = batchState.entradas.count {
                    (it.litrosTexto.toDoubleOrNull() ?: 0.0) > 0
                }
                val totalLitros = batchState.entradas.sumOf {
                    it.litrosTexto.toDoubleOrNull() ?: 0.0
                }

                Row(
                    modifier              = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text("Productores a registrar:", color = TextSecondary, fontSize = 12.sp)
                    Text("$conLitros", color = GreenPrimary, fontSize = 14.sp, fontWeight = FontWeight.Bold)
                }
                Row(
                    modifier              = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text("Total litros:", color = TextSecondary, fontSize = 12.sp)
                    Text(
                        "${((totalLitros * 10).toLong() / 10.0)} L",
                        color = GreenPrimary, fontSize = 14.sp, fontWeight = FontWeight.Bold
                    )
                }

                // Éxito
                AnimatedVisibility(visible = todosGuardados && batchState.totalGuardados > 0) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(8.dp))
                            .background(GreenBg)
                            .border(1.dp, GreenBorder.copy(alpha = 0.4f), RoundedCornerShape(8.dp))
                            .padding(10.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("✓  ", color = GreenPrimary, fontSize = 16.sp)
                        Text(
                            "${batchState.totalGuardados} acopio${if (batchState.totalGuardados > 1) "s" else ""} guardado${if (batchState.totalGuardados > 1) "s" else ""} correctamente",
                            color = GreenPrimary, fontSize = 13.sp, fontWeight = FontWeight.SemiBold
                        )
                    }
                }

                // RF-ACO-07 — balde en modo rápido
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(8.dp))
                        .background(if (batchState.medicionBaldeConfirmada) GreenBg else AmberBg)
                        .padding(10.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Checkbox(
                        checked         = batchState.medicionBaldeConfirmada,
                        onCheckedChange = viewModel::onBatchMedicionBaldeChange,
                        enabled         = !batchState.isLoading,
                        colors          = CheckboxDefaults.colors(checkedColor = GreenPrimary)
                    )
                    Text(
                        "Confirmo medición con balde estándar",
                        color = TextSecondary,
                        fontSize = 12.sp,
                        modifier = Modifier.padding(start = 4.dp)
                    )
                }

                if (todosGuardados && batchState.totalGuardados > 0) {
                    Button(
                        onClick  = { viewModel.resetBatch(); onGuardadoExitoso() },
                        modifier = Modifier.fillMaxWidth().height(52.dp),
                        shape    = RoundedCornerShape(12.dp),
                        colors   = ButtonDefaults.buttonColors(
                            containerColor = GreenPrimary, contentColor = Color.White
                        )
                    ) {
                        Text("✓  VER HISTORIAL", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                    }
                } else {
                    Button(
                        onClick  = { viewModel.guardarBatch() },
                        enabled  = !batchState.isLoading && conLitros > 0
                                   && batchState.medicionBaldeConfirmada,
                        modifier = Modifier.fillMaxWidth().height(52.dp),
                        shape    = RoundedCornerShape(12.dp),
                        colors   = ButtonDefaults.buttonColors(
                            containerColor         = GreenPrimary,
                            contentColor           = Color.White,
                            disabledContainerColor = GreenPrimary.copy(alpha = 0.4f),
                            disabledContentColor   = Color.White.copy(alpha = 0.6f)
                        )
                    ) {
                        if (batchState.isLoading) {
                            CircularProgressIndicator(
                                color    = Color.White,
                                modifier = Modifier.size(22.dp),
                                strokeWidth = 2.dp
                            )
                        } else {
                            Text(
                                "⚡  GUARDAR $conLitros ACOPIO${if (conLitros != 1) "S" else ""}",
                                fontSize = 14.sp, fontWeight = FontWeight.Bold
                            )
                        }
                    }
                }
            }
        }
    }
}

// ── Card por ganadero ─────────────────────────────────────────────────────────

@Composable
private fun BatchEntradaCard(
    entrada: EntradaBatch,
    onLitrosChange: (String) -> Unit,
    enabled: Boolean
) {
    val (borderColor, bgColor) = when {
        entrada.guardado          -> GreenBorder to GreenBg
        entrada.error != null     -> RedBorder to Color(0xFFFFEBEE)
        (entrada.litrosTexto.toDoubleOrNull() ?: 0.0) > 0 -> GreenBorder.copy(alpha = 0.5f) to BgCard
        else                      -> Divider to BgCard
    }

    Card(
        modifier  = Modifier
            .fillMaxWidth()
            .border(1.dp, borderColor, RoundedCornerShape(12.dp)),
        colors    = CardDefaults.cardColors(containerColor = bgColor),
        shape     = RoundedCornerShape(12.dp),
        elevation = CardDefaults.cardElevation(1.dp)
    ) {
        Row(
            modifier          = Modifier.padding(12.dp).fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Avatar con inicial
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .clip(RoundedCornerShape(10.dp))
                    .background(if (entrada.guardado) GreenPrimary else BgCardAlt),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    if (entrada.guardado) "✓"
                    else entrada.ganaderoNombre.take(2).uppercase(),
                    color      = if (entrada.guardado) Color.White else TextSecondary,
                    fontSize   = if (entrada.guardado) 18.sp else 13.sp,
                    fontWeight = FontWeight.ExtraBold
                )
            }

            // Nombre y DNI
            Column(Modifier.weight(1f)) {
                Text(
                    entrada.ganaderoNombre,
                    color      = if (entrada.guardado) GreenDark else TextPrimary,
                    fontSize   = 13.sp,
                    fontWeight = FontWeight.SemiBold
                )
                if (entrada.dniGanadero.isNotBlank()) {
                    Text("DNI: ${entrada.dniGanadero}", color = TextMuted, fontSize = 10.sp)
                }
                entrada.error?.let {
                    Text(it, color = RedPrimary, fontSize = 10.sp)
                }
            }

            // Campo de litros
            OutlinedTextField(
                value         = if (entrada.guardado) entrada.litrosTexto else entrada.litrosTexto,
                onValueChange = onLitrosChange,
                placeholder   = { Text("0.0", color = TextDim, fontSize = 12.sp) },
                modifier      = Modifier.width(90.dp),
                singleLine    = true,
                enabled       = enabled,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                suffix        = { Text("L", color = TextMuted, fontSize = 11.sp) },
                colors        = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor      = GreenBorder,
                    unfocusedBorderColor    = if (entrada.guardado) GreenBorder else Divider,
                    focusedContainerColor   = BgCard,
                    unfocusedContainerColor = BgCard,
                    focusedTextColor        = TextPrimary,
                    unfocusedTextColor      = if (entrada.guardado) GreenPrimary else TextPrimary,
                    disabledTextColor       = GreenPrimary,
                    disabledBorderColor     = GreenBorder,
                    disabledContainerColor  = GreenBg,
                    cursorColor             = GreenPrimary
                ),
                textStyle = androidx.compose.ui.text.TextStyle(
                    fontSize   = 14.sp,
                    fontWeight = FontWeight.Bold
                )
            )
        }
    }
}
