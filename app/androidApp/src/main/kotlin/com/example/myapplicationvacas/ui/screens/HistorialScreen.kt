package com.example.myapplicationvacas.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.myapplicationvacas.data.Acopio
import com.example.myapplicationvacas.data.EstadoAcopio
import com.example.myapplicationvacas.ui.theme.*
import com.example.myapplicationvacas.viewmodel.ProLechViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HistorialScreen(
    viewModel: ProLechViewModel,
    onNavigateBack: () -> Unit
) {
    val acopios by viewModel.acopios.collectAsState()

    Scaffold(
        containerColor = BackgroundDark,
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Historial de Acopios", style = MaterialTheme.typography.titleLarge)
                        Text(
                            text  = "${acopios.size} registros",
                            style = MaterialTheme.typography.bodyMedium,
                            color = TextSecondary
                        )
                    }
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
        ) {
            // Resumen rápido
            ResumenBanner(acopios = acopios)

            LazyColumn(
                modifier            = Modifier.fillMaxSize(),
                contentPadding      = PaddingValues(horizontal = 16.dp, vertical = 12.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                items(acopios, key = { it.id }) { acopio ->
                    AcopioCard(acopio = acopio)
                }
                item { Spacer(Modifier.height(8.dp)) }
            }
        }
    }
}

@Composable
private fun ResumenBanner(acopios: List<Acopio>) {
    val totalLitros = acopios.sumOf { it.litros.toDouble() }.toFloat()
    val aprobados   = acopios.count { it.estado == EstadoAcopio.APROBADO }
    val rechazados  = acopios.count { it.estado == EstadoAcopio.RECHAZADO }

    Row(
        modifier = Modifier
            .fillMaxWidth()
            .background(SurfaceDark)
            .padding(horizontal = 20.dp, vertical = 14.dp),
        horizontalArrangement = Arrangement.SpaceEvenly
    ) {
        ResumenItem("${totalLitros.toInt()}L", "Total Litros", GreenPrimary)
        ResumenItem("${acopios.size}",         "Acopios",      TextPrimary)
        ResumenItem("$aprobados",              "Aprobados",    GreenPrimary)
        ResumenItem("$rechazados",             "Rechazados",   RedAlertLight)
    }
}

@Composable
private fun ResumenItem(value: String, label: String, color: Color) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(
            text  = value,
            style = MaterialTheme.typography.titleLarge.copy(color = color, fontWeight = FontWeight.Bold)
        )
        Text(
            text  = label,
            style = MaterialTheme.typography.labelSmall,
            color = TextSecondary
        )
    }
}

@Composable
private fun AcopioCard(acopio: Acopio) {
    val (borderColor, iconTint, bgColor, badge) = when (acopio.estado) {
        EstadoAcopio.APROBADO  -> CardColors(GreenPrimary,    GreenPrimary,    GreenContainer, "APROBADO")
        EstadoAcopio.RECHAZADO -> CardColors(RedAlert,        RedAlertLight,   RedContainer,   "RECHAZADO")
        EstadoAcopio.OBSERVADO -> CardColors(YellowObservado, YellowObservado, Color(0xFF2E2800), "OBSERVADO")
    }
    val statusIcon = when (acopio.estado) {
        EstadoAcopio.APROBADO  -> Icons.Filled.CheckCircle
        EstadoAcopio.RECHAZADO -> Icons.Filled.Cancel
        EstadoAcopio.OBSERVADO -> Icons.Filled.Warning
    }

    Surface(
        shape    = RoundedCornerShape(16.dp),
        color    = CardDark,
        modifier = Modifier
            .fillMaxWidth()
            .border(1.5.dp, borderColor, RoundedCornerShape(16.dp))
    ) {
        Row(
            modifier              = Modifier.padding(16.dp),
            verticalAlignment     = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            // Ícono de estado
            Box(
                modifier         = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .background(bgColor),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector        = statusIcon,
                    contentDescription = acopio.estado.name,
                    tint               = iconTint,
                    modifier           = Modifier.size(24.dp)
                )
            }

            // Datos principales
            Column(
                modifier            = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                Text(
                    text  = acopio.nombreGanadero,
                    style = MaterialTheme.typography.titleMedium.copy(fontWeight = FontWeight.SemiBold),
                    color = TextPrimary
                )
                Text(
                    text  = "${acopio.litros}L  ·  ${acopio.hora}",
                    style = MaterialTheme.typography.bodyMedium,
                    color = TextSecondary
                )
                Text(
                    text  = "DNI: ${acopio.dniGanadero}",
                    style = MaterialTheme.typography.labelSmall,
                    color = TextDisabled
                )
            }

            // Badge estado
            Surface(shape = RoundedCornerShape(6.dp), color = bgColor) {
                Text(
                    text     = badge,
                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                    style    = MaterialTheme.typography.labelSmall.copy(
                        color      = iconTint,
                        fontWeight = FontWeight.Bold
                    )
                )
            }
        }
    }
}

private data class CardColors(
    val border: Color,
    val icon: Color,
    val bg: Color,
    val badge: String
)
