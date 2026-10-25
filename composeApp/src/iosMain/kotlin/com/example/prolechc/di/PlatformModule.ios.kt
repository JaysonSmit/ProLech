package com.example.prolechc.di

import com.example.prolechc.data.local.TokenStorage
import com.example.prolechc.data.local.db.DriverFactory
import com.example.prolechc.data.location.ControlRastreoIos
import com.example.prolechc.data.location.ControlRastreoSegundoPlano
import com.example.prolechc.data.location.ProveedorUbicacion
import com.example.prolechc.data.location.ProveedorUbicacionIos
import com.example.prolechc.data.network.MonitorConectividad
import com.example.prolechc.data.network.MonitorConectividadIos
import org.koin.core.module.Module
import org.koin.dsl.module
import platform.Foundation.NSUserDefaults

// ── TokenStorage iOS implementation ──────────────────────────────────────────

/**
 * Implementación de [TokenStorage] usando NSUserDefaults.
 * Para producción considera usar Keychain a través de una lib KMP como
 * multiplatform-settings-secure o una implementación nativa propia.
 */
class IosTokenStorage : TokenStorage {
    private val defaults = NSUserDefaults.standardUserDefaults
    private val key = "bearer_token"

    override suspend fun saveToken(token: String) {
        defaults.setObject(token, key)
    }

    override suspend fun getToken(): String? =
        defaults.stringForKey(key)

    override suspend fun clearToken() {
        defaults.removeObjectForKey(key)
    }
}

// ── actual platformModule ─────────────────────────────────────────────────────

actual fun platformModule(): Module = module {
    single<TokenStorage> { IosTokenStorage() }
    single { DriverFactory() }
    single<MonitorConectividad> { MonitorConectividadIos() }
    single<ProveedorUbicacion> { ProveedorUbicacionIos() }
    single<ControlRastreoSegundoPlano> { ControlRastreoIos() }
    single<com.example.prolechc.data.dispositivo.LectorLactoscan> {
        com.example.prolechc.data.dispositivo.LectorLactoscanIos()
    }
    single<com.example.prolechc.data.dispositivo.AccionesTicket> {
        com.example.prolechc.data.dispositivo.AccionesTicketIos()
    }
}
