package com.example.prolechc.presentation.productos

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.example.prolechc.domain.model.AcopioHistorial
import com.example.prolechc.presentation.formato
import org.koin.compose.viewmodel.koinViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AcopioListScreen(
    viewModel: AcopioListViewModel = koinViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    // Mostrar errores en Snackbar
    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearError()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Historial de Acopios") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer
                )
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { innerPadding ->

        if (uiState.isLoading) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator()
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding)
            ) {
                // Resumen de ruta
                uiState.ruta?.let { ruta ->
                    RutaResumenCard(
                        rutaNombre      = ruta.rutaNombre,
                        capacidadLitros = ruta.capacidadLitros,
                        totalLitros     = uiState.totalLitros,
                        totalParadas    = ruta.paradas.size
                    )
                }

                if (uiState.acopios.isEmpty()) {
                    Box(
                        modifier = Modifier.fillMaxSize(),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            text  = "No hay acopios registrados",
                            style = MaterialTheme.typography.bodyLarge,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                } else {
                    LazyColumn(
                        contentPadding = PaddingValues(16.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        items(uiState.acopios, key = { it.id }) { acopio ->
                            AcopioCard(acopio = acopio)
                        }
                    }
                }
            }
        }
    }
}

// ── Componentes internos ──────────────────────────────────────────────────────

@Composable
private fun RutaResumenCard(
    rutaNombre: String,
    capacidadLitros: Double,
    totalLitros: Double,
    totalParadas: Int
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 8.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.secondaryContainer
        )
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text  = rutaNombre,
                style = MaterialTheme.typography.titleMedium
            )
            Row(
                modifier            = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(
                    text  = "Paradas: $totalParadas",
                    style = MaterialTheme.typography.bodyMedium
                )
                Text(
                    text  = "${formato(totalLitros, 1)} / ${formato(capacidadLitros, 0)} L",
                    style = MaterialTheme.typography.bodyMedium
                )
            }
        }
    }
}

@Composable
private fun AcopioCard(acopio: AcopioHistorial) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier              = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(
                    text  = acopio.ganaderoNombre,
                    style = MaterialTheme.typography.titleSmall
                )
                Text(
                    text  = acopio.estado,
                    style = MaterialTheme.typography.labelMedium,
                    color = when (acopio.estado.uppercase()) {
                        "RECHAZADO" -> MaterialTheme.colorScheme.error
                        "OBSERVADO" -> MaterialTheme.colorScheme.tertiary
                        else        -> MaterialTheme.colorScheme.primary
                    }
                )
            }
            Text(
                text  = "${formato(acopio.litros, 2)} L",
                style = MaterialTheme.typography.bodyLarge
            )
            acopio.porcentajeAgua?.let {
                Text(
                    text  = "Agua: ${formato(it, 2)}%",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Text(
                text  = acopio.fecha,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}
