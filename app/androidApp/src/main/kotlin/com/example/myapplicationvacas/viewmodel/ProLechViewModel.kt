package com.example.myapplicationvacas.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.myapplicationvacas.data.*
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

// ── Estados ───────────────────────────────────────────────────────────────────

data class LoginState(
    val conductorSeleccionado: Conductor? = null,
    val camionSeleccionado: Camion?       = null,
    val rutaSeleccionada: Ruta?           = null,
    val rutaIniciada: Boolean             = false
)

data class AcopioFormState(
    val dni: String                   = "",
    val ganaderoEncontrado: Ganadero? = null,
    val litros: String                = "",
    val porcentajeAgua: String        = "",
    val acidezPh: String              = "",
    val lecheRechazada: Boolean       = false,
    val motivoRechazo: String         = "",
    val guardadoExitoso: Boolean      = false,
    val ultimoAcopio: Acopio?         = null
)

enum class SyncEstado { IDLE, SINCRONIZANDO, EXITOSO }

data class SyncState(
    val registrosPendientes: Int = 3,
    val estado: SyncEstado      = SyncEstado.IDLE
)

// ── ViewModel ─────────────────────────────────────────────────────────────────

class ProLechViewModel : ViewModel() {

    // Login
    private val _loginState = MutableStateFlow(LoginState())
    val loginState: StateFlow<LoginState> = _loginState.asStateFlow()

    fun onConductorChange(c: Conductor) { _loginState.value = _loginState.value.copy(conductorSeleccionado = c) }
    fun onCamionChange(c: Camion)       { _loginState.value = _loginState.value.copy(camionSeleccionado = c) }
    fun onRutaChange(r: Ruta)           { _loginState.value = _loginState.value.copy(rutaSeleccionada = r) }
    fun iniciarRuta()                   { _loginState.value = _loginState.value.copy(rutaIniciada = true) }

    // Cisterna
    private val _cisterna = MutableStateFlow(MockData.cisterna)
    val cisterna: StateFlow<EstadoCisterna> = _cisterna.asStateFlow()

    // Lista de acopios (incluye mock + nuevos)
    private val _acopios = MutableStateFlow(MockData.historialAcopios.toMutableList())
    val acopios: StateFlow<MutableList<Acopio>> = _acopios.asStateFlow()

    // Formulario
    private val _form = MutableStateFlow(AcopioFormState())
    val formState: StateFlow<AcopioFormState> = _form.asStateFlow()

    fun onDniChange(value: String) {
        val ganadero = MockData.ganaderos.find { it.dni == value.trim() }
        _form.value = _form.value.copy(dni = value, ganaderoEncontrado = ganadero)
    }

    fun onLitrosChange(value: String) {
        _form.value = _form.value.copy(litros = value)
    }

    fun onPorcentajeAguaChange(value: String) {
        val rechazada = value.toFloatOrNull()?.let { it > 5.0f } ?: false
        _form.value = _form.value.copy(porcentajeAgua = value, lecheRechazada = rechazada)
    }

    fun onAcidezPhChange(value: String) {
        _form.value = _form.value.copy(acidezPh = value)
    }

    fun onMotivoRechazoChange(value: String) {
        _form.value = _form.value.copy(motivoRechazo = value)
    }

    fun guardarAcopio() {
        val f = _form.value
        val ganadero = f.ganaderoEncontrado ?: return
        val nuevoId = (_acopios.value.maxOfOrNull { it.id } ?: 0) + 1
        val estado = if (f.lecheRechazada) EstadoAcopio.RECHAZADO else EstadoAcopio.APROBADO

        val hora = java.text.SimpleDateFormat("hh:mm a", java.util.Locale.getDefault())
            .format(java.util.Date())

        val nuevo = Acopio(
            id             = nuevoId,
            ticketNumero   = "ACO-${nuevoId.toString().padStart(6, '0')}",
            nombreGanadero = ganadero.nombre,
            dniGanadero    = ganadero.dni,
            litros         = f.litros.toFloatOrNull() ?: 0f,
            porcentajeAgua = f.porcentajeAgua.toFloatOrNull() ?: 0f,
            acidezPh       = f.acidezPh.toFloatOrNull() ?: 0f,
            hora           = hora,
            estado         = estado,
            motivoRechazo  = if (f.lecheRechazada) f.motivoRechazo.ifBlank { "Parámetros fuera de rango" } else null,
            pendienteSinc  = true
        )

        val lista = _acopios.value.toMutableList().also { it.add(0, nuevo) }
        _acopios.value = lista

        // Actualizar cisterna si aprobado
        if (!f.lecheRechazada) {
            val c = _cisterna.value
            _cisterna.value = c.copy(
                litrosActuales = (c.litrosActuales + (f.litros.toFloatOrNull() ?: 0f))
                    .coerceAtMost(c.capacidadTotal)
            )
        }

        _form.value = _form.value.copy(guardadoExitoso = true, ultimoAcopio = nuevo)
    }

    fun resetForm() {
        _form.value = AcopioFormState()
    }

    // Sincronización
    private val _sync = MutableStateFlow(SyncState())
    val syncState: StateFlow<SyncState> = _sync.asStateFlow()

    fun registrosPendientes(): Int = _acopios.value.count { it.pendienteSinc }

    fun iniciarSincronizacion() {
        if (_sync.value.estado == SyncEstado.SINCRONIZANDO) return
        viewModelScope.launch {
            _sync.value = _sync.value.copy(estado = SyncEstado.SINCRONIZANDO)
            delay(3_000L)
            val sincronizados = _acopios.value.map { it.copy(pendienteSinc = false) }.toMutableList()
            _acopios.value = sincronizados
            _sync.value = _sync.value.copy(estado = SyncEstado.EXITOSO, registrosPendientes = 0)
        }
    }

    fun resetSync() {
        _sync.value = SyncState(registrosPendientes = registrosPendientes())
    }
}
