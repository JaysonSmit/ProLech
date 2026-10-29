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

    /** Correo del último chofer que usó el celular (dueño de los datos offline). */
    fun ultimoUsuario(): String? = queries.obtenerConfig(CLAVE_USUARIO).executeAsOneOrNull()

    fun guardarUltimoUsuario(email: String) = queries.guardarConfig(CLAVE_USUARIO, email.trim().lowercase())

    /** Nombre del chofer para el ticket del ganadero. */
    /** Id del último aviso que ya salió como notificación del sistema. */
    fun ultimoAvisoNotificado(): Int = queries.obtenerConfig(CLAVE_ULTIMO_AVISO).executeAsOneOrNull()?.toIntOrNull() ?: 0

    fun guardarUltimoAvisoNotificado(id: Int) = queries.guardarConfig(CLAVE_ULTIMO_AVISO, id.toString())

    fun nombreChofer(): String = queries.obtenerConfig(CLAVE_NOMBRE).executeAsOneOrNull().orEmpty()

    fun guardarNombreChofer(nombre: String) = queries.guardarConfig(CLAVE_NOMBRE, nombre.trim())

    private companion object {
        const val CLAVE_SERVIDOR = "servidor_api"
        const val CLAVE_ULTIMO_AVISO = "ultimo_aviso_notificado"
        const val CLAVE_USUARIO = "ultimo_usuario"
        const val CLAVE_NOMBRE = "nombre_chofer"
    }
}
