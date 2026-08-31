package com.example.prolechc.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.*
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

// ── Estados ──────────────────────────────────────────────────────────────────

data class LoginState(
    val conductorIndex: Int = 0,
    val conductorNombre: String = mockConductores[0].nombre,
    val vehiculo: String = mockConductores[0].vehiculo,
    val ruta: String = mockConductores[0].ruta,
    val rutaIniciada: Boolean = false
)

data class AcopioFormState(
    val dni: String = "",
    val nombreAutocompletado: String = "",
    val litros: Double = 0.0,
    val litrosTexto: String = "0.0",
    val porcentajeAgua: String = "",
    val acidezPh: String = "",
    val lechRechazada: Boolean = false,
    val guardadoExitoso: Boolean = false,
    val ticketNumero: String = "02341"
)

data class SyncState(
    val pendientes: Int = pendientesSync,
    val sincronizando: Boolean = false,
    val exitoso: Boolean = false,
    val progreso: Float = 0f
)

// ── ViewModel ─────────────────────────────────────────────────────────────────
class ProLechViewModel : ViewModel() {

    // ── Login ─────────────────────────────────────────────────────────────
    private val _loginState = MutableStateFlow(LoginState())
    val loginState: StateFlow<LoginState> = _loginState.asStateFlow()

    fun onConductorChange(index: Int) {
        val c = mockConductores[index]
        _loginState.value = _loginState.value.copy(
            conductorIndex  = index,
            conductorNombre = c.nombre,
            vehiculo        = c.vehiculo,
            ruta            = c.ruta
        )
    }
    fun iniciarRuta() {
        _loginState.value = _loginState.value.copy(rutaIniciada = true)
    }

    // ── Dashboard ─────────────────────────────────────────────────────────
    val cisternaActual   = CISTERNA_ACTUAL
    val cisternaTotal    get() = mockConductores[_loginState.value.conductorIndex].capacidadL
    val cisternaProgress get() = (CISTERNA_ACTUAL / cisternaTotal).toFloat()

    // ── Formulario Acopio ─────────────────────────────────────────────────
    private val _form = MutableStateFlow(AcopioFormState())
    val formState: StateFlow<AcopioFormState> = _form.asStateFlow()

    fun onDniChange(v: String) {
        _form.value = _form.value.copy(
            dni                  = v,
            nombreAutocompletado = dniAutocompletado[v] ?: ""
        )
    }

    fun incrementarLitros() {
        val nuevo = (_form.value.litros + 0.5).coerceAtMost(9999.0)
        _form.value = _form.value.copy(litros = nuevo, litrosTexto = "%.1f".format(nuevo))
    }

    fun decrementarLitros() {
        val nuevo = (_form.value.litros - 0.5).coerceAtLeast(0.0)
        _form.value = _form.value.copy(litros = nuevo, litrosTexto = "%.1f".format(nuevo))
    }

    fun onLitrosTextoChange(v: String) {
        val num = v.toDoubleOrNull() ?: 0.0
        _form.value = _form.value.copy(litros = num, litrosTexto = v)
    }

    fun onPorcentajeAguaChange(v: String) {
        val rechazada = v.toDoubleOrNull()?.let { it > 5.0 } ?: false
        _form.value = _form.value.copy(porcentajeAgua = v, lechRechazada = rechazada)
    }

    fun onAcidezPhChange(v: String) {
        _form.value = _form.value.copy(acidezPh = v)
    }

    fun guardarAcopio() {
        _form.value = _form.value.copy(guardadoExitoso = true)
    }

    fun resetForm() {
        _form.value = AcopioFormState()
    }

    // ── Sync ──────────────────────────────────────────────────────────────
    private val _sync = MutableStateFlow(SyncState())
    val syncState: StateFlow<SyncState> = _sync.asStateFlow()

    fun sincronizarAhora() {
        viewModelScope.launch {
            _sync.value = _sync.value.copy(sincronizando = true, exitoso = false, progreso = 0f)
            repeat(30) { i ->
                delay(100L)
                _sync.value = _sync.value.copy(progreso = (i + 1) / 30f)
            }
            _sync.value = _sync.value.copy(sincronizando = false, exitoso = true, pendientes = 0, progreso = 1f)
        }
    }

    fun resetSync() {
        _sync.value = SyncState()
    }
}
