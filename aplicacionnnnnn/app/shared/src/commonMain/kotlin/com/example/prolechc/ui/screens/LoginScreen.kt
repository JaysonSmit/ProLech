package com.example.prolechc.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.data.mockConductores
import com.example.prolechc.ui.theme.*
import com.example.prolechc.viewmodel.ProLechViewModel

@Composable
fun LoginScreen(
    viewModel: ProLechViewModel,
    onIniciarRuta: () -> Unit
) {
    val state by viewModel.loginState.collectAsStateWithLifecycle()
    var conductorExp by remember { mutableStateOf(false) }
    var vehiculoExp  by remember { mutableStateOf(false) }
    var rutaExp      by remember { mutableStateOf(false) }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(BgPage)
            .systemBarsPadding()
    ) {
        // ── TopBar verde oscuro ───────────────────────────────────────────
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(SurfaceDark)
                .padding(horizontal = 18.dp, vertical = 14.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Column {
                Text("Control de Acopio", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
                Text("Sistema Móvil Huata", color = Color.White.copy(alpha = 0.6f), fontSize = 11.sp)
            }
            Text("ProLech", color = GreenAccent, fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
        }

        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 22.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(Modifier.height(32.dp))

            // ── Logo ──────────────────────────────────────────────────────
            Box(
                modifier = Modifier
                    .size(96.dp)
                    .clip(CircleShape)
                    .background(GreenBg)
                    .border(2.5.dp, GreenPrimary, CircleShape),
                contentAlignment = Alignment.Center
            ) {
                Text("PL", color = GreenPrimary, fontSize = 30.sp, fontWeight = FontWeight.ExtraBold)
            }
            Spacer(Modifier.height(10.dp))
            Text("ProLech", color = GreenPrimary, fontSize = 24.sp, fontWeight = FontWeight.ExtraBold)
            Text("ACOPIO DIGITAL RURAL", color = TextMuted, fontSize = 10.sp, letterSpacing = 2.sp)

            Spacer(Modifier.height(28.dp))
            HorizontalDivider(color = Divider)
            Spacer(Modifier.height(20.dp))

            // ── Selectores ────────────────────────────────────────────────
            LoginSelectorLabel("CHOFER / TRANSPORTISTA")
            Spacer(Modifier.height(6.dp))
            Box {
                LoginDropCard(value = state.conductorNombre, onClick = { conductorExp = true })
                DropdownMenu(expanded = conductorExp, onDismissRequest = { conductorExp = false },
                    modifier = Modifier.background(BgCard)) {
                    mockConductores.forEachIndexed { i, c ->
                        DropdownMenuItem(
                            text    = { Text(c.nombre, color = TextPrimary, fontSize = 13.sp) },
                            onClick = { viewModel.onConductorChange(i); conductorExp = false }
                        )
                    }
                }
            }

            Spacer(Modifier.height(14.dp))
            LoginSelectorLabel("CAMIÓN / PLACA")
            Spacer(Modifier.height(6.dp))
            Box {
                LoginDropCard(value = state.vehiculo, onClick = { vehiculoExp = true })
                DropdownMenu(expanded = vehiculoExp, onDismissRequest = { vehiculoExp = false },
                    modifier = Modifier.background(BgCard)) {
                    mockConductores.forEachIndexed { i, c ->
                        DropdownMenuItem(
                            text    = { Text(c.vehiculo, color = TextPrimary, fontSize = 13.sp) },
                            onClick = { viewModel.onConductorChange(i); vehiculoExp = false }
                        )
                    }
                }
            }

            Spacer(Modifier.height(14.dp))
            LoginSelectorLabel("RUTA ASIGNADA")
            Spacer(Modifier.height(6.dp))
            Box {
                LoginDropCard(value = state.ruta, onClick = { rutaExp = true })
                DropdownMenu(expanded = rutaExp, onDismissRequest = { rutaExp = false },
                    modifier = Modifier.background(BgCard)) {
                    mockConductores.forEachIndexed { i, c ->
                        DropdownMenuItem(
                            text    = { Text(c.ruta, color = TextPrimary, fontSize = 13.sp) },
                            onClick = { viewModel.onConductorChange(i); rutaExp = false }
                        )
                    }
                }
            }

            Spacer(Modifier.weight(1f))

            // ── Alerta offline ────────────────────────────────────────────
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(10.dp))
                    .background(AmberBg)
                    .border(1.dp, AmberPrimary.copy(alpha = 0.4f), RoundedCornerShape(10.dp))
                    .padding(12.dp),
                verticalAlignment = Alignment.Top
            ) {
                Text("⚠  ", color = AmberPrimary, fontSize = 15.sp)
                Column {
                    Text("Modo Offline Activo", color = AmberText, fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    Text("Puedes registrar acopios sin internet.\nSincroniza antes de salir.",
                        color = AmberText.copy(alpha = 0.85f), fontSize = 11.sp)
                }
            }

            Spacer(Modifier.height(14.dp))

            // ── Botón Iniciar Ruta ────────────────────────────────────────
            Button(
                onClick  = { viewModel.iniciarRuta(); onIniciarRuta() },
                modifier = Modifier.fillMaxWidth().height(56.dp),
                shape    = RoundedCornerShape(12.dp),
                colors   = ButtonDefaults.buttonColors(containerColor = GreenPrimary, contentColor = Color.White)
            ) {
                Text("▶  INICIAR RUTA", fontSize = 17.sp, fontWeight = FontWeight.ExtraBold)
            }

            Spacer(Modifier.height(12.dp))
            Text(
                "Última Sincronización: 07/02/2026 06:12 AM\nProLech Android v1.4.2 · Com Trapel",
                color = TextDim, fontSize = 10.sp, textAlign = TextAlign.Center
            )
            Spacer(Modifier.height(18.dp))
        }
    }
}

@Composable
private fun LoginSelectorLabel(text: String) {
    Text(text, color = TextMuted, fontSize = 11.sp, fontWeight = FontWeight.SemiBold,
        modifier = Modifier.fillMaxWidth())
}

@Composable
private fun LoginDropCard(value: String, onClick: () -> Unit) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(10.dp))
            .background(BgCard)
            .border(1.dp, Divider, RoundedCornerShape(10.dp))
            .clickable(onClick = onClick)
            .padding(horizontal = 14.dp, vertical = 14.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment     = Alignment.CenterVertically
    ) {
        Text(value, color = TextPrimary, fontSize = 13.sp, modifier = Modifier.weight(1f))
        Text("▾", color = TextMuted, fontSize = 16.sp)
    }
}
