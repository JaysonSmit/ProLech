package com.example.prolechc.presentation.login

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.local.ConfigLocal
import com.example.prolechc.data.network.MonitorConectividad
import com.example.prolechc.data.remote.datasource.ErrorHttp
import com.example.prolechc.data.sync.SyncManager
import com.example.prolechc.domain.usecase.LoginUseCase
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class LoginUiState(
    val email: String     = "",
    val password: String  = "",
    val servidor: String  = "",
    val cargando: Boolean = false,
    val error: String?    = null,
    val exitoso: Boolean  = false
)

class LoginViewModel(
    private val loginUseCase: LoginUseCase,
    private val monitor: MonitorConectividad,
    private val syncManager: SyncManager,
    private val config: ConfigLocal
) : ViewModel() {

    private val _uiState = MutableStateFlow(LoginUiState(servidor = config.servidor()))
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()

    fun onEmail(valor: String) = _uiState.update { it.copy(email = valor, error = null) }
    fun onPassword(valor: String) = _uiState.update { it.copy(password = valor, error = null) }
    fun onServidor(valor: String) = _uiState.update { it.copy(servidor = valor, error = null) }

    fun ingresar() {
        val estado = _uiState.value
        if (estado.email.isBlank() || estado.password.isBlank()) {
            _uiState.update { it.copy(error = "Ingresa tu correo y contraseña") }
            return
        }
        if (estado.servidor.isBlank()) {
            _uiState.update { it.copy(error = "Escribe la dirección del servidor (ej. 192.168.0.105:8000)") }
            return
        }
        // El login es lo único que exige red: después la app trabaja offline
        if (!monitor.hayConexion()) {
            _uiState.update { it.copy(error = "Necesitas conexión para iniciar sesión la primera vez") }
            return
        }
        config.guardarServidor(estado.servidor)
        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true, error = null, servidor = config.servidor()) }
            loginUseCase(estado.email.trim(), estado.password)
                .onSuccess {
                    _uiState.update { it.copy(cargando = false, exitoso = true) }
                    // Primera descarga: acopios del servidor + catálogo de ganaderos + ruta
                    syncManager.solicitarSincronizacion()
                }
                .onFailure { e ->
                    val mensaje = when (e) {
                        is ErrorHttp -> e.detalle
                        // Sin respuesta: casi siempre es la IP del servidor o que Laravel no escucha en la red
                        else -> "No se pudo conectar con ${config.servidor()}. Revisa la dirección del servidor " +
                            "y que Laravel esté iniciado con --host=0.0.0.0."
                    }
                    _uiState.update { it.copy(cargando = false, error = mensaje) }
                }
        }
    }
}
