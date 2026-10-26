package com.example.prolechc.presentation.ruta

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SmallFloatingActionButton
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalUriHandler
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.prolechc.domain.model.Parada
import com.example.prolechc.domain.model.PlanRuta
import androidx.lifecycle.compose.LifecycleResumeEffect
import com.example.prolechc.domain.ruta.Geo
import com.example.prolechc.domain.ruta.coordenada
import com.example.prolechc.presentation.colorCarga
import com.example.prolechc.presentation.formato
import com.example.prolechc.presentation.theme.ProLechColores
import org.koin.compose.viewmodel.koinViewModel

@Composable
fun MiRutaScreen(
    onRegistrarAcopio: (Parada) -> Unit,
    viewModel: MiRutaViewModel = koinViewModel()
) {
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    val snackbar = remember { SnackbarHostState() }
    var centrar by remember { mutableIntStateOf(0) }
    val pedirPermiso = rememberSolicitarPermisoUbicacion(viewModel::onPermisoResultado)
    val uri = LocalUriHandler.current

    // Al volver a «Mi ruta» se descargan los cambios del administrador
    LifecycleResumeEffect(Unit) {
        viewModel.alVolverAPantalla()
        onPauseOrDispose { }
    }

    LaunchedEffect(state.mensaje) {
        state.mensaje?.let { snackbar.showSnackbar(it); viewModel.limpiarMensaje() }
    }

    state.resumen?.let { r -> DialogoResumen(r, onCerrar = viewModel::cerrarResumen) }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbar) },
        containerColor = MaterialTheme.colorScheme.background
    ) { padding ->
        if (state.cargando) {
            Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
            return@Scaffold
        }

        val ruta = state.ruta?.ruta
        if (ruta == null) {
            SinRuta(Modifier.padding(padding), state.enLinea, onReintentar = { viewModel.refrescar() })
            return@Scaffold
        }

        LazyColumn(
            modifier = Modifier.fillMaxSize().padding(padding),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            item { Encabezado(state, onActualizar = { viewModel.refrescar() }) }

            item {
                Box(
                    Modifier.fillMaxWidth().height(340.dp).clip(RoundedCornerShape(24.dp))
                        .border(1.dp, MaterialTheme.colorScheme.outline, RoundedCornerShape(24.dp))
                ) {
                    MapaRuta(
                        modifier      = Modifier.fillMaxSize(),
                        planta        = ruta.planta,
                        marcadores    = state.marcadores,
                        trazoPlan     = state.plan?.trazo.orEmpty(),
                        recorrido     = state.recorrido,
                        miUbicacion   = state.miUbicacion,
                        centrarEn     = centrar,
                        onParadaClick = onRegistrarAcopio
                    )
                    Leyenda(Modifier.align(Alignment.BottomStart).padding(10.dp), state.plan)
                    SmallFloatingActionButton(
                        onClick = { centrar++ },
                        modifier = Modifier.align(Alignment.TopEnd).padding(10.dp),
                        containerColor = Color.White
                    ) { Text("🎯") }
                }
            }

            if (!state.tienePermiso) {
                item {
                    Aviso(
                        icono = "📍",
                        titulo = "Activa tu ubicación",
                        texto = "La necesitamos para trazar el camino y registrar tu recorrido.",
                        accion = "Permitir",
                        onAccion = pedirPermiso
                    )
                }
            }

            item {
                if (state.rastreando) {
                    OutlinedButton(
                        onClick = viewModel::finalizarRuta,
                        modifier = Modifier.fillMaxWidth().height(54.dp),
                        shape = RoundedCornerShape(999.dp)
                    ) {
                        Text("⏹  Finalizar ruta · ${formato(state.recorridoKm, 1)} km recorridos", fontWeight = FontWeight.Bold)
                    }
                } else {
                    Button(
                        onClick = { if (state.tienePermiso) viewModel.iniciarRuta() else pedirPermiso() },
                        modifier = Modifier.fillMaxWidth().height(54.dp),
                        shape = RoundedCornerShape(999.dp)
                    ) {
                        Text("▶  Iniciar ruta del día", fontWeight = FontWeight.ExtraBold, fontSize = 16.sp)
                    }
                }
            }

            state.siguiente?.let { siguiente ->
                item {
                    SiguienteParada(
                        parada = siguiente,
                        distanciaKm = state.distanciaSiguienteKm,
                        onRegistrar = { onRegistrarAcopio(siguiente) },
                        onComoLlegar = siguiente.coordenada?.let { c ->
                            { uri.openUri("https://www.google.com/maps/dir/?api=1&destination=${c.lat},${c.lon}&travelmode=driving") }
                        }
                    )
                }
            }

            item {
                Text(
                    "Paradas de hoy",
                    style = MaterialTheme.typography.titleMedium,
                    modifier = Modifier.padding(top = 4.dp)
                )
            }
            items(
                state.marcadores.sortedWith(compareBy({ it.numero ?: Int.MAX_VALUE }, { it.parada.ganaderoNombre })),
                key = { it.parada.ganaderoId }
            ) { mk ->
                FilaParada(
                    mk, state,
                    onClick = { onRegistrarAcopio(mk.parada) },
                    onFijarUbicacion = { viewModel.fijarUbicacion(mk.parada) }
                )
            }
        }
    }
}

@Composable
private fun Encabezado(state: MiRutaUiState, onActualizar: () -> Unit) {
    val ruta = state.ruta!!.ruta
    val progreso = if (state.total == 0) 0f else state.atendidos.toFloat() / state.total
    Column(
        Modifier.fillMaxWidth().clip(RoundedCornerShape(24.dp))
            .background(ProLechColores.DegradadoOscuro).padding(20.dp)
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(
                    when (ruta.origenRuta) {
                        "zona" -> "TU ZONA ESTE MES" + if (ruta.rolMes == "titular") " · TITULAR" else ""
                        "manual" -> "RUTA ASIGNADA"
                        else -> "RUTA DEL DÍA"
                    },
                    color = Color(0xFF9EC5FF), fontSize = 11.sp, fontWeight = FontWeight.Bold, letterSpacing = 1.sp
                )
                Text(ruta.rutaNombre.ifBlank { "Mi ruta" }, color = Color.White, style = MaterialTheme.typography.headlineSmall)
            }
            Insignia(if (state.enLinea) "● En línea" else "● Sin señal", if (state.enLinea) ProLechColores.Exito else ProLechColores.Caramelo)
        }
        Spacer(Modifier.height(14.dp))
        Row(verticalAlignment = Alignment.Bottom) {
            Text("${state.atendidos}", color = Color.White, fontSize = 34.sp, fontWeight = FontWeight.ExtraBold)
            Text(" / ${state.total} ganaderos atendidos", color = Color(0xFFCFE1FF), modifier = Modifier.padding(bottom = 6.dp))
        }
        LinearProgressIndicator(
            progress = { progreso },
            modifier = Modifier.fillMaxWidth().height(8.dp).clip(RoundedCornerShape(99.dp)),
            color = ProLechColores.Celeste,
            trackColor = Color.White.copy(alpha = .15f)
        )
        Spacer(Modifier.height(14.dp))
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            state.plan?.let {
                Dato("🛣️", "${formato(it.distanciaKm, 1)} km", "por recorrer")
                Dato("⏱️", horas(it.duracionMin), "estimado")
            }
            Dato("〰️", "${formato(state.recorridoKm, 1)} km", "hecho hoy")
        }
        if (state.capacidadLitros > 0) {
            val carga = (state.litrosHoy / state.capacidadLitros).toFloat()
            Spacer(Modifier.height(10.dp))
            Text(
                "🚛 ${formato(state.litrosHoy, 0)} / ${formato(state.capacidadLitros, 0)} L en el camión",
                color = Color(0xFFCFE1FF), fontSize = 12.sp
            )
            LinearProgressIndicator(
                progress = { carga.coerceIn(0f, 1f) },
                modifier = Modifier.fillMaxWidth().height(6.dp).clip(RoundedCornerShape(99.dp)),
                color = colorCarga(carga.toDouble()),
                trackColor = Color.White.copy(alpha = .15f)
            )
        }
        TextButton(onClick = onActualizar, modifier = Modifier.align(Alignment.End)) {
            Text("↻ Actualizar ruta", color = Color(0xFF9EC5FF))
        }
    }
}

@Composable
private fun Dato(icono: String, valor: String, etiqueta: String) {
    Column(
        Modifier.clip(RoundedCornerShape(14.dp)).background(Color.White.copy(alpha = .08f))
            .padding(horizontal = 12.dp, vertical = 8.dp)
    ) {
        Text("$icono $valor", color = Color.White, fontWeight = FontWeight.Bold, fontSize = 14.sp)
        Text(etiqueta, color = Color(0xFF9EC5FF), fontSize = 11.sp)
    }
}

@Composable
private fun Insignia(texto: String, color: Color) {
    Text(
        texto, color = color, fontSize = 11.sp, fontWeight = FontWeight.Bold,
        modifier = Modifier.clip(RoundedCornerShape(99.dp)).background(Color.White).padding(horizontal = 10.dp, vertical = 5.dp)
    )
}

@Composable
private fun Leyenda(modifier: Modifier, plan: PlanRuta?) {
    Row(
        modifier.clip(RoundedCornerShape(12.dp)).background(Color.White.copy(alpha = .94f))
            .padding(horizontal = 10.dp, vertical = 6.dp),
        horizontalArrangement = Arrangement.spacedBy(10.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Linea(ProLechColores.Azul, when {
            plan?.trazadoPorPlanta == true -> "Ruta de la planta"
            plan?.porCarretera == true     -> "Camino sugerido"
            else                           -> "Plan (línea recta)"
        })
        Linea(ProLechColores.Caramelo, "Recorrido")
    }
}

@Composable
private fun Linea(color: Color, texto: String) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Box(Modifier.width(16.dp).height(4.dp).clip(RoundedCornerShape(2.dp)).background(color))
        Text(" $texto", fontSize = 11.sp, color = Color(0xFF334155))
    }
}

@Composable
private fun SiguienteParada(parada: Parada, distanciaKm: Double?, onRegistrar: () -> Unit, onComoLlegar: (() -> Unit)?) {
    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.tertiaryContainer),
        shape = RoundedCornerShape(22.dp)
    ) {
        Column(Modifier.padding(18.dp)) {
            Text("SIGUIENTE PARADA", fontSize = 11.sp, fontWeight = FontWeight.Bold, letterSpacing = 1.sp,
                color = MaterialTheme.colorScheme.onTertiaryContainer)
            Text(parada.ganaderoNombre, style = MaterialTheme.typography.titleLarge)
            Text(
                listOfNotNull(parada.direccion, distanciaKm?.let { "a ${formato(it, 1)} km" }).joinToString(" · "),
                color = MaterialTheme.colorScheme.onSurfaceVariant, style = MaterialTheme.typography.bodyMedium
            )
            Spacer(Modifier.height(12.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                Button(onClick = onRegistrar, modifier = Modifier.weight(1f), shape = RoundedCornerShape(999.dp)) {
                    Text("🥛 Registrar acopio")
                }
                if (onComoLlegar != null) {
                    OutlinedButton(onClick = onComoLlegar, shape = RoundedCornerShape(999.dp)) { Text("🧭 Cómo llegar") }
                }
            }
        }
    }
}

@Composable
private fun FilaParada(
    mk: MarcadorParada,
    state: MiRutaUiState,
    onClick: () -> Unit,
    onFijarUbicacion: () -> Unit
) {
    val (fondo, texto, etiqueta) = when (mk.estado) {
        EstadoParada.SIGUIENTE  -> Triple(ProLechColores.Caramelo, Color.White, "Siguiente")
        EstadoParada.PENDIENTE  -> Triple(ProLechColores.Azul, Color.White, "Pendiente")
        EstadoParada.ATENDIDO   -> Triple(ProLechColores.Exito, Color.White, "Atendido")
        EstadoParada.SUSPENDIDO -> Triple(ProLechColores.Error, Color.White, "Suspendido")
    }
    val distancia = state.miUbicacion?.let { yo -> mk.parada.coordenada?.let { Geo.distanciaKm(yo, it) } }
    Card(
        modifier = Modifier.fillMaxWidth().clickable(enabled = mk.estado != EstadoParada.SUSPENDIDO, onClick = onClick),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        shape = RoundedCornerShape(18.dp),
        border = androidx.compose.foundation.BorderStroke(1.dp, MaterialTheme.colorScheme.outline)
    ) {
        Row(Modifier.padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
            Box(Modifier.size(38.dp).clip(CircleShape).background(fondo), contentAlignment = Alignment.Center) {
                Text(
                    when (mk.estado) {
                        EstadoParada.ATENDIDO -> "✓"
                        EstadoParada.SUSPENDIDO -> "!"
                        else -> mk.numero?.toString() ?: "•"
                    },
                    color = texto, fontWeight = FontWeight.ExtraBold
                )
            }
            Column(Modifier.weight(1f).padding(horizontal = 12.dp)) {
                Text(mk.parada.ganaderoNombre, style = MaterialTheme.typography.titleSmall, maxLines = 1, overflow = TextOverflow.Ellipsis)
                Text(
                    listOfNotNull(
                        mk.parada.direccion,
                        if (mk.parada.coordenada == null) "sin GPS" else distancia?.let { "${formato(it, 1)} km" }
                    ).joinToString(" · "),
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant, maxLines = 1, overflow = TextOverflow.Ellipsis
                )
            }
            if (mk.parada.coordenada == null && mk.estado != EstadoParada.SUSPENDIDO) {
                // Sin GPS: el chofer, parado en el predio, guarda la ubicación para el mapa
                TextButton(onClick = onFijarUbicacion) { Text("📍 Fijar aquí", fontSize = 12.sp) }
            } else {
                Text(etiqueta, fontSize = 11.sp, fontWeight = FontWeight.Bold, color = fondo)
            }
        }
    }
}

@Composable
private fun Aviso(icono: String, titulo: String, texto: String, accion: String, onAccion: () -> Unit) {
    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer), shape = RoundedCornerShape(20.dp)) {
        Row(Modifier.padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
            Text(icono, fontSize = 26.sp)
            Column(Modifier.weight(1f).padding(horizontal = 12.dp)) {
                Text(titulo, style = MaterialTheme.typography.titleSmall)
                Text(texto, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            Button(onClick = onAccion, shape = RoundedCornerShape(999.dp), colors = ButtonDefaults.buttonColors()) { Text(accion) }
        }
    }
}

@Composable
private fun SinRuta(modifier: Modifier, enLinea: Boolean, onReintentar: () -> Unit) {
    Column(
        modifier.fillMaxSize().padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text("🗺️", fontSize = 56.sp)
        Text("Aún no tienes la ruta de hoy", style = MaterialTheme.typography.titleLarge, modifier = Modifier.padding(top = 12.dp))
        Text(
            if (enLinea) "Descárgala ahora; después funcionará aunque no haya señal."
            else "Conéctate a internet una vez para descargarla.",
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.padding(top = 6.dp, bottom = 18.dp)
        )
        Button(onClick = onReintentar, enabled = enLinea, shape = RoundedCornerShape(999.dp)) { Text("Descargar ruta") }
    }
}

@Composable
private fun DialogoResumen(r: com.example.prolechc.domain.ruta.ResumenRuta, onCerrar: () -> Unit) {
    androidx.compose.material3.AlertDialog(
        onDismissRequest = onCerrar,
        title = { Text("🏁 Ruta terminada") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                Text("${formato(r.litros, 1)} L", style = MaterialTheme.typography.headlineMedium)
                Text("${r.entregas} entregas · ${r.atendidos}/${r.totalParadas} ganaderos atendidos")
                Text("〰️ ${formato(r.km, 1)} km" + (r.minutos?.let { " · ⏱️ ${horas(it)}" } ?: ""))
                Text(
                    "Calidad: ✅ ${r.aprobados}  ⚠️ ${r.observados}  ❌ ${r.rechazados}  (${r.porcentajeAprobado} % aprobado)" +
                        (r.aguaPromedio?.let { " · agua prom. ${formato(it, 1)} %" } ?: "")
                )
                if (r.noAtendidos.isNotEmpty()) {
                    Text(
                        "Sin visitar (${r.noAtendidos.size}): " + r.noAtendidos.take(8).joinToString(", ") +
                            if (r.noAtendidos.size > 8) "…" else "",
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodySmall
                    )
                }
            }
        },
        confirmButton = { TextButton(onClick = onCerrar) { Text("Listo") } }
    )
}

private fun horas(min: Int): String = if (min < 60) "$min min" else "${min / 60} h ${min % 60} min"
