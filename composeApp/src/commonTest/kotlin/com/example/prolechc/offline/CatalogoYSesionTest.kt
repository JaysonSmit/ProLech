package com.example.prolechc.offline

import com.example.prolechc.data.local.AcopioLocalSqlDelight
import com.example.prolechc.data.local.GanaderoLocalSqlDelight
import com.example.prolechc.data.sync.SyncManager
import com.example.prolechc.domain.model.EstadoSync
import com.example.prolechc.domain.model.GanaderoCampo
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.TestScope
import kotlinx.coroutines.test.UnconfinedTestDispatcher
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.prolech.db.AppDatabase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

@OptIn(ExperimentalCoroutinesApi::class)
class CatalogoYSesionTest {

    private class Escenario(scope: TestScope, enLinea: Boolean = true, sesion: Boolean = true) {
        private val db = AppDatabase(crearDriverEnMemoria())
        private val dispatcher = UnconfinedTestDispatcher(scope.testScheduler)
        val local = AcopioLocalSqlDelight(db, dispatcher)
        val ganaderoLocal = GanaderoLocalSqlDelight(db, dispatcher)
        val remoto = AcopioRemotoFalso()
        val ganaderoRemoto = GanaderoRemotoFalso()
        val monitor = MonitorConectividadFalso(enLinea)
        val auth = AuthRepositoryFalso(sesion)
        val sync = SyncManager(
            local          = local,
            remoto         = remoto,
            monitor        = monitor,
            scope          = scope.backgroundScope,
            ganaderoLocal  = ganaderoLocal,
            ganaderoRemoto = ganaderoRemoto,
            sesionActiva   = { auth.isLoggedIn() }
        )
    }

    private val juan = GanaderoCampo(7, "Juan Quispe", "40201653")
    private val martina = GanaderoCampo(12, "Martina Choque", "29301823", suspendido = true)

    @Test
    fun sincronizar_descarga_el_catalogo_de_ganaderos_para_usarlo_offline() = runTest {
        val e = Escenario(this)
        e.ganaderoRemoto.ganaderos = listOf(martina, juan)

        e.sync.sincronizar()

        val catalogo = e.ganaderoLocal.observarTodos().first()
        assertEquals(listOf("Juan Quispe", "Martina Choque"), catalogo.map { it.nombre })
        assertTrue(catalogo.single { it.id == 12L }.suspendido)
    }

    @Test
    fun si_mi_ruta_viene_vacia_o_falla_se_conserva_el_catalogo_anterior() = runTest {
        val e = Escenario(this)
        e.ganaderoRemoto.ganaderos = listOf(juan)
        e.sync.sincronizar()

        e.ganaderoRemoto.ganaderos = emptyList()
        e.sync.sincronizar()
        e.ganaderoRemoto.fallar = true
        val resultado = e.sync.sincronizar()

        assertEquals(listOf(juan), e.ganaderoLocal.observarTodos().first())
        assertEquals(0, resultado.fallidos)
    }

    @Test
    fun sin_sesion_no_se_llama_a_la_api_y_los_datos_quedan_pendientes() = runTest {
        val e = Escenario(this, sesion = false)
        e.local.guardar(acopioDePrueba(estadoSync = EstadoSync.PENDIENTE_CREAR))

        val resultado = e.sync.sincronizar()

        assertTrue(resultado.sinSesion)
        assertEquals(0, e.remoto.totalLlamadas)
        assertEquals(EstadoSync.PENDIENTE_CREAR, e.local.obtenerPorId("uuid-1")?.estadoSync)
    }

    @Test
    fun tras_iniciar_sesion_los_pendientes_offline_se_suben() = runTest {
        val e = Escenario(this, sesion = false)
        e.local.guardar(acopioDePrueba(estadoSync = EstadoSync.PENDIENTE_CREAR))
        e.sync.sincronizar()

        e.auth.login("chofer@prolech.pe", "secreto")
        val resultado = e.sync.sincronizar()

        assertEquals(1, resultado.subidos)
        assertEquals(listOf("uuid-1"), e.remoto.creados)
    }
}
