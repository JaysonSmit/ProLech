package com.example.prolechc.offline

import com.example.prolechc.data.location.ProveedorUbicacion
import com.example.prolechc.data.remote.datasource.RutaVialOsrm
import com.example.prolechc.data.remote.datasource.TrackingRemoto
import com.example.prolechc.data.repository.RutaDiaRepositorio
import com.example.prolechc.data.tracking.RastreoGps
import com.example.prolechc.domain.model.Coordenada
import com.example.prolechc.domain.model.LecturaGps
import com.example.prolechc.domain.model.Parada
import com.example.prolechc.domain.ruta.Geo
import com.example.prolechc.domain.ruta.PlanificadorRuta
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.TestScope
import kotlinx.coroutines.test.UnconfinedTestDispatcher
import kotlinx.coroutines.test.runCurrent
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.prolech.db.AppDatabase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue

// ── Dobles de prueba ──────────────────────────────────────────────────────────

class ProveedorUbicacionFalso(var permiso: Boolean = true) : ProveedorUbicacion {
    val lecturas = MutableSharedFlow<LecturaGps>(extraBufferCapacity = 64)
    override fun tienePermiso() = permiso
    override fun lecturas(intervaloMs: Long): Flow<LecturaGps> = lecturas
    override suspend fun ultimaConocida(): LecturaGps? = null
}

class TrackingRemotoFalso : TrackingRemoto {
    val lotes = mutableListOf<Pair<List<LecturaGps>, Boolean>>()
    var fallar = false
    override suspend fun enviarLote(puntos: List<LecturaGps>, finalizado: Boolean) {
        if (fallar) throw IllegalStateException("sin servidor")
        lotes += puntos to finalizado
    }
}

private fun parada(id: Int, lat: Double?, lon: Double?, atendido: Boolean = false, suspendido: Boolean = false) =
    Parada(id, id, id, "Ganadero $id", "0000000$id", null, lat, lon, atendido, suspendido)

private fun lectura(lat: Double, lon: Double, ms: Long, precision: Double? = 5.0) =
    LecturaGps(Coordenada(lat, lon), ms, precision)

// Planta de Huata y ganaderos al sur (coordenadas reales de la zona)
private val PLANTA = Coordenada(-15.6167, -69.9708)

@OptIn(ExperimentalCoroutinesApi::class)
class RutaYGpsTest {

    // ── Reloj real (regresión: kotlinx.datetime.Clock no existe en 0.7) ──────

    @Test
    fun el_reloj_real_funciona_con_la_version_de_kotlinx_datetime_en_uso() {
        val ahora = com.example.prolechc.domain.Tiempo.ahoraMs()
        val inicio = com.example.prolechc.domain.Tiempo.inicioDelDiaMs(ahora)

        assertTrue(ahora > 1_700_000_000_000L)
        assertTrue(inicio <= ahora && ahora - inicio < 25L * 60 * 60 * 1000)
        assertEquals(10, com.example.prolechc.domain.Tiempo.fechaHoy().length)
    }

    @Test
    fun la_ruta_se_guarda_con_el_reloj_por_defecto() = runTest {
        val repo = RutaDiaRepositorio(
            AppDatabase(crearDriverEnMemoria()),
            { """{"ruta_nombre":"X","paradas":[]}""" },
            UnconfinedTestDispatcher(testScheduler)
        )
        val r = repo.refrescar()
        assertTrue(r.isSuccess, "${r.exceptionOrNull()}")
    }

    // ── Geo / planificador ───────────────────────────────────────────────────

    @Test
    fun haversine_un_grado_de_latitud_son_unos_111_km() {
        val km = Geo.distanciaKm(Coordenada(-15.0, -70.0), Coordenada(-16.0, -70.0))
        assertTrue(km in 110.5..111.7, "obtuve $km")
    }

    @Test
    fun ordena_por_vecino_mas_cercano_desde_la_planta() {
        val lejos  = parada(1, -15.818, -70.021)
        val medio  = parada(2, -15.805, -70.018)
        val cerca  = parada(3, -15.792, -70.009)

        val orden = PlanificadorRuta().ordenar(PLANTA, listOf(lejos, medio, cerca))

        assertEquals(listOf(3, 2, 1), orden.map { it.ganaderoId })
    }

    @Test
    fun excluye_atendidos_y_suspendidos_y_deja_sin_gps_al_final() {
        val paradas = listOf(
            parada(1, -15.80, -70.01, atendido = true),
            parada(2, -15.81, -70.02, suspendido = true),
            parada(3, null, null),
            parada(4, -15.79, -70.00)
        )

        val orden = PlanificadorRuta().ordenar(PLANTA, paradas)

        assertEquals(listOf(4, 3), orden.map { it.ganaderoId })
    }

    @Test
    fun plan_en_linea_recta_sale_y_regresa_a_la_planta() {
        val plan = PlanificadorRuta().planEnLineaRecta(PLANTA, listOf(parada(1, -15.70, -69.99)), regresoA = PLANTA)

        assertEquals(PLANTA, plan.trazo.first())
        assertEquals(PLANTA, plan.trazo.last())
        assertFalse(plan.porCarretera)
        assertTrue(plan.distanciaKm > 0 && plan.duracionMin > 0)
    }

    // ── OSRM ─────────────────────────────────────────────────────────────────

    @Test
    fun parsea_la_respuesta_de_osrm_invirtiendo_lon_lat() {
        val json = """
            {"code":"Ok","routes":[{"distance":12500.0,"duration":1800.0,
              "geometry":{"type":"LineString","coordinates":[[-69.97,-15.61],[-70.0,-15.79]]}}]}
        """.trimIndent()

        val trazo = RutaVialOsrm.parsearOsrm(json)

        assertNotNull(trazo)
        assertEquals(Coordenada(-15.61, -69.97), trazo.puntos.first())
        assertEquals(12.5, trazo.distanciaKm)
        assertEquals(30, trazo.duracionMin)
    }

    @Test
    fun osrm_sin_ruta_devuelve_null() {
        assertNull(RutaVialOsrm.parsearOsrm("""{"code":"NoRoute","routes":[]}"""))
    }

    // ── Rastreo GPS offline-first ───────────────────────────────────────────

    private class Escenario(scope: TestScope, enLinea: Boolean) {
        val db = AppDatabase(crearDriverEnMemoria())
        val proveedor = ProveedorUbicacionFalso()
        val remoto = TrackingRemotoFalso()
        val monitor = MonitorConectividadFalso(enLinea)
        val rastreo = RastreoGps(
            database  = db,
            proveedor = proveedor,
            remoto    = remoto,
            monitor   = monitor,
            scope     = scope.backgroundScope,
            dispatcher = UnconfinedTestDispatcher(scope.testScheduler),
            reloj     = { 10_000_000L },
            intervaloSubidaMs = Long.MAX_VALUE / 4
        )
    }

    @Test
    fun guarda_puntos_sin_senal_y_filtra_ruido_del_gps() = runTest {
        val e = Escenario(this, enLinea = false)

        e.rastreo.registrar(lectura(-15.6167, -69.9708, 1_000))
        e.rastreo.registrar(lectura(-15.61671, -69.97081, 2_000))          // ~1 m: se ignora
        e.rastreo.registrar(lectura(-15.6200, -69.9750, 3_000, precision = 500.0)) // sin fix: se ignora
        e.rastreo.registrar(lectura(-15.6200, -69.9750, 4_000))            // ~550 m: se guarda

        assertEquals(2, e.rastreo.recorrido(0).first().size)
        assertEquals(2L, e.rastreo.pendientes())
        assertEquals(0, e.rastreo.subirPendientes())
        assertTrue(e.remoto.lotes.isEmpty())
    }

    @Test
    fun guarda_un_punto_aunque_este_detenido_si_pasaron_dos_minutos() = runTest {
        val e = Escenario(this, enLinea = false)

        e.rastreo.registrar(lectura(-15.6167, -69.9708, 0))
        e.rastreo.registrar(lectura(-15.6167, -69.9708, 130_000))

        assertEquals(2, e.rastreo.recorrido(0).first().size)
    }

    @Test
    fun al_volver_la_senal_sube_los_puntos_y_los_marca_enviados() = runTest {
        val e = Escenario(this, enLinea = false)
        e.rastreo.registrar(lectura(-15.6167, -69.9708, 1_000))
        e.rastreo.registrar(lectura(-15.6300, -69.9800, 2_000))

        e.monitor.enLinea.value = true
        val subidos = e.rastreo.subirPendientes(finalizado = true)

        assertEquals(2, subidos)
        assertEquals(1, e.remoto.lotes.size)
        assertTrue(e.remoto.lotes.single().second, "el último lote marca la ruta como finalizada")
        assertEquals(0L, e.rastreo.pendientes())
    }

    @Test
    fun si_falla_el_servidor_los_puntos_siguen_pendientes() = runTest {
        val e = Escenario(this, enLinea = true)
        e.remoto.fallar = true
        e.rastreo.registrar(lectura(-15.6167, -69.9708, 1_000))

        assertEquals(0, e.rastreo.subirPendientes())
        assertEquals(1L, e.rastreo.pendientes())
    }

    @Test
    fun iniciar_escucha_el_gps_y_detener_lo_apaga() = runTest {
        val e = Escenario(this, enLinea = false)

        e.rastreo.iniciar()
        runCurrent()
        e.proveedor.lecturas.emit(lectura(-15.6167, -69.9708, 1_000))
        e.proveedor.lecturas.emit(lectura(-15.6300, -69.9800, 2_000))
        runCurrent()

        assertTrue(e.rastreo.activo.value)
        assertEquals(Coordenada(-15.6300, -69.9800), e.rastreo.ultimaLectura.value?.coordenada)
        assertEquals(2, e.rastreo.recorrido(0).first().size)

        e.rastreo.detener()
        runCurrent()
        assertFalse(e.rastreo.activo.value)
    }

    // ── Ruta del día en caché ────────────────────────────────────────────────

    @Test
    fun la_ruta_descargada_queda_disponible_sin_senal() = runTest {
        val db = AppDatabase(crearDriverEnMemoria())
        var descargas = 0
        val json = """
            {"ruta_nombre":"Collana 2","origen_ruta":"zona","rol_mes":"titular",
             "planta":{"lat":-15.6167,"lon":-69.9708},
             "paradas":[{"id":3,"orden":1,"ganadero_id":3,"ganadero_nombre":"Pedro López",
                         "ganadero_dni":"34567890","geo_lat":-15.792,"geo_lon":-70.009,
                         "atendido":false,"suspendido":true}]}
        """.trimIndent()
        val repo = RutaDiaRepositorio(db, { descargas++; json }, UnconfinedTestDispatcher(testScheduler), { 42L })

        assertNull(repo.observar().first())
        assertTrue(repo.refrescar().isSuccess)

        val guardada = repo.observar().first()!!
        assertEquals(1, descargas)
        assertEquals("Collana 2", guardada.ruta.rutaNombre)
        assertEquals("zona", guardada.ruta.origenRuta)
        assertEquals(Coordenada(-15.6167, -69.9708), guardada.ruta.planta)
        assertTrue(guardada.ruta.paradas.single().suspendido)
        assertEquals(42L, guardada.actualizadaEn)
    }

    @Test
    fun una_descarga_fallida_no_borra_la_ruta_guardada() = runTest {
        val db = AppDatabase(crearDriverEnMemoria())
        var respuesta = """{"ruta_nombre":"Collana 1","paradas":[]}"""
        val repo = RutaDiaRepositorio(db, { respuesta }, UnconfinedTestDispatcher(testScheduler), { 1L })
        val primera = repo.refrescar()
        assertTrue(primera.isSuccess, "primera descarga: ${primera.exceptionOrNull()}")

        respuesta = "<html>error del servidor</html>"
        assertTrue(repo.refrescar().isFailure)

        assertEquals("Collana 1", repo.observar().first()?.ruta?.rutaNombre)
    }
}
