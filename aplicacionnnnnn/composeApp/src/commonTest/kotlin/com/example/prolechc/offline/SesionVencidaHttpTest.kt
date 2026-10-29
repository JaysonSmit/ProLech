package com.example.prolechc.offline

import com.example.prolechc.data.remote.ApiConfig
import com.example.prolechc.data.remote.HttpClientFactory
import io.ktor.client.engine.mock.MockEngine
import io.ktor.client.engine.mock.respond
import io.ktor.client.request.get
import io.ktor.client.request.post
import io.ktor.http.HttpStatusCode
import kotlinx.coroutines.test.runTest
import kotlin.test.Test
import kotlin.test.assertEquals

/** Un 401 en cualquier pantalla (no solo al sincronizar) lleva al login. */
class SesionVencidaHttpTest {

    private fun cliente(estado: HttpStatusCode, token: String?, avisos: MutableList<String>) =
        HttpClientFactory.create(
            tokenProvider   = { token },
            alSesionVencida = { avisos += "vencida" },
            motor           = MockEngine { respond("""{"message":"Unauthenticated."}""", estado) }
        )

    @Test
    fun un_401_en_mi_ruta_marca_la_sesion_vencida() = runTest {
        val avisos = mutableListOf<String>()
        cliente(HttpStatusCode.Unauthorized, "token-viejo", avisos).get("http://10.0.2.2:8001${ApiConfig.MI_RUTA}")
        assertEquals(listOf("vencida"), avisos)
    }

    @Test
    fun otros_errores_o_el_login_no_cierran_la_sesion() = runTest {
        val avisos = mutableListOf<String>()
        cliente(HttpStatusCode.UnprocessableEntity, "token", avisos).get("http://x${ApiConfig.MI_RUTA}")
        cliente(HttpStatusCode.Unauthorized, null, avisos).post("http://x${ApiConfig.LOGIN}")     // aún sin sesión
        cliente(HttpStatusCode.Unauthorized, "token", avisos).post("http://x${ApiConfig.LOGIN}")
        assertEquals(emptyList(), avisos)
    }
}
