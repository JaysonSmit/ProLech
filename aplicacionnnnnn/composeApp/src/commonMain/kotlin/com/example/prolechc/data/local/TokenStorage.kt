package com.example.prolechc.data.local

/**
 * Contrato de almacenamiento del Bearer token.
 * Cada plataforma provee su implementación a través de Koin (PlatformModule).
 */
interface TokenStorage {
    suspend fun saveToken(token: String)
    suspend fun getToken(): String?
    suspend fun clearToken()
}
