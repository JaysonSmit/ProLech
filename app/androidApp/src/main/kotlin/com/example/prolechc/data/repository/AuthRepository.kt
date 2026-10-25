package com.example.prolechc.data.repository

import android.content.Context
import com.example.prolechc.data.api.ApiResult
import com.example.prolechc.data.api.LoginRequest
import com.example.prolechc.data.api.LoginResponse
import com.example.prolechc.data.api.RetrofitClient
import com.example.prolechc.data.local.TokenDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.first
import java.io.IOException

// ══════════════════════════════════════════════════════════════════════════════
//  AuthRepository
//
//  Responsabilidades:
//    1. Enviar credenciales al endpoint POST /api/login
//    2. Guardar el Bearer Token (Sanctum) + datos de usuario en DataStore
//    3. Cerrar sesión: invalidar token en servidor y limpiar DataStore
//
//  El ViewModel no toca Retrofit ni DataStore directamente — todo pasa por aquí.
// ══════════════════════════════════════════════════════════════════════════════

class AuthRepository(context: Context) {

    private val api        = RetrofitClient.getInstance(context)
    private val tokenStore = TokenDataStore(context)

    // ── Flows públicos para observar el estado de sesión ──────────────────────

    /** Emite el token actual; null si no hay sesión. La UI observa esto. */
    val tokenFlow: Flow<String?> get() = tokenStore.tokenFlow

    /** Nombre completo del conductor autenticado. */
    val userNombreFlow: Flow<String> get() = tokenStore.userNombreFlow

    /** Rol del usuario (CONDUCTOR, ADMIN, etc.) */
    val userRolFlow: Flow<String?> get() = tokenStore.userRolFlow

    // ── Login ─────────────────────────────────────────────────────────────────

    /**
     * Autentica al conductor contra la API Laravel.
     *
     * En éxito:  guarda la sesión en DataStore y devuelve [ApiResult.Success].
     * En fallo HTTP (401, 422): devuelve [ApiResult.Error] con el código y mensaje.
     * En fallo de red (sin internet): devuelve [ApiResult.NetworkError].
     *
     * @param email    Correo electrónico del conductor
     * @param password Contraseña
     */
    suspend fun login(email: String, password: String): ApiResult<LoginResponse> {
        return try {
            val response = api.login(LoginRequest(email = email, password = password))

            if (response.isSuccessful) {
                val body = response.body()!!
                // Persistir sesión localmente usando el campo unificado
                tokenStore.saveSession(
                    token     = body.token,
                    userId    = body.user.id,
                    nombres   = body.user.nombreCompleto,
                    apellidos = "",
                    rol       = body.user.rol
                )
                ApiResult.Success(body)
            } else {
                val msg = response.errorBody()?.string() ?: "Error desconocido"
                ApiResult.Error(code = response.code(), message = msg)
            }
        } catch (e: IOException) {
            // Sin conexión o timeout
            ApiResult.NetworkError(e)
        } catch (e: Exception) {
            ApiResult.NetworkError(e)
        }
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    /**
     * Cierra la sesión:
     *  1. Intenta invalidar el token en el servidor (POST /api/logout).
     *  2. Borra la sesión local independientemente del resultado del servidor.
     *     Así el usuario siempre queda deslogueado en la app aunque no haya red.
     */
    suspend fun logout() {
        try {
            api.logout()
        } catch (_: Exception) {
            // Si falla la red en logout, igual limpiamos localmente
        } finally {
            tokenStore.clearSession()
        }
    }

    // ── Verificación rápida ───────────────────────────────────────────────────

    /**
     * Devuelve true si hay un token guardado localmente.
     * Útil en el splash/startup para saber si saltar la pantalla de login.
     */
    suspend fun isLoggedIn(): Boolean {
        return try {
            tokenStore.tokenFlow.first() != null
        } catch (_: Exception) {
            false
        }
    }
}
