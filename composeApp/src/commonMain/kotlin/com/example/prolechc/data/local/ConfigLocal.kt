package com.example.prolechc.data.local

import com.example.prolechc.data.remote.ApiConfig
import pe.edu.upeu.prolech.db.AppDatabase

/** Ajustes persistentes del celular guardados en SQLDelight. */
class ConfigLocal(database: AppDatabase) {
    private val queries = database.configQueries

    fun servidor(): String =
        queries.obtenerConfig(CLAVE_SERVIDOR).executeAsOneOrNull() ?: ApiConfig.SERVIDOR_POR_DEFECTO

    /** Guarda la URL y la aplica de inmediato a todas las llamadas Ktor. */
    fun guardarServidor(url: String) {
        val normalizada = ApiConfig.normalizar(url)
        queries.guardarConfig(CLAVE_SERVIDOR, normalizada)
        ApiConfig.cambiarServidor(normalizada)
    }

    /** Se llama al iniciar la app. */
    fun aplicarServidorGuardado() = ApiConfig.cambiarServidor(servidor())

    private companion object {
        const val CLAVE_SERVIDOR = "servidor_api"
    }
}
