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
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.prolechc.ui.components.BottomNavRoute
import com.example.prolechc.ui.components.ProLechBottomBar
import com.example.prolechc.ui.theme.*

@Composable
fun MiRutaScreen(
    onBack: () -> Unit,
    onBottomDashboard: () -> Unit,
    onBottomAcopio: () -> Unit,
    onBottomRuta: () -> Unit,
    onBottomSync: () -> Unit
) {
    val paradas = listOf(
        Parada("✓", "Estancia El Sol",      "Juan Quispe C.",    "06:14 AM  ·  45.5L",  GreenPrimary, GreenBg,  true),
        Parada("✓", "Estancia Altiplano",   "Martina Choque F.", "06:45 AM  ·  120.0L", GreenPrimary, GreenBg,  true),
        Parada("●", "La Rinconada",         "Zenón Mamani Q.",   "Próxima parada",       CyanAccent,   CyanBg,   false),
        Parada("○", "Estancia Junín",       "Pedro Huaman M.",   "Pendiente",            TextMuted,    BgCardAlt, false),
        Parada("○", "Hacienda Norte",       "Sofía Colque B.",   "Pendiente",            TextMuted,    BgCardAlt, false),
    )

    Scaffold(
        containerColor = BgPage,
        bottomBar = {
            ProLechBottomBar(
                current     = BottomNavRoute.RUTA,
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
                modifier = Modifier.fillMaxWidth().background(SurfaceDark)
                    .padding(horizontal = 16.dp, vertical = 12.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment     = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("← ", color = GreenAccent, fontSize = 18.sp,
                        modifier = Modifier.clickable(onClick = onBack))
                    Column {
                        Text("Mi Ruta", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold)
                        Text("Huata Centro – Sectores", color = Color.White.copy(alpha = 0.5f), fontSize = 10.sp)
                    }
                }
                Text("ProLech", color = GreenAccent, fontSize = 14.sp, fontWeight = FontWeight.ExtraBold)
            }

            Column(
                modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp)
            ) {
                // ── Mapa simulado ──────────────────────────────────────────
                Box(
                    modifier = Modifier.fillMaxWidth().height(200.dp)
                        .clip(RoundedCornerShape(14.dp))
                        .background(Color(0xFFD0E8D0))
                        .border(1.dp, GreenBorder.copy(0.4f), RoundedCornerShape(14.dp)),
                    contentAlignment = Alignment.Center
                ) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("◎", color = GreenPrimary, fontSize = 48.sp)
                        Text("Mapa de Ruta GPS", color = GreenDark, fontSize = 16.sp, fontWeight = FontWeight.Bold)
                        Text("·15.632, -70.104  ·  Huata Centro", color = TextSecondary, fontSize = 11.sp)
                    }
                }

                Spacer(Modifier.height(16.dp))

                // ── Resumen de ruta ────────────────────────────────────────
                Row(
                    modifier = Modifier.fillMaxWidth().clip(RoundedCornerShape(10.dp))
                        .background(BgCard).border(1.dp, Divider, RoundedCornerShape(10.dp))
                        .padding(14.dp),
                    horizontalArrangement = Arrangement.SpaceEvenly
                ) {
                    RutaStatItem("Paradas",   "5",       TextPrimary)
                    Box(Modifier.width(1.dp).height(28.dp).background(Divider))
                    RutaStatItem("Visitadas", "2",       GreenPrimary)
                    Box(Modifier.width(1.dp).height(28.dp).background(Divider))
                    RutaStatItem("Pendientes","3",       AmberPrimary)
                    Box(Modifier.width(1.dp).height(28.dp).background(Divider))
                    RutaStatItem("Distancia", "46 km",   CyanAccent)
                }

                Spacer(Modifier.height(16.dp))

                Text("PARADAS DE LA RUTA", color = TextMuted, fontSize = 10.sp,
                    letterSpacing = 1.sp, fontWeight = FontWeight.SemiBold)
                Spacer(Modifier.height(8.dp))

                // ── Paradas ────────────────────────────────────────────────
                paradas.forEach { parada ->
                    Row(
                        modifier = Modifier.fillMaxWidth()
                            .clip(RoundedCornerShape(10.dp))
                            .background(parada.bg)
                            .border(1.dp, parada.color.copy(alpha = 0.3f), RoundedCornerShape(10.dp))
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Box(
                            modifier = Modifier.size(36.dp).clip(RoundedCornerShape(8.dp))
                                .background(if (parada.visitado) GreenBg else BgCardAlt),
                            contentAlignment = Alignment.Center
                        ) { Text(parada.icon, color = parada.color, fontSize = 16.sp, fontWeight = FontWeight.Bold) }
                        Spacer(Modifier.width(10.dp))
                        Column(Modifier.weight(1f)) {
                            Text(parada.lugar,    color = TextPrimary,   fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                            Text(parada.ganadero, color = TextSecondary, fontSize = 11.sp)
                            Text(parada.detalle,  color = TextMuted,     fontSize = 10.sp)
                        }
                        if (parada.visitado) {
                            Box(Modifier.clip(RoundedCornerShape(4.dp)).background(GreenBg)
                                .padding(horizontal = 8.dp, vertical = 4.dp)) {
                                Text("Visitado", color = GreenPrimary, fontSize = 10.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                    Spacer(Modifier.height(8.dp))
                }
            }
        }
    }
}

private data class Parada(
    val icon: String, val lugar: String, val ganadero: String,
    val detalle: String, val color: Color, val bg: Color, val visitado: Boolean
)

@Composable
private fun RutaStatItem(label: String, value: String, color: Color) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(value, color = color,    fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
        Text(label, color = TextMuted, fontSize = 9.sp)
    }
}
