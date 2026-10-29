package com.example.prolechc.presentation.avisos

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.dispositivo.AvisadorSistema
import com.example.prolechc.data.local.ConfigLocal
import com.example.prolechc.domain.model.Notificacion
import com.example.prolechc.domain.repository.NotificacionRepository
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class AvisosUiState(
    val avisos: List<Notificacion> = emptyList(),
    val cargando: Boolean = false,
    val error: String? = null
) {
    val noLeidas: Int get() = avisos.count { !it.leida }
}

/**
 * Bandeja de avisos del administrador (reuniones, comunicados, calidad…).
 * Consulta el servidor cada [intervaloMs] mientras la app está abierta o en segundo plano, y
 * cada aviso nuevo sin leer sale una sola vez como notificación del celular.
 */
class AvisosViewModel(
    private val repositorio: NotificacionRepository,
    private val config: ConfigLocal,
    private val avisador: AvisadorSistema? = null,
    private val intervaloMs: Long = 60_000
) : ViewModel() {

    private val _estado = MutableStateFlow(AvisosUiState())
    val estado: StateFlow<AvisosUiState> = _estado.asStateFlow()

    init {
        viewModelScope.launch {
            while (true) {
                refrescar()
                delay(intervaloMs)
            }
        }
    }

    fun refrescar() {
        viewModelScope.launch {
            _estado.update { it.copy(cargando = true) }
            repositorio.getNotificaciones()
                .onSuccess { lista ->
                    _estado.update { AvisosUiState(avisos = lista) }
                    notificarNuevos(lista)
                }
                // Sin red o sesión vencida: se conserva lo último y se reintenta en el próximo ciclo
                .onFailure { e -> _estado.update { it.copy(cargando = false, error = "No se pudieron cargar los avisos: ${e.message}") } }
        }
    }

    private fun notificarNuevos(lista: List<Notificacion>) {
        val ultimo = config.ultimoAvisoNotificado()
        val nuevos = lista.filter { !it.leida && it.id > ultimo }.sortedBy { it.id }
        if (nuevos.isEmpty()) return
        nuevos.forEach { avisador?.mostrar(it.id, it.titulo, it.mensaje) }
        config.guardarUltimoAvisoNotificado(nuevos.maxOf { it.id })
    }

    fun marcarLeido(id: Int) {
        _estado.update { s -> s.copy(avisos = s.avisos.map { if (it.id == id) it.copy(leida = true) else it }) }
        viewModelScope.launch { repositorio.marcarLeida(id) }
    }

    fun marcarTodosLeidos() {
        _estado.update { s -> s.copy(avisos = s.avisos.map { it.copy(leida = true) }) }
        viewModelScope.launch { repositorio.marcarTodasLeidas() }
    }
}
