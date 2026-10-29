package com.example.prolechc.offline

import com.example.prolechc.data.local.AcopioLocalSqlDelight
import com.example.prolechc.data.remote.datasource.AcopioRemoto
import com.example.prolechc.data.remote.datasource.ErrorHttp
import com.example.prolechc.data.sync.SubidorFotos
import com.example.prolechc.data.sync.SyncManager
import com.example.prolechc.domain.lactoscan.ParserLactoscan
import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.domain.model.EstadoSync
import com.example.prolechc.domain.model.Parada
import com.example.prolechc.domain.ruta.ResumenRuta
import com.example.prolechc.domain.ticket.Ticket
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.UnconfinedTestDispatcher
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.prolech.db.AppDatabase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

/** Servidor que responde con un error HTTP configurable. */
private class RemotoConError(var codigo: Int?, var detalle: String = "El campo litros es inválido.") : AcopioRemoto {
    val creados = mutableListOf<String>()
    private fun quizasFallar() { codigo?.let { throw ErrorHttp(it, detalle) } }
    override suspend fun crear(acopio: Acopio) { quizasFallar(); creados += acopio.id }
    override suspend fun actualizar(acopio: Acopio) { quizasFallar() }
    override suspend fun eliminar(id: String) { quizasFallar() }
    // Un 422 solo ocurre al enviar datos; la descarga falla con 401 o errores del servidor
    override suspend fun obtenerTodos(): List<Acopio> {
        codigo?.takeIf { it == 401 || it >= 500 }?.let { throw ErrorHttp(it, detalle) }
        return emptyList()
    }
}

@OptIn(ExperimentalCoroutinesApi::class)
class MejorasCampoTest {

    // ── 4. Registros rechazados ──────────────────────────────────────────────

    @Test
    fun un_422_marca_el_registro_con_error_y_no_lo_reintenta() = runTest {
        val local = AcopioLocalSqlDelight(AppDatabase(crearDriverEnMemoria()), UnconfinedTestDispatcher(testScheduler))
        val remoto = RemotoConError(422)
        val sync = SyncManager(local, remoto, MonitorConectividadFalso(true), backgroundScope)
        local.guardar(acopioDePrueba(estadoSync = EstadoSync.PENDIENTE_CREAR))

        val r1 = sync.sincronizar()
        assertEquals(1, r1.conError)
        assertEquals(0, r1.fallidos)
        assertEquals("El campo litros es inválido.", local.obtenerPorId("uuid-1")?.errorSync)

        // Aunque el servidor ya acepte, no se reenvía solo: espera la corrección del chofer
        remoto.codigo = null
        sync.sincronizar()
        assertTrue(remoto.creados.isEmpty())

        // Al corregirlo (se limpia el error) vuelve a subirse
        local.guardar(local.obtenerPorId("uuid-1")!!.copy(litros = 45.0, errorSync = null))
        assertEquals(1, sync.sincronizar().subidos)
        assertEquals(listOf("uuid-1"), remoto.creados)
    }

    @Test
    fun sin_red_o_500_queda_pendiente_para_reintentar() = runTest {
        val local = AcopioLocalSqlDelight(AppDatabase(crearDriverEnMemoria()), UnconfinedTestDispatcher(testScheduler))
        val sync = SyncManager(local, RemotoConError(503), MonitorConectividadFalso(true), backgroundScope)
        local.guardar(acopioDePrueba(estadoSync = EstadoSync.PENDIENTE_CREAR))

        val r = sync.sincronizar()

        assertEquals(2, r.fallidos, "falla la subida y la descarga")
        assertEquals(0, r.conError)
        assertNull(local.obtenerPorId("uuid-1")?.errorSync)
    }

    @Test
    fun clasifica_que_errores_son_definitivos() {
        assertTrue(SyncManager.esRechazoDefinitivo(422))
        assertTrue(SyncManager.esRechazoDefinitivo(403))
        assertFalse(SyncManager.esRechazoDefinitivo(401))
        assertFalse(SyncManager.esRechazoDefinitivo(429))
        assertFalse(SyncManager.esRechazoDefinitivo(500))
    }

    // ── 5. Sesión vencida ───────────────────────────────────────────────────

    @Test
    fun un_401_avisa_sesion_vencida_y_conserva_los_datos() = runTest {
        val local = AcopioLocalSqlDelight(AppDatabase(crearDriverEnMemoria()), UnconfinedTestDispatcher(testScheduler))
        var avisos = 0
        val sync = SyncManager(local, RemotoConError(401, "Unauthenticated."), MonitorConectividadFalso(true),
            backgroundScope, alSesionVencida = { avisos++ })
        local.guardar(acopioDePrueba(estadoSync = EstadoSync.PENDIENTE_CREAR))

        val r = sync.sincronizar()

        assertTrue(r.sesionVencida)
        assertEquals(1, avisos)
        val conservado = local.obtenerPorId("uuid-1")!!
        assertEquals(EstadoSync.PENDIENTE_CREAR, conservado.estadoSync)
        assertNull(conservado.errorSync, "un 401 no es culpa del registro")
    }

    // ── 9. Fotos ─────────────────────────────────────────────────────────────

    @Test
    fun la_foto_se_sube_despues_del_acopio_y_se_conserva_en_el_pull() = runTest {
        val local = AcopioLocalSqlDelight(AppDatabase(crearDriverEnMemoria()), UnconfinedTestDispatcher(testScheduler))
        val remoto = AcopioRemotoFalso()
        val subidas = mutableListOf<String>()
        val sync = SyncManager(local, remoto, MonitorConectividadFalso(true), backgroundScope,
            subidorFotos = SubidorFotos { subidas += it.fotoLocal!! })
        local.guardar(acopioDePrueba(estadoSync = EstadoSync.PENDIENTE_CREAR).copy(fotoLocal = "/fotos/balde.jpg"))

        sync.sincronizar()

        assertEquals(listOf("/fotos/balde.jpg"), subidas)
        val final = local.obtenerPorId("uuid-1")!!
        assertEquals("/fotos/balde.jpg", final.fotoLocal, "el pull no debe borrar la foto local")
        assertTrue(final.fotoSubida)

        sync.sincronizar()
        assertEquals(1, subidas.size, "no se vuelve a subir")
    }

    // ── 6. OCR del Lactoscan ────────────────────────────────────────────────

    @Test
    fun lee_los_valores_del_lactoscan_con_errores_tipicos_de_ocr() {
        val texto = """
            LACTOSCAN SA
            Grasa      3,62 %
            SNF        8.45
            Densidad   31.2
            Agua añadida  0.O0 %
            Proteína   3.10
            Temp.  18.5 C
        """.trimIndent()

        val r = ParserLactoscan.parsear(texto)

        assertEquals(3.62, r.grasa)
        assertEquals(1.0312, r.densidad!!, 0.00001)
        assertEquals(0.0, r.aguaAniadida)
        assertEquals(8.45, r.sng)
        assertFalse(r.vacio)
    }

    @Test
    fun lactoscan_en_ingles_y_valor_en_la_linea_siguiente() {
        val r = ParserLactoscan.parsear("FAT\n4.1\nDENSITY\n1.029\nADDED WATER\n2.5")

        assertEquals(4.1, r.grasa)
        assertEquals(1.029, r.densidad)
        assertEquals(2.5, r.aguaAniadida)
    }

    @Test
    fun texto_sin_valores_queda_vacio() {
        assertTrue(ParserLactoscan.parsear("foto borrosa sin datos").vacio)
    }

    // ── 7. Ticket ────────────────────────────────────────────────────────────

    @Test
    fun el_ticket_cabe_en_una_impresora_de_58_mm() {
        val acopio = acopioDePrueba(litros = 45.5).copy(
            id = "6f1c2a3b-4d5e-4f60-8a7b-9c0d1e2f3a4b", grasa = 3.6, estadoCalidad = EstadoCalidad.OBSERVADO
        )

        val ticket = Ticket.de(acopio, chofer = "Carlos Ramos", ruta = "Collana 2")

        assertEquals("AC-6F1C2A3B", ticket.numero)
        assertTrue(ticket.lineas().all { it.length <= Ticket.ANCHO }, ticket.comoTexto())
        assertTrue(ticket.comoTexto().contains("45.50 L"))
        assertTrue(ticket.comoTexto().contains("OBSERVADO"))
        assertTrue(ticket.comoTexto().contains("Grasa"))
    }

    // ── 10. Resumen de ruta ─────────────────────────────────────────────────

    @Test
    fun resumen_de_ruta_cuenta_litros_calidad_y_pendientes() {
        val paradas = listOf(
            Parada(1, 1, 1, "Juan", "1", null, null, null, atendido = true),
            Parada(2, 2, 2, "María", "2", null, null, null, atendido = false),
            Parada(3, 3, 3, "Pedro", "3", null, null, null, atendido = false, suspendido = true)
        )
        val hoy = listOf(
            acopioDePrueba(id = "a", litros = 40.0),
            acopioDePrueba(id = "b", litros = 10.0).copy(estadoCalidad = EstadoCalidad.RECHAZADO, porcentajeAgua = 6.0)
        )

        val r = ResumenRuta.calcular(hoy, paradas, km = 23.4, inicioMs = 0, ahoraMs = 95 * 60_000)

        assertEquals(50.0, r.litros)
        assertEquals(1, r.atendidos)
        assertEquals(2, r.totalParadas, "los suspendidos no cuentan")
        assertEquals(listOf("María"), r.noAtendidos)
        assertEquals(95, r.minutos)
        assertEquals(50, r.porcentajeAprobado)
        assertEquals(3.0, r.aguaPromedio)
    }
}
