package com.example.prolechc.domain.repository

import com.example.prolechc.domain.model.Usuario

interface AuthRepository {
    /** Autentica con email/contraseña y persiste el token. */
    suspend fun login(email: String, password: String): Result<Usuario>
    /** Invalida el token en el servidor y limpia la sesión local. */
    suspend fun logout()
    /** Devuelve true si existe token guardado localmente. */
    suspend fun isLoggedIn(): Boolean
}
