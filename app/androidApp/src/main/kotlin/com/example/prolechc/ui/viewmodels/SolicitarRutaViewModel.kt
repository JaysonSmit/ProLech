package com.example.prolechc.ui.viewmodels

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.api.ApiResult
import com.example.prolechc.data.api.EstadoSolicitudData
import com.example.prolechc.data.api.RetrofitClient
import com.example.prolechc.data.api.SolicitarRutaRequest
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

// ══════════════════════════════════════════════════════════════════════════════
//  SolicitarRutaViewModel
//
//  Responsabilidades:
//    • Mantener el estado del formulario (ruta seleccionada, motivo, loading).
//    • Enviar POST /api/solicitar-ruta via RetrofitClient.
//    • Exponer el resultado (éxito / error) para que la pantalla muestre un Toast.
//
//  Inyección:
//    RetrofitClient se obtiene vía applicationContext — no se instancia
//    Retrofit directamente en el ViewModel (cumple la regla de Clean Architecture).
// ══════════════════════════════════════════════════════════════════════════════

// ── Modelo de dominio: Ruta disponible ────────────────────────────────────────

data class RutaOpcion(
    val codigo: String,
    val nombre: String
) {
    /** Texto a mostrar en el DropdownMenu. */
    val etiqueta: String get() = "$codigo — $nombre"
}

// ── Estado de la UI ────────────────────────────────────────────────────────────

data class SolicitarRutaUiState(
    /** Ruta actualmente seleccionada en el Dropdown (null = sin selección). */
    val rutaSeleccionada: RutaOpcion? = null,
    /** Texto libre del conductor explicando el motivo del cambio. */
    val motivo: String                = "",
    /** true mientras se espera la respuesta del servidor. */
    val isLoading: Boolean            = false,
    /**
     * Mensaje de éxito para mostrar en un Toast y volver atrás.
     * null = no mostrar; se resetea después de consumirlo.
     */
    val mensajeExito: String?         = null,
    /**
     * Mensaje de error para mostrar en un Toast.
     * null = sin error.
     */
    val mensajeError: String?         = null,
    /** Última solicitud enviada (GET /api/estado-solicitud-ruta); null = ninguna. */
    val ultimaSolicitud: EstadoSolicitudData? = null
) {
    /** true si ya hay una solicitud esperando revisión: no se permite otra. */
    val tienePendiente: Boolean get() = ultimaSolicitud?.estado == "pendiente"
}

// ── ViewModel ─────────────────────────────────────────────────────────────────

class SolicitarRutaViewModel(application: Application) : AndroidViewModel(application) {

    // ── Catálogo de rutas disponibles (en producción: cargar desde /api/rutas) ─
    val rutasDisponibles: List<RutaOpcion> = listOf(
        RutaOpcion("A-1", "Huata Centro – Costa"),
        RutaOpcion("A-2", "Huata Sur – Capachica"),
        RutaOpcion("B-1", "Coata – Caracoto")
    )

    private val _uiState = MutableStateFlow(SolicitarRutaUiState())
    val uiState: StateFlow<SolicitarRutaUiState> = _uiState.asStateFlow()

    init { cargarEstado() }

    // ── Estado de la última solicitud ─────────────────────────────────────────

    /** GET /api/estado-solicitud-ruta — silencioso si falla (la pantalla sigue usable). */
    fun cargarEstado() {
        viewModelScope.launch {
            try {
                val response = RetrofitClient.getInstance(getApplication()).getEstadoSolicitudRuta()
                if (response.isSuccessful) {
                    _uiState.value = _uiState.value.copy(ultimaSolicitud = response.body()?.solicitud)
                }
            } catch (e: Exception) {
                // Sin red: no se muestra la tarjeta de estado
            }
        }
    }

    // ── Actualizaciones del formulario ────────────────────────────────────────

    fun seleccionarRuta(ruta: RutaOpcion) {
        _uiState.value = _uiState.value.copy(rutaSeleccionada = ruta, mensajeError = null)
    }

    fun onMotivoChange(texto: String) {
        _uiState.value = _uiState.value.copy(motivo = texto, mensajeError = null)
    }

    // ── Envío al servidor ─────────────────────────────────────────────────────

    /**
     * Valida el formulario y envía POST /api/solicitar-ruta.
     * El backend espera: { ruta_solicitada: string, motivo: string }
     *
     * Validaciones:
     *   • Debe haber una ruta seleccionada.
     *   • El motivo no puede estar vacío ni tener menos de 10 caracteres.
     */
    fun enviarSolicitud() {
        val state = _uiState.value

        // ── Validación local ─────────────────────────────────────────────────
        if (state.rutaSeleccionada == null) {
            _uiState.value = state.copy(mensajeError = "Selecciona una ruta destino")
            return
        }
        if (state.motivo.trim().length < 10) {
            _uiState.value = state.copy(
                mensajeError = "El motivo debe tener al menos 10 caracteres"
            )
            return
        }

        // ── Llamada a la API ─────────────────────────────────────────────────
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, mensajeError = null)

            try {
                val api = RetrofitClient.getInstance(getApplication())

                // El backend valida: ruta_solicitada (string max:100), motivo (string max:500)
                // Concatenamos código + nombre para que el admin vea el nombre completo
                val rutaSolicitada = "${state.rutaSeleccionada.codigo} — ${state.rutaSeleccionada.nombre}"

                val response = api.solicitarRuta(
                    SolicitarRutaRequest(
                        rutaSolicitada = rutaSolicitada,
                        motivo         = state.motivo.trim()
                    )
                )

                if (response.isSuccessful) {
                    val msg = response.body()?.message
                        ?: "Solicitud enviada. El administrador la revisará pronto."
                    _uiState.value = _uiState.value.copy(
                        isLoading    = false,
                        mensajeExito = msg
                    )
                    cargarEstado()
                } else {
                    val errorMsg = when (response.code()) {
                        401  -> "Sesión expirada. Vuelve a iniciar sesión."
                        422  -> {
                            // Laravel devuelve los errores de validación en el body
                            val errorBody = response.errorBody()?.string() ?: ""
                            when {
                                errorBody.contains("ruta_solicitada") -> "Selecciona una ruta válida."
                                errorBody.contains("motivo")          -> "El motivo es requerido (mínimo 10 caracteres)."
                                errorBody.contains("pendiente")       -> "Ya tienes una solicitud pendiente de revisión."
                                else -> "Datos inválidos. Revisa el formulario."
                            }
                        }
                        else -> "Error del servidor (${response.code()}). Intenta más tarde."
                    }
                    _uiState.value = _uiState.value.copy(
                        isLoading    = false,
                        mensajeError = errorMsg
                    )
                }
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading    = false,
                    mensajeError = "Sin conexión. Verifica tu red e intenta de nuevo."
                )
            }
        }
    }

    // ── Reset de mensajes ─────────────────────────────────────────────────────

    /** Llamado desde la Screen después de mostrar el Toast de éxito. */
    fun consumirMensajeExito() {
        _uiState.value = _uiState.value.copy(mensajeExito = null)
    }

    /** Llamado desde la Screen después de mostrar el Toast de error. */
    fun consumirMensajeError() {
        _uiState.value = _uiState.value.copy(mensajeError = null)
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    companion object {
        fun Factory(application: Application): ViewModelProvider.Factory =
            object : ViewModelProvider.AndroidViewModelFactory(application) {
                @Suppress("UNCHECKED_CAST")
                override fun <T : androidx.lifecycle.ViewModel> create(
                    modelClass: Class<T>
                ): T = SolicitarRutaViewModel(application) as T
            }
    }
}
