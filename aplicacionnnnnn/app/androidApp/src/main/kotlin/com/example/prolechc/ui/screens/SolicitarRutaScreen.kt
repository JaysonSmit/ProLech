package com.example.prolechc.ui.screens

import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.systemBarsPadding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.prolechc.ui.viewmodels.SolicitarRutaViewModel

// ══════════════════════════════════════════════════════════════════════════════
//  SolicitarRutaScreen
//
//  Estructura:
//    • TopBar con botón volver
//    • Card informativa: "¿Por qué solicitar un cambio de ruta?"
//    • ExposedDropdownMenuBox con 3 rutas disponibles
//    • OutlinedTextField multi-línea para el motivo (mínimo 10 caracteres)
//    • Indicador de validación del motivo (caracteres restantes)
//    • Botón "ENVIAR SOLICITUD" con estado de carga (CircularProgressIndicator)
//    • Toast de éxito (verde) → navega atrás
//    • Toast de error (rojo) → permanece en la pantalla
//
//  Integración:
//    El botón llama a viewModel.enviarSolicitud().
//    La Screen observa mensajeExito / mensajeError para mostrar los Toasts.
//    Después de consumir cada mensaje llama consumirMensajeExito/Error para
//    evitar que se repitan en recomposiciones.
// ══════════════════════════════════════════════════════════════════════════════

private val BgPage      = Color(0xFFF4F6F4)
private val BgCard      = Color(0xFFFFFFFF)
private val GreenPrimary = Color(0xFF1E8A3C)
private val GreenBg     = Color(0xFFE8F5EE)
private val GreenBorder = Color(0xFF27AE60)
private val RedPrimary  = Color(0xFFB71C1C)
private val RedBg       = Color(0xFFFFEBEE)
private val TextPrimary  = Color(0xFF0D1A0D)
private val TextMuted   = Color(0xFF8A9E8A)
private val TextDim     = Color(0xFFB0BEB0)
private val Divider     = Color(0xFFE0E8E0)
private val SurfaceDark = Color(0xFF1A2E1A)
private val AmberPrimary = Color(0xFFF57F17)
private val AmberBg     = Color(0xFFFFF8E1)
private val AmberText   = Color(0xFFF57F17)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SolicitarRutaScreen(
    onBack: () -> Unit
) {
    val context = LocalContext.current
    // Obtener Application de forma segura
    val application = remember(context) {
        generateSequence(context) { (it as? android.content.ContextWrapper)?.baseContext }
            .filterIsInstance<android.app.Application>()
            .firstOrNull() ?: context.applicationContext as android.app.Application
    }
    val viewModel: SolicitarRutaViewModel = viewModel(
        factory = SolicitarRutaViewModel.Factory(application)
    )

    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    var dropdownExpanded by remember { mutableStateOf(false) }

    // ── Mostrar Toast de éxito y volver atrás ─────────────────────────────────
    LaunchedEffect(uiState.mensajeExito) {
        uiState.mensajeExito?.let { msg ->
            Toast.makeText(context, "✓ $msg", Toast.LENGTH_LONG).show()
            viewModel.consumirMensajeExito()
            onBack()
        }
    }

    // ── Mostrar Toast de error (permanece en pantalla) ────────────────────────
    LaunchedEffect(uiState.mensajeError) {
        uiState.mensajeError?.let { msg ->
            Toast.makeText(context, "✗ $msg", Toast.LENGTH_LONG).show()
            viewModel.consumirMensajeError()
        }
    }

    // ── Layout principal ──────────────────────────────────────────────────────
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(BgPage)
            .systemBarsPadding()
    ) {

        // ── TopBar ────────────────────────────────────────────────────────────
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(SurfaceDark)
                .padding(horizontal = 14.dp, vertical = 12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Button(
                onClick  = onBack,
                modifier = Modifier.size(width = 44.dp, height = 36.dp),
                shape    = RoundedCornerShape(8.dp),
                colors   = ButtonDefaults.buttonColors(
                    containerColor = Color.White.copy(alpha = 0.15f),
                    contentColor   = Color.White
                ),
                contentPadding = androidx.compose.foundation.layout.PaddingValues(0.dp)
            ) {
                Text("←", fontSize = 18.sp)
            }
            Spacer(Modifier.width(12.dp))
            Column {
                Text(
                    "Solicitar Cambio de Ruta",
                    color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold
                )
                Text(
                    "Envía tu solicitud al administrador",
                    color = Color.White.copy(alpha = 0.6f), fontSize = 11.sp
                )
            }
        }

        // ── Contenido con scroll ──────────────────────────────────────────────
        Column(
            modifier = Modifier
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 20.dp, vertical = 16.dp)
        ) {

            // ── Card informativa ──────────────────────────────────────────────
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(10.dp))
                    .background(AmberBg)
                    .border(1.dp, AmberPrimary.copy(alpha = 0.4f), RoundedCornerShape(10.dp))
                    .padding(14.dp)
            ) {
                Text("ℹ️  ", fontSize = 16.sp)
                Column {
                    Text(
                        "¿Cuándo solicitar un cambio?",
                        color = AmberText, fontSize = 12.sp, fontWeight = FontWeight.Bold
                    )
                    Spacer(Modifier.height(4.dp))
                    Text(
                        "Puedes solicitar cambiar tu ruta asignada por motivos de emergencia, " +
                        "enfermedad, problemas con el vehículo o razones operativas. " +
                        "El administrador revisará tu solicitud a la brevedad.",
                        color = AmberText.copy(alpha = 0.85f), fontSize = 11.sp,
                        lineHeight = 17.sp
                    )
                }
            }

            // ── Estado de la última solicitud ─────────────────────────────────
            uiState.ultimaSolicitud?.let { solicitud ->
                val (titulo, color, fondo) = when (solicitud.estado) {
                    "aprobado"  -> Triple("✓ Solicitud APROBADA", GreenPrimary, GreenBg)
                    "rechazado" -> Triple("✗ Solicitud RECHAZADA", RedPrimary, RedBg)
                    else        -> Triple("⏳ Solicitud PENDIENTE de revisión", AmberText, AmberBg)
                }
                Spacer(Modifier.height(14.dp))
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(10.dp))
                        .background(fondo)
                        .border(1.dp, color.copy(alpha = 0.4f), RoundedCornerShape(10.dp))
                        .padding(14.dp)
                ) {
                    Text(titulo, color = color, fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    Spacer(Modifier.height(4.dp))
                    Text(
                        "${solicitud.rutaActual ?: "Sin asignar"} → ${solicitud.rutaSolicitada}",
                        color = TextPrimary, fontSize = 12.sp, fontWeight = FontWeight.SemiBold
                    )
                    solicitud.fechaSolicitud?.let {
                        Text("Enviada el $it", color = TextMuted, fontSize = 11.sp)
                    }
                    if (uiState.tienePendiente) {
                        Spacer(Modifier.height(4.dp))
                        Text(
                            "No puedes enviar otra solicitud hasta que el administrador responda.",
                            color = color.copy(alpha = 0.85f), fontSize = 11.sp
                        )
                    }
                }
            }

            Spacer(Modifier.height(20.dp))
            HorizontalDivider(color = Divider)
            Spacer(Modifier.height(20.dp))

            // ── Sección: Ruta destino ─────────────────────────────────────────
            FormLabel("RUTA DESTINO SOLICITADA *")
            Spacer(Modifier.height(8.dp))

            ExposedDropdownMenuBox(
                expanded         = dropdownExpanded,
                onExpandedChange = { if (!uiState.isLoading) dropdownExpanded = it }
            ) {
                OutlinedTextField(
                    value         = uiState.rutaSeleccionada?.etiqueta ?: "",
                    onValueChange = {},
                    readOnly      = true,
                    placeholder   = {
                        Text(
                            "Selecciona una ruta disponible",
                            color = TextDim, fontSize = 13.sp
                        )
                    },
                    trailingIcon  = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = dropdownExpanded) },
                    modifier      = Modifier
                        .fillMaxWidth()
                        .menuAnchor(),
                    shape         = RoundedCornerShape(10.dp),
                    colors        = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor   = GreenPrimary,
                        unfocusedBorderColor = Divider,
                        focusedTextColor     = TextPrimary,
                        unfocusedTextColor   = TextPrimary
                    ),
                    enabled       = !uiState.isLoading
                )

                ExposedDropdownMenu(
                    expanded         = dropdownExpanded,
                    onDismissRequest = { dropdownExpanded = false },
                    modifier         = Modifier.background(BgCard)
                ) {
                    viewModel.rutasDisponibles.forEach { ruta ->
                        DropdownMenuItem(
                            text = {
                                Column {
                                    Text(
                                        ruta.codigo,
                                        color = GreenPrimary, fontSize = 11.sp,
                                        fontWeight = FontWeight.Bold
                                    )
                                    Text(
                                        ruta.nombre,
                                        color = TextPrimary, fontSize = 13.sp
                                    )
                                }
                            },
                            onClick = {
                                viewModel.seleccionarRuta(ruta)
                                dropdownExpanded = false
                            },
                            modifier = Modifier
                                .fillMaxWidth()
                                .background(
                                    if (uiState.rutaSeleccionada == ruta) GreenBg
                                    else BgCard
                                )
                        )
                        if (ruta != viewModel.rutasDisponibles.last()) {
                            HorizontalDivider(color = Divider, thickness = 0.5.dp)
                        }
                    }
                }
            }

            Spacer(Modifier.height(20.dp))

            // ── Sección: Motivo ───────────────────────────────────────────────
            FormLabel("MOTIVO DEL CAMBIO *")
            Spacer(Modifier.height(8.dp))

            val motivoLength = uiState.motivo.trim().length
            val motivoValido = motivoLength >= 10

            OutlinedTextField(
                value         = uiState.motivo,
                onValueChange = { viewModel.onMotivoChange(it) },
                placeholder   = {
                    Text(
                        "Describe el motivo del cambio de ruta (mínimo 10 caracteres)…",
                        color = TextDim, fontSize = 13.sp
                    )
                },
                minLines  = 4,
                maxLines  = 8,
                modifier  = Modifier.fillMaxWidth(),
                shape     = RoundedCornerShape(10.dp),
                colors    = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor   = if (motivoValido) GreenPrimary else GreenPrimary,
                    unfocusedBorderColor = if (uiState.motivo.isEmpty()) Divider
                                          else if (motivoValido) GreenBorder
                                          else RedPrimary,
                    focusedTextColor     = TextPrimary,
                    unfocusedTextColor   = TextPrimary,
                    cursorColor          = GreenPrimary
                ),
                enabled   = !uiState.isLoading
            )

            // Contador de caracteres + indicador de validez
            Row(
                modifier              = Modifier
                    .fillMaxWidth()
                    .padding(top = 4.dp, end = 4.dp),
                horizontalArrangement = androidx.compose.foundation.layout.Arrangement.SpaceBetween
            ) {
                AnimatedVisibility(visible = !motivoValido && uiState.motivo.isNotEmpty()) {
                    Text(
                        "Mínimo 10 caracteres",
                        color = RedPrimary, fontSize = 11.sp
                    )
                }
                AnimatedVisibility(visible = motivoValido) {
                    Text(
                        "✓ Motivo válido",
                        color = GreenPrimary, fontSize = 11.sp, fontWeight = FontWeight.SemiBold
                    )
                }
                Text(
                    "$motivoLength caracteres",
                    color    = if (motivoValido) GreenPrimary else TextMuted,
                    fontSize = 11.sp
                )
            }

            Spacer(Modifier.height(24.dp))

            // ── Resumen de la solicitud (preview antes de enviar) ─────────────
            AnimatedVisibility(
                visible = uiState.rutaSeleccionada != null && motivoValido
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(10.dp))
                        .background(GreenBg)
                        .border(1.dp, GreenBorder.copy(alpha = 0.4f), RoundedCornerShape(10.dp))
                        .padding(14.dp)
                ) {
                    Text(
                        "📋 Resumen de tu solicitud",
                        color = GreenPrimary, fontSize = 12.sp, fontWeight = FontWeight.Bold
                    )
                    Spacer(Modifier.height(8.dp))
                    ResumenFila("Ruta solicitada", uiState.rutaSeleccionada?.etiqueta ?: "")
                    ResumenFila("Motivo", uiState.motivo.trim().take(80) +
                        if (uiState.motivo.trim().length > 80) "…" else "")
                    ResumenFila("Estado inicial", "PENDIENTE (revisión del admin)")
                }
                Spacer(Modifier.height(16.dp))
            }

            Spacer(Modifier.height(8.dp))

            // ── Botón enviar ──────────────────────────────────────────────────
            Button(
                onClick  = { viewModel.enviarSolicitud() },
                enabled  = !uiState.isLoading &&
                           !uiState.tienePendiente &&
                           uiState.rutaSeleccionada != null &&
                           motivoValido,
                modifier = Modifier.fillMaxWidth().height(56.dp),
                shape    = RoundedCornerShape(12.dp),
                colors   = ButtonDefaults.buttonColors(
                    containerColor         = GreenPrimary,
                    contentColor           = Color.White,
                    disabledContainerColor = GreenPrimary.copy(alpha = 0.45f),
                    disabledContentColor   = Color.White.copy(alpha = 0.6f)
                )
            ) {
                if (uiState.isLoading) {
                    CircularProgressIndicator(
                        modifier    = Modifier.size(22.dp),
                        color       = Color.White,
                        strokeWidth = 2.dp
                    )
                    Spacer(Modifier.width(10.dp))
                    Text(
                        "ENVIANDO SOLICITUD…",
                        fontSize = 15.sp, fontWeight = FontWeight.Bold
                    )
                } else {
                    Text(
                        "✉  ENVIAR SOLICITUD",
                        fontSize = 17.sp, fontWeight = FontWeight.ExtraBold
                    )
                }
            }

            Spacer(Modifier.height(12.dp))
            Text(
                "* Campos obligatorios. La solicitud será revisada por el administrador " +
                "en un plazo de 24 horas.",
                color = TextMuted, fontSize = 10.sp, lineHeight = 15.sp
            )
            Spacer(Modifier.height(24.dp))
        }
    }
}

// ── Subcomposables auxiliares ─────────────────────────────────────────────────

@Composable
private fun FormLabel(text: String) {
    Text(
        text,
        color = TextMuted, fontSize = 11.sp, fontWeight = FontWeight.SemiBold,
        modifier = Modifier.fillMaxWidth()
    )
}

@Composable
private fun ResumenFila(etiqueta: String, valor: String) {
    if (valor.isBlank()) return
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 3.dp)
    ) {
        Text(
            "$etiqueta: ",
            color = TextMuted, fontSize = 12.sp, fontWeight = FontWeight.SemiBold,
            modifier = Modifier.width(120.dp)
        )
        Text(valor, color = TextPrimary, fontSize = 12.sp)
    }
}
