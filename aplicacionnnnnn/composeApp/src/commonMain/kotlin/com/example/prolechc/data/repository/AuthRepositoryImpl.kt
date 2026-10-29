package com.example.prolechc.data.repository

import com.example.prolechc.data.local.TokenStorage
import com.example.prolechc.data.mapper.toDomain
import com.example.prolechc.data.remote.datasource.AuthRemoteDataSource
import com.example.prolechc.domain.model.Usuario
import com.example.prolechc.domain.repository.AuthRepository

class AuthRepositoryImpl(
    private val remoteDataSource: AuthRemoteDataSource,
    private val tokenStorage: TokenStorage
) : AuthRepository {

    override suspend fun login(email: String, password: String): Result<Usuario> =
        runCatching {
            val response = remoteDataSource.login(email, password)
            tokenStorage.saveToken(response.token)
            response.toDomain()
        }

    override suspend fun logout() {
        runCatching { remoteDataSource.logout() }
        tokenStorage.clearToken()
    }

    override suspend fun isLoggedIn(): Boolean =
        tokenStorage.getToken() != null
}
