package com.example.myapplicationvacas.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
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
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.myapplicationvacas.data.*
import com.example.myapplicationvacas.ui.theme.*
import com.example.myapplicationvacas.viewmodel.ProLechViewModel

@Composable
fun LoginScreen(
    viewModel: ProLechViewModel,
    onRutaIniciada: () -> Unit
) {
    val login by viewModel.loginState.collectAsState()

    val canStart = login.conductorSeleccionado != null
            && login.camionSeleccionado != null
            && login.rutaSeleccionada != null

    LaunchedEffect(login.rutaIniciada) {
        if (login.rutaIniciada) onRutaIniciada()
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(BackgroundDark)
            .systemBarsPadding()
            .verticalScroll(rememberScrollState())
    ) {
        // Header
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .height(220.dp)
                .background(
                    Brush.verticalGradient(listOf(Color(0xFF003918), Color(0xFF001A0C)))
                ),
            contentAlignment = Alignment.Center
        ) {
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Box(
                    modifier = Modifier
                        .size(72.dp)
                        .clip(CircleShape)
                        .background(GreenPrimary),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text  = "PL",
                        style = MaterialTheme.typography.headlineMedium.copy(
                            fontWeight = FontWeight.ExtraBold,
                            color      = Color.Black
                        )
                    )
                }
                Text(
                    text  = "ProLech",
                    style = MaterialTheme.typography.headlineMedium.copy(
                        fontWeight = FontWeight.ExtraBold,
                        color      = GreenPrimary
                    )
                )
                Text(
                    text  = "ACOPIO DIGITAL RURAL",
                    style = MaterialTheme.typography.labelSmall.copy(
                        color         = TextSecondary,
                        letterSpacing = 3.sp
                    )
                )
            }
        }

        Column(
            modifier = Modifier
                .padding(horizontal = 20.dp)
                .padding(top = 24.dp, bottom = 32.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Conductor
            SectionLabel(icon = Icons.Filled.Person, title = "Chofer / Transportista")
            SimpleDropdown(
                label       = "Seleccionar Conductor",
                selected    = login.conductorSeleccionado?.nombre ?: "",
                options     = MockData.conductores,
                optionLabel = { it.nombre },
                onSelect    = viewModel::onConductorChange
            )

            // Camión
            SectionLabel(icon = Icons.Filled.LocalShipping, title = "Camión / Placa")
            SimpleDropdown(
                label       = "Seleccionar Camión",
                selected    = login.camionSeleccionado?.let { "${it.placa} (${it.descripcion})" } ?: "",
                options     = MockData.camiones,
                optionLabel = { "${it.placa} — ${it.descripcion}" },
                onSelect    = viewModel::onCamionChange
            )

            // Ruta
            SectionLabel(icon = Icons.Filled.Route, title = "Ruta Asignada")
            MockData.rutas.forEach { ruta ->
                val selected = login.rutaSeleccionada?.id == ruta.id
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(12.dp))
                        .background(if (selected) GreenContainer else SurfaceVariantDark)
                        .border(
                            width = 1.5.dp,
                            color = if (selected) GreenPrimary else OutlineDefault,
                            shape = RoundedCornerShape(12.dp)
                        )
                        .clickable { viewModel.onRutaChange(ruta) }
                        .padding(16.dp),
                    verticalAlignment     = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    RadioButton(
                        selected = selected,
                        onClick  = null,
                        colors   = RadioButtonDefaults.colors(
                            selectedColor   = GreenPrimary,
                            unselectedColor = TextSecondary
                        )
                    )
                    Column(modifier = Modifier.weight(1f)) {
                        Text(
                            text  = ruta.nombre,
                            style = MaterialTheme.typography.titleMedium.copy(
                                color      = if (selected) GreenPrimary else TextPrimary,
                                fontWeight = FontWeight.SemiBold
                            )
                        )
                        Text(
                            text  = ruta.descripcion,
                            style = MaterialTheme.typography.bodyMedium,
                            color = TextSecondary
                        )
                    }
                    if (selected) {
                        Icon(
                            imageVector        = Icons.Filled.CheckCircle,
                            contentDescription = null,
                            tint               = GreenPrimary,
                            modifier           = Modifier.size(20.dp)
                        )
                    }
                }
            }

            Spacer(Modifier.height(8.dp))

            // Botón iniciar
            Button(
                onClick  = viewModel::iniciarRuta,
                enabled  = canStart,
                modifier = Modifier
                    .fillMaxWidth()
                    .height(60.dp),
                shape  = RoundedCornerShape(14.dp),
                colors = ButtonDefaults.buttonColors(
                    containerColor         = GreenPrimary,
                    disabledContainerColor = Color(0xFF1A3A1A)
                )
            ) {
                Icon(
                    imageVector        = Icons.Filled.PlayArrow,
                    contentDescription = null,
                    tint               = if (canStart) Color.Black else TextDisabled,
                    modifier           = Modifier.size(24.dp)
                )
                Spacer(Modifier.width(10.dp))
                Text(
                    text  = "INICIAR RUTA",
                    style = MaterialTheme.typography.titleMedium.copy(
                        fontWeight    = FontWeight.ExtraBold,
                        color         = if (canStart) Color.Black else TextDisabled,
                        letterSpacing = 1.sp
                    )
                )
            }

            if (!canStart) {
                Text(
                    text      = "Selecciona conductor, camión y ruta para continuar",
                    style     = MaterialTheme.typography.labelSmall,
                    color     = TextDisabled,
                    textAlign = TextAlign.Center,
                    modifier  = Modifier.fillMaxWidth()
                )
            }

            Text(
                text      = "ProLech v1.0 · Sistema Móvil Huata, Puno",
                style     = MaterialTheme.typography.labelSmall,
                color     = TextDisabled,
                textAlign = TextAlign.Center,
                modifier  = Modifier.fillMaxWidth()
            )
        }
    }
}

@Composable
private fun SectionLabel(icon: ImageVector, title: String) {
    Row(
        verticalAlignment     = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Icon(icon, contentDescription = null, tint = GreenPrimary, modifier = Modifier.size(18.dp))
        Text(
            text  = title,
            style = MaterialTheme.typography.labelLarge.copy(color = TextSecondary)
        )
    }
}

@Composable
private fun <T> SimpleDropdown(
    label: String,
    selected: String,
    options: List<T>,
    optionLabel: (T) -> String,
    onSelect: (T) -> Unit
) {
    var expanded by remember { mutableStateOf(false) }

    Box(modifier = Modifier.fillMaxWidth()) {
        OutlinedTextField(
            value         = selected,
            onValueChange = {},
            readOnly      = true,
            label         = { Text(label) },
            trailingIcon  = {
                Icon(
                    imageVector        = if (expanded) Icons.Filled.KeyboardArrowUp else Icons.Filled.KeyboardArrowDown,
                    contentDescription = null,
                    tint               = TextSecondary,
                    modifier           = Modifier.clickable { expanded = !expanded }
                )
            },
            modifier = Modifier
                .fillMaxWidth()
                .clickable { expanded = !expanded },
            shape  = RoundedCornerShape(12.dp),
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor      = GreenPrimary,
                unfocusedBorderColor    = OutlineDefault,
                focusedLabelColor       = GreenPrimary,
                unfocusedLabelColor     = TextSecondary,
                focusedTextColor        = TextPrimary,
                unfocusedTextColor      = TextPrimary,
                focusedContainerColor   = SurfaceVariantDark,
                unfocusedContainerColor = SurfaceVariantDark
            )
        )
        DropdownMenu(
            expanded         = expanded,
            onDismissRequest = { expanded = false },
            containerColor   = SurfaceDark,
            modifier         = Modifier.fillMaxWidth()
        ) {
            options.forEach { option ->
                DropdownMenuItem(
                    text    = { Text(optionLabel(option), color = TextPrimary) },
                    onClick = { onSelect(option); expanded = false }
                )
            }
        }
    }
}
