package com.example.prolechc.di

import com.example.prolechc.data.local.ConfigLocal
import com.example.prolechc.data.sync.SyncManager
import org.koin.core.context.GlobalContext
import org.koin.core.context.startKoin
import org.koin.dsl.KoinAppDeclaration

/**
 * Punto de entrada común para inicializar Koin.
 * Se llama desde cada plataforma (Application en Android, MainViewController en iOS).
 * Es idempotente: iOS puede recrear el ViewController y un segundo startKoin fallaría.
 *
 * @param appDeclaration bloque opcional para configuración adicional de Koin
 *                       (ej: logger, propiedades).
 */
fun initKoin(appDeclaration: KoinAppDeclaration = {}) {
    if (GlobalContext.getOrNull() != null) return

    val koin = startKoin {
        appDeclaration()
        modules(appModules + platformModule())
    }.koin
    // URL del servidor elegida por el chofer (antes estaba fija en el código)
    koin.get<ConfigLocal>().aplicarServidorGuardado()
    // Sincroniza automáticamente (Push & Pull) cada vez que vuelve la red
    koin.get<SyncManager>().iniciar()
}
