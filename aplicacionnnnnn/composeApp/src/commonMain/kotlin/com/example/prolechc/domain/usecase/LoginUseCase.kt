package com.example.prolechc.domain.usecase

import com.example.prolechc.domain.model.Usuario
import com.example.prolechc.domain.repository.AuthRepository

class LoginUseCase(private val repository: AuthRepository) {
    suspend operator fun invoke(email: String, password: String): Result<Usuario> =
        repository.login(email, password)
}
