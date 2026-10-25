package com.example.prolechc.presentation

import androidx.compose.ui.graphics.Color
import com.example.prolechc.presentation.theme.ProLechColores

/** Qué tan lleno va el camión: amarillo desde el 85 % y rojo al llegar al 100 %. */
enum class NivelCarga { NORMAL, ALERTA, LLENO }

fun nivelCarga(ocupacion: Double): NivelCarga = when {
    ocupacion >= 1.0  -> NivelCarga.LLENO
    ocupacion >= 0.85 -> NivelCarga.ALERTA
    else              -> NivelCarga.NORMAL
}

fun colorCarga(ocupacion: Double): Color = when (nivelCarga(ocupacion)) {
    NivelCarga.LLENO  -> ProLechColores.Error
    NivelCarga.ALERTA -> Color(0xFFFACC15)
    NivelCarga.NORMAL -> ProLechColores.Celeste
}
