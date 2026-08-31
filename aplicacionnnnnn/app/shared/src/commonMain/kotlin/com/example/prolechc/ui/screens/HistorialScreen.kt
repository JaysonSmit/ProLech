package com.example.prolechc.ui.screens

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
import com.example.prolechc.data.*
import com.example.prolechc.ui.components.BottomNavRoute
import com.example.prolechc.ui.components.ProLechBottomBar
import com.example.prolechc.ui.theme.*

@Composable
fun HistorialScreen(
    onBack: () -> Unit,
    onNuevoAcopio: () -> Unit,
    onBottomDashboard: () -> Unit,
    onBottomAcopio: () -> Unit,
    onBottomRuta: () -> Unit,
    onBottomSync: () -> Unit
) {
    var query by remember { mutableStateOf("") }
    val filtered = remember(query) {
        if (query.isEmpty()) mockAcopios
        else mockAcopios.filter {
            it.ganaderoNombre.contains(query, ignoreCase = true) || it.dniGanadero.contains(query)
        }
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
                modifier = Modifier.fillMaxWidth().background(SurfaceDark)
                    .padding(horizontal = 16.dp, vertical = 12.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment     = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("← ", color = GreenAccent, fontSize = 18.sp,
                        modifier = Modifier.clickable(onClick = onBack))
                    Column {
                        Text("Historial de Acopio", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold)
                        Text("Sábado, 7 de Febrero", color = Color.White.copy(alpha = 0.5f), fontSize = 10.sp)
                    }
                }
                Text("ProLech", color = GreenAccent, fontSize = 14.sp, fontWeight = FontWeight.ExtraBold)
            }

            // ── Stats Row ──────────────────────────────────────────────────
            Row(
                modifier = Modifier.fillMaxWidth().background(BgCard)
                    .padding(horizontal = 16.dp, vertical = 12.dp),
                horizontalArrangement = Arrangement.SpaceEvenly
            ) {
                HistStatItem("TOTAL LITROS", "${"%.1f".format(totalLitrosHoy)} L", GreenPrimary)
                Box(Modifier.width(1.dp).height(32.dp).background(Divider))
                HistStatItem("PRODUCTORES", "${mockAcopios.size} Acopios", TextPrimary)
                Box(Modifier.width(1.dp).height(32.dp).background(Divider))
                HistStatItem("PROM. TEMP.", "4.5 °C", AmberPrimary)
            }

            // ── Buscador ───────────────────────────────────────────────────
            Row(
                modifier = Modifier.fillMaxWidth().padding(horizontal = 14.dp, vertical = 10.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                OutlinedTextField(
                    value         = query,
                    onValueChange = { query = it },
                    placeholder   = { Text("Buscar por ganadero o DNI...", color = TextDim, fontSize = 13.sp) },
                    modifier      = Modifier.weight(1f),
                    singleLine    = true,
                    colors        = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor      = GreenBorder,
                        unfocusedBorderColor    = Divider,
                        focusedContainerColor   = BgCard,
                        unfocusedContainerColor = BgCard,
                        focusedTextColor        = TextPrimary,
                        unfocusedTextColor      = TextPrimary,
                        cursorColor             = GreenPrimary
                    )
                )
                Box(
                    modifier = Modifier.size(52.dp).clip(RoundedCornerShape(10.dp))
                        .background(BgCard).border(1.dp, Divider, RoundedCornerShape(10.dp)),
                    contentAlignment = Alignment.Center
                ) { Text("⊞", color = TextMuted, fontSize = 20.sp) }
            }

            // ── Lista ──────────────────────────────────────────────────────
            LazyColumn(
                modifier = Modifier.weight(1f).padding(horizontal = 14.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(filtered, key = { it.id }) { acopio ->
                    HistorialCard(acopio = acopio)
                }
                item { Spacer(Modifier.height(8.dp)) }
            }
        }
    }
}

@Composable
private fun HistorialCard(acopio: AcopioRecord) {
    val (borderColor, bgColor, badgeText, iconText) = when (acopio.estado) {
        EstadoAcopio.APROBADO  -> Quad(GreenBorder, GreenBg,  "APROBADO",  "✓")
        EstadoAcopio.RECHAZADO -> Quad(RedBorder,   RedBg,    "RECHAZADO", "✗")
        EstadoAcopio.OBSERVADO -> Quad(AmberPrimary, AmberBg, "OBSERVADO", "!")
    }
    val textColor = when (acopio.estado) {
        EstadoAcopio.APROBADO  -> GreenPrimary
        EstadoAcopio.RECHAZADO -> RedPrimary
        EstadoAcopio.OBSERVADO -> AmberPrimary
    }

    Card(
        modifier  = Modifier.fillMaxWidth()
            .border(1.dp, borderColor.copy(alpha = 0.5f), RoundedCornerShape(12.dp)),
        colors    = CardDefaults.cardColors(containerColor = BgCard),
        shape     = RoundedCornerShape(12.dp),
        elevation = CardDefaults.cardElevation(1.dp)
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Ícono estado
            Box(
                modifier = Modifier.size(44.dp).clip(RoundedCornerShape(10.dp)).background(bgColor),
                contentAlignment = Alignment.Center
            ) {
                Text(iconText, color = textColor, fontSize = 20.sp, fontWeight = FontWeight.ExtraBold)
            }
            Spacer(Modifier.width(12.dp))
            Column(Modifier.weight(1f)) {
                Text(acopio.ganaderoNombre, color = TextPrimary, fontSize = 14.sp, fontWeight = FontWeight.Bold)
                Text("DNI: ${acopio.dniGanadero}  ·  Hora: ${acopio.hora}", color = TextMuted, fontSize = 10.sp)
                Spacer(Modifier.height(5.dp))
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    Box(Modifier.clip(RoundedCornerShape(4.dp)).background(bgColor).padding(horizontal = 7.dp, vertical = 3.dp)) {
                        Text(badgeText, color = textColor, fontSize = 9.sp, fontWeight = FontWeight.ExtraBold)
                    }
                    Text("${acopio.porcentajeAgua}% Agua  ·  ${acopio.acidezPh} pH",
                        color = TextMuted, fontSize = 10.sp)
                }
            }
            Column(horizontalAlignment = Alignment.End) {
                Text("${acopio.litros} L", color = GreenPrimary, fontSize = 18.sp, fontWeight = FontWeight.ExtraBold)
                Spacer(Modifier.height(4.dp))
                Text("Ver Coordenadas", color = CyanAccent, fontSize = 10.sp,
                    modifier = Modifier.clickable {})
            }
        }
    }
}

@Composable
private fun HistStatItem(label: String, value: String, valueColor: Color) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(value, color = valueColor, fontSize = 17.sp, fontWeight = FontWeight.ExtraBold)
        Text(label, color = TextMuted,  fontSize = 9.sp,  letterSpacing = 0.5.sp)
    }
}

private data class Quad<A, B, C, D>(val a: A, val b: B, val c: C, val d: D)
private operator fun <A,B,C,D> Quad<A,B,C,D>.component1() = a
private operator fun <A,B,C,D> Quad<A,B,C,D>.component2() = b
private operator fun <A,B,C,D> Quad<A,B,C,D>.component3() = c
private operator fun <A,B,C,D> Quad<A,B,C,D>.component4() = d
