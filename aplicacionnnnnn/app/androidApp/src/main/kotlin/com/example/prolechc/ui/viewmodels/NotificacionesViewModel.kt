package com.example.prolechc.ui.viewmodels

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.repository.NotificacionesRepositoryAdapter
import com.example.prolechc.data.repositories.NotificacionInfo
import com.example.prolechc.data.repositories.RepoResult
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

// ══════════════════════════════════════════════════════════════════════════════
//  NotificacionesViewModel — MVVM para la pantalla de notificaciones
//
//  Consume GET  /api/notificaciones
//          POST /api/notificaciones/{id}/leer
//          POST /api/notificaciones/leer-todas
//
//  El badge de la campana se alimenta de [noLeidasCount].
//  Llama a [cargar()] al entrar a la pantalla y [cargarCount()] desde el
//  TopBar para mantener el badge actualizado sin cargar toda la lista.
// ══════════════════════════════════════════════════════════════════════════════

data class NotificacionesUiState(
    val notificaciones: List<NotificacionInfo> = emptyList(),
    val noLeidasCount: Int    = 0,
    val isLoading: Boolean    = false,
    val error: String?        = null,
    val mensajeExito: String? = null
)

class NotificacionesViewModel(application: Application) : AndroidViewModel(application) {

    private val repo = NotificacionesRepositoryAdapter(application)

    private val _uiState = MutableStateFlow(NotificacionesUiState())
    val uiState: StateFlow<NotificacionesUiState> = _uiState.asStateFlow()

    /** Alias directo para el badge de la campana — evita exponer todo el estado */
    val noLeidasCount: StateFlow<Int> get() = MutableStateFlow(0).also { flow ->
        viewModelScope.launch { _uiState.collect { flow.emit(it.noLeidasCount) } }
    }

    // ── Init: cargar conteo al crear el ViewModel ─────────────────────────────
    init {
        cargarConteo()
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Carga la lista completa + el conteo (al entrar a NotificacionesScreen)
    // ──────────────────────────────────────────────────────────────────────────

    fun cargar() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            when (val result = repo.getNotificaciones()) {
                is RepoResult.Success -> _uiState.update { it.copy(
                    isLoading      = false,
                    notificaciones = result.data,
                    noLeidasCount  = result.data.size  // todas las devueltas son no leídas
                )}
                is RepoResult.Error -> _uiState.update { it.copy(
                    isLoading = false,
                    error     = "Error ${result.code}: ${result.message}"
                )}
                is RepoResult.NetworkError -> _uiState.update { it.copy(
                    isLoading = false,
                    error     = "Sin conexión. Revisa tu red."
                )}
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Solo actualiza el conteo — llamado periódicamente desde el TopBar/Dashboard
    // ──────────────────────────────────────────────────────────────────────────

    fun cargarConteo() {
        viewModelScope.launch {
            when (val result = repo.contarNoLeidas()) {
                is RepoResult.Success -> _uiState.update { it.copy(noLeidasCount = result.data) }
                else                  -> { /* silencioso — no interrumpir la UI */ }
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Marca una notificación como leída y la remueve del estado local
    // ──────────────────────────────────────────────────────────────────────────

    fun marcarLeida(id: Int) {
        viewModelScope.launch {
            when (repo.marcarLeida(id)) {
                is RepoResult.Success -> {
                    _uiState.update { state ->
                        val nuevas = state.notificaciones.filter { it.id != id }
                        state.copy(
                            notificaciones = nuevas,
                            noLeidasCount  = nuevas.size
                        )
                    }
                }
                else -> { /* mantener como está */ }
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Marca todas como leídas
    // ──────────────────────────────────────────────────────────────────────────

    fun marcarTodasLeidas() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            when (repo.marcarTodasLeidas()) {
                is RepoResult.Success -> _uiState.update { it.copy(
                    isLoading      = false,
                    notificaciones = emptyList(),
                    noLeidasCount  = 0,
                    mensajeExito   = "Todas las notificaciones marcadas como leídas."
                )}
                is RepoResult.Error -> _uiState.update { it.copy(
                    isLoading = false,
                    error     = "No se pudo actualizar."
                )}
                is RepoResult.NetworkError -> _uiState.update { it.copy(
                    isLoading = false,
                    error     = "Sin conexión."
                )}
            }
        }
    }

    fun consumirMensaje() {
        _uiState.update { it.copy(mensajeExito = null, error = null) }
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    companion object {
        fun Factory(application: Application): ViewModelProvider.Factory =
            object : ViewModelProvider.AndroidViewModelFactory(application) {
                @Suppress("UNCHECKED_CAST")
                override fun <T : androidx.lifecycle.ViewModel> create(
                    modelClass: Class<T>
                ): T = NotificacionesViewModel(application) as T
            }
    }
}
