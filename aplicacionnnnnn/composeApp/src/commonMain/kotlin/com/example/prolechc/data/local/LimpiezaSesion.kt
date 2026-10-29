package com.example.prolechc.data.local

import com.example.prolechc.data.location.ControlRastreoSegundoPlano
import com.example.prolechc.data.tracking.RastreoGps
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import pe.edu.upeu.prolech.db.AppDatabase
import kotlin.coroutines.CoroutineContext

/**
 * Al cerrar sesión: apaga el GPS, sube lo pendiente y borra los datos del chofer
 * del celular. Sin esto, el siguiente chofer que entrara en el mismo equipo
 * subiría como suyos los puntos GPS y vería la ruta del anterior.
 */
class LimpiezaSesion(
    private val database: AppDatabase,
    private val rastreo: RastreoGps,
    private val control: ControlRastreoSegundoPlano,
    private val dispatcher: CoroutineContext = Dispatchers.Default
) {
    /** Acopios o puntos GPS que todavía no llegaron al servidor. */
    suspend fun hayDatosSinSubir(): Boolean = withContext(dispatcher) {
        database.acopioQueries.selectPendientes().executeAsList().isNotEmpty()
    } || rastreo.pendientes() > 0

    suspend fun ejecutar() {
        control.detener()
        rastreo.detenerSinSubir()
        rastreo.subirPendientes(finalizado = true)   // con señal, el recorrido no se pierde
        borrarDatosLocales()
    }

    suspend fun borrarDatosLocales() {
        withContext(dispatcher) {
            database.transaction {
                database.puntoGpsQueries.borrarTodosLosPuntos()
                database.rutaCacheQueries.borrarRuta()
                database.ganaderoQueries.deleteAllGanaderos()
                database.acopioQueries.deleteAll()
            }
        }
    }
}
