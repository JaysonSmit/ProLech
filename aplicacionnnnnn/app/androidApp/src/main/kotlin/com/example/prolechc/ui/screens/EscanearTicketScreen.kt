package com.example.prolechc.ui.screens

import android.graphics.Bitmap
import android.util.Log
import androidx.camera.core.*
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import com.example.prolechc.data.services.LactomatOcrService
import com.example.prolechc.data.services.ResultadoLactomat
import com.example.prolechc.ui.theme.*
import com.google.accompanist.permissions.ExperimentalPermissionsApi
import com.google.accompanist.permissions.isGranted
import com.google.accompanist.permissions.rememberPermissionState
import kotlinx.coroutines.launch
import java.util.concurrent.Executors

// ══════════════════════════════════════════════════════════════════════════════
//  EscanearTicketScreen
//
//  Permite al conductor apuntar la cámara al ticket impreso del LACTOMAT.
//  Al presionar el botón, ML Kit lee el texto y extrae los parámetros automáticamente.
//  Devuelve los valores via [onResultado] al formulario de acopio.
// ══════════════════════════════════════════════════════════════════════════════

@OptIn(ExperimentalPermissionsApi::class)
@Composable
fun EscanearTicketScreen(
    onResultado: (ResultadoLactomat) -> Unit,
    onCancelar:  () -> Unit
) {
    val context        = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current
    val scope          = rememberCoroutineScope()

    val cameraPermission = rememberPermissionState(android.Manifest.permission.CAMERA)

    var isProcessing by remember { mutableStateOf(false) }
    var errorMsg     by remember { mutableStateOf<String?>(null) }
    var imageCapture by remember { mutableStateOf<ImageCapture?>(null) }

    LaunchedEffect(Unit) {
        if (!cameraPermission.status.isGranted) {
            cameraPermission.launchPermissionRequest()
        }
    }

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Color.Black)
    ) {
        if (cameraPermission.status.isGranted) {
            // ── Vista previa de cámara ────────────────────────────────────
            AndroidView(
                factory  = { ctx ->
                    val previewView = PreviewView(ctx)
                    val executor    = ContextCompat.getMainExecutor(ctx)

                    val cameraProviderFuture = ProcessCameraProvider.getInstance(ctx)
                    cameraProviderFuture.addListener({
                        val cameraProvider = cameraProviderFuture.get()

                        val preview = Preview.Builder().build().also {
                            it.surfaceProvider = previewView.surfaceProvider
                        }
                        val capture = ImageCapture.Builder()
                            .setCaptureMode(ImageCapture.CAPTURE_MODE_MINIMIZE_LATENCY)
                            .build()
                        imageCapture = capture

                        try {
                            cameraProvider.unbindAll()
                            cameraProvider.bindToLifecycle(
                                lifecycleOwner,
                                CameraSelector.DEFAULT_BACK_CAMERA,
                                preview,
                                capture
                            )
                        } catch (e: Exception) {
                            Log.e("OCR", "Error al iniciar cámara", e)
                        }
                    }, executor)

                    previewView
                },
                modifier = Modifier.fillMaxSize()
            )

            // ── Guía visual — recuadro centrado ───────────────────────────
            Box(
                modifier          = Modifier.fillMaxSize(),
                contentAlignment  = Alignment.Center
            ) {
                Box(
                    modifier = Modifier
                        .width(280.dp)
                        .height(180.dp)
                        .border(2.dp, GreenAccent, RoundedCornerShape(8.dp))
                )
                // Etiqueta superior
                Text(
                    "Centra el ticket LACTOMAT dentro del recuadro",
                    color      = Color.White,
                    fontSize   = 13.sp,
                    textAlign  = TextAlign.Center,
                    modifier   = Modifier
                        .align(Alignment.TopCenter)
                        .padding(top = 60.dp)
                        .background(Color.Black.copy(alpha = 0.5f), RoundedCornerShape(6.dp))
                        .padding(horizontal = 12.dp, vertical = 6.dp)
                )
            }

            // ── Controles inferiores ──────────────────────────────────────
            Column(
                modifier              = Modifier
                    .align(Alignment.BottomCenter)
                    .fillMaxWidth()
                    .background(Color.Black.copy(alpha = 0.6f))
                    .padding(24.dp),
                horizontalAlignment   = Alignment.CenterHorizontally,
                verticalArrangement   = Arrangement.spacedBy(16.dp)
            ) {
                // Error si lo hay
                errorMsg?.let {
                    Text(
                        it,
                        color    = Color(0xFFFF6B6B),
                        fontSize = 12.sp,
                        textAlign = TextAlign.Center
                    )
                }

                Row(
                    modifier              = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceEvenly,
                    verticalAlignment     = Alignment.CenterVertically
                ) {
                    // Botón cancelar
                    TextButton(onClick = onCancelar) {
                        Text("Cancelar", color = Color.White, fontSize = 14.sp)
                    }

                    // Botón capturar
                    Box(
                        modifier = Modifier
                            .size(72.dp)
                            .clip(CircleShape)
                            .background(if (isProcessing) Color.Gray else GreenPrimary),
                        contentAlignment = Alignment.Center
                    ) {
                        if (isProcessing) {
                            CircularProgressIndicator(
                                color    = Color.White,
                                modifier = Modifier.size(32.dp)
                            )
                        } else {
                            IconButton(
                                onClick = {
                                    val capture = imageCapture ?: return@IconButton
                                    isProcessing = true
                                    errorMsg     = null

                                    val executor = Executors.newSingleThreadExecutor()
                                    capture.takePicture(
                                        executor,
                                        object : ImageCapture.OnImageCapturedCallback() {
                                            override fun onCaptureSuccess(image: ImageProxy) {
                                                val bitmap = imageToBitmap(image)
                                                image.close()
                                                scope.launch {
                                                    val result = LactomatOcrService.leerTicket(bitmap)
                                                    isProcessing = false
                                                    result.fold(
                                                        onSuccess  = { onResultado(it) },
                                                        onFailure  = {
                                                            errorMsg = "No se pudo leer el ticket. Intenta de nuevo."
                                                        }
                                                    )
                                                }
                                            }
                                            override fun onError(e: ImageCaptureException) {
                                                isProcessing = false
                                                errorMsg = "Error al capturar: ${e.message}"
                                            }
                                        }
                                    )
                                }
                            ) {
                                Text("📷", fontSize = 28.sp)
                            }
                        }
                    }

                    // Espacio simétrico
                    Spacer(Modifier.width(80.dp))
                }

                Text(
                    "Presiona el botón para escanear el ticket",
                    color    = Color.White.copy(alpha = 0.7f),
                    fontSize = 11.sp
                )
            }

        } else {
            // ── Sin permiso de cámara ─────────────────────────────────────
            Column(
                modifier            = Modifier.fillMaxSize(),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.Center
            ) {
                Text("📷", fontSize = 48.sp)
                Spacer(Modifier.height(16.dp))
                Text(
                    "Se necesita permiso de cámara\npara leer el ticket LACTOMAT",
                    color     = Color.White,
                    fontSize  = 14.sp,
                    textAlign = TextAlign.Center
                )
                Spacer(Modifier.height(16.dp))
                Button(onClick = { cameraPermission.launchPermissionRequest() }) {
                    Text("Conceder permiso")
                }
                Spacer(Modifier.height(8.dp))
                TextButton(onClick = onCancelar) {
                    Text("Cancelar", color = Color.White)
                }
            }
        }
    }
}

// ── Convierte ImageProxy a Bitmap ─────────────────────────────────────────────

private fun imageToBitmap(image: ImageProxy): Bitmap {
    val plane  = image.planes[0]
    val buffer = plane.buffer
    val bytes  = ByteArray(buffer.remaining())
    buffer.get(bytes)
    return android.graphics.BitmapFactory.decodeByteArray(bytes, 0, bytes.size)
        ?: run {
            // Fallback: convertir YUV a Bitmap manualmente
            val yuvImage = android.graphics.YuvImage(
                bytes,
                android.graphics.ImageFormat.NV21,
                image.width, image.height, null
            )
            val out = java.io.ByteArrayOutputStream()
            yuvImage.compressToJpeg(
                android.graphics.Rect(0, 0, image.width, image.height), 90, out
            )
            android.graphics.BitmapFactory.decodeByteArray(out.toByteArray(), 0, out.size())
        }
}
