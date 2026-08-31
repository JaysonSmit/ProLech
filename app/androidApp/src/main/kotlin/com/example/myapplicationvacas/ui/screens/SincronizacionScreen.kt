package com.example.myapplicationvacas.ui.screens

import androidx.compose.animation.AnimatedContent
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.togetherWith
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.example.myapplicationvacas.ui.theme.*
import com.example.myapplicationvacas.viewmodel.ProLechViewModel
import com.example.myapplicationvacas.viewmodel.SyncEstado

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SincronizacionScreen(
    viewModel: ProLechViewModel,
    onNavigateBack: () -> Unit
) {
    val sync    by viewModel.syncState.collectAsState()
    val acopios by viewModel.acopios.collectAsState()
    val pendientes = acopios.count { it.pendienteSinc }

    Scaffold(
        containerColor = BackgroundDark,
        topBar = {
            TopAppBar(
                title = {
                    Text("Sincronizar Datos", style = MaterialTheme.typography.titleLarge)
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Volver",
                            tint               = TextPrimary
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor    = SurfaceDark,
                    titleContentColor = TextPrimary
                )
            )
        }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(BackgroundDark)
                .padding(padding)
                .padding(horizontal = 24.dp, vertical = 24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(20.dp)
        ) {

            // ── Card estado dispositivo ───────────────────────────────────────
            Surface(
                shape    = RoundedCornerShape(16.dp),
                color    = SurfaceDark,
                modifier = Modifier.fillMaxWidth()
            ) {
                Row(
                    modifier              = Modifier.padding(16.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment     = Alignment.CenterVertically
                ) {
                    Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                        Text(
                            text  = "Estado del Dispositivo",
                            style = MaterialTheme.typography.labelLarge,
                            color = TextSecondary
                        )
                        Row(
                            verticalAlignment     = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(6.dp)
                        ) {
                            Icon(
                                Icons.Filled.WifiOff,
                                null,
                                tint     = RedAlertLight,
                                modifier = Modifier.size(16.dp)
                            )
                            Text(
                                text  = "SIN CONEXIÓN",
                                style = MaterialTheme.typography.bodyMedium.copy(
                                    color      = RedAlertLight,
                                    fontWeight = FontWeight.SemiBold
                                )
                            )
                        }
                        Text(
                            text  = "Última sincronización: Hoy 06:12 AM",
                            style = MaterialTheme.typography.labelSmall,
                            color = TextDisabled
                        )
                    }
                    Surface(
                        shape = RoundedCornerShape(8.dp),
                        color = RedContainer
                    ) {
                        Text(
                            text     = "OFFLINE",
                            modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                            style    = MaterialTheme.typography.labelLarge.copy(
                                color      = RedAlertLight,
                                fontWeight = FontWeight.Bold
                            )
                        )
                    }
                }
            }

            // ── Registros pendientes ──────────────────────────────────────────
            val hayPendientes = pendientes > 0
            Surface(
                shape    = RoundedCornerShape(16.dp),
                color    = if (hayPendientes) Color(0xFF1A1200) else GreenContainer,
                modifier = Modifier
                    .fillMaxWidth()
                    .border(
                        1.dp,
                        if (hayPendientes) OrangeWarning else GreenPrimary,
                        RoundedCornerShape(16.dp)
                    )
            ) {
                Row(
                    modifier              = Modifier.padding(20.dp),
                    verticalAlignment     = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    Icon(
                        imageVector        = if (hayPendientes) Icons.Filled.PendingActions else Icons.Filled.CheckCircle,
                        contentDescription = null,
                        tint               = if (hayPendientes) OrangeWarning else GreenPrimary,
                        modifier           = Modifier.size(36.dp)
                    )
                    Column {
                        Text(
                            text  = if (hayPendientes)
                                "$pendientes Registros pendientes de envío"
                            else
                                "Sin registros pendientes",
                            style = MaterialTheme.typography.titleMedium.copy(
                                color      = if (hayPendientes) OrangeWarning else GreenPrimary,
                                fontWeight = FontWeight.Bold
                            )
                        )
                        Text(
                            text  = if (hayPendientes)
                                "Se sincronizarán cuando haya red disponible"
                            else
                                "Todo sincronizado con el servidor",
                            style = MaterialTheme.typography.bodyMedium,
                            color = TextSecondary
                        )
                    }
                }
            }

            Spacer(Modifier.weight(1f))

            // ── Área central animada ──────────────────────────────────────────
            AnimatedContent(
                targetState  = sync.estado,
                transitionSpec = { fadeIn() togetherWith fadeOut() },
                label        = "sync_state"
            ) { estado ->
                when (estado) {
                    SyncEstado.IDLE -> {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Icon(
                                Icons.Filled.CloudUpload,
                                null,
                                tint     = GreenPrimary,
                                modifier = Modifier.size(80.dp)
                            )
                            Text(
                                text      = "$pendientes registros pendientes de envío",
                                style     = MaterialTheme.typography.titleMedium,
                                color     = TextPrimary,
                                textAlign = TextAlign.Center
                            )
                            Text(
                                text      = "Los datos se enviarán al servidor\ncuando haya conexión disponible.",
                                style     = MaterialTheme.typography.bodyMedium,
                                color     = TextSecondary,
                                textAlign = TextAlign.Center
                            )
                        }
                    }

                    SyncEstado.SINCRONIZANDO -> {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(20.dp)
                        ) {
                            CircularProgressIndicator(
                                modifier    = Modifier.size(80.dp),
                                color       = GreenPrimary,
                                trackColor  = OutlineDefault,
                                strokeWidth = 6.dp
                            )
                            Text(
                                text      = "Sincronizando datos…",
                                style     = MaterialTheme.typography.titleMedium,
                                color     = TextPrimary,
                                textAlign = TextAlign.Center
                            )
                            Text(
                                text      = "Por favor no cierre la aplicación",
                                style     = MaterialTheme.typography.bodyMedium,
                                color     = TextSecondary,
                                textAlign = TextAlign.Center
                            )
                        }
                    }

                    SyncEstado.EXITOSO -> {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(16.dp)
                        ) {
                            Icon(
                                Icons.Filled.CheckCircle,
                                null,
                                tint     = GreenPrimary,
                                modifier = Modifier.size(90.dp)
                            )
                            Text(
                                text      = "Sincronización Exitosa al 100%",
                                style     = MaterialTheme.typography.headlineMedium.copy(
                                    color      = GreenPrimary,
                                    fontWeight = FontWeight.Bold
                                ),
                                textAlign = TextAlign.Center
                            )
                            Text(
                                text      = "Todos los registros han sido enviados\ncorrectamente al servidor.",
                                style     = MaterialTheme.typography.bodyMedium,
                                color     = TextSecondary,
                                textAlign = TextAlign.Center
                            )
                        }
                    }
                }
            }

            Spacer(Modifier.weight(1f))

            // ── Botón Sincronizar Ahora (solo en IDLE) ────────────────────────
            if (sync.estado == SyncEstado.IDLE) {
                Button(
                    onClick  = viewModel::iniciarSincronizacion,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(60.dp),
                    shape  = RoundedCornerShape(14.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = GreenPrimary)
                ) {
                    Icon(
                        Icons.Filled.CloudUpload,
                        null,
                        tint     = Color.Black,
                        modifier = Modifier.size(22.dp)
                    )
                    Spacer(Modifier.width(12.dp))
                    Text(
                        text  = "Sincronizar Ahora",
                        style = MaterialTheme.typography.titleMedium.copy(
                            color      = Color.Black,
                            fontWeight = FontWeight.Bold
                        )
                    )
                }
            }

            // ── Botón Reiniciar Demo (solo en EXITOSO) ────────────────────────
            if (sync.estado == SyncEstado.EXITOSO) {
                OutlinedButton(
                    onClick  = viewModel::resetSync,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(56.dp),
                    shape  = RoundedCornerShape(14.dp),
                    colors = ButtonDefaults.outlinedButtonColors(contentColor = TextSecondary),
                    border = androidx.compose.foundation.BorderStroke(1.dp, OutlineDefault)
                ) {
                    Text(
                        text  = "Reiniciar Demo",
                        style = MaterialTheme.typography.labelLarge
                    )
                }
            }
        }
    }
}
