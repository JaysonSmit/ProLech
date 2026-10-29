package com.example.prolechc.presentation.avisos

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.prolechc.domain.model.Notificacion

/** Bandeja de avisos: los no leídos resaltados; tocar uno lo marca como leído. */
@Composable
fun DialogoAvisos(
    estado: AvisosUiState,
    onLeer: (Int) -> Unit,
    onLeerTodos: () -> Unit,
    onActualizar: () -> Unit,
    onCerrar: () -> Unit
) {
    AlertDialog(
        onDismissRequest = onCerrar,
        title = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text("🔔 Avisos", modifier = Modifier.weight(1f))
                if (estado.noLeidas > 0) {
                    Text("${estado.noLeidas} sin leer", style = MaterialTheme.typography.labelLarge, color = MaterialTheme.colorScheme.primary)
                }
            }
        },
        text = {
            when {
                estado.avisos.isEmpty() && estado.cargando -> Text("Cargando avisos…")
                estado.avisos.isEmpty() -> Text(estado.error ?: "No tienes avisos. Aquí llegarán las reuniones y comunicados de la planta.")
                else -> LazyColumn(
                    modifier = Modifier.heightIn(max = 460.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(estado.avisos, key = { it.id }) { aviso -> TarjetaAviso(aviso) { onLeer(aviso.id) } }
                }
            }
        },
        confirmButton = { TextButton(onClick = onCerrar) { Text("Cerrar") } },
        dismissButton = {
            Row {
                TextButton(onClick = onActualizar) { Text("Actualizar") }
                if (estado.noLeidas > 0) TextButton(onClick = onLeerTodos) { Text("Marcar todo leído") }
            }
        }
    )
}

@Composable
private fun TarjetaAviso(aviso: Notificacion, onLeer: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth().clickable(enabled = !aviso.leida, onClick = onLeer),
        colors = CardDefaults.cardColors(
            containerColor = if (aviso.leida) MaterialTheme.colorScheme.surfaceVariant
                             else MaterialTheme.colorScheme.primaryContainer
        )
    ) {
        Column(Modifier.padding(12.dp)) {
            Text(
                aviso.titulo,
                style = MaterialTheme.typography.titleSmall,
                fontWeight = if (aviso.leida) FontWeight.Normal else FontWeight.Bold
            )
            Text(aviso.mensaje.replace("*", ""), style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 4.dp))
            Row(Modifier.fillMaxWidth().padding(top = 6.dp)) {
                Text(aviso.fechaCorta, style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.weight(1f))
                Text(if (aviso.leida) "Leído" else "Toca para marcar leído", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.primary)
            }
        }
    }
}
