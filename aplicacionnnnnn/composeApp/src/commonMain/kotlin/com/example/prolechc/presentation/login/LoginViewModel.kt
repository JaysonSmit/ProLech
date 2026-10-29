package com.example.prolechc.presentation.login

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.local.ConfigLocal
import com.example.prolechc.data.local.EstadoSesion
import com.example.prolechc.data.local.LimpiezaSesion
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
    /** Aviso informativo (p. ej. sesión vencida), no es un error del usuario. */
    val aviso: String?    = null,
    val exitoso: Boolean  = false
)

class LoginViewModel(
    private val loginUseCase: LoginUseCase,
    private val monitor: MonitorConectividad,
    private val syncManager: SyncManager,
    private val config: ConfigLocal,
    private val estadoSesion: EstadoSesion,
    private val limpieza: LimpiezaSesion
) : ViewModel() {

    private val _uiState = MutableStateFlow(
        LoginUiState(
            servidor = config.servidor(),
            email    = config.ultimoUsuario().orEmpty(),
            aviso    = if (estadoSesion.vencida.value) {
                "Tu sesión venció. Vuelve a ingresar: tus registros sin subir están a salvo en el celular."
            } else null
        )
    )
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()

    fun onEmail(valor: String) = _uiState.update { it.copy(email = valor, error = null) }
    fun onPassword(valor: String) = _uiState.update { it.copy(password = valor, error = null) }
    fun onServidor(valor: String) = _uiState.update { it.copy(servidor = valor, error = null) }

    fun ingresar() {
        val estado = _uiState.value
        val email = estado.email.trim().lowercase()
        if (email.isBlank() || estado.password.isBlank()) {
            _uiState.update { it.copy(error = "Ingresa tu correo y contraseña") }
            return
        }
        if (estado.servidor.isBlank()) {
            _uiState.update { it.copy(error = "Escribe la dirección del servidor (ej. 192.168.0.105:8000)") }
            return
        }
        // El login es lo único que exige red: después la app trabaja offline
        if (!monitor.hayConexion()) {
            _uiState.update { it.copy(error = "Necesitas conexión para iniciar sesión") }
            return
        }
        config.guardarServidor(estado.servidor)
        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true, error = null, servidor = config.servidor()) }

            // Datos offline de OTRO chofer: no se pueden subir a nombre de quien entra ahora
            val anterior = config.ultimoUsuario()
            val cambiaDeChofer = anterior != null && anterior != email
            if (cambiaDeChofer && limpieza.hayDatosSinSubir()) {
                _uiState.update {
                    it.copy(
                        cargando = false,
                        error = "En este celular hay registros sin subir de $anterior. " +
                            "Ingresa primero con esa cuenta para sincronizarlos."
                    )
                }
                return@launch
            }

            loginUseCase(estado.email.trim(), estado.password)
                .onSuccess { usuario ->
                    if (cambiaDeChofer) limpieza.borrarDatosLocales()
                    config.guardarUltimoUsuario(email)
                    config.guardarNombreChofer(usuario.nombreCompleto)
                    estadoSesion.restablecer()
                    _uiState.update { it.copy(cargando = false, exitoso = true) }
                    // Sube lo pendiente y descarga acopios, catálogo y ruta
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
