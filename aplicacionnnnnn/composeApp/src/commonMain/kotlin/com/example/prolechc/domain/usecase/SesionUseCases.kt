package com.example.prolechc.domain.usecase

import com.example.prolechc.domain.repository.AuthRepository

class VerificarSesionUseCase(private val repository: AuthRepository) {
    suspend operator fun invoke(): Boolean = repository.isLoggedIn()
}

/**
 * Cierra sesión. [antesDeSalir] apaga el GPS y borra los datos locales del chofer
 * (lo provee Koin con LimpiezaSesion) para que el siguiente usuario empiece limpio.
 */
class LogoutUseCase(
    private val repository: AuthRepository,
    private val antesDeSalir: suspend () -> Unit = {}
) {
    suspend operator fun invoke() {
        runCatching { antesDeSalir() }
        repository.logout()
    }
}
