package com.example.prolechc.data.network

import kotlinx.coroutines.flow.StateFlow

/** Expone en tiempo real si el dispositivo tiene acceso a internet. */
interface MonitorConectividad {
    val enLinea: StateFlow<Boolean>
    fun hayConexion(): Boolean = enLinea.value
}
