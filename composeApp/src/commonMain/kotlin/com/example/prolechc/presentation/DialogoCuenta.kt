package com.example.prolechc.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.prolechc.data.local.ConfigLocal
import com.example.prolechc.data.remote.ApiConfig

/**
 * Cuenta del chofer: servidor (se puede cambiar sin cerrar sesión, p. ej. si la PC cambió de IP),
 * sincronizar y cerrar sesión. Solo deja salir cuando no quedan acopios por subir.
 */
@Composable
fun DialogoCuenta(
    config: ConfigLocal,
    pendientes: Int,
    sincronizando: Boolean,
    onSincronizar: () -> Unit,
    onServidorCambiado: () -> Unit,
    onCerrarSesion: () -> Unit,
    onCerrar: () -> Unit
) {
    var servidor by remember { mutableStateOf(config.servidor().removePrefix("http://")) }
    var aviso by remember { mutableStateOf<String?>(null) }
    val chofer = config.nombreChofer().ifBlank { config.ultimoUsuario().orEmpty() }

    AlertDialog(
        onDismissRequest = onCerrar,
        title = { Text("👤 Mi cuenta") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text(chofer.ifBlank { "Chofer" }, fontWeight = FontWeight.Bold)
                Text(
                    if (pendientes > 0) "⏳ $pendientes acopio(s) por subir" else "✓ Todo sincronizado",
                    color = if (pendientes > 0) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.primary
                )
                OutlinedTextField(
                    value = servidor,
                    onValueChange = { servidor = it; aviso = null },
                    label = { Text("Servidor (IP de la PC o dominio)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedButton(
                    onClick = {
                        config.guardarServidor(servidor)
                        aviso = "Servidor guardado: ${ApiConfig.BASE_URL}"
                        onServidorCambiado()
                    },
                    enabled = servidor.isNotBlank(),
                    modifier = Modifier.fillMaxWidth()
                ) { Text("Guardar servidor") }
                OutlinedButton(
                    onClick = onSincronizar,
                    enabled = !sincronizando,
                    modifier = Modifier.fillMaxWidth()
                ) { Text(if (sincronizando) "Sincronizando…" else "🔄 Sincronizar ahora") }
                aviso?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                Button(
                    onClick = onCerrarSesion,
                    enabled = pendientes == 0,
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error),
                    modifier = Modifier.fillMaxWidth()
                ) { Text("Cerrar sesión") }
                if (pendientes > 0) {
                    Text(
                        "Sincroniza antes de salir: si cierras sesión, esos acopios no llegarían a la planta.",
                        style = MaterialTheme.typography.bodySmall
                    )
                }
            }
        },
        confirmButton = { TextButton(onClick = onCerrar) { Text("Listo") } }
    )
}
