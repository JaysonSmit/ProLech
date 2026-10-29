package com.example.prolechc.ui.screens

import android.Manifest
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.provider.Settings
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.slideInVertically
import androidx.compose.animation.slideOutVertically
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.prolechc.data.repositories.ParadaInfo
import com.example.prolechc.data.services.GpsTrackingService
import com.example.prolechc.ui.viewmodels.MapaChoferUiState
import com.example.prolechc.ui.viewmodels.MapaChoferViewModel
import com.example.prolechc.viewmodel.ProLechViewModel
import com.google.accompanist.permissions.ExperimentalPermissionsApi
import com.google.accompanist.permissions.MultiplePermissionsState
import com.google.accompanist.permissions.rememberMultiplePermissionsState
import com.google.android.gms.location.LocationServices
import com.google.android.gms.maps.CameraUpdateFactory
import com.google.android.gms.maps.model.BitmapDescriptorFactory
import com.google.android.gms.maps.model.CameraPosition
import com.google.android.gms.maps.model.LatLng
import com.google.maps.android.compose.*
import kotlinx.coroutines.launch

// ══════════════════════════════════════════════════════════════════════════════
//  MapaChoferScreen
//  • GoogleMap a pantalla completa
//  • Marcadores de cada parada (verde = visitada, rojo = pendiente)
//  • Polyline que traza el camino entre paradas pendientes en orden
//  • Panel flotante inferior con la próxima parada + botón de navegación
//  • Popup de parada con botón "Cómo llegar" → abre Google Maps navegación
//  • FAB Iniciar / Terminar Jornada
// ══════════════════════════════════════════════════════════════════════════════

@OptIn(ExperimentalPermissionsApi::class)
@Composable
fun MapaChoferScreen(
    onBack: () -> Unit,
    mainVm: ProLechViewModel? = null,
    viewModel: MapaChoferViewModel = viewModel(
        factory = MapaChoferViewModel.Factory(
            LocalContext.current.applicationContext as android.app.Application
        )
    )
) {
    val uiState  by viewModel.uiState.collectAsStateWithLifecycle()
    val miRuta   = mainVm?.miRutaState?.collectAsStateWithLifecycle()?.value
    val paradas  = miRuta?.paradas ?: emptyList()

    val context        = LocalContext.current
    val snackbarHost   = remember { SnackbarHostState() }
    val coroutineScope = rememberCoroutineScope()

    // Huata, Puno como posición inicial
    val posInicial  = LatLng(-15.8234, -70.0123)
    val cameraState = rememberCameraPositionState {
        position = CameraPosition.fromLatLngZoom(posInicial, 13f)
    }

    val locationPermissions = rememberMultiplePermissionsState(
        permissions = listOf(
            Manifest.permission.ACCESS_FINE_LOCATION,
            Manifest.permission.ACCESS_COARSE_LOCATION
        )
    )

    LaunchedEffect(Unit) {
        if (!locationPermissions.allPermissionsGranted) {
            locationPermissions.launchMultiplePermissionRequest()
        }
    }

    // Centrar en posición actual cuando cambia
    LaunchedEffect(uiState.posicionActual) {
        uiState.posicionActual?.let { pos ->
            cameraState.animate(CameraUpdateFactory.newLatLngZoom(pos, 15f), durationMs = 800)
        }
    }

    // Obtener ubicación inicial
    LaunchedEffect(locationPermissions.allPermissionsGranted) {
        if (locationPermissions.allPermissionsGranted) {
            obtenerPosicionActual(context) { lat, lng ->
                viewModel.actualizarPosicion(lat, lng)
            }
        }
    }

    // Mostrar errores
    LaunchedEffect(uiState.error) {
        uiState.error?.let { msg ->
            coroutineScope.launch { snackbarHost.showSnackbar(msg) }
            viewModel.limpiarError()
        }
    }

    Scaffold(snackbarHost = { SnackbarHost(snackbarHost) }) { innerPadding ->
        Box(modifier = Modifier.fillMaxSize().padding(innerPadding)) {
            if (locationPermissions.allPermissionsGranted) {
                MapaContenido(
                    uiState           = uiState,
                    cameraState       = cameraState,
                    paradas           = paradas,
                    onIniciarJornada  = {
                        viewModel.iniciarJornada()
                        iniciarGpsService(context, uiState.asignacionId)
                    },
                    onTerminarJornada = {
                        viewModel.terminarJornada()
                        detenerGpsService(context)
                    },
                    onBack            = onBack
                )
            } else {
                PermisosBloqueadosContent(
                    permissionsState = locationPermissions,
                    onBack           = onBack
                )
            }
        }
    }
}

// ── Mapa principal ────────────────────────────────────────────────────────────

@Suppress("MissingPermission")
@Composable
private fun MapaContenido(
    uiState: MapaChoferUiState,
    cameraState: CameraPositionState,
    paradas: List<ParadaInfo>,
    onIniciarJornada: () -> Unit,
    onTerminarJornada: () -> Unit,
    onBack: () -> Unit
) {
    val context = LocalContext.current
    var paradaSeleccionada by remember { mutableStateOf<ParadaInfo?>(null) }

    // Paradas pendientes con coordenadas válidas, en orden de visita
    val paradasPendientesConCoords = remember(paradas) {
        paradas
            .filter { !it.atendido && it.geoLat != null && it.geoLon != null }
            .sortedBy { it.orden }
    }

    // Próxima parada = primera pendiente con coords
    val proximaParada = paradasPendientesConCoords.firstOrNull()

    // Puntos de la polyline: posición actual del chofer → paradas pendientes en orden
    val puntosRuta = remember(uiState.posicionActual, paradasPendientesConCoords) {
        buildList {
            uiState.posicionActual?.let { add(it) }
            paradasPendientesConCoords.forEach { p ->
                add(LatLng(p.geoLat!!, p.geoLon!!))
            }
        }
    }

    Box(modifier = Modifier.fillMaxSize()) {

        // ── Google Map ────────────────────────────────────────────────────
        GoogleMap(
            modifier            = Modifier.fillMaxSize(),
            cameraPositionState = cameraState,
            properties          = MapProperties(
                isMyLocationEnabled = true,
                isBuildingEnabled   = true,
                isTrafficEnabled    = false
            ),
            uiSettings = MapUiSettings(
                myLocationButtonEnabled = true,
                zoomControlsEnabled     = true,
                compassEnabled          = true
            )
        ) {
            // ── Polyline de trazado de ruta ───────────────────────────────
            // Solo visible cuando hay al menos 2 puntos (pos actual + 1 parada)
            if (puntosRuta.size >= 2) {
                Polyline(
                    points    = puntosRuta,
                    color     = Color(0xFF1565C0),   // azul navegación
                    width     = 8f,
                    geodesic  = true                  // sigue la curvatura de la Tierra
                )
            }

            // ── Marcadores de paradas ─────────────────────────────────────
            paradas.forEachIndexed { index, parada ->
                val lat = parada.geoLat
                val lng = parada.geoLon
                if (lat != null && lng != null) {
                    val pos = LatLng(lat, lng)
                    val hue = when {
                        parada.atendido  -> BitmapDescriptorFactory.HUE_GREEN
                        parada.suspendido -> BitmapDescriptorFactory.HUE_ORANGE
                        else             -> BitmapDescriptorFactory.HUE_RED
                    }

                    Marker(
                        state   = MarkerState(position = pos),
                        title   = "${parada.orden}. ${parada.ganaderoNombre}",
                        snippet = if (!parada.direccion.isNullOrBlank()) parada.direccion else "DNI: ${parada.ganaderoNii}",
                        icon    = BitmapDescriptorFactory.defaultMarker(hue),
                        onClick = { marker ->
                            paradaSeleccionada = parada
                            marker.showInfoWindow()
                            false
                        }
                    )
                }
            }

            // ── Círculo en posición actual del chofer ─────────────────────
            uiState.posicionActual?.let { pos ->
                Circle(
                    center      = pos,
                    radius      = 30.0,
                    fillColor   = Color(0xFF1E8A3C).copy(alpha = 0.2f),
                    strokeColor = Color(0xFF1E8A3C),
                    strokeWidth = 2f
                )
            }
        }

        // ── TopBar flotante ───────────────────────────────────────────────
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .statusBarsPadding()
                .padding(horizontal = 12.dp, vertical = 8.dp)
                .clip(RoundedCornerShape(12.dp))
                .background(Color(0xFF1A2E1A).copy(alpha = 0.94f))
                .padding(horizontal = 14.dp, vertical = 10.dp),
            verticalAlignment     = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Button(
                onClick        = onBack,
                modifier       = Modifier.size(width = 40.dp, height = 34.dp),
                shape          = RoundedCornerShape(8.dp),
                contentPadding = PaddingValues(0.dp),
                colors         = ButtonDefaults.buttonColors(
                    containerColor = Color.White.copy(alpha = 0.2f),
                    contentColor   = Color.White
                )
            ) {
                Text("←", fontSize = 16.sp)
            }

            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Text(
                    "Mapa del Conductor",
                    color = Color.White, fontSize = 14.sp, fontWeight = FontWeight.Bold
                )
                if (uiState.nombreConductor.isNotBlank()) {
                    Text(uiState.nombreConductor,
                        color = Color.White.copy(alpha = 0.65f), fontSize = 10.sp)
                }
            }

            Box(
                modifier = Modifier
                    .clip(RoundedCornerShape(20.dp))
                    .background(
                        if (uiState.jornadaActiva) Color(0xFF2ECC71).copy(alpha = 0.9f)
                        else Color.White.copy(alpha = 0.15f)
                    )
                    .padding(horizontal = 10.dp, vertical = 4.dp)
            ) {
                Text(
                    if (uiState.jornadaActiva) "● ACTIVA" else "○ INACTIVA",
                    color = Color.White, fontSize = 10.sp, fontWeight = FontWeight.Bold
                )
            }
        }

        // ── Popup info de parada seleccionada ─────────────────────────────
        AnimatedVisibility(
            visible = paradaSeleccionada != null,
            enter   = fadeIn() + slideInVertically { -it / 2 },
            exit    = fadeOut() + slideOutVertically { -it / 2 },
            modifier = Modifier
                .align(Alignment.TopCenter)
                .statusBarsPadding()
                .padding(top = 70.dp, start = 12.dp, end = 12.dp)
        ) {
            paradaSeleccionada?.let { parada ->
                Card(
                    shape     = RoundedCornerShape(12.dp),
                    colors    = CardDefaults.cardColors(containerColor = Color.White),
                    elevation = CardDefaults.cardElevation(defaultElevation = 6.dp)
                ) {
                    Column(modifier = Modifier.padding(12.dp)) {
                        // ── Fila principal: icono + datos + cerrar ────────
                        Row(
                            modifier          = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(40.dp)
                                    .clip(CircleShape)
                                    .background(
                                        when {
                                            parada.atendido   -> Color(0xFFE8F5EE)
                                            parada.suspendido -> Color(0xFFFFF3E0)
                                            else              -> Color(0xFFFFEBEE)
                                        }
                                    ),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    when {
                                        parada.atendido   -> "✓"
                                        parada.suspendido -> "⚠"
                                        else              -> "${parada.orden}"
                                    },
                                    color = when {
                                        parada.atendido   -> Color(0xFF1E8A3C)
                                        parada.suspendido -> Color(0xFFE65100)
                                        else              -> Color(0xFFB71C1C)
                                    },
                                    fontSize   = 15.sp,
                                    fontWeight = FontWeight.Bold
                                )
                            }

                            Spacer(Modifier.width(10.dp))

                            Column(Modifier.weight(1f)) {
                                Text(
                                    parada.ganaderoNombre,
                                    color      = Color(0xFF0D1A0D),
                                    fontSize   = 13.sp,
                                    fontWeight = FontWeight.Bold,
                                    maxLines   = 1,
                                    overflow   = TextOverflow.Ellipsis
                                )
                                Text(
                                    buildString {
                                        append("DNI: ${parada.ganaderoNii}")
                                        if (!parada.direccion.isNullOrBlank()) append(" · ${parada.direccion}")
                                        if (parada.suspendido) append(" · ⛔ Bloqueado")
                                    },
                                    color    = Color(0xFF8A9E8A),
                                    fontSize = 10.sp,
                                    maxLines = 2,
                                    overflow = TextOverflow.Ellipsis
                                )
                            }

                            Box(
                                modifier = Modifier
                                    .size(28.dp)
                                    .clip(CircleShape)
                                    .background(Color(0xFFF0F0F0))
                                    .clickable { paradaSeleccionada = null },
                                contentAlignment = Alignment.Center
                            ) {
                                Text("✕", color = Color(0xFF8A9E8A), fontSize = 13.sp, fontWeight = FontWeight.Bold)
                            }
                        }

                        // ── Botón "Cómo llegar" (solo si tiene coords) ────
                        if (parada.geoLat != null && parada.geoLon != null && !parada.atendido) {
                            Spacer(Modifier.height(10.dp))
                            Button(
                                onClick = {
                                    abrirNavegacionGoogleMaps(
                                        context = context,
                                        lat     = parada.geoLat!!,
                                        lon     = parada.geoLon!!,
                                        nombre  = parada.ganaderoNombre
                                    )
                                },
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .height(38.dp),
                                shape  = RoundedCornerShape(8.dp),
                                colors = ButtonDefaults.buttonColors(
                                    containerColor = Color(0xFF1565C0),
                                    contentColor   = Color.White
                                )
                            ) {
                                Text("🧭  Cómo llegar", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }
            }
        }

        // ── Leyenda de marcadores ─────────────────────────────────────────
        if (paradas.isNotEmpty()) {
            Row(
                modifier = Modifier
                    .align(Alignment.TopEnd)
                    .statusBarsPadding()
                    .padding(top = 70.dp, end = 12.dp)
                    .clip(RoundedCornerShape(8.dp))
                    .background(Color.White.copy(alpha = 0.92f))
                    .padding(horizontal = 10.dp, vertical = 6.dp),
                verticalAlignment     = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(6.dp)
            ) {
                Text("🟢", fontSize = 10.sp)
                Text("Visitada",  color = Color(0xFF1E8A3C), fontSize = 9.sp, fontWeight = FontWeight.SemiBold)
                Spacer(Modifier.width(2.dp))
                Text("🔴", fontSize = 10.sp)
                Text("Pendiente", color = Color(0xFFB71C1C), fontSize = 9.sp, fontWeight = FontWeight.SemiBold)
                Spacer(Modifier.width(2.dp))
                Text("🟠", fontSize = 10.sp)
                Text("Bloqueado", color = Color(0xFFE65100), fontSize = 9.sp, fontWeight = FontWeight.SemiBold)
            }
        }

        // ── Panel próxima parada (barra inferior fija) ────────────────────
        AnimatedVisibility(
            visible  = proximaParada != null && uiState.jornadaActiva,
            enter    = slideInVertically { it },
            exit     = slideOutVertically { it },
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .navigationBarsPadding()
                .padding(horizontal = 12.dp)
                .padding(bottom = 88.dp)   // por encima del FAB
        ) {
            proximaParada?.let { sig ->
                Card(
                    shape     = RoundedCornerShape(14.dp),
                    colors    = CardDefaults.cardColors(containerColor = Color(0xFF0D47A1)),
                    elevation = CardDefaults.cardElevation(defaultElevation = 8.dp),
                    modifier  = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier          = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 14.dp, vertical = 10.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        // Indicador de número de parada
                        Box(
                            modifier = Modifier
                                .size(38.dp)
                                .clip(CircleShape)
                                .background(Color.White.copy(alpha = 0.15f)),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                "${sig.orden}",
                                color      = Color.White,
                                fontSize   = 16.sp,
                                fontWeight = FontWeight.ExtraBold
                            )
                        }

                        Spacer(Modifier.width(10.dp))

                        Column(Modifier.weight(1f)) {
                            Text(
                                "PRÓXIMA PARADA",
                                color      = Color.White.copy(alpha = 0.6f),
                                fontSize   = 9.sp,
                                fontWeight = FontWeight.Bold,
                                letterSpacing = 1.sp
                            )
                            Text(
                                sig.ganaderoNombre,
                                color      = Color.White,
                                fontSize   = 14.sp,
                                fontWeight = FontWeight.Bold,
                                maxLines   = 1,
                                overflow   = TextOverflow.Ellipsis
                            )
                            if (!sig.direccion.isNullOrBlank()) {
                                Text(
                                    sig.direccion.orEmpty(),
                                    color    = Color.White.copy(alpha = 0.65f),
                                    fontSize = 10.sp,
                                    maxLines = 1,
                                    overflow = TextOverflow.Ellipsis
                                )
                            }
                        }

                        // Botón "Ir" — abre Google Maps con navegación
                        if (sig.geoLat != null && sig.geoLon != null) {
                            Spacer(Modifier.width(8.dp))
                            Button(
                                onClick = {
                                    abrirNavegacionGoogleMaps(
                                        context = context,
                                        lat     = sig.geoLat!!,
                                        lon     = sig.geoLon!!,
                                        nombre  = sig.ganaderoNombre
                                    )
                                },
                                shape  = RoundedCornerShape(10.dp),
                                colors = ButtonDefaults.buttonColors(
                                    containerColor = Color(0xFF2196F3),
                                    contentColor   = Color.White
                                ),
                                contentPadding = PaddingValues(horizontal = 14.dp, vertical = 8.dp)
                            ) {
                                Text("🧭 IR", fontSize = 12.sp, fontWeight = FontWeight.ExtraBold)
                            }
                        }
                    }
                }
            }
        }

        // ── Resumen de paradas (esquina inferior izquierda) ───────────────
        if (paradas.isNotEmpty()) {
            val visitadas  = paradas.count { it.atendido }
            val pendientes = paradas.count { !it.atendido }
            // Desplazar hacia arriba cuando el panel de próxima parada está visible
            val bottomOffset = if (proximaParada != null && uiState.jornadaActiva) 160.dp else 80.dp
            Row(
                modifier = Modifier
                    .align(Alignment.BottomStart)
                    .navigationBarsPadding()
                    .padding(start = 12.dp, bottom = bottomOffset)
                    .clip(RoundedCornerShape(10.dp))
                    .background(Color(0xFF1A2E1A).copy(alpha = 0.88f))
                    .padding(horizontal = 12.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(12.dp),
                verticalAlignment     = Alignment.CenterVertically
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("${paradas.size}", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
                    Text("Paradas", color = Color.White.copy(alpha = 0.6f), fontSize = 9.sp)
                }
                Box(Modifier.width(1.dp).height(24.dp).background(Color.White.copy(alpha = 0.2f)))
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("$visitadas",  color = Color(0xFF2ECC71), fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
                    Text("Visitadas",  color = Color.White.copy(alpha = 0.6f), fontSize = 9.sp)
                }
                Box(Modifier.width(1.dp).height(24.dp).background(Color.White.copy(alpha = 0.2f)))
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("$pendientes", color = Color(0xFFF57F17), fontSize = 16.sp, fontWeight = FontWeight.ExtraBold)
                    Text("Pendientes", color = Color.White.copy(alpha = 0.6f), fontSize = 9.sp)
                }
            }
        }

        // ── FABs Iniciar / Terminar Jornada ───────────────────────────────
        Column(
            modifier = Modifier
                .align(Alignment.BottomEnd)
                .navigationBarsPadding()
                .padding(end = 16.dp, bottom = 24.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
            horizontalAlignment = Alignment.End
        ) {
            AnimatedVisibility(visible = uiState.jornadaActiva, enter = fadeIn(), exit = fadeOut()) {
                FloatingActionButton(
                    onClick        = onTerminarJornada,
                    containerColor = Color(0xFFB71C1C),
                    contentColor   = Color.White,
                    shape          = RoundedCornerShape(16.dp)
                ) {
                    Row(
                        modifier          = Modifier.padding(horizontal = 16.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("⏹", fontSize = 18.sp)
                        Spacer(Modifier.width(8.dp))
                        Text("TERMINAR JORNADA", fontSize = 13.sp, fontWeight = FontWeight.ExtraBold)
                    }
                }
            }

            AnimatedVisibility(visible = !uiState.jornadaActiva, enter = fadeIn(), exit = fadeOut()) {
                FloatingActionButton(
                    onClick        = onIniciarJornada,
                    containerColor = Color(0xFF1E8A3C),
                    contentColor   = Color.White,
                    shape          = RoundedCornerShape(16.dp)
                ) {
                    Row(
                        modifier          = Modifier.padding(horizontal = 16.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("▶", fontSize = 18.sp)
                        Spacer(Modifier.width(8.dp))
                        Text("INICIAR JORNADA", fontSize = 13.sp, fontWeight = FontWeight.ExtraBold)
                    }
                }
            }
        }

        // ── Coordenadas GPS actuales ──────────────────────────────────────
        uiState.posicionActual?.let { pos ->
            Box(
                modifier = Modifier
                    .align(Alignment.BottomStart)
                    .navigationBarsPadding()
                    .padding(start = 12.dp, bottom = 24.dp)
                    .clip(RoundedCornerShape(8.dp))
                    .background(Color(0xFF1A2E1A).copy(alpha = 0.85f))
                    .padding(horizontal = 10.dp, vertical = 6.dp)
            ) {
                Text(
                    "GPS  ${"%.5f".format(pos.latitude)}, ${"%.5f".format(pos.longitude)}",
                    color = Color(0xFF2ECC71), fontSize = 10.sp, fontWeight = FontWeight.Medium
                )
            }
        }
    }
}

// ── Pantalla de permisos bloqueados ───────────────────────────────────────────

@OptIn(ExperimentalPermissionsApi::class)
@Composable
private fun PermisosBloqueadosContent(
    permissionsState: MultiplePermissionsState,
    onBack: () -> Unit
) {
    val context = LocalContext.current
    Column(
        modifier            = Modifier
            .fillMaxSize()
            .background(Color(0xFFF4F6F4))
            .systemBarsPadding()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text("📍", fontSize = 64.sp)
        Spacer(Modifier.height(16.dp))
        Text(
            "Permiso de Ubicación Requerido",
            fontSize   = 20.sp,
            fontWeight = FontWeight.Bold,
            color      = Color(0xFF0D1A0D)
        )
        Spacer(Modifier.height(12.dp))
        Text(
            "ProLech necesita acceso a tu ubicación GPS para:\n\n" +
            "• Mostrar tu posición y las paradas de la ruta en el mapa\n" +
            "• Trazar el camino hacia cada ganadero\n" +
            "• Registrar la ruta de acopio en tiempo real",
            fontSize    = 14.sp,
            color       = Color(0xFF4A5E4A),
            lineHeight  = 22.sp
        )
        Spacer(Modifier.height(32.dp))
        if (permissionsState.shouldShowRationale) {
            Button(
                onClick  = { permissionsState.launchMultiplePermissionRequest() },
                modifier = Modifier.fillMaxWidth().height(52.dp),
                shape    = RoundedCornerShape(12.dp),
                colors   = ButtonDefaults.buttonColors(containerColor = Color(0xFF1E8A3C))
            ) {
                Text("CONCEDER PERMISO", color = Color.White, fontWeight = FontWeight.Bold)
            }
        } else {
            Button(
                onClick = {
                    context.startActivity(
                        Intent(
                            Settings.ACTION_APPLICATION_DETAILS_SETTINGS,
                            Uri.fromParts("package", context.packageName, null)
                        )
                    )
                },
                modifier = Modifier.fillMaxWidth().height(52.dp),
                shape    = RoundedCornerShape(12.dp),
                colors   = ButtonDefaults.buttonColors(containerColor = Color(0xFF1565C0))
            ) {
                Text("ABRIR AJUSTES DEL SISTEMA", color = Color.White, fontWeight = FontWeight.Bold)
            }
        }
        Spacer(Modifier.height(12.dp))
        Button(
            onClick  = onBack,
            modifier = Modifier.fillMaxWidth().height(48.dp),
            shape    = RoundedCornerShape(12.dp),
            colors   = ButtonDefaults.buttonColors(containerColor = Color(0xFFE0E8E0))
        ) {
            Text("VOLVER", color = Color(0xFF0D1A0D), fontWeight = FontWeight.SemiBold)
        }
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Abre Google Maps en modo navegación turn-by-turn hacia las coordenadas dadas.
 * URI: google.navigation:q=lat,lon&mode=d (modo driving)
 * Si Google Maps no está instalado, abre el navegador con maps.google.com como fallback.
 */
private fun abrirNavegacionGoogleMaps(
    context: Context,
    lat: Double,
    lon: Double,
    nombre: String
) {
    // Intent de navegación nativa de Google Maps
    val navUri  = Uri.parse("google.navigation:q=$lat,$lon&mode=d")
    val navIntent = Intent(Intent.ACTION_VIEW, navUri).apply {
        setPackage("com.google.android.apps.maps")
    }

    if (navIntent.resolveActivity(context.packageManager) != null) {
        context.startActivity(navIntent)
    } else {
        // Fallback: abre en navegador con el nombre del lugar como label
        val webUri = Uri.parse("https://maps.google.com/?daddr=$lat,$lon&directionsmode=driving")
        context.startActivity(Intent(Intent.ACTION_VIEW, webUri))
    }
}

@Suppress("MissingPermission")
private fun obtenerPosicionActual(context: Context, onPosicion: (Double, Double) -> Unit) {
    LocationServices.getFusedLocationProviderClient(context)
        .lastLocation
        .addOnSuccessListener { location ->
            location?.let { onPosicion(it.latitude, it.longitude) }
        }
}

private fun iniciarGpsService(context: Context, asignacionId: Int) {
    context.startForegroundService(GpsTrackingService.startIntent(context, asignacionId))
}

private fun detenerGpsService(context: Context) {
    context.stopService(GpsTrackingService.stopIntent(context))
}
