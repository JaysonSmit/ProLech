package com.example.prolechc.offline

import com.example.prolechc.presentation.NivelCarga
import com.example.prolechc.presentation.nivelCarga
import kotlin.test.Test
import kotlin.test.assertEquals

class NivelCargaTest {
    @Test
    fun amarillo_desde_85_y_rojo_al_100_por_ciento() {
        assertEquals(NivelCarga.NORMAL, nivelCarga(850.0 / 1500))   // 57 %
        assertEquals(NivelCarga.NORMAL, nivelCarga(0.849))
        assertEquals(NivelCarga.ALERTA, nivelCarga(0.85))
        assertEquals(NivelCarga.ALERTA, nivelCarga(0.99))
        assertEquals(NivelCarga.LLENO, nivelCarga(1.0))
        assertEquals(NivelCarga.LLENO, nivelCarga(1.2))
    }
}
