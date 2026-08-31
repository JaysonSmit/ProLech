package com.example.myapplicationvacas

import androidx.compose.ui.ExperimentalComposeUiApi
import androidx.compose.ui.window.ComposeViewport
import com.example.myapplicationvacas.web.ProLechWebApp

@OptIn(ExperimentalComposeUiApi::class)
fun main() {
    ComposeViewport {
        ProLechWebApp()
    }
}
