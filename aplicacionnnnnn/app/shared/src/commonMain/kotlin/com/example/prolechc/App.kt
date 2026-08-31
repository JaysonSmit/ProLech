package com.example.prolechc

import androidx.compose.runtime.Composable
import com.example.prolechc.navigation.ProLechNavGraph
import com.example.prolechc.ui.theme.ProLechTheme

@Composable
fun App() {
    ProLechTheme {
        ProLechNavGraph()
    }
}
