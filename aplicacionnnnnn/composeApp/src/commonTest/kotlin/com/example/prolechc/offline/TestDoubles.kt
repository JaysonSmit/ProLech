package com.example.prolechc.offline

import app.cash.sqldelight.db.SqlDriver
import com.example.prolechc.data.network.MonitorConectividad
import com.example.prolechc.data.remote.datasource.AcopioRemoto
import com.example.prolechc.data.remote.datasource.GanaderoRemoto
import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.domain.model.EstadoSync
import com.example.prolechc.domain.model.GanaderoCampo
import com.example.prolechc.domain.model.Usuario
import com.example.prolechc.domain.repository.AuthRepository
import kotlinx.coroutines.flow.MutableStateFlow

/** Driver SQLite en memoria (JDBC en JVM/Android, NativeSqliteDriver en iOS). */
expect fun crearDriverEnMemoria(): SqlDriver

/** Simula el Modo Avión: basta con cambiar [enLinea].value. */
class MonitorConectividadFalso(inicial: Boolean) : MonitorConectividad {
    override val enLinea = MutableStateFlow(inicial)
}

/** Servidor Laravel en memoria que registra cada llamada recibida. */
class AcopioRemotoFalso : AcopioRemoto {
    val servidor = mutableMapOf<String, Acopio>()
    val creados = mutableListOf<String>()
    val actualizados = mutableListOf<String>()
    val eliminados = mutableListOf<String>()
    var fallar = false

    private fun verificar() {
        if (fallar) throw IllegalStateException("Servidor no disponible")
    }

    override suspend fun crear(acopio: Acopio) {
        verificar()
        creados += acopio.id
        servidor[acopio.id] = acopio.copy(estadoSync = EstadoSync.SINCRONIZADO)
    }

    override suspend fun actualizar(acopio: Acopio) {
        verificar()
        actualizados += acopio.id
        // El backend también aplica Last-Write-Wins: ignora versiones más antiguas
        val actual = servidor[acopio.id]
        if (actual == null || acopio.actualizadoEn >= actual.actualizadoEn) {
            servidor[acopio.id] = acopio.copy(estadoSync = EstadoSync.SINCRONIZADO)
        }
    }

    override suspend fun eliminar(id: String) {
        verificar()
        eliminados += id
        servidor.remove(id)
    }

    override suspend fun obtenerTodos(): List<Acopio> {
        verificar()
        return servidor.values.toList()
    }

    val totalLlamadas: Int get() = creados.size + actualizados.size + eliminados.size
}

fun acopioDePrueba(
    id: String = "uuid-1",
    litros: Double = 50.0,
    actualizadoEn: Long = 1_000L,
    estadoSync: EstadoSync = EstadoSync.SINCRONIZADO
) = Acopio(
    id             = id,
    ganaderoId     = 7,
    ganaderoNombre = "Juan Quispe",
    litros         = litros,
    porcentajeAgua = 0.0,
    densidad       = 1.030,
    estadoCalidad  = EstadoCalidad.APROBADO,
    actualizadoEn  = actualizadoEn,
    estadoSync     = estadoSync
)

/** Catálogo de ganaderos que devuelve /api/mi-ruta. */
class GanaderoRemotoFalso(var ganaderos: List<GanaderoCampo> = emptyList()) : GanaderoRemoto {
    var fallar = false
    override suspend fun obtenerGanaderos(): List<GanaderoCampo> {
        if (fallar) throw IllegalStateException("Sin ruta")
        return ganaderos
    }
}

class AuthRepositoryFalso(var logueado: Boolean = true) : AuthRepository {
    var logouts = 0
    override suspend fun login(email: String, password: String): Result<Usuario> {
        logueado = true
        return Result.success(Usuario(1, "Chofer Prueba", "chofer"))
    }
    override suspend fun logout() {
        logouts++
        logueado = false
    }
    override suspend fun isLoggedIn(): Boolean = logueado
}
