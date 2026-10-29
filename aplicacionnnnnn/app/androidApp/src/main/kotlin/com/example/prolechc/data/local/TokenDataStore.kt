package com.example.prolechc.data.local

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.intPreferencesKey
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

// ══════════════════════════════════════════════════════════════════════════════
//  TokenDataStore
//
//  Persiste de forma segura el Bearer Token de Laravel Sanctum y los datos
//  básicos del usuario autenticado usando Jetpack DataStore Preferences.
//
//  DataStore escribe en el almacenamiento privado de la app (no accesible
//  desde otras apps sin root), suficiente para tokens de vida corta.
//
//  Uso:
//      val ds = TokenDataStore(context)
//      ds.saveToken("1|abc123...")
//      val token = ds.tokenFlow.first()
// ══════════════════════════════════════════════════════════════════════════════

// Extensión de Context — crea UNA sola instancia del DataStore por proceso
private val Context.dataStore: DataStore<Preferences>
    by preferencesDataStore(name = "prolech_auth")

class TokenDataStore(private val context: Context) {

    // ── Claves ────────────────────────────────────────────────────────────────
    companion object {
        private val KEY_TOKEN          = stringPreferencesKey("sanctum_token")
        private val KEY_USER_ID        = intPreferencesKey("user_id")
        private val KEY_USER_NOMBRES   = stringPreferencesKey("user_nombres")
        private val KEY_USER_APELLIDOS = stringPreferencesKey("user_apellidos")
        private val KEY_USER_ROL       = stringPreferencesKey("user_rol")
    }

    // ── Lecturas (Flow) ───────────────────────────────────────────────────────

    /** Flow del token; emite null si no hay sesión activa. */
    val tokenFlow: Flow<String?> = context.dataStore.data
        .map { prefs -> prefs[KEY_TOKEN] }

    /** Flow del ID del usuario autenticado. */
    val userIdFlow: Flow<Int?> = context.dataStore.data
        .map { prefs -> prefs[KEY_USER_ID] }

    /** Flow del nombre completo para mostrarlo en la UI. */
    val userNombreFlow: Flow<String> = context.dataStore.data
        .map { prefs ->
            val n = prefs[KEY_USER_NOMBRES]   ?: ""
            val a = prefs[KEY_USER_APELLIDOS] ?: ""
            "$n $a".trim()
        }

    /** Flow del rol (CONDUCTOR, ADMIN, etc.) */
    val userRolFlow: Flow<String?> = context.dataStore.data
        .map { prefs -> prefs[KEY_USER_ROL] }

    // ── Escrituras ────────────────────────────────────────────────────────────

    /** Guarda el token y los datos del usuario tras un login exitoso. */
    suspend fun saveSession(
        token: String,
        userId: Int,
        nombres: String,
        apellidos: String,
        rol: String
    ) {
        context.dataStore.edit { prefs ->
            prefs[KEY_TOKEN]          = token
            prefs[KEY_USER_ID]        = userId
            prefs[KEY_USER_NOMBRES]   = nombres
            prefs[KEY_USER_APELLIDOS] = apellidos
            prefs[KEY_USER_ROL]       = rol
        }
    }

    /** Elimina toda la sesión (logout). */
    suspend fun clearSession() {
        context.dataStore.edit { prefs ->
            prefs.remove(KEY_TOKEN)
            prefs.remove(KEY_USER_ID)
            prefs.remove(KEY_USER_NOMBRES)
            prefs.remove(KEY_USER_APELLIDOS)
            prefs.remove(KEY_USER_ROL)
        }
    }
}
