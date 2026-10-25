package com.example.prolechc.presentation.productos

import com.example.prolechc.domain.model.AcopioHistorial
import com.example.prolechc.domain.model.Parada
import com.example.prolechc.domain.model.Ruta

/** Estado de la pantalla de listado/historial de acopios. */
data class AcopioListUiState(
    val isLoading: Boolean              = false,
    val acopios: List<AcopioHistorial>  = emptyList(),
    val ruta: Ruta?                     = null,
    val paradas: List<Parada>           = emptyList(),
    val totalLitros: Double             = 0.0,
    val error: String?                  = null
)
