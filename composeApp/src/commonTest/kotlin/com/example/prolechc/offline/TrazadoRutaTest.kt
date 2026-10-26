package com.example.prolechc.offline

import com.example.prolechc.data.mapper.toDomain
import com.example.prolechc.data.remote.dto.MiRutaResponseDto
import com.example.prolechc.domain.model.Coordenada
import com.example.prolechc.domain.model.Parada
import com.example.prolechc.domain.ruta.Geo
import com.example.prolechc.domain.ruta.PlanificadorRuta
import kotlinx.serialization.json.Json
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

/** Ruta trazada por el administrador en el panel web. */
class TrazadoRutaTest {

    private val json = Json { ignoreUnknownKeys = true }

    private fun parada(id: Int, orden: Int, atendido: Boolean = false, suspendido: Boolean = false) = Parada(
        id = id, orden = orden, ganaderoId = id, ganaderoNombre = "G$id", ganaderoNii = "$id",
        direccion = null, geoLat = -15.7, geoLon = -70.0, atendido = atendido, suspendido = suspendido
    )

    @Test
    fun el_mapper_lee_el_trazado_de_mi_ruta() {
        val dto = json.decodeFromString(MiRutaResponseDto.serializer(),
            """{"ruta_nombre":"Collana 1","paradas":[],"trazado":[[-15.61,-69.97],[-15.70,-70.01]]}""")

        assertEquals(listOf(Coordenada(-15.61, -69.97), Coordenada(-15.70, -70.01)), dto.toDomain().trazado)
    }

    @Test
    fun un_backend_sin_trazado_deja_la_lista_vacia() {
        val dto = json.decodeFromString(MiRutaResponseDto.serializer(), """{"ruta_nombre":"Collana 1","paradas":[]}""")

        assertTrue(dto.toDomain().trazado.isEmpty())
    }

    @Test
    fun el_plan_trazado_respeta_el_orden_del_servidor_y_dibuja_el_trazo() {
        val trazo = listOf(Coordenada(-15.6167, -69.9708), Coordenada(-15.818, -70.10), Coordenada(-15.6167, -69.9708))
        val paradas = listOf(parada(3, 3), parada(1, 1, atendido = true), parada(2, 2), parada(4, 4, suspendido = true))

        val plan = PlanificadorRuta().planTrazado(trazo, paradas)

        assertEquals(listOf(2, 3), plan.orden.map { it.ganaderoId })   // sin atendidos ni suspendidos
        assertEquals(trazo, plan.trazo)
        assertEquals(Geo.distanciaTotalKm(trazo), plan.distanciaKm, 1e-9)
        assertTrue(plan.trazadoPorPlanta)
    }
}
