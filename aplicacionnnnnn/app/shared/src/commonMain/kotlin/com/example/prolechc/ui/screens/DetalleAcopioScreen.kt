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
import com.example.prolechc.data.AcopioRecord
import com.example.prolechc.data.EstadoAcopio
import com.example.prolechc.data.EstadoSync
import com.example.prolechc.ui.theme.*

// ══════════════════════════════════════════════════════════════════════════════
//  DetalleAcopioScreen
//
//  Muestra todos los datos de un acopio registrado: ganadero, litros,
//  parámetros LACTOMAT completos y estado de calidad.
//  Incluye botón para exportar la boleta como PDF.
// ══════════════════════════════════════════════════════════════════════════════

@Composable
fun DetalleAcopioScreen(
    acopio: AcopioRecord,
    onBack: () -> Unit,
    onExportarPdf: (() -> Unit)? = null
) {
    val (borderColor, bgColor, badgeText, iconText) = when (acopio.estado) {
        EstadoAcopio.APROBADO  -> Quad(GreenBorder,  GreenBg, "APROBADO",  "✓")
        EstadoAcopio.RECHAZADO -> Quad(RedBorder,    RedBg,   "RECHAZADO", "✗")
        EstadoAcopio.OBSERVADO -> Quad(AmberPrimary, AmberBg, "OBSERVADO", "!")
    }
    val estadoColor = when (acopio.estado) {
        EstadoAcopio.APROBADO  -> GreenPrimary
        EstadoAcopio.RECHAZADO -> RedPrimary
        EstadoAcopio.OBSERVADO -> AmberPrimary
    }

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
                    color    = GreenAccent,
                    fontSize = 20.sp,
                    modifier = Modifier.clickable(onClick = onBack)
                )
                Column(Modifier.weight(1f)) {
                    Text(
                        "Detalle del Acopio",
                        color      = Color.White,
                        fontSize   = 17.sp,
                        fontWeight = FontWeight.Bold
                    )
                    Text(
                        acopio.ticketNumero,
                        color    = GreenAccent.copy(alpha = 0.8f),
                        fontSize = 11.sp
                    )
                }
                // Badge de estado
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(6.dp))
                        .background(bgColor)
                        .border(1.dp, borderColor.copy(alpha = 0.5f), RoundedCornerShape(6.dp))
                        .padding(horizontal = 10.dp, vertical = 5.dp)
                ) {
                    Text(
                        "$iconText  $badgeText",
                        color      = estadoColor,
                        fontSize   = 11.sp,
                        fontWeight = FontWeight.ExtraBold
                    )
                }
            }

            // ── Contenido scrollable ───────────────────────────────────────
            Column(
                modifier = Modifier
                    .weight(1f)
                    .verticalScroll(rememberScrollState())
                    .padding(horizontal = 16.dp, vertical = 14.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {

                // ── Sección: Productor ─────────────────────────────────────
                DetalleSeccion(titulo = "👤  PRODUCTOR") {
                    DetalleFilaItem("Nombre", acopio.ganaderoNombre.ifBlank { "—" })
                    if (acopio.dniGanadero.isNotBlank())
                        DetalleFilaItem("DNI", acopio.dniGanadero)
                }

                // ── Sección: Registro ──────────────────────────────────────
                DetalleSeccion(titulo = "📋  REGISTRO") {
                    DetalleFilaItem("Ticket", acopio.ticketNumero)
                    DetalleFilaItem("Hora de Acopio", acopio.hora)
                    DetalleFilaItem("Litros Recibidos", "${"%.1f".format(acopio.litros)} L",
                        valueColor = GreenPrimary)
                    DetalleFilaItem(
                        "Sincronización",
                        when (acopio.syncEstado) {
                            EstadoSync.ENVIADO   -> "✓ Enviado al servidor"
                            EstadoSync.ENVIANDO  -> "⏫ Enviando..."
                            EstadoSync.PENDIENTE -> "⏳ Pendiente"
                        },
                        valueColor = when (acopio.syncEstado) {
                            EstadoSync.ENVIADO   -> GreenPrimary
                            EstadoSync.ENVIANDO  -> AmberPrimary
                            EstadoSync.PENDIENTE -> TextMuted
                        }
                    )
                }

                // ── Sección: Calidad / LACTOMAT ────────────────────────────
                DetalleSeccion(titulo = "🔬  ANÁLISIS LACTOMAT") {
                    DetalleFilaItem(
                        "% Agua Detectada",
                        if (acopio.porcentajeAgua > 0) "${"%.2f".format(acopio.porcentajeAgua)}%" else "—",
                        valueColor = if (acopio.porcentajeAgua >= 10.0) RedPrimary
                                     else if (acopio.porcentajeAgua >= 5.0) AmberPrimary
                                     else GreenPrimary
                    )
                    DetalleFilaItem(
                        "Acidez pH",
                        if (acopio.acidezPh > 0) "${"%.2f".format(acopio.acidezPh)} pH" else "—",
                        valueColor = if (acopio.acidezPh > 0 &&
                            (acopio.acidezPh < 6.4 || acopio.acidezPh > 6.8)) AmberPrimary
                                     else TextPrimary
                    )
                    acopio.densidad?.let {
                        DetalleFilaItem(
                            "Densidad",
                            "${"%.3f".format(it)} g/mL",
                            valueColor = if (it < 1.028 || it > 1.033) AmberPrimary else TextPrimary
                        )
                    }
                }

                // ── Sección: Parámetros extra si existen ───────────────────
                val tieneExtras = listOf(
                    acopio.grasa, acopio.proteina, acopio.lactosa,
                    acopio.sng, acopio.sales, acopio.totalSolidos, acopio.puntoCongelacion
                ).any { it != null && it > 0.0 }

                if (tieneExtras) {
                    DetalleSeccion(titulo = "📊  PARÁMETROS ADICIONALES") {
                        acopio.grasa?.takeIf { it > 0 }?.let {
                            DetalleFilaItem("Grasa", "${"%.2f".format(it)}%")
                        }
                        acopio.proteina?.takeIf { it > 0 }?.let {
                            DetalleFilaItem("Proteína", "${"%.2f".format(it)}%")
                        }
                        acopio.lactosa?.takeIf { it > 0 }?.let {
                            DetalleFilaItem("Lactosa", "${"%.2f".format(it)}%")
                        }
                        acopio.sng?.takeIf { it > 0 }?.let {
                            DetalleFilaItem("SNG", "${"%.2f".format(it)}%")
                        }
                        acopio.sales?.takeIf { it > 0 }?.let {
                            DetalleFilaItem("Sales", "${"%.2f".format(it)}%")
                        }
                        acopio.totalSolidos?.takeIf { it > 0 }?.let {
                            DetalleFilaItem("Total Sólidos", "${"%.2f".format(it)}%")
                        }
                        acopio.puntoCongelacion?.let {
                            DetalleFilaItem("Punto Congelación", "${"%.3f".format(it)} °C")
                        }
                    }
                }

                // ── Sección: GPS ───────────────────────────────────────────
                if (acopio.gpsLat != null && acopio.gpsLon != null) {
                    DetalleSeccion(titulo = "📍  UBICACIÓN GPS") {
                        DetalleFilaItem("Latitud",  "${"%.5f".format(acopio.gpsLat)}")
                        DetalleFilaItem("Longitud", "${"%.5f".format(acopio.gpsLon)}")
                    }
                }

                // ── Descuento ──────────────────────────────────────────────
                if (acopio.tieneDescuento) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(10.dp))
                            .background(AmberBg)
                            .border(1.dp, AmberPrimary.copy(alpha = 0.4f), RoundedCornerShape(10.dp))
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("⚠  ", color = AmberPrimary, fontSize = 16.sp)
                        Column {
                            Text(
                                "DESCUENTO APLICADO",
                                color      = AmberPrimary,
                                fontSize   = 11.sp,
                                fontWeight = FontWeight.Bold
                            )
                            Text(
                                "% Agua ~1%: se aplicará descuento en la liquidación.",
                                color    = AmberText,
                                fontSize = 10.sp
                            )
                        }
                    }
                }

                Spacer(Modifier.height(8.dp))
            }

            // ── Botón exportar PDF ─────────────────────────────────────────
            if (onExportarPdf != null) {
                Box(modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp)) {
                    Button(
                        onClick  = onExportarPdf,
                        modifier = Modifier.fillMaxWidth().height(52.dp),
                        shape    = RoundedCornerShape(12.dp),
                        colors   = ButtonDefaults.buttonColors(
                            containerColor = GreenPrimary,
                            contentColor   = Color.White
                        )
                    ) {
                        Text(
                            "📄  EXPORTAR BOLETA PDF",
                            fontSize   = 14.sp,
                            fontWeight = FontWeight.Bold
                        )
                    }
                }
            }
        }
    }
}

// ── Componentes internos ──────────────────────────────────────────────────────

@Composable
private fun DetalleSeccion(titulo: String, content: @Composable ColumnScope.() -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(12.dp))
            .background(BgCard)
            .border(1.dp, Divider, RoundedCornerShape(12.dp))
            .padding(14.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Text(
            titulo,
            color      = TextMuted,
            fontSize   = 10.sp,
            fontWeight = FontWeight.Bold
        )
        HorizontalDivider(color = Divider, thickness = 0.5.dp)
        content()
    }
}

@Composable
private fun DetalleFilaItem(
    label: String,
    value: String,
    valueColor: Color = TextPrimary
) {
    Row(
        modifier          = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(label, color = TextSecondary, fontSize = 12.sp, modifier = Modifier.weight(1f))
        Text(
            value,
            color      = valueColor,
            fontSize   = 13.sp,
            fontWeight = FontWeight.SemiBold
        )
    }
}

