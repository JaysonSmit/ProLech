package com.example.prolechc.offline

import com.example.prolechc.data.local.AcopioLocalSqlDelight
import com.example.prolechc.domain.model.EstadoSync
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.TestDispatcher
import kotlinx.coroutines.test.UnconfinedTestDispatcher
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.prolech.db.AppDatabase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull
import kotlin.test.assertTrue

@OptIn(ExperimentalCoroutinesApi::class)
class AcopioLocalSqlDelightTest {

    private fun crearLocal(dispatcher: TestDispatcher) =
        AcopioLocalSqlDelight(AppDatabase(crearDriverEnMemoria()), dispatcher)

    @Test
    fun guardar_y_observar_emite_ordenado_por_fecha_desc() = runTest {
        val local = crearLocal(UnconfinedTestDispatcher(testScheduler))
        local.guardar(acopioDePrueba(id = "a", actualizadoEn = 1))
        local.guardar(acopioDePrueba(id = "b", actualizadoEn = 2))

        val lista = local.observarTodos().first()

        assertEquals(listOf("b", "a"), lista.map { it.id })
    }

    @Test
    fun upsert_reemplaza_el_registro_con_el_mismo_id() = runTest {
        val local = crearLocal(UnconfinedTestDispatcher(testScheduler))
        local.guardar(acopioDePrueba(litros = 10.0))
        local.guardar(acopioDePrueba(litros = 99.0))

        val todos = local.obtenerTodos()

        assertEquals(1, todos.size)
        assertEquals(99.0, todos.single().litros)
    }

    @Test
    fun eliminar_pendiente_crear_borra_fisicamente() = runTest {
        val local = crearLocal(UnconfinedTestDispatcher(testScheduler))
        local.guardar(acopioDePrueba(estadoSync = EstadoSync.PENDIENTE_CREAR))

        local.eliminar("uuid-1", ahora = 5_000)

        assertNull(local.obtenerPorId("uuid-1"))
        assertTrue(local.obtenerPendientes().isEmpty())
    }

    @Test
    fun eliminar_sincronizado_marca_pendiente_eliminar_y_lo_oculta() = runTest {
        val local = crearLocal(UnconfinedTestDispatcher(testScheduler))
        local.guardar(acopioDePrueba(estadoSync = EstadoSync.SINCRONIZADO))

        local.eliminar("uuid-1", ahora = 5_000)

        val registro = local.obtenerPorId("uuid-1")
        assertEquals(EstadoSync.PENDIENTE_ELIMINAR, registro?.estadoSync)
        assertEquals(5_000L, registro?.actualizadoEn)
        assertTrue(local.observarTodos().first().isEmpty())
        assertEquals(listOf("uuid-1"), local.obtenerPendientes().map { it.id })
    }

    @Test
    fun selectPendientes_excluye_los_sincronizados() = runTest {
        val local = crearLocal(UnconfinedTestDispatcher(testScheduler))
        local.guardar(acopioDePrueba(id = "ok", estadoSync = EstadoSync.SINCRONIZADO))
        local.guardar(acopioDePrueba(id = "nuevo", estadoSync = EstadoSync.PENDIENTE_CREAR))
        local.guardar(acopioDePrueba(id = "editado", estadoSync = EstadoSync.PENDIENTE_ACTUALIZAR))

        val pendientes = local.obtenerPendientes().map { it.id }.toSet()

        assertEquals(setOf("nuevo", "editado"), pendientes)
    }

    @Test
    fun marcarSincronizado_cambia_el_estado() = runTest {
        val local = crearLocal(UnconfinedTestDispatcher(testScheduler))
        local.guardar(acopioDePrueba(estadoSync = EstadoSync.PENDIENTE_CREAR))

        local.marcarSincronizado("uuid-1")

        assertEquals(EstadoSync.SINCRONIZADO, local.obtenerPorId("uuid-1")?.estadoSync)
    }
}
