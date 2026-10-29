package com.example.prolechc.ui.screens

import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.scale
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.data.EstadoAcopio
import com.example.prolechc.ui.theme.*
import com.example.prolechc.viewmodel.ProLechViewModel

@Composable
fun RegistroExitosoScreen(
    viewModel: ProLechViewModel,
    onNuevoAcopio: () -> Unit,
    onVerHistorial: () -> Unit,
    onImprimirBoleta: (() -> Unit)? = null   // Inyectado desde Android con BluetoothPrintService
) {
    val form      by viewModel.formState.collectAsStateWithLifecycle()
    val miRuta    by viewModel.miRutaState.collectAsStateWithLifecycle()
    val loginState by viewModel.loginState.collectAsStateWithLifecycle()

    // Animación de entrada del check
    val scale by animateFloatAsState(
        targetValue    = 1f,
        animationSpec  = spring(dampingRatio = Spring.DampingRatioMediumBouncy, stiffness = Spring.StiffnessMedium),
        label          = "checkScale"
    )

    // Calidad del acopio guardado
    val estadoLabel = when {
        form.lechRechazada                          -> "RECHAZADA"
        (form.acidezPh.toDoubleOrNull() ?: 6.5).let { it < 6.0 || it > 7.0 } &&
            form.acidezPh.isNotEmpty()              -> "OBSERVADA"
        else                                        -> "APROBADA"
    }
    val estadoColor = when (estadoLabel) {
        "RECHAZADA" -> RedPrimary
        "OBSERVADA" -> AmberPrimary
        else        -> GreenPrimary
    }
    val estadoBg = when (estadoLabel) {
        "RECHAZADA" -> RedBg
        "OBSERVADA" -> AmberBg
        else        -> GreenBg
    }

    Column(
        modifier = Modifier.fillMaxSize().background(BgPage).systemBarsPadding()
    ) {
        // ── TopBar ─────────────────────────────────────────────────────────
        Row(
            modifier = Modifier.fillMaxWidth().background(SurfaceDark)
                .padding(horizontal = 16.dp, vertical = 12.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment     = Alignment.CenterVertically
        ) {
            Text("Comprobante de Acopio", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold)
            Text("ProLech", color = GreenAccent, fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
        }

        Column(
            modifier            = Modifier.fillMaxSize().verticalScroll(rememberScrollState())
                .padding(horizontal = 20.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(Modifier.height(28.dp))

            // ── Check animado ──────────────────────────────────────────────
            Box(
                modifier         = Modifier.size(100.dp).scale(scale).clip(CircleShape)
                    .background(estadoBg).border(3.dp, estadoColor.copy(alpha = 0.6f), CircleShape),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    when (estadoLabel) { "RECHAZADA" -> "✗"; "OBSERVADA" -> "!"; else -> "✓" },
                    color      = estadoColor,
                    fontSize   = 48.sp,
                    fontWeight = FontWeight.Bold
                )
            }
            Spacer(Modifier.height(14.dp))
            Text(
                "¡Acopio Registrado!",
                color      = estadoColor,
                fontSize   = 24.sp,
                fontWeight = FontWeight.ExtraBold,
                textAlign  = TextAlign.Center
            )
            Text(
                "COMPROBANTE DIGITAL GENERADO",
                color = TextMuted, fontSize = 11.sp, letterSpacing = 1.sp
            )

            Spacer(Modifier.height(22.dp))

            // ── Ticket ─────────────────────────────────────────────────────
            Card(
                modifier  = Modifier.fillMaxWidth(),
                colors    = CardDefaults.cardColors(containerColor = BgCard),
                shape     = RoundedCornerShape(14.dp),
                elevation = CardDefaults.cardElevation(3.dp)
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    // Cabecera del ticket
                    Row(
                        Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment     = Alignment.CenterVertically
                    ) {
                        Column {
                            Text("TICKET N° ${form.ticketNumero.ifBlank { "---" }}",
                                color = TextMuted, fontSize = 11.sp, fontWeight = FontWeight.SemiBold)
                            Text(loginState.conductorNombre.ifBlank { "ProLech Sistema" },
                                color = TextPrimary, fontSize = 12.sp, fontWeight = FontWeight.Bold)
                        }
                        Box(
                            Modifier.clip(RoundedCornerShape(6.dp))
                                .background(estadoBg)
                                .padding(horizontal = 10.dp, vertical = 5.dp)
                        ) {
                            Text(estadoLabel, color = estadoColor, fontSize = 10.sp,
                                fontWeight = FontWeight.ExtraBold)
                        }
                    }

                    HorizontalDivider(color = Divider, modifier = Modifier.padding(vertical = 12.dp))

                    // Filas del ticket
                    TicketRow(
                        "Productor:",
                        form.ganaderoNombre.ifBlank { "Ganadero Registrado" }
                    )
                    if (form.dniGanadero.isNotBlank()) {
                        TicketRow("DNI Productor:", form.dniGanadero)
                    }
                    TicketRow(
                        "Cantidad Recibida:",
                        "${"%.1f".format(form.litros)} Litros",
                        valueColor = GreenPrimary
                    )
                    TicketRow("Temperatura:", "4.2 °C")
                    TicketRow(
                        "Calidad / Estado:",
                        estadoLabel,
                        valueColor = estadoColor
                    )
                    if (form.porcentajeAgua.isNotBlank()) {
                        TicketRow("% Agua Detectado:", "${form.porcentajeAgua}%",
                            valueColor = if (form.lechRechazada) RedPrimary else TextPrimary)
                    }
                    if (form.acidezPh.isNotBlank()) {
                        TicketRow("Acidez pH:", "${form.acidezPh} pH")
                    }
                    if (form.densidad.isNotBlank()) {
                        TicketRow("Densidad:", "${form.densidad} g/mL")
                    }
                    // ── Parámetros LACTOMAT adicionales ──────────────────────
                    if (form.grasa.isNotBlank()) {
                        TicketRow("Grasa:", "${form.grasa}%")
                    }
                    if (form.proteina.isNotBlank()) {
                        TicketRow("Proteína:", "${form.proteina}%")
                    }
                    if (form.lactosa.isNotBlank()) {
                        TicketRow("Lactosa:", "${form.lactosa}%")
                    }
                    if (form.sng.isNotBlank()) {
                        TicketRow("SNG:", "${form.sng}%")
                    }
                    if (form.sales.isNotBlank()) {
                        TicketRow("Sales:", "${form.sales}%")
                    }
                    if (form.totalSolidos.isNotBlank()) {
                        TicketRow("Total Sólidos:", "${form.totalSolidos}%")
                    }
                    if (form.puntoCongelacion.isNotBlank()) {
                        TicketRow("Punto Congelación:", "${form.puntoCongelacion} °C")
                    }
                    // ─────────────────────────────────────────────────────────
                    TicketRow("Conductor:", loginState.conductorNombre.ifBlank { "—" })
                    TicketRow(
                        "Ruta:",
                        miRuta.rutaNombre.ifBlank { loginState.ruta.ifBlank { "—" } }
                    )
                    if (form.gpsLat != null && form.gpsLon != null) {
                        TicketRow(
                            "Coordenadas GPS:",
                            "${"%.4f".format(form.gpsLat)}, ${"%.4f".format(form.gpsLon)}",
                            valueColor = CyanAccent
                        )
                    } else {
                        TicketRow("Coordenadas GPS:", "Registrando...", valueColor = TextMuted)
                    }

                    HorizontalDivider(color = Divider, modifier = Modifier.padding(vertical = 10.dp))

                    // Cisterna
                    Row(
                        Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment     = Alignment.CenterVertically
                    ) {
                        Text("Cisterna de Recolección", color = TextMuted, fontSize = 11.sp)
                        Text(
                            "${viewModel.cisternaActual.toInt()}L / ${viewModel.cisternaTotal.toInt()}L",
                            color = GreenPrimary, fontSize = 11.sp, fontWeight = FontWeight.Bold
                        )
                    }
                    Spacer(Modifier.height(6.dp))
                    LinearProgressIndicator(
                        progress   = { viewModel.cisternaProgress },
                        modifier   = Modifier.fillMaxWidth().height(8.dp).clip(RoundedCornerShape(4.dp)),
                        color      = GreenPrimary,
                        trackColor = Divider
                    )
                    Spacer(Modifier.height(4.dp))
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text(
                            "${(viewModel.cisternaProgress * 100).toInt()}% utilizado",
                            color = TextMuted, fontSize = 10.sp
                        )
                        Text(
                            miRuta.rutaNombre.ifBlank { "Huata – Puno" },
                            color = TextMuted, fontSize = 10.sp
                        )
                    }
                }
            }

            Spacer(Modifier.height(20.dp))

            // ── Aviso de estado ────────────────────────────────────────────
            if (estadoLabel == "RECHAZADA") {
                Row(
                    modifier = Modifier.fillMaxWidth()
                        .clip(RoundedCornerShape(10.dp))
                        .background(RedBg)
                        .border(1.dp, RedBorder.copy(0.3f), RoundedCornerShape(10.dp))
                        .padding(14.dp),
                    verticalAlignment = Alignment.Top
                ) {
                    Text("⚠  ", color = RedPrimary, fontSize = 16.sp)
                    Column {
                        Text("Acopio Rechazado", color = RedPrimary,
                            fontSize = 13.sp, fontWeight = FontWeight.Bold)
                        Text(
                            "Este acopio fue marcado como RECHAZADO por exceder el límite de " +
                            "% agua (5%). El administrador será notificado.",
                            color = RedText, fontSize = 11.sp, lineHeight = 16.sp
                        )
                    }
                }
                Spacer(Modifier.height(14.dp))
            }

            // ── Botones ────────────────────────────────────────────────────
            Button(
                onClick  = { viewModel.resetForm(); onNuevoAcopio() },
                modifier = Modifier.fillMaxWidth().height(54.dp),
                shape    = RoundedCornerShape(12.dp),
                colors   = ButtonDefaults.buttonColors(containerColor = GreenPrimary, contentColor = Color.White)
            ) {
                Text("+  NUEVO ACOPIO", fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
            }

            Spacer(Modifier.height(10.dp))

            // ── Botón Imprimir Boleta (Bluetooth) ──────────────────────────
            if (onImprimirBoleta != null) {
                OutlinedButton(
                    onClick  = onImprimirBoleta,
                    modifier = Modifier.fillMaxWidth().height(52.dp),
                    shape    = RoundedCornerShape(12.dp),
                    border   = androidx.compose.foundation.BorderStroke(1.5.dp, CyanAccent.copy(alpha = 0.7f)),
                    colors   = ButtonDefaults.outlinedButtonColors(contentColor = CyanAccent)
                ) {
                    Text("🖨  IMPRIMIR BOLETA (Bluetooth)", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                }
                Spacer(Modifier.height(10.dp))
            }

            OutlinedButton(
                onClick  = onVerHistorial,
                modifier = Modifier.fillMaxWidth().height(52.dp),
                shape    = RoundedCornerShape(12.dp),
                border   = ButtonDefaults.outlinedButtonBorder(true),
                colors   = ButtonDefaults.outlinedButtonColors(contentColor = GreenPrimary)
            ) {
                Text("☰  VER HISTORIAL HOY (${viewModel.totalAcopioSession})", fontSize = 14.sp, fontWeight = FontWeight.Bold)
            }

            Spacer(Modifier.height(28.dp))
        }
    }
}

@Composable
private fun TicketRow(label: String, value: String, valueColor: Color = TextPrimary) {
    Row(
        Modifier.fillMaxWidth().padding(vertical = 4.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment     = Alignment.Top
    ) {
        Text(label, color = TextMuted, fontSize = 12.sp, modifier = Modifier.weight(1f))
        Text(value, color = valueColor, fontSize = 12.sp,
            fontWeight = FontWeight.SemiBold, textAlign = TextAlign.End,
            modifier   = Modifier.weight(1.2f))
    }
}
