package com.example.prolechc.data.local

import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * Estado global de la sesión. Cuando el servidor rechaza el token (401) la app
 * vuelve al login, pero SIN borrar los datos offline: al reingresar se suben.
 */
class EstadoSesion {
    private val _vencida = MutableStateFlow(false)
    val vencida: StateFlow<Boolean> = _vencida.asStateFlow()

    fun marcarVencida() { _vencida.value = true }
    fun restablecer() { _vencida.value = false }
}
