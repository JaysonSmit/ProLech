package com.example.prolechc.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
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
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.ui.theme.*
import com.example.prolechc.viewmodel.ProLechViewModel

@Composable
fun RegistroExitosoScreen(
    viewModel: ProLechViewModel,
    onNuevoAcopio: () -> Unit,
    onVerHistorial: () -> Unit
) {
    val form by viewModel.formState.collectAsStateWithLifecycle()

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
            Text("Transacción", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
            Text("ProLech", color = GreenAccent, fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
        }

        Column(
            modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState())
                .padding(horizontal = 20.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(Modifier.height(28.dp))

            // ── Check verde ────────────────────────────────────────────────
            Box(
                modifier = Modifier.size(100.dp).clip(CircleShape)
                    .background(GreenBg).border(3.dp, GreenBorder, CircleShape),
                contentAlignment = Alignment.Center
            ) { Text("✓", color = GreenPrimary, fontSize = 48.sp, fontWeight = FontWeight.Bold) }

            Spacer(Modifier.height(14.dp))
            Text("¡Acopio Registrado!", color = GreenPrimary, fontSize = 24.sp,
                fontWeight = FontWeight.ExtraBold, textAlign = TextAlign.Center)
            Text("DOCUMENTO DIGITAL GENERADO", color = TextMuted, fontSize = 11.sp, letterSpacing = 1.sp)

            Spacer(Modifier.height(22.dp))

            // ── Ticket ─────────────────────────────────────────────────────
            Card(
                modifier  = Modifier.fillMaxWidth(),
                colors    = CardDefaults.cardColors(containerColor = BgCard),
                shape     = RoundedCornerShape(14.dp),
                elevation = CardDefaults.cardElevation(2.dp)
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically) {
                        Text("TICKET N° ${form.ticketNumero}", color = TextMuted, fontSize = 10.sp, fontWeight = FontWeight.SemiBold)
                        Box(Modifier.clip(RoundedCornerShape(4.dp)).background(GreenBg)
                            .padding(horizontal = 8.dp, vertical = 3.dp)) {
                            Text("FIRMA DIGITAL OK", color = GreenPrimary, fontSize = 9.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                    HorizontalDivider(color = Divider, modifier = Modifier.padding(vertical = 10.dp))
                    TicketRow("Productor:",        form.nombreAutocompletado.ifEmpty { "Ganadero Registrado" })
                    TicketRow("Cantidad Recibida:", "${form.litros} Litros")
                    TicketRow("Temperatura:",      "4.2 °C")
                    TicketRow("Calidad:",          if (form.lechRechazada) "RECHAZADA" else "APROBADA",
                        valueColor = if (form.lechRechazada) RedPrimary else GreenPrimary)
                    TicketRow("Fecha/Hora:",       "07/02/2026 08:24 AM")
                    TicketRow("Coordenadas GPS:",  "-15.8322, -70.1042")
                    HorizontalDivider(color = Divider, modifier = Modifier.padding(vertical = 10.dp))
                    Text("Cisterna de Recolección", color = TextMuted, fontSize = 11.sp)
                    Spacer(Modifier.height(6.dp))
                    LinearProgressIndicator(
                        progress   = { viewModel.cisternaProgress },
                        modifier   = Modifier.fillMaxWidth().height(8.dp).clip(RoundedCornerShape(4.dp)),
                        color      = GreenPrimary,
                        trackColor = Divider
                    )
                    Spacer(Modifier.height(4.dp))
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text("${viewModel.cisternaActual.toInt()}L / ${viewModel.cisternaTotal.toInt()}L", color = TextMuted, fontSize = 10.sp)
                        Text("${(viewModel.cisternaProgress * 100).toInt()}%  ·  Huata – Puno", color = TextMuted, fontSize = 10.sp)
                    }
                }
            }

            Spacer(Modifier.height(24.dp))

            Button(
                onClick  = { viewModel.resetForm(); onNuevoAcopio() },
                modifier = Modifier.fillMaxWidth().height(54.dp),
                shape    = RoundedCornerShape(12.dp),
                colors   = ButtonDefaults.buttonColors(containerColor = GreenPrimary, contentColor = Color.White)
            ) { Text("+  NUEVO ACOPIO", fontSize = 16.sp, fontWeight = FontWeight.ExtraBold) }

            Spacer(Modifier.height(10.dp))

            OutlinedButton(
                onClick  = onVerHistorial,
                modifier = Modifier.fillMaxWidth().height(52.dp),
                shape    = RoundedCornerShape(12.dp),
                border   = ButtonDefaults.outlinedButtonBorder(true),
                colors   = ButtonDefaults.outlinedButtonColors(contentColor = GreenPrimary)
            ) { Text("VER HISTORIAL HOY", fontSize = 14.sp, fontWeight = FontWeight.Bold) }

            Spacer(Modifier.height(28.dp))
        }
    }
}

@Composable
private fun TicketRow(label: String, value: String, valueColor: Color = TextPrimary) {
    Row(Modifier.fillMaxWidth().padding(vertical = 4.dp), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, color = TextMuted,   fontSize = 12.sp)
        Text(value, color = valueColor,  fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
    }
}
