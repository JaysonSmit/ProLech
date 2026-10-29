package com.example.prolechc.ui.viewmodels

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.api.ReunionData
import com.example.prolechc.data.api.RetrofitClient
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

// ══════════════════════════════════════════════════════════════════════════════
//  ReunionesViewModel
//
//  Consume GET /api/reuniones: reuniones a las que fue invitado el usuario
//  (desde ayer en adelante, ordenadas por fecha).
// ══════════════════════════════════════════════════════════════════════════════

data class ReunionesUiState(
    val reuniones: List<ReunionData> = emptyList(),
    val isLoading: Boolean           = false,
    val error: String?               = null
)

class ReunionesViewModel(application: Application) : AndroidViewModel(application) {

    private val _uiState = MutableStateFlow(ReunionesUiState())
    val uiState: StateFlow<ReunionesUiState> = _uiState.asStateFlow()

    fun cargar() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null)
            try {
                val response = RetrofitClient.getInstance(getApplication()).getReuniones()
                _uiState.value = if (response.isSuccessful) {
                    _uiState.value.copy(
                        isLoading = false,
                        reuniones = response.body()?.reuniones.orEmpty()
                    )
                } else {
                    _uiState.value.copy(
                        isLoading = false,
                        error     = if (response.code() == 401) "Sesión expirada. Vuelve a iniciar sesión."
                                    else "Error del servidor (${response.code()})"
                    )
                }
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error     = "Sin conexión. Verifica tu red e intenta de nuevo."
                )
            }
        }
    }

    companion object {
        fun Factory(application: Application): ViewModelProvider.Factory =
            object : ViewModelProvider.AndroidViewModelFactory(application) {
                @Suppress("UNCHECKED_CAST")
                override fun <T : androidx.lifecycle.ViewModel> create(
                    modelClass: Class<T>
                ): T = ReunionesViewModel(application) as T
            }
    }
}
