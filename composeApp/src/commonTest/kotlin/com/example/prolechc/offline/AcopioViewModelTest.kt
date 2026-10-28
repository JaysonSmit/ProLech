package com.example.prolechc.offline

import com.example.prolechc.data.local.AcopioLocalSqlDelight
import com.example.prolechc.data.local.GanaderoLocalSqlDelight
import com.example.prolechc.data.repository.AcopioOfflineRepositoryImpl
import com.example.prolechc.data.sync.SyncManager
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.domain.model.EstadoSync
import com.example.prolechc.domain.model.GanaderoCampo
import com.example.prolechc.domain.model.Observacion
import com.example.prolechc.domain.usecase.ActualizarAcopioUseCase
import com.example.prolechc.domain.usecase.EliminarAcopioUseCase
import com.example.prolechc.domain.usecase.LogoutUseCase
import com.example.prolechc.domain.usecase.ObservarAcopiosUseCase
import com.example.prolechc.domain.usecase.ObservarGanaderosUseCase
import com.example.prolechc.domain.usecase.RegistrarAcopioUseCase
import com.example.prolechc.domain.usecase.SincronizarAcopiosUseCase
import com.example.prolechc.presentation.acopios.AcopioViewModel
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.launch
import kotlinx.coroutines.test.TestScope
import kotlinx.coroutines.test.UnconfinedTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import pe.edu.upeu.prolech.db.AppDatabase
import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue

@OptIn(ExperimentalCoroutinesApi::class)
class AcopioViewModelTest {

    @BeforeTest
    fun setUp() = Dispatchers.setMain(UnconfinedTestDispatcher())

    @AfterTest
    fun tearDown() = Dispatchers.resetMain()

    private class Escenario(scope: TestScope, enLinea: Boolean = false) {
        private val db = AppDatabase(crearDriverEnMemoria())
        private val dispatcher = UnconfinedTestDispatcher(scope.testScheduler)
        val local = AcopioLocalSqlDelight(db, dispatcher)
        val ganaderoLocal = GanaderoLocalSqlDelight(db, dispatcher)
        val remoto = AcopioRemotoFalso()
        val monitor = MonitorConectividadFalso(enLinea)
        val auth = AuthRepositoryFalso()
        val sync = SyncManager(local, remoto, monitor, scope.backgroundScope)
        private var secuencia = 0
        val repo = AcopioOfflineRepositoryImpl(
            local         = local,
            syncManager   = sync,
            ganaderoLocal = ganaderoLocal,
            reloj         = { 1_000L },
            generarId     = { "uuid-${++secuencia}" }
        )
        val vm = AcopioViewModel(
            observarAcopios    = ObservarAcopiosUseCase(repo),
            observarGanaderos  = ObservarGanaderosUseCase(repo),
            registrarAcopio    = RegistrarAcopioUseCase(repo),
            actualizarAcopio   = ActualizarAcopioUseCase(repo),
            eliminarAcopio     = EliminarAcopioUseCase(repo),
            sincronizarAcopios = SincronizarAcopiosUseCase(repo),
            logout             = LogoutUseCase(auth),
            monitor            = monitor,
            syncManager        = sync,
            reloj              = { 1_000L }
        )

        init {
            // Mantiene vivo el StateFlow (SharingStarted.WhileSubscribed) como lo haría la UI
            scope.backgroundScope.launch(dispatcher) { vm.uiState.collect {} }
        }
    }

    @Test
    fun registrar_en_modo_avion_aparece_en_la_lista_como_pendiente() = runTest {
        val e = Escenario(this)
        e.vm.onGanaderoId("7")
        e.vm.onGanaderoNombre("Juan Quispe")
        e.vm.onLitros("45,5")   // coma decimal del teclado en español

        e.vm.guardar()
        advanceUntilIdle()

        val estado = e.vm.uiState.value
        assertEquals(1, estado.acopios.size)
        assertEquals(45.5, estado.acopios.single().litros)
        assertEquals(1, estado.pendientes)
        assertFalse(estado.enLinea)
        assertEquals(0, e.remoto.totalLlamadas)
    }

    @Test
    fun no_permite_dos_entregas_del_mismo_ganadero_el_mismo_dia() = runTest {
        val e = Escenario(this)
        e.vm.onGanaderoId("7"); e.vm.onGanaderoNombre("Juan Quispe"); e.vm.onLitros("40")
        e.vm.guardar()
        advanceUntilIdle()

        e.vm.onLitros("30")
        e.vm.guardar()
        advanceUntilIdle()

        assertEquals(1, e.vm.uiState.value.acopios.size)
        assertTrue(e.vm.uiState.value.mensaje.orEmpty().contains("ya entregó hoy"))
    }

    @Test
    fun campos_invalidos_muestran_mensaje_y_no_guardan() = runTest {
        val e = Escenario(this)
        e.vm.onLitros("abc")

        e.vm.guardar()
        advanceUntilIdle()

        assertNotNull(e.vm.uiState.value.mensaje)
        assertTrue(e.vm.uiState.value.acopios.isEmpty())
    }

    @Test
    fun no_permite_seleccionar_un_ganadero_suspendido() = runTest {
        val e = Escenario(this)
        val suspendido = GanaderoCampo(12, "Martina Choque", "29301823", suspendido = true)

        e.vm.onGanaderoSeleccionado(suspendido)
        advanceUntilIdle()

        assertEquals("", e.vm.uiState.value.formulario.ganaderoId)
        assertNotNull(e.vm.uiState.value.mensaje)
    }

    @Test
    fun no_permite_acopiar_a_un_ganadero_bloqueado_por_calidad() = runTest {
        val e = Escenario(this)
        e.vm.onGanaderoSeleccionado(GanaderoCampo(12, "Martina Choque", "29301823", bloqueado = 2))
        advanceUntilIdle()

        assertEquals("", e.vm.uiState.value.formulario.ganaderoId)
        assertTrue(e.vm.uiState.value.mensaje!!.contains("2 entrega"))
    }

    @Test
    fun registra_solo_litros_con_observacion_y_frasco_de_muestra() = runTest {
        val e = Escenario(this)
        e.vm.onGanaderoSeleccionado(GanaderoCampo(7, "Juan Quispe", "1", muestra = "M-0001"))
        e.vm.onLitros("40")
        e.vm.onObservacion(Observacion.OLOR)
        e.vm.onObservacionNota("huele agrio")
        e.vm.guardar()
        advanceUntilIdle()

        val acopio = e.vm.uiState.value.acopios.single()
        assertEquals(EstadoCalidad.APROBADO, acopio.estadoCalidad)
        assertEquals("olor", acopio.observacion)
        assertEquals("huele agrio", acopio.observacionNota)
        assertEquals("M-0001", acopio.muestraCodigo)
    }

    @Test
    fun editar_un_acopio_actualiza_el_registro_existente() = runTest {
        val e = Escenario(this)
        e.local.guardar(acopioDePrueba(litros = 10.0, estadoSync = EstadoSync.SINCRONIZADO))
        advanceUntilIdle()

        e.vm.editar(e.vm.uiState.value.acopios.single())
        e.vm.onLitros("60")
        e.vm.guardar()
        advanceUntilIdle()

        val acopio = e.vm.uiState.value.acopios.single()
        assertEquals(60.0, acopio.litros)
        assertEquals(EstadoSync.PENDIENTE_ACTUALIZAR, acopio.estadoSync)
        assertNull(e.vm.uiState.value.formulario.editando)
    }

    @Test
    fun no_cierra_sesion_si_hay_cambios_sin_sincronizar() = runTest {
        val e = Escenario(this)
        e.local.guardar(acopioDePrueba(estadoSync = EstadoSync.PENDIENTE_CREAR))
        advanceUntilIdle()
        var cerrada = false

        e.vm.cerrarSesion { cerrada = true }
        advanceUntilIdle()

        assertFalse(cerrada)
        assertEquals(0, e.auth.logouts)
    }

    @Test
    fun cierra_sesion_cuando_todo_esta_sincronizado() = runTest {
        val e = Escenario(this)
        var cerrada = false

        e.vm.cerrarSesion { cerrada = true }
        advanceUntilIdle()

        assertTrue(cerrada)
        assertEquals(1, e.auth.logouts)
    }
}
