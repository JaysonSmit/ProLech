package com.example.prolechc.presentation.ruta

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.viewinterop.UIKitView
import com.example.prolechc.domain.model.Coordenada
import com.example.prolechc.domain.model.Parada
import kotlinx.cinterop.ExperimentalForeignApi
import kotlinx.cinterop.allocArray
import kotlinx.cinterop.get
import kotlinx.cinterop.memScoped
import platform.CoreLocation.CLLocationCoordinate2D
import platform.CoreLocation.CLLocationCoordinate2DMake
import platform.CoreLocation.CLLocationManager
import platform.MapKit.MKAnnotationProtocol
import platform.MapKit.MKAnnotationView
import platform.MapKit.MKMapView
import platform.MapKit.MKMapViewDelegateProtocol
import platform.MapKit.MKOverlayProtocol
import platform.MapKit.MKOverlayRenderer
import platform.MapKit.MKPointAnnotation
import platform.MapKit.MKPolyline
import platform.MapKit.MKPolylineRenderer
import platform.UIKit.UIColor
import platform.darwin.NSObject

private fun azul() = UIColor(red = 0.12, green = 0.44, blue = 0.92, alpha = 1.0)
private fun caramelo() = UIColor(red = 0.95, green = 0.65, blue = 0.25, alpha = 1.0)

/** Delegado de MapKit: colorea las líneas y avisa qué parada se tocó. */
@OptIn(ExperimentalForeignApi::class)
private class DelegadoMapa : NSObject(), MKMapViewDelegateProtocol {
    var paradasPorAnotacion: Map<MKPointAnnotation, Parada> = emptyMap()
    var onParadaClick: (Parada) -> Unit = {}

    override fun mapView(mapView: MKMapView, rendererForOverlay: MKOverlayProtocol): MKOverlayRenderer {
        val linea = rendererForOverlay as MKPolyline
        return MKPolylineRenderer(linea).apply {
            strokeColor = if (linea.title == "plan") azul() else caramelo()
            lineWidth = if (linea.title == "plan") 6.0 else 5.0
        }
    }

    override fun mapView(mapView: MKMapView, didSelectAnnotationView: MKAnnotationView) {
        val anotacion = didSelectAnnotationView.annotation as? MKPointAnnotation ?: return
        paradasPorAnotacion[anotacion]?.let(onParadaClick)
    }
}

@OptIn(ExperimentalForeignApi::class)
private fun polilinea(puntos: List<Coordenada>, titulo: String): MKPolyline = memScoped {
    val arreglo = allocArray<CLLocationCoordinate2D>(puntos.size)
    puntos.forEachIndexed { i, c ->
        arreglo[i].latitude = c.lat
        arreglo[i].longitude = c.lon
    }
    MKPolyline.polylineWithCoordinates(arreglo, puntos.size.toULong()).apply { setTitle(titulo) }
}

@OptIn(ExperimentalForeignApi::class)
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
    val delegado = remember { DelegadoMapa() }
    delegado.onParadaClick = onParadaClick
    val mapa = remember {
        MKMapView().apply {
            delegate = delegado
            showsUserLocation = true
        }
    }

    UIKitView(
        factory = { mapa },
        modifier = modifier,
        update = { m ->
            m.removeOverlays(m.overlays)
            m.removeAnnotations(m.annotations.filterIsInstance<MKPointAnnotation>())

            if (recorrido.size >= 2) m.addOverlay(polilinea(recorrido, "recorrido"))
            if (trazoPlan.size >= 2) m.addOverlay(polilinea(trazoPlan, "plan"))

            val mapaAnotaciones = mutableMapOf<MKPointAnnotation, Parada>()
            marcadores.forEach { mk ->
                val lat = mk.parada.geoLat ?: return@forEach
                val lon = mk.parada.geoLon ?: return@forEach
                val anotacion = MKPointAnnotation().apply {
                    setCoordinate(CLLocationCoordinate2DMake(lat, lon))
                    val prefijo = when (mk.estado) {
                        EstadoParada.ATENDIDO   -> "✓ "
                        EstadoParada.SUSPENDIDO -> "⚠ "
                        else -> mk.numero?.let { "$it. " } ?: ""
                    }
                    setTitle(prefijo + mk.parada.ganaderoNombre)
                    setSubtitle(mk.parada.direccion)
                }
                mapaAnotaciones[anotacion] = mk.parada
                m.addAnnotation(anotacion)
            }
            planta?.let {
                m.addAnnotation(MKPointAnnotation().apply {
                    setCoordinate(CLLocationCoordinate2DMake(it.lat, it.lon))
                    setTitle("🏭 Planta ProLech")
                })
            }
            delegado.paradasPorAnotacion = mapaAnotaciones
        }
    )

    LaunchedEffect(centrarEn, marcadores.isNotEmpty()) {
        val anotaciones: List<MKAnnotationProtocol> = mapa.annotations.filterIsInstance<MKAnnotationProtocol>()
        if (anotaciones.isNotEmpty()) mapa.showAnnotations(anotaciones, animated = true)
    }
}

@Composable
actual fun rememberSolicitarPermisoUbicacion(onResultado: (Boolean) -> Unit): () -> Unit {
    val manager = remember { CLLocationManager() }
    return remember(manager) {
        {
            // iOS muestra su propio diálogo; el resultado se refleja al volver a leer el GPS
            manager.requestWhenInUseAuthorization()
            onResultado(true)
        }
    }
}
