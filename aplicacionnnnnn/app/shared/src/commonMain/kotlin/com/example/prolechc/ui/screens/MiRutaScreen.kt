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
import com.example.prolechc.data.repositories.ParadaInfo
import com.example.prolechc.ui.components.BottomNavRoute
import com.example.prolechc.ui.components.ProLechBottomBar
import com.example.prolechc.ui.theme.*
import com.example.prolechc.viewmodel.ProLechViewModel

@Composable
fun MiRutaScreen(
    viewModel: ProLechViewModel? = null,
    onBack: () -> Unit,
    onVerMapa: (() -> Unit)?          = null,   // navega a MapaChoferScreen
    onSolicitarRuta: (() -> Unit)?    = null,   // navega a SolicitarRutaScreen
    onBottomDashboard: () -> Unit,
    onBottomAcopio: () -> Unit,
    onBottomRuta: () -> Unit,
    onBottomSync: () -> Unit
) {
    val miRutaState by viewModel?.miRutaState?.collectAsStateWithLifecycle()
        ?: androidx.compose.runtime.remember { androidx.compose.runtime.mutableStateOf(
            com.example.prolechc.viewmodel.MiRutaState()
        ) }.let { s -> androidx.compose.runtime.derivedStateOf { s.value } }
            .let { androidx.compose.runtime.remember { it }.let {
                // fallback seguro: devuelve MiRutaState mock
                object : androidx.compose.runtime.State<com.example.prolechc.viewmodel.MiRutaState> {
                    override val value get() = com.example.prolechc.viewmodel.MiRutaState()
                }
            }}

    // Paradas reales del backend (vacío si aún no hay ruta asignada)
    val paradасReales = viewModel?.miRutaState?.collectAsStateWithLifecycle()?.value?.paradas
        ?: emptyList()

    val rutaNombre = viewModel?.miRutaState?.collectAsStateWithLifecycle()?.value?.rutaNombre
        ?.ifBlank { "Sin ruta asignada" } ?: "Sin ruta asignada"

    val visitadas  = paradасReales.count { it.atendido }
    val pendientes = paradасReales.count { !it.atendido }

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
                        Text(rutaNombre, color = Color.White.copy(alpha = 0.5f), fontSize = 10.sp)
                    }
                }
                Text("ProLech", color = GreenAccent, fontSize = 14.sp, fontWeight = FontWeight.ExtraBold)
            }

            Column(
                modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState())
                    .padding(horizontal = 16.dp, vertical = 12.dp)
            ) {

                // ── Botón Ver Mapa GPS ─────────────────────────────────────
                Button(
                    onClick  = { onVerMapa?.invoke() },
                    modifier = Modifier.fillMaxWidth().height(56.dp),
                    shape    = RoundedCornerShape(12.dp),
                    colors   = ButtonDefaults.buttonColors(
                        containerColor = SurfaceDark,
                        contentColor   = Color.White
                    )
                ) {
                    Text("◎", fontSize = 18.sp)
                    Spacer(Modifier.width(10.dp))
                    Column {
                        Text("VER MAPA GPS", fontSize = 13.sp, fontWeight = FontWeight.ExtraBold)
                        Text("Inicia rastreo de ruta en tiempo real",
                            fontSize = 9.sp, color = Color.White.copy(alpha = 0.6f))
                    }
                    Spacer(Modifier.weight(1f))
                    Text("→", fontSize = 16.sp, color = GreenAccent)
                }

                Spacer(Modifier.height(10.dp))

                // ── Botón Solicitar Cambio de Ruta ─────────────────────────
                Button(
                    onClick  = { onSolicitarRuta?.invoke() },
                    modifier = Modifier.fillMaxWidth().height(52.dp),
                    shape    = RoundedCornerShape(12.dp),
                    colors   = ButtonDefaults.buttonColors(
                        containerColor = AmberBg,
                        contentColor   = AmberText
                    )
                ) {
                    Text("⇄", fontSize = 18.sp)
                    Spacer(Modifier.width(10.dp))
                    Text("SOLICITAR CAMBIO DE RUTA",
                        fontSize = 13.sp, fontWeight = FontWeight.ExtraBold)
                    Spacer(Modifier.weight(1f))
                    Text("→", fontSize = 16.sp)
                }

                Spacer(Modifier.height(16.dp))

                // ── Resumen de ruta ────────────────────────────────────────
                Row(
                    modifier = Modifier.fillMaxWidth().clip(RoundedCornerShape(10.dp))
                        .background(BgCard).border(1.dp, Divider, RoundedCornerShape(10.dp))
                        .padding(14.dp),
                    horizontalArrangement = Arrangement.SpaceEvenly
                ) {
                    RutaStatItem("Paradas",    "${paradасReales.size}", TextPrimary)
                    Box(Modifier.width(1.dp).height(28.dp).background(Divider))
                    RutaStatItem("Visitadas",  "$visitadas",  GreenPrimary)
                    Box(Modifier.width(1.dp).height(28.dp).background(Divider))
                    RutaStatItem("Pendientes", "$pendientes", AmberPrimary)
                }

                Spacer(Modifier.height(16.dp))

                Text("PARADAS DE LA RUTA", color = TextMuted, fontSize = 10.sp,
                    letterSpacing = 1.sp, fontWeight = FontWeight.SemiBold)
                Spacer(Modifier.height(8.dp))

                // ── Estado de carga ────────────────────────────────────────
                val isLoading = viewModel?.miRutaState?.collectAsStateWithLifecycle()?.value?.isLoading ?: false
                val errorMsg  = viewModel?.miRutaState?.collectAsStateWithLifecycle()?.value?.error

                if (isLoading) {
                    Box(Modifier.fillMaxWidth().padding(24.dp), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = GreenPrimary)
                    }
                } else if (paradасReales.isNotEmpty()) {
                    // ── Paradas reales del backend ─────────────────────────
                    paradасReales.forEachIndexed { index, parada ->
                        ParadaCard(parada = parada, esSiguiente = !parada.atendido &&
                            paradасReales.indexOfFirst { !it.atendido } == index)
                        Spacer(Modifier.height(8.dp))
                    }
                } else {
                    // ── Sin ruta asignada aún ─────────────────────────────
                    if (errorMsg != null) {
                        Row(
                            modifier = Modifier.fillMaxWidth()
                                .clip(RoundedCornerShape(10.dp))
                                .background(AmberBg)
                                .padding(14.dp)
                        ) {
                            Text("⚠  ", color = AmberPrimary)
                            Text(errorMsg, color = AmberText, fontSize = 12.sp)
                        }
                        Spacer(Modifier.height(12.dp))
                    }
                    Column(
                        modifier = Modifier.fillMaxWidth().padding(vertical = 24.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text("🗺", fontSize = 36.sp)
                        Spacer(Modifier.height(8.dp))
                        Text("Aún no tienes una ruta asignada", color = TextPrimary,
                            fontSize = 14.sp, fontWeight = FontWeight.SemiBold)
                        Text("El administrador te asignará los productores a visitar.",
                            color = TextMuted, fontSize = 12.sp)
                    }
                }

                Spacer(Modifier.height(16.dp))
            }
        }
    }
}

// ── Parada con datos reales del backend ────────────────────────────────────────

@Composable
private fun ParadaCard(parada: ParadaInfo, esSiguiente: Boolean) {
    val (bgColor, borderColor, iconColor, iconText) = when {
        parada.atendido -> Tuple4(GreenBg,   GreenBorder,  GreenPrimary, "✓")
        esSiguiente     -> Tuple4(CyanBg,    CyanAccent,   CyanAccent,   "●")
        else            -> Tuple4(BgCardAlt, Divider,      TextMuted,    "○")
    }

    Row(
        modifier = Modifier.fillMaxWidth()
            .clip(RoundedCornerShape(10.dp))
            .background(bgColor)
            .border(1.dp, borderColor.copy(alpha = 0.4f), RoundedCornerShape(10.dp))
            .padding(12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Box(
            modifier = Modifier.size(36.dp).clip(RoundedCornerShape(8.dp))
                .background(if (parada.atendido) GreenPrimary else borderColor.copy(0.15f)),
            contentAlignment = Alignment.Center
        ) {
            Text(iconText, color = if (parada.atendido) Color.White else iconColor,
                fontSize = 15.sp, fontWeight = FontWeight.ExtraBold)
        }
        Spacer(Modifier.width(10.dp))
        Column(Modifier.weight(1f)) {
            Text(parada.ganaderoNombre.ifBlank { "Ganadero" },
                color = TextPrimary, fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
            Text("DNI: ${parada.ganaderoNii}${if (parada.direccion != null) "  ·  ${parada.direccion}" else ""}",
                color = TextSecondary, fontSize = 10.sp)
            if (esSiguiente) {
                Text("→ Próxima parada", color = CyanAccent, fontSize = 10.sp, fontWeight = FontWeight.Bold)
            }
        }
        if (parada.atendido) {
            Box(Modifier.clip(RoundedCornerShape(4.dp)).background(GreenBg)
                .padding(horizontal = 8.dp, vertical = 4.dp)) {
                Text("Visitado", color = GreenPrimary, fontSize = 10.sp, fontWeight = FontWeight.Bold)
            }
        }
    }
}

// ── Helper tuple ──────────────────────────────────────────────────────────────

private data class Tuple4(val a: Color, val b: Color, val c: Color, val d: String)

@Composable
private fun RutaStatItem(label: String, value: String, color: Color) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(value, color = color,     fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
        Text(label, color = TextMuted, fontSize = 9.sp)
    }
}
