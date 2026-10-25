package com.example.prolechc.ui.screens

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.focus.FocusDirection
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.ui.theme.*
import com.example.prolechc.viewmodel.ProLechViewModel

// ══════════════════════════════════════════════════════════════════════════════
//  LoginScreen — Autenticación real con Laravel Sanctum
//
//  Misma paleta y estilo visual que la app original.
//  Ahora con campos Email + Contraseña en lugar de los selectores mock.
//  El botón "INICIAR RUTA" llama a viewModel.login() que hace POST /api/login.
// ══════════════════════════════════════════════════════════════════════════════

@Composable
fun LoginScreen(
    viewModel: ProLechViewModel,
    onIniciarRuta: () -> Unit
) {
    val state        by viewModel.loginState.collectAsStateWithLifecycle()
    val focusManager = LocalFocusManager.current
    var showPassword by remember { mutableStateOf(false) }

    // Navegar automáticamente cuando el login sea exitoso
    LaunchedEffect(state.rutaIniciada) {
        if (state.rutaIniciada) onIniciarRuta()
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(BgPage)
            .systemBarsPadding()
    ) {
        // ── TopBar verde oscuro ───────────────────────────────────────────────
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(SurfaceDark)
                .padding(horizontal = 18.dp, vertical = 14.dp),
            verticalAlignment         = Alignment.CenterVertically,
            horizontalArrangement     = Arrangement.SpaceBetween
        ) {
            Column {
                Text("Control de Acopio", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
                Text("Sistema Móvil Huata", color = Color.White.copy(alpha = 0.6f), fontSize = 11.sp)
            }
            Text("ProLech", color = GreenAccent, fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
        }

        Column(
            modifier              = Modifier
                .fillMaxSize()
                .padding(horizontal = 22.dp),
            horizontalAlignment   = Alignment.CenterHorizontally
        ) {
            Spacer(Modifier.height(32.dp))

            // ── Logo ──────────────────────────────────────────────────────────
            Box(
                modifier           = Modifier
                    .size(96.dp)
                    .clip(CircleShape)
                    .background(GreenBg)
                    .border(2.5.dp, GreenPrimary, CircleShape),
                contentAlignment   = Alignment.Center
            ) {
                Text("PL", color = GreenPrimary, fontSize = 30.sp, fontWeight = FontWeight.ExtraBold)
            }
            Spacer(Modifier.height(10.dp))
            Text("ProLech", color = GreenPrimary, fontSize = 24.sp, fontWeight = FontWeight.ExtraBold)
            Text("ACOPIO DIGITAL RURAL", color = TextMuted, fontSize = 10.sp, letterSpacing = 2.sp)

            Spacer(Modifier.height(28.dp))
            HorizontalDivider(color = Divider)
            Spacer(Modifier.height(24.dp))

            // ── Campo Email ───────────────────────────────────────────────────
            Text(
                "CORREO ELECTRÓNICO",
                color = TextMuted, fontSize = 11.sp, fontWeight = FontWeight.SemiBold,
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(Modifier.height(6.dp))
            OutlinedTextField(
                value         = state.email,
                onValueChange = { viewModel.onEmailChange(it) },
                placeholder   = { Text("conductor@prolech.com", color = TextDim, fontSize = 13.sp) },
                singleLine    = true,
                keyboardOptions = KeyboardOptions(
                    keyboardType = KeyboardType.Email,
                    imeAction    = ImeAction.Next
                ),
                keyboardActions = KeyboardActions(
                    onNext = { focusManager.moveFocus(FocusDirection.Down) }
                ),
                modifier = Modifier.fillMaxWidth(),
                shape    = RoundedCornerShape(10.dp),
                colors   = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor   = GreenPrimary,
                    unfocusedBorderColor = Divider,
                    focusedTextColor     = TextPrimary,
                    unfocusedTextColor   = TextPrimary,
                    cursorColor          = GreenPrimary
                ),
                enabled = !state.isLoading
            )

            Spacer(Modifier.height(16.dp))

            // ── Campo Contraseña ──────────────────────────────────────────────
            Text(
                "CONTRASEÑA",
                color = TextMuted, fontSize = 11.sp, fontWeight = FontWeight.SemiBold,
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(Modifier.height(6.dp))
            OutlinedTextField(
                value         = state.password,
                onValueChange = { viewModel.onPasswordChange(it) },
                placeholder   = { Text("••••••••", color = TextDim, fontSize = 13.sp) },
                singleLine    = true,
                visualTransformation = if (showPassword) VisualTransformation.None
                                       else PasswordVisualTransformation(),
                keyboardOptions = KeyboardOptions(
                    keyboardType = KeyboardType.Password,
                    imeAction    = ImeAction.Done
                ),
                keyboardActions = KeyboardActions(
                    onDone = {
                        focusManager.clearFocus()
                        if (!state.isLoading) viewModel.login()
                    }
                ),
                trailingIcon = {
                    TextButton(onClick = { showPassword = !showPassword }) {
                        Text(
                            if (showPassword) "OCULTAR" else "VER",
                            color = GreenPrimary, fontSize = 10.sp, fontWeight = FontWeight.Bold
                        )
                    }
                },
                modifier = Modifier.fillMaxWidth(),
                shape    = RoundedCornerShape(10.dp),
                colors   = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor   = GreenPrimary,
                    unfocusedBorderColor = Divider,
                    focusedTextColor     = TextPrimary,
                    unfocusedTextColor   = TextPrimary,
                    cursorColor          = GreenPrimary
                ),
                enabled = !state.isLoading
            )

            Spacer(Modifier.height(10.dp))

            // ── Error de login ────────────────────────────────────────────────
            AnimatedVisibility(visible = state.error != null) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(8.dp))
                        .background(RedBg)
                        .padding(horizontal = 12.dp, vertical = 10.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("✕  ", color = RedPrimary, fontSize = 14.sp, fontWeight = FontWeight.Bold)
                    Text(
                        state.error ?: "",
                        color = RedPrimary, fontSize = 12.sp
                    )
                }
            }

            Spacer(Modifier.weight(1f))

            // ── Aviso de conexión al servidor ─────────────────────────────────
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(10.dp))
                    .background(AmberBg)
                    .border(1.dp, AmberPrimary.copy(alpha = 0.4f), RoundedCornerShape(10.dp))
                    .padding(12.dp),
                verticalAlignment = Alignment.Top
            ) {
                Text("⚡  ", color = AmberPrimary, fontSize = 15.sp)
                Column {
                    Text("Modo Online", color = AmberText, fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    Text(
                        "Conectado a la API ProLech.\nIngresa tus credenciales para iniciar.",
                        color = AmberText.copy(alpha = 0.85f), fontSize = 11.sp
                    )
                }
            }

            Spacer(Modifier.height(14.dp))

            // ── Botón Iniciar Ruta ────────────────────────────────────────────
            Button(
                onClick  = {
                    focusManager.clearFocus()
                    viewModel.login()
                },
                enabled  = !state.isLoading && state.email.isNotBlank() && state.password.isNotBlank(),
                modifier = Modifier.fillMaxWidth().height(56.dp),
                shape    = RoundedCornerShape(12.dp),
                colors   = ButtonDefaults.buttonColors(
                    containerColor  = GreenPrimary,
                    contentColor    = Color.White,
                    disabledContainerColor = GreenPrimary.copy(alpha = 0.5f),
                    disabledContentColor   = Color.White.copy(alpha = 0.6f)
                )
            ) {
                if (state.isLoading) {
                    CircularProgressIndicator(
                        modifier  = Modifier.size(24.dp),
                        color     = Color.White,
                        strokeWidth = 2.dp
                    )
                    Spacer(Modifier.width(10.dp))
                    Text("AUTENTICANDO...", fontSize = 15.sp, fontWeight = FontWeight.Bold)
                } else {
                    Text("▶  INICIAR RUTA", fontSize = 17.sp, fontWeight = FontWeight.ExtraBold)
                }
            }

            Spacer(Modifier.height(12.dp))
            Text(
                "ProLech Android v1.4.2 · API Laravel Sanctum",
                color = TextDim, fontSize = 10.sp, textAlign = TextAlign.Center
            )
            Spacer(Modifier.height(18.dp))
        }
    }
}
