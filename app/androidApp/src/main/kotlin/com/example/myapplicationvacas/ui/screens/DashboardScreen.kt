package com.example.myapplicationvacas.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.myapplicationvacas.ui.theme.*
import com.example.myapplicationvacas.viewmodel.ProLechViewModel

@Composable
fun DashboardScreen(
    viewModel: ProLechViewModel,
    onNavigateToAcopio: () -> Unit,
    onNavigateToHistorial: () -> Unit,
    onNavigateToSync: () -> Unit
) {
    val cisterna  by viewModel.cisterna.collectAsState()
    val acopios   by viewModel.acopios.collectAsState()
    val login     by viewModel.loginState.collectAsState()
    val pendientes = acopios.count { it.pendienteSinc }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(BackgroundDark)
            .verticalScroll(rememberScrollState())
            .padding(bottom = 16.dp)
    ) {
        // ── TopBar ────────────────────────────────────────────────────────────
        Surface(color = SurfaceDark) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .statusBarsPadding()
                    .padding(horizontal = 20.dp, vertical = 14.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment     = Alignment.CenterVertically
            ) {
                Column {
                    Text(
                        text  = "ProLech",
                        style = MaterialTheme.typography.titleLarge.copy(
                            fontWeight = FontWeight.ExtraBold,
                            color      = GreenPrimary
                        )
                    )
                    Text(
                        text  = login.rutaSeleccionada?.nombre ?: "Sistema Móvil Huata",
                        style = MaterialTheme.typography.bodyMedium,
                        color = TextSecondary
                    )
                }
                // Etiqueta roja OFFLINE
                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = RedContainer
                ) {
                    Row(
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        verticalAlignment     = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        Box(
                            modifier = Modifier
                                .size(7.dp)
                                .clip(CircleShape)
                                .background(RedAlertLight)
                        )
                        Text(
                            text  = "Modo Offline Activo",
                            style = MaterialTheme.typography.labelSmall.copy(
                                color      = RedAlertLight,
                                fontWeight = FontWeight.Bold
                            )
                        )
                    }
                }
            }
        }

        Column(
            modifier = Modifier.padding(horizontal = 20.dp, vertical = 16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // ── Cisterna ──────────────────────────────────────────────────────
            Surface(
                shape    = RoundedCornerShape(16.dp),
                color    = SurfaceDark,
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(
                    modifier            = Modifier.padding(18.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Text(
                        text  = "Cisterna de Recolección",
                        style = MaterialTheme.typography.labelSmall.copy(color = TextSecondary)
                    )
                    Row(
                        modifier              = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment     = Alignment.Bottom
                    ) {
                        Text(
                            text  = "${cisterna.litrosActuales.toInt()}L / ${cisterna.capacidadTotal.toInt()}L",
                            style = MaterialTheme.typography.headlineMedium.copy(
                                fontWeight = FontWeight.Bold,
                                color      = TextPrimary
                            )
                        )
                        Surface(shape = RoundedCornerShape(8.dp), color = GreenContainer) {
                            Text(
                                text     = "${(cisterna.porcentaje * 100).toInt()}%",
                                modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                                style    = MaterialTheme.typography.labelLarge.copy(
                                    color      = GreenPrimary,
                                    fontWeight = FontWeight.Bold
                                )
                            )
                        }
                    }
                    LinearProgressIndicator(
                        progress   = { cisterna.porcentaje },
                        modifier   = Modifier
                            .fillMaxWidth()
                            .height(10.dp),
                        color      = GreenPrimary,
                        trackColor = OutlineDefault,
                        strokeCap  = StrokeCap.Round
                    )
                    Text(
                        text  = "Ruta Huata – Centro",
                        style = MaterialTheme.typography.labelSmall,
                        color = TextDisabled
                    )
                }
            }

            // ── Botones grandes de acción ─────────────────────────────────────
            // Registrar Acopio
            BigActionButton(
                label    = "Registrar Acopio",
                subtitle = "Nuevo registro de hoy",
                icon     = Icons.Filled.Add,
                color    = GreenPrimary,
                onClick  = onNavigateToAcopio
            )

            // Ver Historial
            BigActionButton(
                label    = "Ver Historial",
                subtitle = "${acopios.size} registros hoy",
                icon     = Icons.Filled.History,
                color    = Color(0xFF1976D2),
                onClick  = onNavigateToHistorial
            )

            // Sincronizar
            BigActionButton(
                label    = "Sincronizar",
                subtitle = if (pendientes > 0) "$pendientes pendientes de envío" else "Todo sincronizado",
                icon     = Icons.Filled.Sync,
                color    = Color(0xFF7B1FA2),
                onClick  = onNavigateToSync,
                badge    = if (pendientes > 0) "$pendientes" else null
            )

            // ── Banner Offline ────────────────────────────────────────────────
            Surface(
                shape    = RoundedCornerShape(10.dp),
                color    = Color(0xFF1A0808),
                modifier = Modifier.fillMaxWidth()
            ) {
                Row(
                    modifier = Modifier.padding(horizontal = 14.dp, vertical = 12.dp),
                    verticalAlignment     = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Icon(
                        imageVector        = Icons.Filled.WifiOff,
                        contentDescription = null,
                        tint               = RedAlertLight,
                        modifier           = Modifier.size(18.dp)
                    )
                    Text(
                        text  = "Modo Offline Activo · Puedes registrar sin conexión.",
                        style = MaterialTheme.typography.labelSmall,
                        color = RedAlertLight
                    )
                }
            }

            Text(
                text  = "Última Sincronización: Hoy 06:12 AM",
                style = MaterialTheme.typography.labelSmall,
                color = TextDisabled
            )
        }
    }
}

@Composable
private fun BigActionButton(
    label: String,
    subtitle: String,
    icon: ImageVector,
    color: Color,
    onClick: () -> Unit,
    badge: String? = null
) {
    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .height(72.dp),
        shape   = RoundedCornerShape(16.dp),
        color   = SurfaceDark,
        onClick = onClick
    ) {
        Box {
            Row(
                modifier          = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 18.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                Box(
                    modifier = Modifier
                        .size(46.dp)
                        .clip(RoundedCornerShape(12.dp))
                        .background(color.copy(alpha = 0.18f)),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(icon, contentDescription = null, tint = color, modifier = Modifier.size(24.dp))
                }
                Column {
                    Text(
                        text  = label,
                        style = MaterialTheme.typography.titleMedium.copy(
                            fontWeight = FontWeight.SemiBold,
                            color      = TextPrimary
                        )
                    )
                    Text(
                        text  = subtitle,
                        style = MaterialTheme.typography.labelSmall,
                        color = TextSecondary
                    )
                }
            }
            if (badge != null) {
                Surface(
                    modifier  = Modifier
                        .align(Alignment.CenterEnd)
                        .padding(end = 18.dp),
                    shape     = CircleShape,
                    color     = OrangeWarning
                ) {
                    Text(
                        text     = badge,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                        style    = MaterialTheme.typography.labelSmall.copy(
                            color      = Color.Black,
                            fontWeight = FontWeight.Bold,
                            fontSize   = 11.sp
                        )
                    )
                }
            }
        }
    }
}
