package com.example.prolechc.ui.screens

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.ui.components.BottomNavRoute
import com.example.prolechc.ui.components.ProLechBottomBar
import com.example.prolechc.ui.theme.*
import com.example.prolechc.viewmodel.ProLechViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AcopioScreen(
    viewModel: ProLechViewModel,
    onBack: () -> Unit,
    onGuardadoExitoso: () -> Unit,
    onEscanearTicket: (() -> Unit)? = null,
    /** Abre la cámara del dispositivo para la foto de evidencia (null = no disponible) */
    onTomarFotoEvidencia: (() -> Unit)? = null,
    onBottomDashboard: () -> Unit,
    onBottomAcopio: () -> Unit,
    onBottomRuta: () -> Unit,
    onBottomSync: () -> Unit
) {
    val state      by viewModel.formState.collectAsStateWithLifecycle()
    val miRuta     by viewModel.miRutaState.collectAsStateWithLifecycle()

    // Estado del dropdown de ganaderos
    var dropdownExpanded by remember { mutableStateOf(false) }
    val ganaderos = viewModel.ganaderosDisponibles

    LaunchedEffect(state.guardadoExitoso) {
        if (state.guardadoExitoso) onGuardadoExitoso()
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
        ) {
            // ── TopBar ─────────────────────────────────────────────────────
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(SurfaceDark)
                    .padding(horizontal = 14.dp, vertical = 12.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text("← ", color = GreenAccent, fontSize = 20.sp,
                    modifier = Modifier.clickable(onClick = onBack))
                Text("Nuevo Acopio", color = Color.White, fontSize = 17.sp, fontWeight = FontWeight.Bold)
                Spacer(Modifier.weight(1f))
                Column(horizontalAlignment = Alignment.End) {
                    Text(
                        "Cisterna: ${viewModel.cisternaActual.toInt()}/${viewModel.cisternaTotal.toInt()}L",
                        color = Color.White.copy(alpha = 0.6f), fontSize = 10.sp
                    )
                    LinearProgressIndicator(
                        progress   = { viewModel.cisternaProgress },
                        modifier   = Modifier.width(72.dp).height(5.dp).clip(RoundedCornerShape(3.dp)),
                        color      = GreenAccent,
                        trackColor = Color.White.copy(alpha = 0.2f)
                    )
                }
            }

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(horizontal = 16.dp)
            ) {
                Spacer(Modifier.height(14.dp))

                // ══════════════════════════════════════════════════════════
                // SECCIÓN 1 — SELECCIÓN DE GANADERO (por nombre, no DNI)
                // ══════════════════════════════════════════════════════════
                AcopioSectionLabel("NOMBRE DEL GANADERO / PRODUCTOR")
                Spacer(Modifier.height(6.dp))

                // Dropdown con lista de ganaderos de la ruta
                ExposedDropdownMenuBox(
                    expanded         = dropdownExpanded,
                    onExpandedChange = { dropdownExpanded = it }
                ) {
                    OutlinedTextField(
                        value         = state.ganaderoNombre,
                        onValueChange = { viewModel.onNombreChange(it); dropdownExpanded = it.length >= 1 },
                        placeholder   = {
                            Text(
                                if (ganaderos.isEmpty()) "Escribe el nombre del productor"
                                else "Selecciona o escribe el nombre",
                                color = TextDim, fontSize = 13.sp
                            )
                        },
                        trailingIcon  = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = dropdownExpanded) },
                        modifier      = Modifier.fillMaxWidth().menuAnchor(
                            type    = MenuAnchorType.PrimaryEditable,
                            enabled = true
                        ),
                        singleLine    = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Text),
                        colors        = lightFieldColors(),
                        enabled       = !state.isLoading
                    )

                    // Lista filtrada por lo que escribe el usuario
                    val ganaderosFilter = ganaderos.filter {
                        state.ganaderoNombre.isBlank() ||
                        it.second.contains(state.ganaderoNombre, ignoreCase = true)
                    }

                    if (ganaderosFilter.isNotEmpty()) {
                        ExposedDropdownMenu(
                            expanded         = dropdownExpanded,
                            onDismissRequest = { dropdownExpanded = false },
                            modifier         = Modifier.background(BgCard)
                        ) {
                            ganaderosFilter.forEach { (id, nombre) ->
                                val parada     = miRuta.paradas.find { it.ganaderoId == id }
                                val suspendido = parada?.suspendido == true

                                DropdownMenuItem(
                                    text = {
                                        Row(
                                            modifier          = Modifier.fillMaxWidth().padding(vertical = 2.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            // Indicador de estado
                                            Box(
                                                modifier = Modifier
                                                    .size(28.dp)
                                                    .clip(RoundedCornerShape(6.dp))
                                                    .background(
                                                        when {
                                                            suspendido          -> RedBg
                                                            parada?.atendido == true -> GreenBg
                                                            else                -> BgCardAlt
                                                        }
                                                    ),
                                                contentAlignment = Alignment.Center
                                            ) {
                                                Text(
                                                    if (suspendido) "🚫"
                                                    else if (parada?.atendido == true) "✓" else "○",
                                                    color = when {
                                                        suspendido               -> RedPrimary
                                                        parada?.atendido == true -> GreenPrimary
                                                        else                     -> TextMuted
                                                    },
                                                    fontSize   = 12.sp,
                                                    fontWeight = FontWeight.Bold
                                                )
                                            }
                                            Spacer(Modifier.width(10.dp))
                                            Column {
                                                // Nombre en rojo con etiqueta si está suspendido
                                                Text(
                                                    if (suspendido) "$nombre (Bloqueado por calidad)" else nombre,
                                                    color      = if (suspendido) RedPrimary else TextPrimary,
                                                    fontSize   = 13.sp,
                                                    fontWeight = FontWeight.SemiBold
                                                )
                                                if (parada != null) {
                                                    Text(
                                                        if (suspendido)
                                                            "Suspendido — no puede entregar esta semana"
                                                        else
                                                            "DNI: ${parada.ganaderoNii}" +
                                                            if (!parada.direccion.isNullOrBlank()) "  ·  ${parada.direccion}" else "",
                                                        color    = if (suspendido) RedPrimary.copy(alpha = 0.7f) else TextMuted,
                                                        fontSize = 10.sp
                                                    )
                                                }
                                            }
                                        }
                                    },
                                    onClick = {
                                        if (suspendido) {
                                            // Seleccionar pero marcar error — el botón quedará deshabilitado
                                            viewModel.seleccionarGanadero(id, nombre)
                                            viewModel.onGanaderoSuspendidoIntentado()
                                        } else {
                                            viewModel.seleccionarGanadero(id, nombre)
                                        }
                                        dropdownExpanded = false
                                    }
                                )
                                HorizontalDivider(color = Divider, thickness = 0.5.dp)
                            }
                        }
                    }
                }

                // Card con datos del ganadero seleccionado
                AnimatedVisibility(
                    visible = state.ganaderoNombre.isNotBlank() && state.ganaderoId > 0,
                    enter   = fadeIn(),
                    exit    = fadeOut()
                ) {
                    val parada = miRuta.paradas.find { it.ganaderoId == state.ganaderoId }
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 8.dp)
                            .clip(RoundedCornerShape(10.dp))
                            .background(GreenBg)
                            .border(1.dp, GreenBorder.copy(alpha = 0.5f), RoundedCornerShape(10.dp))
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Box(
                            modifier = Modifier.size(40.dp).clip(RoundedCornerShape(8.dp))
                                .background(GreenPrimary),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                state.ganaderoNombre.take(2).uppercase(),
                                color = Color.White, fontSize = 13.sp, fontWeight = FontWeight.ExtraBold
                            )
                        }
                        Spacer(Modifier.width(10.dp))
                        Column {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Text(state.ganaderoNombre, color = GreenDark,
                                    fontSize = 14.sp, fontWeight = FontWeight.Bold)
                                Spacer(Modifier.width(8.dp))
                                Box(
                                    Modifier.clip(RoundedCornerShape(4.dp))
                                        .background(GreenPrimary)
                                        .padding(horizontal = 6.dp, vertical = 2.dp)
                                ) {
                                    Text("Productor Activo", color = Color.White, fontSize = 9.sp)
                                }
                            }
                            Text(
                                buildString {
                                    if (state.dniGanadero.isNotBlank()) append("DNI: ${state.dniGanadero}")
                                    if (parada?.direccion != null) append("  ·  ${parada.direccion}")
                                },
                                color = TextSecondary, fontSize = 10.sp
                            )
                        }
                    }
                }

                Spacer(Modifier.height(16.dp))

                // ══════════════════════════════════════════════════════════
                // SECCIÓN 2 — LITROS DE LECHE
                // ══════════════════════════════════════════════════════════
                AcopioSectionLabel("LITROS DE LECHE (MEDIDOS)")
                Spacer(Modifier.height(8.dp))
                Row(
                    Modifier.fillMaxWidth(),
                    verticalAlignment     = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    // Botón –
                    Box(
                        modifier = Modifier.size(52.dp).clip(RoundedCornerShape(10.dp))
                            .background(BgCard).border(1.5.dp, Divider, RoundedCornerShape(10.dp))
                            .clickable(enabled = !state.isLoading) { viewModel.decrementarLitros() },
                        contentAlignment = Alignment.Center
                    ) {
                        Text("–", color = TextPrimary, fontSize = 26.sp, fontWeight = FontWeight.Bold)
                    }

                    // Cantidad principal
                    Box(
                        modifier = Modifier.weight(1f).clip(RoundedCornerShape(10.dp))
                            .background(GreenBg).border(1.5.dp, GreenBorder, RoundedCornerShape(10.dp))
                            .padding(vertical = 14.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            state.litrosTexto, color = GreenPrimary, fontSize = 40.sp,
                            fontWeight = FontWeight.ExtraBold, textAlign = TextAlign.Center
                        )
                    }

                    // Temperatura (hardcoded por ahora — integrarlo con sensor en siguiente sprint)
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("TEMP.", color = TextMuted, fontSize = 9.sp)
                        Box(
                            modifier = Modifier.width(70.dp).clip(RoundedCornerShape(8.dp))
                                .background(BgCard).border(1.dp, Divider, RoundedCornerShape(8.dp))
                                .padding(vertical = 10.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Text("4.2 °C", color = TextPrimary, fontSize = 13.sp, fontWeight = FontWeight.Bold)
                        }
                    }

                    // Botón +
                    Box(
                        modifier = Modifier.size(52.dp).clip(RoundedCornerShape(10.dp))
                            .background(GreenBg).border(1.5.dp, GreenBorder, RoundedCornerShape(10.dp))
                            .clickable(enabled = !state.isLoading) { viewModel.incrementarLitros() },
                        contentAlignment = Alignment.Center
                    ) {
                        Text("+", color = GreenPrimary, fontSize = 26.sp, fontWeight = FontWeight.Bold)
                    }
                }

                // Campo manual de litros
                Spacer(Modifier.height(8.dp))
                OutlinedTextField(
                    value         = state.litrosTexto,
                    onValueChange = viewModel::onLitrosTextoChange,
                    label         = { Text("Ingreso manual de litros", fontSize = 11.sp) },
                    modifier      = Modifier.fillMaxWidth(),
                    singleLine    = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    suffix        = { Text("L", color = TextMuted) },
                    colors        = if (state.alertaTacho) lightAmberFieldColors() else lightFieldColors(),
                    enabled       = !state.isLoading
                )

                // Capacidad del tacho
                Spacer(Modifier.height(8.dp))
                OutlinedTextField(
                    value           = state.capacidadTachoTexto,
                    onValueChange   = viewModel::onCapacidadTachoChange,
                    label           = { Text("Capacidad del tacho (opcional)", fontSize = 11.sp) },
                    placeholder     = { Text("Ej: 34", color = TextDim, fontSize = 12.sp) },
                    modifier        = Modifier.fillMaxWidth(),
                    singleLine      = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    suffix          = { Text("L", color = TextMuted) },
                    colors          = lightFieldColors(),
                    enabled         = !state.isLoading
                )

                // RF-ACO-07 — Confirmación medición con balde
                Spacer(Modifier.height(10.dp))
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(8.dp))
                        .background(if (state.medicionBaldeConfirmada) GreenBg else AmberBg)
                        .border(
                            1.dp,
                            if (state.medicionBaldeConfirmada) GreenBorder.copy(alpha = 0.4f) else AmberPrimary.copy(alpha = 0.5f),
                            RoundedCornerShape(8.dp)
                        )
                        .clickable(enabled = !state.isLoading) {
                            viewModel.onMedicionBaldeChange(!state.medicionBaldeConfirmada)
                        }
                        .padding(12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Checkbox(
                        checked         = state.medicionBaldeConfirmada,
                        onCheckedChange = viewModel::onMedicionBaldeChange,
                        enabled         = !state.isLoading,
                        colors          = CheckboxDefaults.colors(checkedColor = GreenPrimary)
                    )
                    Column(Modifier.padding(start = 4.dp)) {
                        Text(
                            "Medí con el balde estándar",
                            color = if (state.medicionBaldeConfirmada) GreenPrimary else AmberPrimary,
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Bold
                        )
                        Text(
                            "Obligatorio antes de guardar (RF-ACO-07)",
                            color = TextMuted,
                            fontSize = 10.sp
                        )
                    }
                }
                AnimatedVisibility(
                    visible = !state.medicionBaldeConfirmada && state.litros > 0,
                    enter = fadeIn(),
                    exit = fadeOut()
                ) {
                    Text(
                        "⚠ Debes confirmar la medición con balde para continuar.",
                        color = AmberPrimary,
                        fontSize = 11.sp,
                        modifier = Modifier.padding(top = 6.dp)
                    )
                }

                // Alerta si litros > capacidad del tacho
                AnimatedVisibility(visible = state.alertaTacho, enter = fadeIn(), exit = fadeOut()) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 6.dp)
                            .clip(RoundedCornerShape(8.dp))
                            .background(AmberBg)
                            .border(1.dp, AmberPrimary.copy(alpha = 0.5f), RoundedCornerShape(8.dp))
                            .padding(10.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("⚠  ", color = AmberPrimary, fontSize = 14.sp)
                        Column {
                            Text(
                                "CANTIDAD SOSPECHOSA",
                                color = AmberPrimary, fontSize = 11.sp, fontWeight = FontWeight.Bold
                            )
                            Text(
                                "Los litros ingresados (${state.litrosTexto} L) superan la capacidad del tacho (${state.capacidadTachoTexto} L). Verifica la medición.",
                                color = AmberText, fontSize = 10.sp
                            )
                        }
                    }
                }

                Spacer(Modifier.height(16.dp))

                // ══════════════════════════════════════════════════════════
                // SECCIÓN 3 — PRUEBAS LACTOSCAN
                // ══════════════════════════════════════════════════════════
                AcopioSectionLabel("PRUEBAS DE CALIDAD (LACTOSCAN)")

                // RF-CAL-01 — Equipo LactoScan usado
                Text("Equipo LactoScan usado", color = TextMuted, fontSize = 10.sp)
                Spacer(Modifier.height(6.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    listOf(1, 2, 3).forEach { n ->
                        val selected = state.equipoLactoscan == n
                        FilterChip(
                            selected = selected,
                            onClick  = {
                                viewModel.onEquipoLactoscanChange(if (selected) null else n)
                            },
                            label = { Text("Equipo $n", fontSize = 11.sp) },
                            enabled = !state.isLoading,
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = GreenPrimary,
                                selectedLabelColor     = Color.White
                            )
                        )
                    }
                }

                // ── Botón escanear ticket ─────────────────────────────────
                if (onEscanearTicket != null) {
                    Spacer(Modifier.height(6.dp))
                    Button(
                        onClick  = onEscanearTicket,
                        modifier = Modifier.fillMaxWidth().height(48.dp),
                        shape    = RoundedCornerShape(10.dp),
                        colors   = ButtonDefaults.buttonColors(
                            containerColor = Color(0xFF1565C0),
                            contentColor   = Color.White
                        )
                    ) {
                        Text(
                            "📷  ESCANEAR TICKET LACTOMAT",
                            fontSize   = 13.sp,
                            fontWeight = FontWeight.Bold
                        )
                    }
                    Text(
                        "O ingresa los valores manualmente:",
                        color    = TextMuted,
                        fontSize = 10.sp,
                        modifier = Modifier.padding(top = 8.dp, bottom = 2.dp)
                    )
                }
                Spacer(Modifier.height(8.dp))
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    Column(Modifier.weight(1f)) {
                        Text(
                            "% AGUA (≥10% = Rechazado)",
                            color = if (state.lechRechazada) RedPrimary else TextMuted,
                            fontSize = 10.sp
                        )
                        Spacer(Modifier.height(4.dp))
                        OutlinedTextField(
                            value           = state.porcentajeAgua,
                            onValueChange   = viewModel::onPorcentajeAguaChange,
                            placeholder     = { Text("0.0") },
                            modifier        = Modifier.fillMaxWidth(),
                            singleLine      = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            suffix          = { Text("%") },
                            colors          = if (state.lechRechazada) lightRedFieldColors() else lightFieldColors(),
                            enabled         = !state.isLoading
                        )
                    }
                    Column(Modifier.weight(1f)) {
                        val phVal  = state.acidezPh.toDoubleOrNull() ?: 0.0
                        val phBad  = state.acidezPh.isNotEmpty() && (phVal < 6.0 || phVal > 7.0)
                        Text(
                            "ACIDEZ (pH 6.4 – 6.8)",
                            color = if (phBad) AmberPrimary else TextMuted,
                            fontSize = 10.sp
                        )
                        Spacer(Modifier.height(4.dp))
                        OutlinedTextField(
                            value           = state.acidezPh,
                            onValueChange   = viewModel::onAcidezPhChange,
                            placeholder     = { Text("6.5") },
                            modifier        = Modifier.fillMaxWidth(),
                            singleLine      = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            suffix          = { Text("pH") },
                            colors          = if (phBad) lightAmberFieldColors() else lightFieldColors(),
                            enabled         = !state.isLoading
                        )
                    }
                }

                // Campo de densidad
                Spacer(Modifier.height(8.dp))
                val densidadVal = state.densidad.toDoubleOrNull() ?: 0.0
                val densidadBad = state.densidad.isNotEmpty() && (densidadVal < 1.028 || densidadVal > 1.033)
                Text(
                    "DENSIDAD (Normal: 1.028 – 1.033 g/mL)",
                    color = if (densidadBad) AmberPrimary else TextMuted,
                    fontSize = 10.sp
                )
                Spacer(Modifier.height(4.dp))
                OutlinedTextField(
                    value           = state.densidad,
                    onValueChange   = viewModel::onDensidadChange,
                    placeholder     = { Text("1.030") },
                    modifier        = Modifier.fillMaxWidth(),
                    singleLine      = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    suffix          = { Text("g/mL") },
                    colors          = if (densidadBad) lightAmberFieldColors() else lightFieldColors(),
                    enabled         = !state.isLoading
                )

                // ── Campos LACTOMAT adicionales ────────────────────────────────
                Spacer(Modifier.height(12.dp))
                AcopioSectionLabel("PARÁMETROS ADICIONALES (LACTOMAT)")
                Spacer(Modifier.height(8.dp))

                // Fila 1: Grasa + Proteína
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    Column(Modifier.weight(1f)) {
                        Text("GRASA (%)", color = TextMuted, fontSize = 10.sp)
                        Spacer(Modifier.height(4.dp))
                        OutlinedTextField(
                            value           = state.grasa,
                            onValueChange   = viewModel::onGrasaChange,
                            placeholder     = { Text("3.5") },
                            modifier        = Modifier.fillMaxWidth(),
                            singleLine      = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            suffix          = { Text("%") },
                            colors          = lightFieldColors(),
                            enabled         = !state.isLoading
                        )
                    }
                    Column(Modifier.weight(1f)) {
                        Text("PROTEÍNA (%)", color = TextMuted, fontSize = 10.sp)
                        Spacer(Modifier.height(4.dp))
                        OutlinedTextField(
                            value           = state.proteina,
                            onValueChange   = viewModel::onProteinaChange,
                            placeholder     = { Text("3.2") },
                            modifier        = Modifier.fillMaxWidth(),
                            singleLine      = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            suffix          = { Text("%") },
                            colors          = lightFieldColors(),
                            enabled         = !state.isLoading
                        )
                    }
                }

                // Fila 2: Lactosa + SNG
                Spacer(Modifier.height(8.dp))
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    Column(Modifier.weight(1f)) {
                        Text("LACTOSA (%)", color = TextMuted, fontSize = 10.sp)
                        Spacer(Modifier.height(4.dp))
                        OutlinedTextField(
                            value           = state.lactosa,
                            onValueChange   = viewModel::onLactosaChange,
                            placeholder     = { Text("4.7") },
                            modifier        = Modifier.fillMaxWidth(),
                            singleLine      = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            suffix          = { Text("%") },
                            colors          = lightFieldColors(),
                            enabled         = !state.isLoading
                        )
                    }
                    Column(Modifier.weight(1f)) {
                        Text("SNG (%)", color = TextMuted, fontSize = 10.sp)
                        Spacer(Modifier.height(4.dp))
                        OutlinedTextField(
                            value           = state.sng,
                            onValueChange   = viewModel::onSngChange,
                            placeholder     = { Text("8.5") },
                            modifier        = Modifier.fillMaxWidth(),
                            singleLine      = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            suffix          = { Text("%") },
                            colors          = lightFieldColors(),
                            enabled         = !state.isLoading
                        )
                    }
                }

                // Fila 3: Sales + Total Sólidos
                Spacer(Modifier.height(8.dp))
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    Column(Modifier.weight(1f)) {
                        Text("SALES (%)", color = TextMuted, fontSize = 10.sp)
                        Spacer(Modifier.height(4.dp))
                        OutlinedTextField(
                            value           = state.sales,
                            onValueChange   = viewModel::onSalesChange,
                            placeholder     = { Text("0.7") },
                            modifier        = Modifier.fillMaxWidth(),
                            singleLine      = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            suffix          = { Text("%") },
                            colors          = lightFieldColors(),
                            enabled         = !state.isLoading
                        )
                    }
                    Column(Modifier.weight(1f)) {
                        Text("TOTAL SÓLIDOS (%)", color = TextMuted, fontSize = 10.sp)
                        Spacer(Modifier.height(4.dp))
                        OutlinedTextField(
                            value           = state.totalSolidos,
                            onValueChange   = viewModel::onTotalSolidosChange,
                            placeholder     = { Text("12.0") },
                            modifier        = Modifier.fillMaxWidth(),
                            singleLine      = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            suffix          = { Text("%") },
                            colors          = lightFieldColors(),
                            enabled         = !state.isLoading
                        )
                    }
                }

                // Fila 4: Punto de Congelación (campo completo)
                Spacer(Modifier.height(8.dp))
                val pCongVal = state.puntoCongelacion.toDoubleOrNull() ?: 0.0
                val pCongelBad = state.puntoCongelacion.isNotEmpty() &&
                    (pCongVal > -0.500 || pCongVal < -0.560)
                Text(
                    "PUNTO CONGELACIÓN (Normal: -0.520 a -0.530 °C)",
                    color = if (pCongelBad) AmberPrimary else TextMuted,
                    fontSize = 10.sp
                )
                Spacer(Modifier.height(4.dp))
                OutlinedTextField(
                    value           = state.puntoCongelacion,
                    onValueChange   = viewModel::onPuntoCongelacionChange,
                    placeholder     = { Text("-0.525") },
                    modifier        = Modifier.fillMaxWidth(),
                    singleLine      = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    suffix          = { Text("°C") },
                    colors          = if (pCongelBad) lightAmberFieldColors() else lightFieldColors(),
                    enabled         = !state.isLoading
                )

                // Aviso de descuento por ~1% agua
                AnimatedVisibility(visible = state.tieneDescuento, enter = fadeIn(), exit = fadeOut()) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 8.dp)
                            .clip(RoundedCornerShape(8.dp))
                            .background(AmberBg)
                            .border(1.dp, AmberPrimary.copy(alpha = 0.4f), RoundedCornerShape(8.dp))
                            .padding(10.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("⚠  ", color = AmberPrimary, fontSize = 14.sp)
                        Column {
                            Text("DESCUENTO APLICADO", color = AmberPrimary, fontSize = 11.sp, fontWeight = FontWeight.Bold)
                            Text("% Agua ~1%: se aplicará descuento en la liquidación de esta semana.",
                                color = AmberText, fontSize = 10.sp)
                        }
                    }
                }

                Spacer(Modifier.height(10.dp))

                // ── Estado de calidad (chips dinámicos) ────────────────────
                AcopioSectionLabel("ESTADO / CALIDAD DE LA LECHE")
                Spacer(Modifier.height(6.dp))

                val phVal = state.acidezPh.toDoubleOrNull() ?: 6.5
                val esObservada = !state.lechRechazada &&
                    state.acidezPh.isNotEmpty() &&
                    (phVal < 6.0 || phVal > 7.0)
                val esAprobada  = !state.lechRechazada && !esObservada &&
                    state.porcentajeAgua.isNotEmpty()

                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    QualityChip("APROBADA",  GreenPrimary, GreenBg,  esAprobada,         Modifier.weight(1f))
                    QualityChip("OBSERVADA", AmberPrimary, AmberBg,  esObservada,        Modifier.weight(1f))
                    QualityChip("RECHAZADA", RedPrimary,   RedBg,    state.lechRechazada, Modifier.weight(1f))
                }

                // ── Bloque de rechazo ──────────────────────────────────────
                AnimatedVisibility(visible = state.lechRechazada, enter = fadeIn(), exit = fadeOut()) {
                    Column {
                        Spacer(Modifier.height(10.dp))
                        Row(
                            modifier = Modifier.fillMaxWidth()
                                .clip(RoundedCornerShape(8.dp))
                                .background(RedBg)
                                .border(1.dp, RedBorder.copy(alpha = 0.4f), RoundedCornerShape(8.dp))
                                .padding(12.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text("✗  ", color = RedPrimary, fontSize = 16.sp, fontWeight = FontWeight.Bold)
                            Column {
                                Text("ALERTA — LECHE RECHAZADA", color = RedPrimary,
                                    fontSize = 12.sp, fontWeight = FontWeight.ExtraBold)
                                Text("% Agua supera el límite (5%). Requiere evidencia fotográfica.",
                                    color = RedText, fontSize = 10.sp)
                            }
                        }
                        Spacer(Modifier.height(8.dp))
                        Button(
                            onClick  = { onTomarFotoEvidencia?.invoke() },
                            enabled  = onTomarFotoEvidencia != null && !state.isLoading,
                            modifier = Modifier.fillMaxWidth().height(48.dp),
                            shape    = RoundedCornerShape(10.dp),
                            colors   = ButtonDefaults.buttonColors(containerColor = RedPrimary, contentColor = Color.White)
                        ) {
                            Text(
                                if (state.fotoEvidencia == null) "📷  TOMAR FOTO DE EVIDENCIA"
                                else "📷  VOLVER A TOMAR FOTO",
                                fontSize = 13.sp, fontWeight = FontWeight.Bold
                            )
                        }
                        if (state.fotoEvidencia != null) {
                            Spacer(Modifier.height(4.dp))
                            Text("✓ Foto de evidencia adjunta", color = RedText,
                                fontSize = 11.sp, fontWeight = FontWeight.Bold)
                        }
                        Spacer(Modifier.height(8.dp))
                        OutlinedTextField(
                            value         = state.observacionesRechazo,
                            onValueChange = viewModel::onObservacionesRechazoChange,
                            label         = { Text("OBSERVACIONES DEL RECHAZO") },
                            modifier      = Modifier.fillMaxWidth(),
                            minLines      = 2,
                            colors        = lightRedFieldColors(),
                            enabled       = !state.isLoading
                        )
                    }
                }

                // ── Error del formulario ───────────────────────────────────
                AnimatedVisibility(visible = state.error != null, enter = fadeIn(), exit = fadeOut()) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 10.dp)
                            .clip(RoundedCornerShape(8.dp))
                            .background(RedBg)
                            .padding(12.dp)
                    ) {
                        Text("⚠  ", color = RedPrimary)
                        Text(state.error ?: "", color = RedPrimary, fontSize = 12.sp)
                    }
                }

                Spacer(Modifier.height(20.dp))

                // ══════════════════════════════════════════════════════════
                // BOTÓN GUARDAR
                // ══════════════════════════════════════════════════════════
                Button(
                    onClick  = viewModel::guardarAcopio,
                    enabled  = !state.isLoading && state.ganaderoNombre.isNotBlank()
                               && state.ganaderoId > 0 && state.litros > 0
                               && !state.ganaderoSuspendido && !state.alertaTacho
                               && state.medicionBaldeConfirmada,
                    modifier = Modifier.fillMaxWidth().height(56.dp),
                    shape    = RoundedCornerShape(12.dp),
                    colors   = ButtonDefaults.buttonColors(
                        containerColor         = GreenPrimary,
                        contentColor           = Color.White,
                        disabledContainerColor = GreenPrimary.copy(alpha = 0.45f),
                        disabledContentColor   = Color.White.copy(alpha = 0.6f)
                    )
                ) {
                    if (state.isLoading) {
                        CircularProgressIndicator(
                            modifier    = Modifier.size(22.dp),
                            color       = Color.White,
                            strokeWidth = 2.dp
                        )
                        Spacer(Modifier.width(10.dp))
                        Text("GUARDANDO...", fontSize = 15.sp, fontWeight = FontWeight.Bold)
                    } else {
                        Text("◎  GUARDAR ACOPIO (GPS)", fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
                    }
                }

                // Aviso cuando el ganadero no está seleccionado
                AnimatedVisibility(
                    visible = state.ganaderoNombre.isBlank() || state.litros <= 0.0 || (state.ganaderoNombre.isNotBlank() && state.ganaderoId == 0)
                ) {
                    Column(modifier = Modifier.padding(top = 6.dp)) {
                        if (state.ganaderoNombre.isNotBlank() && state.ganaderoId == 0) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clip(RoundedCornerShape(8.dp))
                                    .background(AmberBg)
                                    .padding(10.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text("⚠  ", color = AmberPrimary)
                                Text(
                                    "\"${state.ganaderoNombre}\" no está en tu lista de ruta. Selecciónalo del dropdown.",
                                    color = AmberPrimary, fontSize = 11.sp
                                )
                            }
                        } else {
                            Text(
                                buildString {
                                    if (state.ganaderoNombre.isBlank()) append("• Selecciona un productor  ")
                                    if (state.litros <= 0.0) append("• Ingresa los litros")
                                },
                                color    = TextMuted,
                                fontSize = 11.sp
                            )
                        }
                    }
                }

                Spacer(Modifier.height(24.dp))
            }
        }
    }
}

// ── Composables auxiliares ─────────────────────────────────────────────────────

@Composable
private fun AcopioSectionLabel(text: String) {
    Text(text, color = TextMuted, fontSize = 10.sp, fontWeight = FontWeight.SemiBold, letterSpacing = 1.sp)
}

@Composable
private fun QualityChip(label: String, textColor: Color, bg: Color, active: Boolean, modifier: Modifier) {
    Box(
        modifier = modifier
            .clip(RoundedCornerShape(8.dp))
            .background(if (active) bg else BgCardAlt)
            .border(1.5.dp, if (active) textColor.copy(alpha = 0.6f) else Divider, RoundedCornerShape(8.dp))
            .padding(vertical = 10.dp),
        contentAlignment = Alignment.Center
    ) {
        Text(label, color = if (active) textColor else TextDim,
            fontSize = 11.sp, fontWeight = FontWeight.Bold)
    }
}

@Composable
private fun lightFieldColors() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor      = GreenBorder,
    unfocusedBorderColor    = Divider,
    focusedLabelColor       = GreenPrimary,
    unfocusedLabelColor     = TextMuted,
    focusedContainerColor   = BgCard,
    unfocusedContainerColor = BgCard,
    focusedTextColor        = TextPrimary,
    unfocusedTextColor      = TextPrimary,
    cursorColor             = GreenPrimary
)

@Composable
private fun lightRedFieldColors() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor      = RedBorder,
    unfocusedBorderColor    = RedBorder,
    focusedLabelColor       = RedPrimary,
    unfocusedLabelColor     = RedPrimary,
    focusedContainerColor   = RedBg,
    unfocusedContainerColor = RedBg,
    focusedTextColor        = TextPrimary,
    unfocusedTextColor      = TextPrimary,
    cursorColor             = RedPrimary
)

@Composable
private fun lightAmberFieldColors() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor      = AmberPrimary,
    unfocusedBorderColor    = AmberPrimary,
    focusedLabelColor       = AmberPrimary,
    unfocusedLabelColor     = AmberText,
    focusedContainerColor   = AmberBg,
    unfocusedContainerColor = AmberBg,
    focusedTextColor        = TextPrimary,
    unfocusedTextColor      = TextPrimary,
    cursorColor             = AmberPrimary
)
