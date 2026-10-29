package com.example.prolechc.ui.viewmodels

import android.app.Application
import android.content.Context
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.local.TokenDataStore
import com.google.android.gms.maps.model.LatLng
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch

// ══════════════════════════════════════════════════════════════════════════════
//  MapaChoferViewModel
//
//  Responsabilidades:
//    • Mantener el estado de la jornada (activa / inactiva).
//    • Exponer la posición actual del conductor para centrar el mapa.
//    • Proporcionar el ID de la asignación activa (necesario para el Service).
//    • Leer el nombre del conductor desde DataStore para mostrarlo en la UI.
//
//  Extiende AndroidViewModel (no ViewModel) porque necesita Application para
//  acceder a DataStore y a TokenDataStore sin requerir un Context externo.
//
//  Inyección:
//    Se instancia desde MainActivity o NavGraph con la Factory incluida.
//    No instancia Retrofit directamente — el GpsTrackingService lo hace
//    de forma independiente con su propio applicationContext.
// ══════════════════════════════════════════════════════════════════════════════

// ── Estado de la UI ────────────────────────────────────────────────────────────

data class MapaChoferUiState(
    /** true mientras el GpsTrackingService está corriendo. */
    val jornadaActiva: Boolean      = false,
    /** Posición actual del conductor (null hasta la primera lectura). */
    val posicionActual: LatLng?     = null,
    /** Nombre completo del conductor para mostrarlo en el topbar. */
    val nombreConductor: String     = "",
    /** ID de la asignación activa (0 = sin asignación). */
    val asignacionId: Int           = 0,
    /** Mensaje de error a mostrar en un Snackbar (null = sin error). */
    val error: String?              = null
)

// ── ViewModel ─────────────────────────────────────────────────────────────────

class MapaChoferViewModel(application: Application) : AndroidViewModel(application) {

    private val tokenStore = TokenDataStore(application)

    private val _uiState = MutableStateFlow(MapaChoferUiState())
    val uiState: StateFlow<MapaChoferUiState> = _uiState.asStateFlow()

    init {
        cargarDatosConductor()
    }

    // ── Inicialización ────────────────────────────────────────────────────────

    /**
     * Lee el nombre del conductor desde DataStore al arrancar el ViewModel.
     * El userId se usa como asignacionId provisional; en producción este dato
     * debe venir de GET /api/mi-ruta que ya resuelve el ID real.
     */
    private fun cargarDatosConductor() {
        viewModelScope.launch {
            val nombre = tokenStore.userNombreFlow.first()
            val userId = tokenStore.userIdFlow.first() ?: 0

            _uiState.value = _uiState.value.copy(
                nombreConductor = nombre,
                asignacionId    = userId   // Sustituir por asignacionId real cuando integres mi-ruta
            )
        }
    }

    // ── Jornada ───────────────────────────────────────────────────────────────

    /**
     * Marca la jornada como activa.
     * La pantalla escucha este cambio para iniciar el [GpsTrackingService].
     */
    fun iniciarJornada() {
        _uiState.value = _uiState.value.copy(jornadaActiva = true, error = null)
    }

    /**
     * Marca la jornada como inactiva.
     * La pantalla escucha este cambio para detener el [GpsTrackingService].
     */
    fun terminarJornada() {
        _uiState.value = _uiState.value.copy(jornadaActiva = false)
    }

    // ── Posición ──────────────────────────────────────────────────────────────

    /**
     * Actualiza la posición actual del conductor.
     * Llamado desde [MapaChoferScreen] cuando FusedLocation emite una nueva ubicación
     * para re-centrar la cámara del mapa.
     */
    fun actualizarPosicion(lat: Double, lng: Double) {
        _uiState.value = _uiState.value.copy(posicionActual = LatLng(lat, lng))
    }

    // ── Errores ───────────────────────────────────────────────────────────────

    fun mostrarError(mensaje: String) {
        _uiState.value = _uiState.value.copy(error = mensaje)
    }

    fun limpiarError() {
        _uiState.value = _uiState.value.copy(error = null)
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    companion object {
        /**
         * Factory estándar para AndroidViewModel.
         * Úsala en NavGraph o Activity:
         *   val vm: MapaChoferViewModel by viewModels { MapaChoferViewModel.Factory(app) }
         */
        fun Factory(application: Application): ViewModelProvider.Factory =
            object : ViewModelProvider.AndroidViewModelFactory(application) {
                @Suppress("UNCHECKED_CAST")
                override fun <T : androidx.lifecycle.ViewModel> create(
                    modelClass: Class<T>
                ): T = MapaChoferViewModel(application) as T
            }
    }
}
