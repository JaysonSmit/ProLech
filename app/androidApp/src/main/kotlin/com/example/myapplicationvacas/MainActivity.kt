package com.example.myapplicationvacas

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.navigation.compose.rememberNavController
import com.example.myapplicationvacas.navigation.ProLechNavGraph
import com.example.myapplicationvacas.ui.theme.ProLechTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        enableEdgeToEdge()
        super.onCreate(savedInstanceState)
        setContent {
            ProLechTheme {
                val navController = rememberNavController()
                ProLechNavGraph(navController = navController)
            }
        }
    }
}
