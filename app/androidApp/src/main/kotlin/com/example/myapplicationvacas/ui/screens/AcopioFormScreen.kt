package com.example.myapplicationvacas.ui.screens

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.myapplicationvacas.ui.theme.*
import com.example.myapplicationvacas.viewmodel.ProLechViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AcopioFormScreen(
    viewModel: ProLechViewModel,
    onNavigateBack: () -> Unit,
    onGuardadoExitoso: () -> Unit
) {
    val form    by viewModel.formState.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(form.guardadoExitoso) {
        if (form.guardadoExitoso) {
            Toast.makeText(context, "Guardado con éxito", Toast.LENGTH_SHORT).show()
            onGuardadoExitoso()
        }
    }

    Scaffold(
        containerColor = BackgroundDark,
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Nuevo Acopio", style = MaterialTheme.typography.titleLarge)
                        Text(
                            text  = "Cisterna: 895L / 1290L",
                            style = MaterialTheme.typography.labelSmall,
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
                .padding(horizontal = 20.dp, vertical = 16.dp)
                .verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {

            // ── DNI con autocompletado ────────────────────────────────────────
            FormSectionLabel("DNI del Ganadero")

            Row(
                horizontalArrangement = Arrangement.spacedBy(10.dp),
                verticalAlignment     = Alignment.CenterVertically
            ) {
                OutlinedTextField(
                    value           = form.dni,
                    onValueChange   = viewModel::onDniChange,
                    label           = { Text("DNI") },
                    placeholder     = { Text("Ej: 45281093") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier        = Modifier.weight(1f),
                    singleLine      = true,
                    colors          = fieldColors(),
                    shape           = RoundedCornerShape(12.dp)
                )
                Button(
                    onClick  = { /* autocompletado reactivo */ },
                    modifier = Modifier.height(56.dp),
                    shape    = RoundedCornerShape(12.dp),
                    colors   = ButtonDefaults.buttonColors(containerColor = GreenPrimary)
                ) {
                    Text(
                        text  = "BUSCAR",
                        style = MaterialTheme.typography.labelLarge.copy(
                            color      = Color.Black,
                            fontWeight = FontWeight.Bold
                        )
                    )
                }
            }

            // Resultado autocompletado
            if (form.ganaderoEncontrado != null) {
                Surface(
                    shape    = RoundedCornerShape(12.dp),
                    color    = GreenContainer,
                    modifier = Modifier
                        .fillMaxWidth()
                        .border(1.dp, GreenPrimary, RoundedCornerShape(12.dp))
                ) {
                    Row(
                        modifier              = Modifier.padding(14.dp),
                        verticalAlignment     = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        Icon(Icons.Filled.Person, null, tint = GreenPrimary, modifier = Modifier.size(20.dp))
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                text  = form.ganaderoEncontrado!!.nombre,
                                style = MaterialTheme.typography.titleMedium.copy(
                                    color      = GreenPrimary,
                                    fontWeight = FontWeight.SemiBold
                                )
                            )
                            Text(
                                text  = "DNI: ${form.ganaderoEncontrado!!.dni} · Sector: ${form.ganaderoEncontrado!!.sector}",
                                style = MaterialTheme.typography.bodyMedium,
                                color = TextSecondary
                            )
                        }
                    }
                }
            } else if (form.dni.length >= 8) {
                Surface(
                    shape    = RoundedCornerShape(12.dp),
                    color    = RedContainer,
                    modifier = Modifier
                        .fillMaxWidth()
                        .border(1.dp, RedAlert, RoundedCornerShape(12.dp))
                ) {
                    Text(
                        text     = "DNI no encontrado en el sistema",
                        modifier = Modifier.padding(14.dp),
                        style    = MaterialTheme.typography.bodyMedium,
                        color    = RedAlertLight
                    )
                }
            }

            HorizontalDivider(color = OutlineDefault)

            // ── Litros exactos ────────────────────────────────────────────────
            FormSectionLabel("Litros Exactos")

            OutlinedTextField(
                value           = form.litros,
                onValueChange   = viewModel::onLitrosChange,
                label           = { Text("Litros") },
                placeholder     = { Text("Ej: 45.5") },
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                modifier        = Modifier.fillMaxWidth(),
                singleLine      = true,
                colors          = fieldColors(),
                shape           = RoundedCornerShape(12.dp),
                leadingIcon     = {
                    Icon(Icons.Filled.Water, null, tint = TextSecondary, modifier = Modifier.size(18.dp))
                }
            )

            HorizontalDivider(color = OutlineDefault)

            // ── Parámetros Lactoscan ──────────────────────────────────────────
            FormSectionLabel("Parámetros Lactoscan")

            Row(
                modifier              = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                // % Agua — se pone rojo si > 5.0
                OutlinedTextField(
                    value           = form.porcentajeAgua,
                    onValueChange   = viewModel::onPorcentajeAguaChange,
                    label           = { Text("% de Agua") },
                    placeholder     = { Text("0.0") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    modifier        = Modifier.weight(1f),
                    singleLine      = true,
                    isError         = form.lecheRechazada,
                    colors          = fieldColors(),
                    shape           = RoundedCornerShape(12.dp),
                    supportingText  = if (form.lecheRechazada) {
                        { Text("Máx: 5.0%", color = RedAlertLight, fontSize = 11.sp) }
                    } else null
                )

                // Acidez pH
                OutlinedTextField(
                    value           = form.acidezPh,
                    onValueChange   = viewModel::onAcidezPhChange,
                    label           = { Text("Acidez (pH)") },
                    placeholder     = { Text("4.2") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    modifier        = Modifier.weight(1f),
                    singleLine      = true,
                    colors          = fieldColors(),
                    shape           = RoundedCornerShape(12.dp)
                )
            }

            // ── Alerta LECHE RECHAZADA ────────────────────────────────────────
            if (form.lecheRechazada) {
                Surface(
                    shape    = RoundedCornerShape(12.dp),
                    color    = RedContainer,
                    modifier = Modifier
                        .fillMaxWidth()
                        .border(1.5.dp, RedAlert, RoundedCornerShape(12.dp))
                ) {
                    Row(
                        modifier              = Modifier.padding(14.dp),
                        verticalAlignment     = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        Icon(
                            Icons.Filled.Warning,
                            null,
                            tint     = RedAlertLight,
                            modifier = Modifier.size(22.dp)
                        )
                        Column {
                            Text(
                                text  = "LECHE RECHAZADA",
                                style = MaterialTheme.typography.titleMedium.copy(
                                    color      = RedAlertLight,
                                    fontWeight = FontWeight.ExtraBold,
                                    letterSpacing = 1.sp
                                )
                            )
                            Text(
                                text  = "% Agua supera el límite permitido (>5.0%)",
                                style = MaterialTheme.typography.bodyMedium,
                                color = Color(0xFFFF8A80)
                            )
                        }
                    }
                }

                // Motivo de rechazo
                OutlinedTextField(
                    value         = form.motivoRechazo,
                    onValueChange = viewModel::onMotivoRechazoChange,
                    label         = { Text("Observaciones / Detalle Rechazo") },
                    placeholder   = { Text("Describe el motivo...") },
                    modifier      = Modifier
                        .fillMaxWidth()
                        .height(100.dp),
                    colors        = fieldColors(),
                    shape         = RoundedCornerShape(12.dp)
                )
            }

            HorizontalDivider(color = OutlineDefault)

            // ── Foto de evidencia ─────────────────────────────────────────────
            FormSectionLabel("Foto de Evidencia")

            OutlinedButton(
                onClick  = { /* mock — cámara no implementada en prototipo */ },
                enabled  = form.lecheRechazada,
                modifier = Modifier
                    .fillMaxWidth()
                    .height(56.dp),
                shape    = RoundedCornerShape(12.dp),
                colors   = ButtonDefaults.outlinedButtonColors(
                    contentColor         = if (form.lecheRechazada) RedAlertLight else TextDisabled,
                    disabledContentColor = TextDisabled
                ),
                border = androidx.compose.foundation.BorderStroke(
                    1.dp,
                    if (form.lecheRechazada) RedAlert else OutlineDefault
                )
            ) {
                Icon(
                    Icons.Filled.CameraAlt,
                    null,
                    modifier = Modifier.size(20.dp)
                )
                Spacer(Modifier.width(8.dp))
                Text(
                    text = if (form.lecheRechazada)
                        "Tomar Foto de Evidencia (Obligatorio)"
                    else
                        "Tomar Foto de Evidencia (Solo si rechazada)",
                    style = MaterialTheme.typography.labelLarge
                )
            }

            Spacer(Modifier.height(4.dp))

            // ── Botón Guardar ─────────────────────────────────────────────────
            val canSave = form.ganaderoEncontrado != null && form.litros.isNotBlank()

            Button(
                onClick  = { if (canSave) viewModel.guardarAcopio() },
                enabled  = canSave,
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
                    Icons.Filled.Save,
                    null,
                    tint     = if (canSave) Color.Black else TextDisabled,
                    modifier = Modifier.size(22.dp)
                )
                Spacer(Modifier.width(10.dp))
                Text(
                    text  = "Guardar Acopio",
                    style = MaterialTheme.typography.titleMedium.copy(
                        color      = if (canSave) Color.Black else TextDisabled,
                        fontWeight = FontWeight.ExtraBold
                    )
                )
            }

            Spacer(Modifier.height(8.dp))
        }
    }
}

@Composable
private fun FormSectionLabel(text: String) {
    Text(
        text  = text,
        style = MaterialTheme.typography.labelSmall.copy(
            color         = TextSecondary,
            fontWeight    = FontWeight.SemiBold,
            letterSpacing = 1.2.sp
        )
    )
}

@Composable
private fun fieldColors() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor      = GreenPrimary,
    unfocusedBorderColor    = OutlineDefault,
    errorBorderColor        = RedAlert,
    focusedLabelColor       = GreenPrimary,
    unfocusedLabelColor     = TextSecondary,
    errorLabelColor         = RedAlertLight,
    cursorColor             = GreenPrimary,
    errorCursorColor        = RedAlertLight,
    focusedTextColor        = TextPrimary,
    unfocusedTextColor      = TextPrimary,
    errorTextColor          = RedAlertLight,
    focusedContainerColor   = SurfaceVariantDark,
    unfocusedContainerColor = SurfaceVariantDark,
    errorContainerColor     = RedContainer,
    disabledContainerColor  = SurfaceVariantDark,
    disabledTextColor       = TextDisabled,
    disabledBorderColor     = OutlineDefault
)
