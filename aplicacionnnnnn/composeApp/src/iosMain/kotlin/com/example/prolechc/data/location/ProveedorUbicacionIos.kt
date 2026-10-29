package com.example.prolechc.data.location

import com.example.prolechc.domain.model.Coordenada
import com.example.prolechc.domain.model.LecturaGps
import kotlinx.cinterop.ExperimentalForeignApi
import kotlinx.cinterop.useContents
import kotlinx.coroutines.channels.awaitClose
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.callbackFlow
import platform.CoreLocation.CLLocation
import platform.CoreLocation.CLLocationManager
import platform.CoreLocation.CLLocationManagerDelegateProtocol
import platform.CoreLocation.kCLAuthorizationStatusAuthorizedAlways
import platform.CoreLocation.kCLAuthorizationStatusAuthorizedWhenInUse
import platform.CoreLocation.kCLLocationAccuracyBest
import platform.Foundation.NSError
import platform.Foundation.timeIntervalSince1970
import platform.darwin.NSObject

/**
 * GPS con CoreLocation. Requiere en Info.plist:
 * NSLocationWhenInUseUsageDescription (y NSLocationAlwaysAndWhenInUseUsageDescription
 * + UIBackgroundModes=location para rastrear con la pantalla apagada).
 */
@OptIn(ExperimentalForeignApi::class)
class ProveedorUbicacionIos : ProveedorUbicacion {

    private val manager = CLLocationManager()
    private var delegadoActivo: NSObject? = null

    override fun tienePermiso(): Boolean {
        val estado = CLLocationManager.authorizationStatus()
        return estado == kCLAuthorizationStatusAuthorizedWhenInUse ||
            estado == kCLAuthorizationStatusAuthorizedAlways
    }

    fun solicitarPermiso() = manager.requestWhenInUseAuthorization()

    override fun lecturas(intervaloMs: Long): Flow<LecturaGps> = callbackFlow {
        val delegado = object : NSObject(), CLLocationManagerDelegateProtocol {
            override fun locationManager(manager: CLLocationManager, didUpdateLocations: List<*>) {
                didUpdateLocations.filterIsInstance<CLLocation>().forEach { trySend(it.aLectura()) }
            }

            override fun locationManager(manager: CLLocationManager, didFailWithError: NSError) {
                // Errores puntuales (sin fix): se sigue esperando la siguiente lectura
            }
        }
        delegadoActivo = delegado   // `delegate` es weak en iOS: hay que retenerlo aquí
        manager.delegate = delegado
        manager.desiredAccuracy = kCLLocationAccuracyBest
        manager.distanceFilter = 5.0
        manager.requestWhenInUseAuthorization()
        manager.startUpdatingLocation()
        awaitClose {
            manager.stopUpdatingLocation()
            manager.delegate = null
            delegadoActivo = null
        }
    }

    override suspend fun ultimaConocida(): LecturaGps? = manager.location?.aLectura()

    private fun CLLocation.aLectura(): LecturaGps {
        val (lat, lon) = coordinate.useContents { latitude to longitude }
        return LecturaGps(
            coordenada   = Coordenada(lat, lon),
            registradoEn = (timestamp.timeIntervalSince1970 * 1000).toLong(),
            precisionM   = horizontalAccuracy.takeIf { it >= 0 },
            velocidadKmh = speed.takeIf { it >= 0 }?.times(3.6)
        )
    }
}

/** En iOS no hay servicio aparte: CoreLocation sigue activo mientras la app lo use. */
class ControlRastreoIos : ControlRastreoSegundoPlano {
    override fun iniciar() = Unit
    override fun detener() = Unit
}
