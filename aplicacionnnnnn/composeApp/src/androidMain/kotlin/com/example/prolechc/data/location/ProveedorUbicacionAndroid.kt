package com.example.prolechc.data.location

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.pm.PackageManager
import android.location.Location
import android.os.Looper
import androidx.core.content.ContextCompat
import com.example.prolechc.domain.model.Coordenada
import com.example.prolechc.domain.model.LecturaGps
import com.google.android.gms.location.LocationCallback
import com.google.android.gms.location.LocationRequest
import com.google.android.gms.location.LocationResult
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import kotlinx.coroutines.channels.awaitClose
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.callbackFlow
import kotlinx.coroutines.suspendCancellableCoroutine
import kotlin.coroutines.resume

/** GPS con FusedLocationProviderClient (Google Play Services). */
class ProveedorUbicacionAndroid(private val context: Context) : ProveedorUbicacion {

    private val cliente = LocationServices.getFusedLocationProviderClient(context)

    override fun tienePermiso(): Boolean =
        ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) ==
            PackageManager.PERMISSION_GRANTED ||
        ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_COARSE_LOCATION) ==
            PackageManager.PERMISSION_GRANTED

    @SuppressLint("MissingPermission")
    override fun lecturas(intervaloMs: Long): Flow<LecturaGps> = callbackFlow {
        if (!tienePermiso()) {
            close(SecurityException("Sin permiso de ubicación"))
            return@callbackFlow
        }
        val request = LocationRequest.Builder(Priority.PRIORITY_HIGH_ACCURACY, intervaloMs)
            .setMinUpdateIntervalMillis(intervaloMs / 2)
            .setMinUpdateDistanceMeters(5f)
            .build()

        val callback = object : LocationCallback() {
            override fun onLocationResult(result: LocationResult) {
                result.locations.forEach { trySend(it.aLectura()) }
            }
        }
        cliente.requestLocationUpdates(request, callback, Looper.getMainLooper())
        awaitClose { cliente.removeLocationUpdates(callback) }
    }

    @SuppressLint("MissingPermission")
    override suspend fun ultimaConocida(): LecturaGps? {
        if (!tienePermiso()) return null
        return suspendCancellableCoroutine { cont ->
            cliente.lastLocation
                .addOnSuccessListener { cont.resume(it?.aLectura()) }
                .addOnFailureListener { cont.resume(null) }
        }
    }

    private fun Location.aLectura() = LecturaGps(
        coordenada   = Coordenada(latitude, longitude),
        registradoEn = time,
        precisionM   = if (hasAccuracy()) accuracy.toDouble() else null,
        velocidadKmh = if (hasSpeed()) speed * 3.6 else null
    )
}
