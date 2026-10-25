package com.example.prolechc.presentation.login

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.presentation.theme.ProLechColores
import org.koin.compose.viewmodel.koinViewModel

@Composable
fun LoginScreen(
    onLoginExitoso: () -> Unit,
    viewModel: LoginViewModel = koinViewModel()
) {
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    var verClave by remember { mutableStateOf(false) }
    var verServidor by remember { mutableStateOf(false) }

    LaunchedEffect(state.exitoso) {
        if (state.exitoso) onLoginExitoso()
    }

    Box(
        Modifier.fillMaxSize().background(ProLechColores.DegradadoOscuro),
        contentAlignment = Alignment.Center
    ) {
        Column(
            Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Box(
                Modifier.size(76.dp).clip(RoundedCornerShape(24.dp)).background(ProLechColores.Degradado),
                contentAlignment = Alignment.Center
            ) { Text("🧀", fontSize = 38.sp) }
            Spacer(Modifier.height(16.dp))
            Text("ProLech", color = Color.White, fontSize = 34.sp, fontWeight = FontWeight.ExtraBold)
            Text("Acopio de leche en campo · Huata", color = Color(0xFF9EC5FF), fontSize = 14.sp)
            Spacer(Modifier.height(28.dp))

            Card(
                modifier = Modifier.fillMaxWidth().widthIn(max = 440.dp),
                shape = RoundedCornerShape(28.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
            ) {
                Column(Modifier.padding(24.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
                    Text("Ingresar", style = MaterialTheme.typography.headlineSmall)
                    Text(
                        "Usa la cuenta de chofer que te dio la administración.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    OutlinedTextField(
                        value = state.email,
                        onValueChange = viewModel::onEmail,
                        label = { Text("Correo") },
                        leadingIcon = { Text("✉️") },
                        singleLine = true,
                        shape = RoundedCornerShape(16.dp),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
                        modifier = Modifier.fillMaxWidth()
                    )
                    OutlinedTextField(
                        value = state.password,
                        onValueChange = viewModel::onPassword,
                        label = { Text("Contraseña") },
                        leadingIcon = { Text("🔑") },
                        trailingIcon = {
                            TextButton(onClick = { verClave = !verClave }) { Text(if (verClave) "Ocultar" else "Ver") }
                        },
                        singleLine = true,
                        shape = RoundedCornerShape(16.dp),
                        visualTransformation = if (verClave) VisualTransformation.None else PasswordVisualTransformation(),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                        modifier = Modifier.fillMaxWidth()
                    )
                    TextButton(onClick = { verServidor = !verServidor }) {
                        Text(
                            (if (verServidor) "▾ " else "▸ ") + "Servidor: " + state.servidor.removePrefix("http://"),
                            style = MaterialTheme.typography.bodySmall
                        )
                    }
                    if (verServidor) {
                        OutlinedTextField(
                            value = state.servidor,
                            onValueChange = viewModel::onServidor,
                            label = { Text("Dirección del servidor") },
                            placeholder = { Text("192.168.0.105:8000") },
                            leadingIcon = { Text("🖥️") },
                            singleLine = true,
                            shape = RoundedCornerShape(16.dp),
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Uri),
                            supportingText = { Text("IP de la PC con Laravel (php artisan serve --host=0.0.0.0)") },
                            modifier = Modifier.fillMaxWidth()
                        )
                    }
                    state.error?.let {
                        Text(
                            it, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall,
                            modifier = Modifier.clip(RoundedCornerShape(10.dp))
                                .background(MaterialTheme.colorScheme.errorContainer).padding(10.dp).fillMaxWidth()
                        )
                    }
                    Button(
                        onClick = viewModel::ingresar,
                        enabled = !state.cargando,
                        shape = RoundedCornerShape(999.dp),
                        modifier = Modifier.fillMaxWidth().height(52.dp)
                    ) {
                        if (state.cargando) {
                            CircularProgressIndicator(Modifier.size(22.dp), strokeWidth = 2.dp, color = Color.White)
                        } else {
                            Text("Ingresar", fontWeight = FontWeight.ExtraBold, fontSize = 16.sp)
                        }
                    }
                    Text(
                        "📶 Solo necesitas señal para entrar la primera vez. Luego la app funciona sin internet.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
        }
    }
}
