package com.example.prolechc.presentation.productos

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.prolechc.domain.usecase.GetHistorialAcopiosUseCase
import com.example.prolechc.domain.usecase.GetMiRutaUseCase
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

class AcopioListViewModel(
    private val getHistorialUseCase: GetHistorialAcopiosUseCase,
    private val getMiRutaUseCase: GetMiRutaUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(AcopioListUiState())
    val uiState: StateFlow<AcopioListUiState> = _uiState.asStateFlow()

    init {
        cargarDatos()
    }

    fun cargarDatos(fecha: String? = null) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }

            // Carga la ruta y el historial en paralelo
            val rutaResult      = getMiRutaUseCase()
            val historialResult = getHistorialUseCase(fecha)

            val ruta    = rutaResult.getOrNull()
            val acopios = historialResult.getOrNull() ?: emptyList()

            val error = when {
                rutaResult.isFailure && historialResult.isFailure ->
                    "Sin conexión. Verifica tu red."
                rutaResult.isFailure ->
                    rutaResult.exceptionOrNull()?.message
                historialResult.isFailure ->
                    historialResult.exceptionOrNull()?.message
                else -> null
            }

            _uiState.update {
                it.copy(
                    isLoading   = false,
                    acopios     = acopios,
                    ruta        = ruta,
                    paradas     = ruta?.paradas ?: emptyList(),
                    totalLitros = acopios.sumOf { a -> a.litros },
                    error       = error
                )
            }
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }
}
