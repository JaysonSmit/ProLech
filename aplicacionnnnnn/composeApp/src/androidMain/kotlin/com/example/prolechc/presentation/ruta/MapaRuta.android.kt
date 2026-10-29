package com.example.prolechc.presentation.ruta

import android.Manifest
import android.content.Context
import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.Paint
import android.graphics.drawable.BitmapDrawable
import android.os.Build
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.viewinterop.AndroidView
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner
import com.example.prolechc.domain.model.Coordenada
import com.example.prolechc.domain.model.Parada
import org.osmdroid.config.Configuration
import org.osmdroid.tileprovider.tilesource.TileSourceFactory
import org.osmdroid.util.BoundingBox
import org.osmdroid.util.GeoPoint
import org.osmdroid.views.CustomZoomButtonsController
import org.osmdroid.views.MapView
import org.osmdroid.views.overlay.Marker
import org.osmdroid.views.overlay.Polyline

private const val AZUL     = 0xFF1F6FEB.toInt()
private const val CARAMELO = 0xFFF2A541.toInt()
private const val MARINO   = 0xFF0B1F44.toInt()
private const val VERDE    = 0xFF16A34A.toInt()
private const val GRIS     = 0xFF94A3B8.toInt()
private const val ROJO     = 0xFFDC2626.toInt()

@Composable
actual fun MapaRuta(
    modifier: Modifier,
    planta: Coordenada?,
    marcadores: List<MarcadorParada>,
    trazoPlan: List<Coordenada>,
    recorrido: List<Coordenada>,
    miUbicacion: Coordenada?,
    centrarEn: Int,
    onParadaClick: (Parada) -> Unit
) {
    val context = LocalContext.current
    val alTocar by rememberUpdatedState(onParadaClick)

    val mapa = remember {
        // OpenStreetMap exige un user-agent que identifique a la app. Con MAPNIK osmdroid
        // manda el nombre del paquete (por eso el APK usa "pe.prolech.acopio": OSM bloquea
        // los "com.example.*"); este valor se usa con las demás fuentes de mosaicos.
        Configuration.getInstance().apply {
            load(context, context.getSharedPreferences("osmdroid", Context.MODE_PRIVATE))
            userAgentValue = agenteMapa(context)
            // Caché de mosaicos en la carpeta privada: sin permisos de almacenamiento y
            // permite ver las zonas ya recorridas aunque no haya señal. Carpeta nueva para
            // descartar los mosaicos de "bloqueado" que hayan quedado guardados.
            osmdroidBasePath = java.io.File(context.filesDir, "osmdroid")
            osmdroidTileCache = java.io.File(context.cacheDir, "osmdroid-mosaicos")
        }
        MapView(context).apply {
            setTileSource(TileSourceFactory.MAPNIK)
            setMultiTouchControls(true)
            zoomController.setVisibility(CustomZoomButtonsController.Visibility.NEVER)
            controller.setZoom(13.0)
            planta?.let { controller.setCenter(GeoPoint(it.lat, it.lon)) }
        }
    }

    // El MapView necesita los eventos de ciclo de vida (pausa la descarga de mosaicos)
    val lifecycle = LocalLifecycleOwner.current.lifecycle
    DisposableEffect(lifecycle) {
        val observador = LifecycleEventObserver { _, evento ->
            when (evento) {
                Lifecycle.Event.ON_RESUME -> mapa.onResume()
                Lifecycle.Event.ON_PAUSE  -> mapa.onPause()
                else -> Unit
            }
        }
        lifecycle.addObserver(observador)
        onDispose {
            lifecycle.removeObserver(observador)
            mapa.onDetach()
        }
    }

    val iconos = remember { mutableMapOf<String, BitmapDrawable>() }

    AndroidView(factory = { mapa }, modifier = modifier) { m ->
        m.overlays.clear()

        if (recorrido.size >= 2) {
            m.overlays += Polyline(m).apply {
                setPoints(recorrido.map { GeoPoint(it.lat, it.lon) })
                outlinePaint.color = CARAMELO
                outlinePaint.strokeWidth = 10f
                outlinePaint.strokeCap = Paint.Cap.ROUND
                infoWindow = null
            }
        }
        if (trazoPlan.size >= 2) {
            m.overlays += Polyline(m).apply {
                setPoints(trazoPlan.map { GeoPoint(it.lat, it.lon) })
                outlinePaint.color = AZUL
                outlinePaint.strokeWidth = 12f
                outlinePaint.strokeCap = Paint.Cap.ROUND
                outlinePaint.alpha = 210
                infoWindow = null
            }
        }

        planta?.let {
            m.overlays += Marker(m).apply {
                position = GeoPoint(it.lat, it.lon)
                setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_CENTER)
                icon = iconos.getOrPut("planta") { circulo(context, MARINO, "🏭", 46) }
                title = "Planta ProLech"
            }
        }

        marcadores.forEach { mk ->
            val color = when (mk.estado) {
                EstadoParada.SIGUIENTE  -> CARAMELO
                EstadoParada.PENDIENTE  -> AZUL
                EstadoParada.ATENDIDO   -> VERDE
                EstadoParada.SUSPENDIDO -> ROJO
            }
            val texto = when (mk.estado) {
                EstadoParada.ATENDIDO   -> "✓"
                EstadoParada.SUSPENDIDO -> "!"
                else -> mk.numero?.toString() ?: "•"
            }
            val lat = mk.parada.geoLat ?: return@forEach
            val lon = mk.parada.geoLon ?: return@forEach
            m.overlays += Marker(m).apply {
                position = GeoPoint(lat, lon)
                setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_CENTER)
                val tam = if (mk.estado == EstadoParada.SIGUIENTE) 54 else 42
                icon = iconos.getOrPut("$color-$texto-$tam") { circulo(context, color, texto, tam) }
                title = mk.parada.ganaderoNombre
                snippet = mk.parada.direccion
                setOnMarkerClickListener { _, _ -> alTocar(mk.parada); true }
            }
        }

        miUbicacion?.let {
            m.overlays += Marker(m).apply {
                position = GeoPoint(it.lat, it.lon)
                setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_CENTER)
                icon = iconos.getOrPut("yo") { circulo(context, AZUL, "🚛", 50, borde = 0x5538BDF8) }
                title = "Tú"
            }
        }
        m.invalidate()
    }

    // Encuadra todo el recorrido al abrir y cada vez que se pide "centrar"
    LaunchedEffect(centrarEn, marcadores.isNotEmpty()) {
        val puntos = buildList {
            planta?.let { add(GeoPoint(it.lat, it.lon)) }
            miUbicacion?.let { add(GeoPoint(it.lat, it.lon)) }
            marcadores.mapNotNull { mk -> mk.parada.geoLat?.let { GeoPoint(it, mk.parada.geoLon ?: return@mapNotNull null) } }
                .forEach { add(it) }
        }
        if (puntos.size >= 2) {
            mapa.post { mapa.zoomToBoundingBox(BoundingBox.fromGeoPoints(puntos).increaseByScale(1.3f), true) }
        } else if (puntos.size == 1) {
            mapa.controller.animateTo(puntos.first(), 15.0, 600L)
        }
    }
}

/** Ícono circular con un número o emoji, al estilo de la app. */
private fun circulo(context: Context, color: Int, texto: String, dp: Int, borde: Int = 0xFFFFFFFF.toInt()): BitmapDrawable {
    val d = context.resources.displayMetrics.density
    val px = (dp * d).toInt()
    val bmp = Bitmap.createBitmap(px, px, Bitmap.Config.ARGB_8888)
    val c = Canvas(bmp)
    val p = Paint(Paint.ANTI_ALIAS_FLAG)
    p.color = borde
    c.drawCircle(px / 2f, px / 2f, px / 2f, p)
    p.color = color
    c.drawCircle(px / 2f, px / 2f, px / 2f - 3 * d, p)
    p.color = 0xFFFFFFFF.toInt()
    p.textAlign = Paint.Align.CENTER
    p.isFakeBoldText = true
    p.textSize = px * 0.42f
    val y = px / 2f - (p.descent() + p.ascent()) / 2
    c.drawText(texto, px / 2f, y, p)
    return BitmapDrawable(context.resources, bmp)
}

@Composable
actual fun rememberSolicitarPermisoUbicacion(onResultado: (Boolean) -> Unit): () -> Unit {
    val launcher = rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { r ->
        onResultado(r[Manifest.permission.ACCESS_FINE_LOCATION] == true || r[Manifest.permission.ACCESS_COARSE_LOCATION] == true)
    }
    return remember(launcher) {
        {
            val permisos = buildList {
                add(Manifest.permission.ACCESS_FINE_LOCATION)
                add(Manifest.permission.ACCESS_COARSE_LOCATION)
                // Android 13+: la notificación del rastreo en segundo plano necesita permiso
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) add(Manifest.permission.POST_NOTIFICATIONS)
            }
            launcher.launch(permisos.toTypedArray())
        }
    }
}

/** "ProLech-Acopio/2.0 (Android; acopio de leche Huata, Puno)" */
internal fun agenteMapa(context: Context): String {
    val version = runCatching {
        context.packageManager.getPackageInfo(context.packageName, 0).versionName
    }.getOrNull() ?: "1"
    return "ProLech-Acopio/$version (Android; acopio de leche Huata, Puno)"
}
