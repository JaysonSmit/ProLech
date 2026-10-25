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
    suspend fun ejecutar() {
        control.detener()
        rastreo.detenerSinSubir()
        rastreo.subirPendientes(finalizado = true)   // con señal, el recorrido no se pierde
        withContext(dispatcher) {
            database.transaction {
                database.puntoGpsQueries.borrarTodosLosPuntos()
                database.rutaCacheQueries.borrarRuta()
                database.ganaderoQueries.deleteAllGanaderos()
                // Solo se llega aquí sin acopios pendientes (lo valida AcopioViewModel)
                database.acopioQueries.deleteAll()
            }
        }
    }
}
