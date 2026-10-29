package com.example.prolechc.offline

import com.example.prolechc.data.dispositivo.AvisadorSistema
import com.example.prolechc.data.local.ConfigLocal
import com.example.prolechc.data.remote.dto.NotificacionesResponseDto
import com.example.prolechc.domain.model.Notificacion
import com.example.prolechc.domain.repository.NotificacionRepository
import com.example.prolechc.presentation.avisos.AvisosViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.cancel
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.UnconfinedTestDispatcher
import kotlinx.coroutines.test.advanceTimeBy
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runCurrent
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import kotlinx.serialization.json.Json
import pe.edu.upeu.prolech.db.AppDatabase
import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

/** Bandeja de avisos: lectura de la API, contador y notificación del celular una sola vez. */
@OptIn(ExperimentalCoroutinesApi::class)
class AvisosTest {

    @BeforeTest
    fun setUp() = Dispatchers.setMain(UnconfinedTestDispatcher())

    @AfterTest
    fun tearDown() = Dispatchers.resetMain()

    private class RepoFalso(var lista: List<Notificacion>) : NotificacionRepository {
        val leidas = mutableListOf<Int>()
        override suspend fun getNotificaciones() = Result.success(lista)
        override suspend fun contarNoLeidas() = Result.success(lista.count { !it.leida })
        override suspend fun marcarLeida(id: Int): Result<Unit> { leidas += id; return Result.success(Unit) }
        override suspend fun marcarTodasLeidas(): Result<Unit> { leidas += lista.map { it.id }; return Result.success(Unit) }
    }

    private class AvisadorFalso : AvisadorSistema {
        val mostrados = mutableListOf<Int>()
        override fun mostrar(id: Int, titulo: String, mensaje: String) { mostrados += id }
    }

    private fun aviso(id: Int, leida: Boolean = false) =
        Notificacion(id, "📅 Nueva capacitación: Higiene", "Nueva reunión programada", "reunion", "", 1, "2026-10-29T15:26:54.000000Z", leida)

    @Test
    fun la_respuesta_del_servidor_sin_icono_se_lee_bien() {
        val json = """{"notificaciones":[{"id":5,"titulo":"📅 Nueva capacitación: REUNION SIMPLE","mensaje":"Nueva reunión","tipo":"reunion","referencia_id":1,"leida":false,"created_at":"2026-10-29T15:26:54.000000Z"}],"total_no_leidas":1}"""
        val dto = Json { ignoreUnknownKeys = true }.decodeFromString<NotificacionesResponseDto>(json)
        assertEquals(1, dto.totalNoLeidas)
        assertEquals("reunion", dto.notificaciones!!.single().tipo)
    }

    @Test
    fun cuenta_los_no_leidos_y_notifica_cada_aviso_nuevo_una_sola_vez() = runTest {
        val repo = RepoFalso(listOf(aviso(5), aviso(4, leida = true)))
        val avisador = AvisadorFalso()
        val config = ConfigLocal(AppDatabase(crearDriverEnMemoria()))
        val vm = AvisosViewModel(repo, config, avisador, intervaloMs = 1_000)
        runCurrent()

        assertEquals(1, vm.estado.value.noLeidas)
        assertEquals(listOf(5), avisador.mostrados)
        assertEquals("29/10 15:26", vm.estado.value.avisos.first().fechaCorta)

        // Llega otro aviso: solo ese suena; el 5 no se repite
        repo.lista = listOf(aviso(6)) + repo.lista
        advanceTimeBy(1_001)
        assertEquals(listOf(5, 6), avisador.mostrados)
        assertEquals(2, vm.estado.value.noLeidas)

        vm.marcarTodosLeidos()
        runCurrent()
        assertEquals(0, vm.estado.value.noLeidas)
        assertTrue(repo.leidas.containsAll(listOf(5, 6)))
        // El ciclo de consulta no termina solo: sin esto runTest esperaría para siempre
        vm.viewModelScope.cancel()
    }
}
