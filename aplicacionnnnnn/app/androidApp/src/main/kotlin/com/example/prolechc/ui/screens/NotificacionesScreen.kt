package com.example.prolechc.ui.screens

import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.prolechc.data.repositories.NotificacionInfo
import com.example.prolechc.ui.viewmodels.NotificacionesViewModel

// ── Colores locales ───────────────────────────────────────────────────────────
private val BgPage      = Color(0xFFF4F6F4)
private val BgCard      = Color(0xFFFFFFFF)
private val SurfaceDark = Color(0xFF1A2E1A)
private val GreenPrimary = Color(0xFF1E8A3C)
private val GreenAccent  = Color(0xFF2ECC71)
private val GreenBg      = Color(0xFFE8F5EE)
private val GreenBorder  = Color(0xFF27AE60)
private val RedPrimary   = Color(0xFFB71C1C)
private val RedBg        = Color(0xFFFFEBEE)
private val AmberPrimary = Color(0xFFF57F17)
private val AmberBg      = Color(0xFFFFF8E1)
private val BluePrimary  = Color(0xFF1565C0)
private val BlueBg       = Color(0xFFE3F2FD)
private val TextPrimary  = Color(0xFF0D1A0D)
private val TextMuted    = Color(0xFF8A9E8A)
private val TextDim      = Color(0xFFB0BEB0)
private val Divider      = Color(0xFFE0E8E0)

// ══════════════════════════════════════════════════════════════════════════════
//  NotificacionesScreen
//
//  Consume GET /api/notificaciones y muestra la lista de no leídas.
//  Al tocar una tarjeta → marca como leída vía POST /api/notificaciones/{id}/leer
//  Botón "Marcar todas leídas" → POST /api/notificaciones/leer-todas
// ══════════════════════════════════════════════════════════════════════════════

@Composable
fun NotificacionesScreen(
    onBack: () -> Unit
) {
    val context = LocalContext.current
    val application = remember(context) {
        generateSequence(context) { (it as? android.content.ContextWrapper)?.baseContext }
            .filterIsInstance<android.app.Application>()
            .firstOrNull() ?: context.applicationContext as android.app.Application
    }
    val viewModel: NotificacionesViewModel = viewModel(
        factory = NotificacionesViewModel.Factory(application)
    )

    val uiState by viewModel.uiState.collectAsStateWithLifecycle()

    // Cargar al entrar
    LaunchedEffect(Unit) { viewModel.cargar() }

    // Toast de éxito
    LaunchedEffect(uiState.mensajeExito) {
        uiState.mensajeExito?.let {
            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
            viewModel.consumirMensaje()
        }
    }

    // Toast de error
    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            Toast.makeText(context, "✗ $it", Toast.LENGTH_LONG).show()
            viewModel.consumirMensaje()
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(BgPage)
            .systemBarsPadding()
    ) {

        // ── TopBar ────────────────────────────────────────────────────────────
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(SurfaceDark)
                .padding(horizontal = 16.dp, vertical = 14.dp),
            verticalAlignment     = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    "← ",
                    color    = GreenAccent,
                    fontSize = 20.sp,
                    modifier = Modifier.clickable(onClick = onBack)
                )
                Spacer(Modifier.width(6.dp))
                Column {
                    Text("Notificaciones", color = Color.White,
                        fontSize = 16.sp, fontWeight = FontWeight.Bold)
                    Text(
                        if (uiState.noLeidasCount > 0)
                            "${uiState.noLeidasCount} sin leer"
                        else "Todo leído",
                        color    = Color.White.copy(alpha = 0.55f),
                        fontSize = 10.sp
                    )
                }
            }

            // Botón "Marcar todas leídas"
            AnimatedVisibility(
                visible = uiState.notificaciones.isNotEmpty(),
                enter = fadeIn(), exit = fadeOut()
            ) {
                TextButton(
                    onClick = { viewModel.marcarTodasLeidas() },
                    colors  = ButtonDefaults.textButtonColors(contentColor = GreenAccent)
                ) {
                    Text("✓ Todas leídas", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                }
            }
        }

        // ── Contenido ─────────────────────────────────────────────────────────
        when {
            uiState.isLoading -> {
                Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        CircularProgressIndicator(color = GreenPrimary, modifier = Modifier.size(40.dp))
                        Spacer(Modifier.height(12.dp))
                        Text("Cargando notificaciones…", color = TextMuted, fontSize = 13.sp)
                    }
                }
            }

            uiState.notificaciones.isEmpty() && !uiState.isLoading -> {
                EstadoVacio()
            }

            else -> {
                LazyColumn(
                    modifier            = Modifier.fillMaxSize().padding(horizontal = 16.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                    contentPadding      = PaddingValues(vertical = 14.dp)
                ) {
                    items(uiState.notificaciones, key = { it.id }) { notificacion ->
                        NotificacionCard(
                            notificacion = notificacion,
                            onMarcarLeida = { viewModel.marcarLeida(notificacion.id) }
                        )
                    }
                }
            }
        }
    }
}

// ── Tarjeta de notificación ────────────────────────────────────────────────────

@Composable
private fun NotificacionCard(
    notificacion: NotificacionInfo,
    onMarcarLeida: () -> Unit
) {
    val (borderColor, bgColor, iconBg) = when (notificacion.tipo) {
        "reunion"             -> Triple(GreenBorder, GreenBg,  GreenPrimary)
        "ruta_aprobada"       -> Triple(GreenBorder, GreenBg,  GreenPrimary)
        "ruta_rechazada"      -> Triple(RedPrimary,  RedBg,    RedPrimary)
        "suspension"          -> Triple(RedPrimary,  RedBg,    RedPrimary)
        "adulteracion_exceso" -> Triple(AmberPrimary, AmberBg, AmberPrimary)
        else                  -> Triple(BluePrimary,  BlueBg,   BluePrimary)
    }

    // Formatear la hora relativa desde createdAt (ISO string)
    val horaRelativa = remember(notificacion.createdAt) {
        try {
            val instant = java.time.Instant.parse(notificacion.createdAt)
            val mins    = java.time.Duration.between(instant, java.time.Instant.now()).toMinutes()
            when {
                mins < 1    -> "Ahora mismo"
                mins < 60   -> "Hace ${mins}m"
                mins < 1440 -> "Hace ${mins / 60}h"
                else        -> "Hace ${mins / 1440}d"
            }
        } catch (_: Exception) { "" }
    }

    Card(
        modifier  = Modifier
            .fillMaxWidth()
            .border(1.dp, borderColor.copy(alpha = 0.4f), RoundedCornerShape(12.dp))
            .clickable(onClick = onMarcarLeida),
        colors    = CardDefaults.cardColors(containerColor = BgCard),
        shape     = RoundedCornerShape(12.dp),
        elevation = CardDefaults.cardElevation(1.dp)
    ) {
        Row(
            modifier          = Modifier.fillMaxWidth().padding(14.dp),
            verticalAlignment = Alignment.Top
        ) {
            // Icono de tipo
            Box(
                modifier         = Modifier
                    .size(42.dp)
                    .clip(RoundedCornerShape(10.dp))
                    .background(bgColor),
                contentAlignment = Alignment.Center
            ) {
                Text(notificacion.icono, fontSize = 20.sp)
            }

            Spacer(Modifier.width(12.dp))

            Column(modifier = Modifier.weight(1f)) {
                // Título
                Text(
                    notificacion.titulo,
                    color      = TextPrimary,
                    fontSize   = 13.sp,
                    fontWeight = FontWeight.Bold,
                    lineHeight = 18.sp
                )
                Spacer(Modifier.height(4.dp))
                // Mensaje
                Text(
                    notificacion.mensaje,
                    color      = TextMuted,
                    fontSize   = 12.sp,
                    lineHeight = 17.sp
                )
                Spacer(Modifier.height(6.dp))
                // Hora + botón marcar leída
                Row(
                    modifier              = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment     = Alignment.CenterVertically
                ) {
                    Text(horaRelativa, color = TextDim, fontSize = 10.sp)
                    Text(
                        "✓ Marcar leída",
                        color    = iconBg,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.SemiBold,
                        modifier = Modifier.clickable(onClick = onMarcarLeida)
                    )
                }
            }
        }
    }
}

// ── Estado vacío ──────────────────────────────────────────────────────────────

@Composable
private fun EstadoVacio() {
    Box(
        modifier         = Modifier.fillMaxSize().padding(32.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text("🔔", fontSize = 52.sp)
            Spacer(Modifier.height(14.dp))
            Text(
                "Sin notificaciones nuevas",
                color      = TextPrimary,
                fontSize   = 16.sp,
                fontWeight = FontWeight.Bold
            )
            Spacer(Modifier.height(6.dp))
            Text(
                "Aquí aparecerán los avisos de reuniones,\ncambios de ruta y alertas de calidad.",
                color     = TextMuted,
                fontSize  = 13.sp,
                textAlign = androidx.compose.ui.text.style.TextAlign.Center,
                lineHeight = 20.sp
            )
        }
    }
}
