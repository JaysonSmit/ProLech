package com.example.prolechc.offline

import com.example.prolechc.data.local.AcopioLocalSqlDelight
import com.example.prolechc.data.repository.AcopioOfflineRepositoryImpl
import com.example.prolechc.data.sync.SyncManager
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.domain.model.EstadoSync
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.TestScope
import kotlinx.coroutines.test.UnconfinedTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.runCurrent
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.prolech.db.AppDatabase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

@OptIn(ExperimentalCoroutinesApi::class)
class SyncManagerTest {

    /** Arma todo el stack offline-first sobre SQLite en memoria. */
    private class Escenario(scope: TestScope, enLinea: Boolean) {
        val local = AcopioLocalSqlDelight(
            AppDatabase(crearDriverEnMemoria()),
            UnconfinedTestDispatcher(scope.testScheduler)
        )
        val remoto = AcopioRemotoFalso()
        val monitor = MonitorConectividadFalso(enLinea)
        val sync = SyncManager(local, remoto, monitor, scope.backgroundScope)
        var reloj = 1_000L
        private var secuencia = 0
        val repo = AcopioOfflineRepositoryImpl(
            local       = local,
            syncManager = sync,
            reloj       = { reloj },
            generarId   = { "uuid-${++secuencia}" }
        )
    }

    // ── Modo Avión ────────────────────────────────────────────────────────────

    @Test
    fun en_modo_avion_registrar_guarda_local_como_pendiente_crear_sin_llamar_api() = runTest {
        val e = Escenario(this, enLinea = false)

        val acopio = e.repo.registrar(7, "Juan Quispe", 40.0, 0.0, 1.030)
        advanceUntilIdle()

        assertEquals(EstadoSync.PENDIENTE_CREAR, e.local.obtenerPorId(acopio.id)?.estadoSync)
        assertEquals(0, e.remoto.totalLlamadas)
        assertEquals(listOf(acopio.id), e.repo.observarAcopios().first().map { it.id })
    }

    @Test
    fun sincronizar_sin_conexion_devuelve_sinConexion_y_no_toca_el_servidor() = runTest {
        val e = Escenario(this, enLinea = false)
        e.repo.registrar(7, "Juan Quispe", 40.0, 0.0, 1.030)

        val resultado = e.sync.sincronizar()

        assertTrue(resultado.sinConexion)
        assertEquals(0, e.remoto.totalLlamadas)
    }

    @Test
    fun al_recuperar_la_red_sincroniza_automaticamente() = runTest {
        val e = Escenario(this, enLinea = false)
        e.sync.iniciar()
        runCurrent()
        val acopio = e.repo.registrar(7, "Juan Quispe", 40.0, 0.0, 1.030)

        e.monitor.enLinea.value = true   // se desactiva el Modo Avión
        runCurrent() // advanceUntilIdle() no ejecuta tareas de backgroundScope

        assertEquals(listOf(acopio.id), e.remoto.creados)
        assertEquals(EstadoSync.SINCRONIZADO, e.local.obtenerPorId(acopio.id)?.estadoSync)
        e.sync.detener()
    }

    // ── PUSH ──────────────────────────────────────────────────────────────────

    @Test
    fun push_sube_creaciones_actualizaciones_y_eliminaciones() = runTest {
        val e = Escenario(this, enLinea = false)
        e.local.guardar(acopioDePrueba(id = "nuevo", estadoSync = EstadoSync.PENDIENTE_CREAR))
        e.local.guardar(acopioDePrueba(id = "editado", estadoSync = EstadoSync.PENDIENTE_ACTUALIZAR))
        e.local.guardar(acopioDePrueba(id = "borrado", estadoSync = EstadoSync.PENDIENTE_ELIMINAR))
        e.remoto.servidor["editado"] = acopioDePrueba(id = "editado", actualizadoEn = 1)
        e.remoto.servidor["borrado"] = acopioDePrueba(id = "borrado", actualizadoEn = 1)
        e.monitor.enLinea.value = true

        val resultado = e.sync.sincronizar()

        assertEquals(3, resultado.subidos)
        assertEquals(listOf("nuevo"), e.remoto.creados)
        assertEquals(listOf("editado"), e.remoto.actualizados)
        assertEquals(listOf("borrado"), e.remoto.eliminados)
        assertNull(e.local.obtenerPorId("borrado"))
        assertTrue(e.local.obtenerPendientes().isEmpty())
    }

    @Test
    fun si_el_servidor_falla_los_cambios_quedan_pendientes() = runTest {
        val e = Escenario(this, enLinea = true)
        e.remoto.fallar = true
        e.local.guardar(acopioDePrueba(estadoSync = EstadoSync.PENDIENTE_CREAR))

        val resultado = e.sync.sincronizar()

        assertTrue(resultado.fallidos > 0)
        assertEquals(EstadoSync.PENDIENTE_CREAR, e.local.obtenerPorId("uuid-1")?.estadoSync)
    }

    @Test
    fun eliminar_un_pendiente_crear_nunca_llama_al_servidor() = runTest {
        val e = Escenario(this, enLinea = false)
        val acopio = e.repo.registrar(7, "Juan Quispe", 40.0, 0.0, 1.030)
        e.repo.eliminar(acopio.id)
        e.monitor.enLinea.value = true

        e.sync.sincronizar()

        assertEquals(0, e.remoto.totalLlamadas)
        assertNull(e.local.obtenerPorId(acopio.id))
    }

    // ── PULL + Last-Write-Wins ───────────────────────────────────────────────

    @Test
    fun pull_inserta_los_registros_nuevos_del_servidor() = runTest {
        val e = Escenario(this, enLinea = true)
        e.remoto.servidor["remoto"] = acopioDePrueba(id = "remoto")

        val resultado = e.sync.sincronizar()

        assertEquals(1, resultado.bajados)
        assertEquals(EstadoSync.SINCRONIZADO, e.local.obtenerPorId("remoto")?.estadoSync)
    }

    @Test
    fun lww_gana_el_servidor_si_su_cambio_es_mas_reciente() = runTest {
        val e = Escenario(this, enLinea = true)
        // Edición offline (t=100) vs. edición más nueva hecha en la web (t=200)
        e.local.guardar(acopioDePrueba(litros = 10.0, actualizadoEn = 100, estadoSync = EstadoSync.PENDIENTE_ACTUALIZAR))
        e.remoto.servidor["uuid-1"] = acopioDePrueba(litros = 80.0, actualizadoEn = 200)

        e.sync.sincronizar()

        val final = e.local.obtenerPorId("uuid-1")
        assertEquals(80.0, final?.litros)
        assertEquals(EstadoSync.SINCRONIZADO, final?.estadoSync)
        assertTrue(SyncManager.debeAplicarServidor(
            acopioDePrueba(actualizadoEn = 100, estadoSync = EstadoSync.PENDIENTE_ACTUALIZAR),
            acopioDePrueba(actualizadoEn = 200)
        ))
    }

    @Test
    fun lww_conserva_el_cambio_local_si_es_mas_reciente() {
        val local = acopioDePrueba(litros = 10.0, actualizadoEn = 300, estadoSync = EstadoSync.PENDIENTE_ACTUALIZAR)
        val servidor = acopioDePrueba(litros = 80.0, actualizadoEn = 200)

        assertFalse(SyncManager.debeAplicarServidor(local, servidor))
    }

    @Test
    fun pull_borra_localmente_lo_que_se_elimino_en_el_servidor() = runTest {
        val e = Escenario(this, enLinea = true)
        e.local.guardar(acopioDePrueba(id = "huerfano", estadoSync = EstadoSync.SINCRONIZADO))

        e.sync.sincronizar()

        assertNull(e.local.obtenerPorId("huerfano"))
    }

    @Test
    fun editar_un_registro_sincronizado_lo_marca_pendiente_actualizar() = runTest {
        val e = Escenario(this, enLinea = false)
        e.local.guardar(acopioDePrueba(estadoSync = EstadoSync.SINCRONIZADO))
        e.reloj = 9_999

        val editado = e.repo.actualizar(acopioDePrueba(litros = 75.0, actualizadoEn = 0))

        assertEquals(EstadoSync.PENDIENTE_ACTUALIZAR, editado.estadoSync)
        assertEquals(9_999L, e.local.obtenerPorId("uuid-1")?.actualizadoEn)
    }

    // ── Regla de calidad ─────────────────────────────────────────────────────

    @Test
    fun evaluar_calidad_segun_agua_y_densidad() {
        assertEquals(EstadoCalidad.APROBADO, EstadoCalidad.evaluar(0.0, 1.030))
        assertEquals(EstadoCalidad.OBSERVADO, EstadoCalidad.evaluar(2.0, 1.030))
        assertEquals(EstadoCalidad.OBSERVADO, EstadoCalidad.evaluar(0.0, 1.020))
        assertEquals(EstadoCalidad.RECHAZADO, EstadoCalidad.evaluar(5.0, 1.030))
    }
}
