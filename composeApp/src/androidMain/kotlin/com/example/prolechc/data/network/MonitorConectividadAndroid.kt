package com.example.prolechc.data.network

import android.content.Context
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * Escucha los cambios de red con [ConnectivityManager.NetworkCallback].
 * Requiere el permiso ACCESS_NETWORK_STATE (ya declarado en el manifest).
 */
class MonitorConectividadAndroid(context: Context) : MonitorConectividad {

    private val connectivityManager =
        context.getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager

    private val _enLinea = MutableStateFlow(estadoActual())
    override val enLinea: StateFlow<Boolean> = _enLinea.asStateFlow()

    private val callback = object : ConnectivityManager.NetworkCallback() {
        override fun onCapabilitiesChanged(network: Network, capabilities: NetworkCapabilities) {
            _enLinea.value = capabilities.tieneInternet()
        }

        override fun onLost(network: Network) {
            _enLinea.value = estadoActual()
        }

        override fun onUnavailable() {
            _enLinea.value = false
        }
    }

    init {
        connectivityManager.registerDefaultNetworkCallback(callback)
    }

    private fun estadoActual(): Boolean {
        val network = connectivityManager.activeNetwork ?: return false
        return connectivityManager.getNetworkCapabilities(network)?.tieneInternet() == true
    }

    private fun NetworkCapabilities.tieneInternet(): Boolean =
        hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET) &&
            hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED)
}
