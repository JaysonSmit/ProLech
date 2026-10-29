package com.example.prolechc.di

import org.koin.core.module.Module

/**
 * Declaración expect del módulo de plataforma.
 * Cada target (Android / iOS) provee su implementación actual con los
 * bindings específicos de plataforma (TokenStorage, etc.).
 */
expect fun platformModule(): Module
