package com.example.prolechc.di

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.example.prolechc.data.local.TokenStorage
import com.example.prolechc.data.local.db.DriverFactory
import com.example.prolechc.data.location.ControlRastreoAndroid
import com.example.prolechc.data.location.ControlRastreoSegundoPlano
import com.example.prolechc.data.location.ProveedorUbicacion
import com.example.prolechc.data.location.ProveedorUbicacionAndroid
import com.example.prolechc.data.network.MonitorConectividad
import com.example.prolechc.data.network.MonitorConectividadAndroid
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.map
import org.koin.android.ext.koin.androidContext
import org.koin.core.module.Module
import org.koin.dsl.module

// ── DataStore extension ───────────────────────────────────────────────────────

private val Context.dataStore: DataStore<Preferences> by preferencesDataStore(name = "prolechc_prefs")

private val TOKEN_KEY = stringPreferencesKey("bearer_token")

// ── TokenStorage Android implementation ──────────────────────────────────────

class AndroidTokenStorage(private val context: Context) : TokenStorage {
    override suspend fun saveToken(token: String) {
        context.dataStore.edit { it[TOKEN_KEY] = token }
    }

    override suspend fun getToken(): String? =
        context.dataStore.data
            .map { it[TOKEN_KEY] }
            .firstOrNull()

    override suspend fun clearToken() {
        context.dataStore.edit { it.remove(TOKEN_KEY) }
    }
}

// ── actual platformModule ──────────────────────────────────────────────────────

actual fun platformModule(): Module = module {
    single<TokenStorage> { AndroidTokenStorage(androidContext()) }
    single { DriverFactory(androidContext()) }
    single<MonitorConectividad> { MonitorConectividadAndroid(androidContext()) }
    single<ProveedorUbicacion> { ProveedorUbicacionAndroid(androidContext()) }
    single<ControlRastreoSegundoPlano> { ControlRastreoAndroid(androidContext()) }
}
