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
import com.example.prolechc.viewmodel.ProLechViewModel
import androidx.compose.runtime.collectAsState
import kotlinx.coroutines.flow.MutableStateFlow

@Composable
fun HistorialScreen(
    viewModel: ProLechViewModel? = null,
    onBack: () -> Unit,
    onNuevoAcopio: () -> Unit,
    onVerDetalle: ((Int) -> Unit)? = null,
    onBottomDashboard: () -> Unit,
    onBottomAcopio: () -> Unit,
    onBottomRuta: () -> Unit,
    onBottomSync: () -> Unit
) {
    // Usa los acopios reales de la sesión vía StateFlow reactivo
    val sessionAcopios by (viewModel?.acopiosSession
        ?: kotlinx.coroutines.flow.MutableStateFlow(emptyList<AcopioRecord>()))
        .collectAsState()

    // Solo muestra mocks si no hay ViewModel (modo preview/diseño)
    val acopios = if (viewModel == null) mockAcopios else sessionAcopios

    // Recargar historial desde servidor al entrar a esta pantalla
    LaunchedEffect(Unit) {
        viewModel?.cargarHistorial()
    }

    var query      by remember { mutableStateOf("") }
    var filtroEstado by remember { mutableStateOf<EstadoAcopio?>(null) }

    val filtered = remember(query, filtroEstado, acopios) {
        acopios.filter { a ->
            val matchQuery = query.isEmpty() ||
                a.ganaderoNombre.contains(query, ignoreCase = true) ||
                a.dniGanadero.contains(query)
            val matchEstado = filtroEstado == null || a.estado == filtroEstado
            matchQuery && matchEstado
        }
    }

    // Estadísticas dinámicas
    val totalLitros   = acopios.sumOf { it.litros }
    val totalAcopios  = acopios.size
    val aprobados     = acopios.count { it.estado == EstadoAcopio.APROBADO }
    val rechazados    = acopios.count { it.estado == EstadoAcopio.RECHAZADO }
    val pendientesEnv = acopios.count { it.syncEstado == EstadoSync.PENDIENTE }

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
                        Text("Historial de Acopio", color = Color.White,
                            fontSize = 16.sp, fontWeight = FontWeight.Bold)
                        Text(
                            "$totalAcopios acopios  ·  ${"%.1f".format(totalLitros)} L recogidos",
                            color = Color.White.copy(alpha = 0.5f), fontSize = 10.sp
                        )
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
                HistStatItem("TOTAL LITROS",    "${"%.1f".format(totalLitros)} L", GreenPrimary)
                Box(Modifier.width(1.dp).height(32.dp).background(Divider))
                HistStatItem("PRODUCTORES",     "$totalAcopios Acopios",           TextPrimary)
                Box(Modifier.width(1.dp).height(32.dp).background(Divider))
                HistStatItem("APROBADOS",       "$aprobados",                      GreenPrimary)
                Box(Modifier.width(1.dp).height(32.dp).background(Divider))
                HistStatItem("RECHAZADOS",      "$rechazados",                     RedPrimary)
            }

            // ── Barra de pendientes de sincronización ──────────────────────
            if (pendientesEnv > 0) {
                Row(
                    modifier = Modifier.fillMaxWidth()
                        .background(AmberBg)
                        .padding(horizontal = 16.dp, vertical = 8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("⏫  ", color = AmberPrimary, fontSize = 13.sp)
                    Text(
                        "$pendientesEnv acopio${if (pendientesEnv > 1) "s" else ""} pendiente${if (pendientesEnv > 1) "s" else ""} de sincronizar",
                        color = AmberText, fontSize = 12.sp, fontWeight = FontWeight.SemiBold,
                        modifier = Modifier.weight(1f)
                    )
                    Text("Sincronizar →", color = AmberPrimary, fontSize = 11.sp,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.clickable(onClick = onBottomSync))
                }
            }

            // ── Buscador ───────────────────────────────────────────────────
            Row(
                modifier = Modifier.fillMaxWidth().padding(horizontal = 14.dp, vertical = 10.dp),
                verticalAlignment     = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                OutlinedTextField(
                    value         = query,
                    onValueChange = { query = it },
                    placeholder   = {
                        Text("Buscar por nombre o DNI...", color = TextDim, fontSize = 13.sp)
                    },
                    modifier  = Modifier.weight(1f),
                    singleLine = true,
                    leadingIcon = { Text("🔍", fontSize = 14.sp, modifier = Modifier.padding(start = 8.dp)) },
                    colors    = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor      = GreenBorder,
                        unfocusedBorderColor    = Divider,
                        focusedContainerColor   = BgCard,
                        unfocusedContainerColor = BgCard,
                        focusedTextColor        = TextPrimary,
                        unfocusedTextColor      = TextPrimary,
                        cursorColor             = GreenPrimary
                    )
                )
                // Botón nuevo acopio
                Box(
                    modifier = Modifier.size(52.dp).clip(RoundedCornerShape(10.dp))
                        .background(GreenPrimary).clickable(onClick = onNuevoAcopio),
                    contentAlignment = Alignment.Center
                ) {
                    Text("+", color = Color.White, fontSize = 22.sp, fontWeight = FontWeight.Bold)
                }
            }

            // ── Chips de filtro por estado ─────────────────────────────────
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 14.dp)
                    .padding(bottom = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                FiltroChip("Todos",    filtroEstado == null)             { filtroEstado = null }
                FiltroChip("✓ Ok",     filtroEstado == EstadoAcopio.APROBADO,  GreenPrimary) { filtroEstado = if (filtroEstado == EstadoAcopio.APROBADO) null else EstadoAcopio.APROBADO }
                FiltroChip("! Obs.",   filtroEstado == EstadoAcopio.OBSERVADO, AmberPrimary) { filtroEstado = if (filtroEstado == EstadoAcopio.OBSERVADO) null else EstadoAcopio.OBSERVADO }
                FiltroChip("✗ Rech.",  filtroEstado == EstadoAcopio.RECHAZADO, RedPrimary)   { filtroEstado = if (filtroEstado == EstadoAcopio.RECHAZADO) null else EstadoAcopio.RECHAZADO }
            }

            // ── Lista de acopios ───────────────────────────────────────────
            if (filtered.isEmpty()) {
                Box(
                    modifier         = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("🔍", fontSize = 40.sp)
                        Spacer(Modifier.height(8.dp))
                        Text("Sin resultados", color = TextMuted, fontSize = 14.sp)
                        Text(
                            if (query.isNotEmpty()) "No hay acopios con \"$query\""
                            else "No hay acopios con este filtro",
                            color = TextDim, fontSize = 11.sp
                        )
                    }
                }
            } else {
                LazyColumn(
                    modifier            = Modifier.weight(1f).padding(horizontal = 14.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(filtered, key = { it.id }) { acopio ->
                        HistorialCard(
                            acopio    = acopio,
                            onClick   = { onVerDetalle?.invoke(acopio.id) }
                        )
                    }
                    item { Spacer(Modifier.height(8.dp)) }
                }
            }
        }
    }
}

// ── HistorialCard completa ─────────────────────────────────────────────────────

@Composable
private fun HistorialCard(acopio: AcopioRecord, onClick: () -> Unit = {}) {
    val (borderColor, bgColor, badgeText, iconText) = when (acopio.estado) {
        EstadoAcopio.APROBADO  -> Quad(GreenBorder,  GreenBg, "APROBADO",  "✓")
        EstadoAcopio.RECHAZADO -> Quad(RedBorder,    RedBg,   "RECHAZADO", "✗")
        EstadoAcopio.OBSERVADO -> Quad(AmberPrimary, AmberBg, "OBSERVADO", "!")
    }
    val textColor = when (acopio.estado) {
        EstadoAcopio.APROBADO  -> GreenPrimary
        EstadoAcopio.RECHAZADO -> RedPrimary
        EstadoAcopio.OBSERVADO -> AmberPrimary
    }

    Card(
        modifier  = Modifier.fillMaxWidth()
            .border(1.dp, borderColor.copy(alpha = 0.5f), RoundedCornerShape(12.dp))
            .clickable(onClick = onClick),
        colors    = CardDefaults.cardColors(containerColor = BgCard),
        shape     = RoundedCornerShape(12.dp),
        elevation = CardDefaults.cardElevation(1.dp)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            // Fila principal
            Row(
                modifier          = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Ícono de estado
                Box(
                    modifier         = Modifier.size(44.dp).clip(RoundedCornerShape(10.dp)).background(bgColor),
                    contentAlignment = Alignment.Center
                ) {
                    Text(iconText, color = textColor, fontSize = 20.sp, fontWeight = FontWeight.ExtraBold)
                }
                Spacer(Modifier.width(12.dp))

                // Nombre + datos
                Column(Modifier.weight(1f)) {
                    Text(acopio.ganaderoNombre, color = TextPrimary,
                        fontSize = 14.sp, fontWeight = FontWeight.Bold)
                    Row(
                        verticalAlignment     = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        if (acopio.dniGanadero.isNotBlank()) {
                            Text("DNI: ${acopio.dniGanadero}", color = TextMuted, fontSize = 10.sp)
                            Text("·", color = TextDim, fontSize = 10.sp)
                        }
                        Text("Hora: ${acopio.hora}", color = TextMuted, fontSize = 10.sp)
                    }
                    Spacer(Modifier.height(5.dp))
                    Row(
                        verticalAlignment     = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        // Badge estado
                        Box(
                            Modifier.clip(RoundedCornerShape(4.dp)).background(bgColor)
                                .padding(horizontal = 7.dp, vertical = 3.dp)
                        ) {
                            Text(badgeText, color = textColor, fontSize = 9.sp,
                                fontWeight = FontWeight.ExtraBold)
                        }
                        // Datos Lactoscan
                        if (acopio.porcentajeAgua > 0) {
                            Text("${acopio.porcentajeAgua}% Agua", color = TextMuted, fontSize = 10.sp)
                        }
                        if (acopio.acidezPh > 0) {
                            Text("·  ${acopio.acidezPh} pH", color = TextMuted, fontSize = 10.sp)
                        }
                    }
                }

                // Litros + sync
                Column(horizontalAlignment = Alignment.End) {
                    Text(
                        "${acopio.litros} L",
                        color = GreenPrimary, fontSize = 18.sp, fontWeight = FontWeight.ExtraBold
                    )
                    Spacer(Modifier.height(2.dp))
                    // Badge de sincronización
                    val (syncBg, syncColor, syncLabel) = when (acopio.syncEstado) {
                        EstadoSync.ENVIADO   -> Triple(GreenBg,  GreenPrimary, "✓ Enviado")
                        EstadoSync.ENVIANDO  -> Triple(CyanBg,   CyanAccent,   "↑ Enviando")
                        EstadoSync.PENDIENTE -> Triple(AmberBg,  AmberPrimary, "⏳ Pendiente")
                    }
                    Box(
                        Modifier.clip(RoundedCornerShape(4.dp)).background(syncBg)
                            .padding(horizontal = 5.dp, vertical = 2.dp)
                    ) {
                        Text(syncLabel, color = syncColor, fontSize = 9.sp, fontWeight = FontWeight.SemiBold)
                    }
                }
            }

            // Ticket + GPS si están disponibles
            if (acopio.ticketNumero.isNotBlank() || acopio.gpsLat != null) {
                HorizontalDivider(color = Divider, modifier = Modifier.padding(vertical = 8.dp))
                Row(
                    modifier              = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment     = Alignment.CenterVertically
                ) {
                    if (acopio.ticketNumero.isNotBlank()) {
                        Text("# ${acopio.ticketNumero}", color = TextMuted, fontSize = 10.sp)
                    }
                    if (acopio.gpsLat != null && acopio.gpsLon != null) {
                        Text(
                            "📍 ${"%.4f".format(acopio.gpsLat)}, ${"%.4f".format(acopio.gpsLon)}",
                            color  = CyanAccent,
                            fontSize = 10.sp,
                            modifier = Modifier.clickable { /* futuro: abrir en mapa */ }
                        )
                    }
                }
            }
        }
    }
}

// ── Chips de filtro ────────────────────────────────────────────────────────────

@Composable
private fun FiltroChip(
    label: String,
    activo: Boolean,
    color: Color = GreenPrimary,
    onClick: () -> Unit
) {
    Box(
        modifier = Modifier
            .clip(RoundedCornerShape(20.dp))
            .background(if (activo) color.copy(alpha = 0.15f) else BgCard)
            .border(1.dp, if (activo) color.copy(alpha = 0.6f) else Divider, RoundedCornerShape(20.dp))
            .clickable(onClick = onClick)
            .padding(horizontal = 12.dp, vertical = 6.dp)
    ) {
        Text(label, color = if (activo) color else TextMuted,
            fontSize = 11.sp, fontWeight = if (activo) FontWeight.Bold else FontWeight.Normal)
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

@Composable
private fun HistStatItem(label: String, value: String, valueColor: Color) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(value, color = valueColor, fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
        Text(label, color = TextMuted,  fontSize = 9.sp,  letterSpacing = 0.5.sp)
    }
}
