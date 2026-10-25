package com.example.prolechc

import androidx.compose.ui.window.ComposeUIViewController
import com.example.prolechc.di.initKoin

/**
 * Punto de entrada de la UI para iOS.
 * Se llama desde el código Swift/Objective-C del target iOS.
 *
 * En tu archivo Swift (iOSApp.swift o similar):
 *   let controller = MainViewControllerKt.MainViewController()
 */
fun MainViewController() = ComposeUIViewController(
    configure = {
        initKoin()
    }
) {
    App()
}
