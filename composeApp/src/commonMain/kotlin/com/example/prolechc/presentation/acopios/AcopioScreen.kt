package com.example.prolechc.presentation.acopios

import androidx.compose.foundation.background
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.ui.draw.clip
import com.example.prolechc.presentation.theme.ProLechColores
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.domain.model.EstadoSync
import com.example.prolechc.domain.model.GanaderoCampo
import com.example.prolechc.presentation.formato
import org.koin.compose.viewmodel.koinViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AcopioScreen(
    onSesionCerrada: () -> Unit = {},
    viewModel: AcopioViewModel = koinViewModel()
) {
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    val snackbarHostState = remember { SnackbarHostState() }
    var porEliminar by remember { mutableStateOf<Acopio?>(null) }

    LaunchedEffect(state.mensaje) {
        state.mensaje?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.limpiarMensaje()
        }
    }

    porEliminar?.let { acopio ->
        AlertDialog(
            onDismissRequest = { porEliminar = null },
            title = { Text("Eliminar acopio") },
            text = { Text("¿Eliminar ${formato(acopio.litros, 2)} L de ${acopio.ganaderoNombre}?") },
            confirmButton = {
                TextButton(onClick = {
                    viewModel.eliminar(acopio.id)
                    porEliminar = null
                }) { Text("Eliminar") }
            },
            dismissButton = { TextButton(onClick = { porEliminar = null }) { Text("Cancelar") } }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Acopio en campo") },
                actions = {
                    if (state.sincronizando) {
                        CircularProgressIndicator(Modifier.size(24.dp).padding(end = 8.dp))
                    } else {
                        TextButton(onClick = viewModel::sincronizar) {
                            Text("Sincronizar (${state.pendientes})")
                        }
                    }
                    TextButton(onClick = { viewModel.cerrarSesion(onSesionCerrada) }) {
                        Text("Salir")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer
                )
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { innerPadding ->
        LazyColumn(
            modifier            = Modifier.fillMaxSize().padding(innerPadding),
            contentPadding      = PaddingValues(bottom = 16.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            item { ResumenDia(state) }
            item {
                FormularioAcopioCard(
                    form      = state.formulario,
                    ganaderos = state.ganaderos,
                    viewModel = viewModel
                )
            }
            item {
                Text(
                    text     = "Registros",
                    style    = MaterialTheme.typography.titleMedium,
                    modifier = Modifier.padding(start = 16.dp, top = 8.dp)
                )
            }
            if (state.acopios.isEmpty()) {
                item {
                    Text(
                        text     = "Aún no hay acopios. Registra el primero aunque estés sin señal.",
                        style    = MaterialTheme.typography.bodyMedium,
                        color    = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(16.dp)
                    )
                }
            }
            items(state.acopios, key = { it.id }) { acopio ->
                AcopioItem(
                    acopio     = acopio,
                    editando   = state.formulario.editando?.id == acopio.id,
                    onEditar   = { viewModel.editar(acopio) },
                    onEliminar = { porEliminar = acopio }
                )
            }
        }
    }
}

/** Tarjeta superior: litros del día, registros, pendientes y estado de la conexión. */
@Composable
private fun ResumenDia(state: AcopioUiState) {
    Column(
        Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 8.dp)
            .clip(RoundedCornerShape(24.dp))
            .background(ProLechColores.DegradadoOscuro)
            .padding(20.dp)
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Text(
                "LITROS ACOPIADOS",
                color = Color(0xFF9EC5FF), style = MaterialTheme.typography.labelMedium,
                modifier = Modifier.weight(1f)
            )
            Text(
                if (state.enLinea) "● En línea" else "● Sin señal",
                color = if (state.enLinea) ProLechColores.Exito else ProLechColores.Caramelo,
                style = MaterialTheme.typography.labelMedium,
                modifier = Modifier.clip(RoundedCornerShape(99.dp)).background(Color.White)
                    .padding(horizontal = 10.dp, vertical = 4.dp)
            )
        }
        Text(
            "${formato(state.totalLitros, 1)} L",
            color = Color.White,
            style = MaterialTheme.typography.headlineMedium
        )
        Row(Modifier.padding(top = 10.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            Chip("🧾 ${state.acopios.size} registros")
            Chip(if (state.pendientes == 0) "✓ Todo sincronizado" else "⏳ ${state.pendientes} por subir")
        }
    }
}

@Composable
private fun Chip(texto: String) {
    Text(
        texto, color = Color.White, style = MaterialTheme.typography.labelMedium,
        modifier = Modifier.clip(RoundedCornerShape(12.dp)).background(Color.White.copy(alpha = .1f))
            .padding(horizontal = 10.dp, vertical = 6.dp)
    )
}

@Composable
private fun FormularioAcopioCard(
    form: FormularioAcopio,
    ganaderos: List<GanaderoCampo>,
    viewModel: AcopioViewModel
) {
    val numerico = KeyboardOptions(keyboardType = KeyboardType.Decimal)

    Card(Modifier.fillMaxWidth().padding(horizontal = 16.dp)) {
        Column(
            Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            Text(
                text  = if (form.editando == null) "Nuevo acopio" else "Editando acopio",
                style = MaterialTheme.typography.titleMedium
            )

            if (ganaderos.isNotEmpty()) {
                SelectorGanadero(
                    seleccionado = form.ganaderoNombre,
                    ganaderos    = ganaderos,
                    onSeleccion  = viewModel::onGanaderoSeleccionado
                )
            } else {
                // Sin catálogo descargado todavía: ingreso manual
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = form.ganaderoId, onValueChange = viewModel::onGanaderoId,
                        label = { Text("ID") }, singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        modifier = Modifier.weight(1f)
                    )
                    OutlinedTextField(
                        value = form.ganaderoNombre, onValueChange = viewModel::onGanaderoNombre,
                        label = { Text("Ganadero") }, singleLine = true,
                        modifier = Modifier.weight(2f)
                    )
                }
            }

            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(
                    value = form.litros, onValueChange = viewModel::onLitros,
                    label = { Text("Litros") }, keyboardOptions = numerico,
                    singleLine = true, modifier = Modifier.weight(1f)
                )
                OutlinedTextField(
                    value = form.porcentajeAgua, onValueChange = viewModel::onPorcentajeAgua,
                    label = { Text("% Agua") }, keyboardOptions = numerico,
                    singleLine = true, modifier = Modifier.weight(1f)
                )
                OutlinedTextField(
                    value = form.densidad, onValueChange = viewModel::onDensidad,
                    label = { Text("Densidad") }, keyboardOptions = numerico,
                    singleLine = true, modifier = Modifier.weight(1f)
                )
            }

            form.calidadPrevia?.let { calidad ->
                Text(
                    text  = "Calidad estimada: ${calidad.name}",
                    style = MaterialTheme.typography.labelLarge,
                    color = colorCalidad(calidad)
                )
            }

            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                if (form.editando != null) {
                    OutlinedButton(onClick = viewModel::cancelarEdicion, modifier = Modifier.weight(1f)) {
                        Text("Cancelar")
                    }
                }
                Button(onClick = viewModel::guardar, modifier = Modifier.weight(1f)) {
                    Text(if (form.editando == null) "Registrar acopio" else "Guardar cambios")
                }
            }
        }
    }
}

@Composable
private fun SelectorGanadero(
    seleccionado: String,
    ganaderos: List<GanaderoCampo>,
    onSeleccion: (GanaderoCampo) -> Unit
) {
    var abierto by remember { mutableStateOf(false) }
    Box {
        OutlinedButton(onClick = { abierto = true }, modifier = Modifier.fillMaxWidth()) {
            Text(seleccionado.ifBlank { "Seleccionar ganadero (${ganaderos.size})" })
        }
        DropdownMenu(expanded = abierto, onDismissRequest = { abierto = false }) {
            ganaderos.forEach { g ->
                DropdownMenuItem(
                    text = {
                        Column {
                            Text(g.nombre)
                            Text(
                                text  = if (g.suspendido) "DNI ${g.dni} · SUSPENDIDO" else "DNI ${g.dni}",
                                style = MaterialTheme.typography.bodySmall,
                                color = if (g.suspendido) MaterialTheme.colorScheme.error
                                        else MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    },
                    enabled = !g.suspendido,
                    onClick = {
                        onSeleccion(g)
                        abierto = false
                    }
                )
            }
        }
    }
}

@Composable
private fun AcopioItem(
    acopio: Acopio,
    editando: Boolean,
    onEditar: () -> Unit,
    onEliminar: () -> Unit
) {
    Card(
        modifier  = Modifier.fillMaxWidth().padding(horizontal = 16.dp).clickable(onClick = onEditar),
        elevation = CardDefaults.cardElevation(defaultElevation = if (editando) 6.dp else 2.dp),
        colors    = if (editando) {
            CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.secondaryContainer)
        } else {
            CardDefaults.cardColors()
        }
    ) {
        Row(
            Modifier.fillMaxWidth().padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(Modifier.weight(1f)) {
                Text(acopio.ganaderoNombre, style = MaterialTheme.typography.titleSmall)
                Text(
                    "${formato(acopio.litros, 2)} L · Agua ${formato(acopio.porcentajeAgua, 1)}% · " +
                        "Dens. ${formato(acopio.densidad, 3)}",
                    style = MaterialTheme.typography.bodySmall
                )
                Text(
                    text  = acopio.estadoCalidad.name,
                    style = MaterialTheme.typography.labelMedium,
                    color = colorCalidad(acopio.estadoCalidad)
                )
                Text(
                    text  = when (acopio.estadoSync) {
                        EstadoSync.SINCRONIZADO         -> "✓ Sincronizado"
                        EstadoSync.PENDIENTE_CREAR      -> "⏳ Pendiente de subir"
                        EstadoSync.PENDIENTE_ACTUALIZAR -> "⏳ Cambios pendientes"
                        EstadoSync.PENDIENTE_ELIMINAR   -> "⏳ Eliminación pendiente"
                    },
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            TextButton(onClick = onEliminar) { Text("Eliminar") }
        }
    }
}

@Composable
private fun colorCalidad(calidad: EstadoCalidad): Color = when (calidad) {
    EstadoCalidad.RECHAZADO -> MaterialTheme.colorScheme.error
    EstadoCalidad.OBSERVADO -> MaterialTheme.colorScheme.tertiary
    EstadoCalidad.APROBADO  -> MaterialTheme.colorScheme.primary
}
