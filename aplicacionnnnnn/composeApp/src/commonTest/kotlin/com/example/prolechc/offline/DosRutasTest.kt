package com.example.prolechc.offline

import com.example.prolechc.data.mapper.toDomain
import com.example.prolechc.data.remote.dto.MiRutaResponseDto
import kotlinx.serialization.json.Json
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

/** Chofer con 2 rutas en el mes: paradas de ambas y aviso de capacidad del camión. */
class DosRutasTest {

    private val json = Json { ignoreUnknownKeys = true }

    private fun parada(id: Int, ruta: String) =
        """{"id":$id,"orden":$id,"ganadero_id":$id,"ganadero_nombre":"G$id","ganadero_dni":"$id","ruta":"$ruta"}"""

    @Test
    fun la_respuesta_con_dos_rutas_marca_cada_parada_y_el_aviso() {
        val dto = json.decodeFromString<MiRutaResponseDto>(
            """{"ruta_nombre":"Collana 1 + HUATA1","origen_ruta":"zona","rol_mes":"titular",
               "aviso_capacidad":"Tus 2 rutas suelen dar ~1400 L",
               "paradas":[${parada(1, "Collana 1")},${parada(2, "HUATA1")}]}"""
        )
        val ruta = dto.toDomain()

        assertTrue(ruta.variasRutas)
        assertEquals(listOf("Collana 1", "HUATA1"), ruta.paradas.map { it.ruta })
        assertEquals("Tus 2 rutas suelen dar ~1400 L", ruta.avisoCapacidad)
    }

    @Test
    fun con_una_sola_ruta_no_cambia_nada() {
        val ruta = json.decodeFromString<MiRutaResponseDto>(
            """{"ruta_nombre":"Collana 1","paradas":[${parada(1, "Collana 1")},${parada(2, "Collana 1")}]}"""
        ).toDomain()

        assertFalse(ruta.variasRutas)
        assertNull(ruta.avisoCapacidad)
    }
}
