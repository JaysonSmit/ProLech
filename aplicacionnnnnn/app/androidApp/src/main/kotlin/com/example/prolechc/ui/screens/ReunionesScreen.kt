package com.example.prolechc.ui.screens

import android.content.Intent
import android.net.Uri
import android.widget.Toast
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.prolechc.data.api.ReunionData
import com.example.prolechc.ui.viewmodels.ReunionesViewModel

// ── Colores locales ───────────────────────────────────────────────────────────
private val BgPage       = Color(0xFFF4F6F4)
private val BgCard       = Color(0xFFFFFFFF)
private val SurfaceDark  = Color(0xFF1A2E1A)
private val GreenPrimary = Color(0xFF1E8A3C)
private val GreenAccent  = Color(0xFF2ECC71)
private val BluePrimary  = Color(0xFF1565C0)
private val BlueBg       = Color(0xFFE3F2FD)
private val TextPrimary  = Color(0xFF0D1A0D)
private val TextMuted    = Color(0xFF8A9E8A)
private val Divider      = Color(0xFFE0E8E0)

// ══════════════════════════════════════════════════════════════════════════════
//  ReunionesScreen
//
//  Lista las reuniones del GET /api/reuniones. Si la reunión tiene enlace
//  (Meet / Zoom), el botón "Unirse" lo abre en el navegador o la app de video.
// ══════════════════════════════════════════════════════════════════════════════

@Composable
fun ReunionesScreen(onBack: () -> Unit) {
    val context = LocalContext.current
    val application = remember(context) {
        generateSequence(context) { (it as? android.content.ContextWrapper)?.baseContext }
            .filterIsInstance<android.app.Application>()
            .firstOrNull() ?: context.applicationContext as android.app.Application
    }
    val viewModel: ReunionesViewModel = viewModel(factory = ReunionesViewModel.Factory(application))
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(Unit) { viewModel.cargar() }

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
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text("← ", color = GreenAccent, fontSize = 20.sp,
                modifier = Modifier.clickable(onClick = onBack))
            Spacer(Modifier.width(6.dp))
            Column {
                Text("Reuniones", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold)
                Text(
                    if (uiState.reuniones.isEmpty()) "Sin reuniones próximas"
                    else "${uiState.reuniones.size} programada(s)",
                    color = Color.White.copy(alpha = 0.55f), fontSize = 10.sp
                )
            }
        }

        when {
            uiState.isLoading -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = GreenPrimary)
            }

            uiState.error != null -> Column(
                Modifier.fillMaxSize().padding(24.dp),
                verticalArrangement = Arrangement.Center,
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text(uiState.error!!, color = TextMuted, fontSize = 13.sp)
                Spacer(Modifier.height(12.dp))
                Button(
                    onClick = { viewModel.cargar() },
                    colors  = ButtonDefaults.buttonColors(containerColor = GreenPrimary)
                ) { Text("Reintentar") }
            }

            uiState.reuniones.isEmpty() -> Column(
                Modifier.fillMaxSize().padding(24.dp),
                verticalArrangement = Arrangement.Center,
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text("📅", fontSize = 40.sp)
                Spacer(Modifier.height(8.dp))
                Text("No tienes reuniones programadas", color = TextPrimary,
                    fontSize = 14.sp, fontWeight = FontWeight.SemiBold)
                Text("Cuando el administrador te invite, aparecerán aquí.",
                    color = TextMuted, fontSize = 12.sp)
            }

            else -> LazyColumn(
                contentPadding      = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                items(uiState.reuniones, key = { it.id }) { reunion ->
                    ReunionCard(reunion) { url ->
                        try {
                            context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
                        } catch (e: Exception) {
                            Toast.makeText(context, "No se pudo abrir el enlace", Toast.LENGTH_SHORT).show()
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun ReunionCard(reunion: ReunionData, onAbrirEnlace: (String) -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(12.dp))
            .background(BgCard)
            .border(1.dp, Divider, RoundedCornerShape(12.dp))
            .padding(14.dp)
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            reunion.tipoLabel?.let {
                Text(
                    it,
                    color    = BluePrimary,
                    fontSize = 10.sp,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier
                        .clip(RoundedCornerShape(6.dp))
                        .background(BlueBg)
                        .padding(horizontal = 8.dp, vertical = 3.dp)
                )
                Spacer(Modifier.width(8.dp))
            }
            if (reunion.confirmado) {
                Text("✓ Confirmado", color = GreenPrimary, fontSize = 10.sp, fontWeight = FontWeight.Bold)
            }
        }
        Spacer(Modifier.height(6.dp))
        Text(reunion.titulo, color = TextPrimary, fontSize = 15.sp, fontWeight = FontWeight.Bold)
        Text("🕒 ${reunion.fechaHuman ?: reunion.fechaHora}", color = TextMuted, fontSize = 12.sp)
        reunion.lugar?.takeIf { it.isNotBlank() }?.let {
            Text("📍 $it", color = TextMuted, fontSize = 12.sp)
        }
        reunion.descripcion?.takeIf { it.isNotBlank() }?.let {
            Spacer(Modifier.height(6.dp))
            Text(it, color = TextPrimary, fontSize = 12.sp, lineHeight = 17.sp)
        }
        reunion.enlace?.takeIf { it.isNotBlank() }?.let { enlace ->
            Spacer(Modifier.height(10.dp))
            Button(
                onClick  = { onAbrirEnlace(enlace) },
                modifier = Modifier.fillMaxWidth().height(42.dp),
                shape    = RoundedCornerShape(10.dp),
                colors   = ButtonDefaults.buttonColors(containerColor = GreenPrimary, contentColor = Color.White)
            ) {
                Text(if (reunion.tieneVideo) "🎥  UNIRSE A LA REUNIÓN" else "🔗  ABRIR ENLACE",
                    fontSize = 12.sp, fontWeight = FontWeight.Bold)
            }
        }
    }
}
