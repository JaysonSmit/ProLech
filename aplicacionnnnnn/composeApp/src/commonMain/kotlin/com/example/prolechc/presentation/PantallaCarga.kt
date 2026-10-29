package com.example.prolechc.presentation

import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.scale
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.prolechc.recursos.Res
import com.example.prolechc.recursos.logo_ecolacteos
import org.jetbrains.compose.resources.painterResource

/** Pantalla de carga con el logo de Ecolácteos Huata: aparece, «respira» y muestra una barra que avanza. */
@Composable
fun PantallaCarga() {
    var entrar by remember { mutableStateOf(false) }
    LaunchedEffect(Unit) { entrar = true }
    val aparicion by animateFloatAsState(if (entrar) 1f else 0f, tween(700, easing = FastOutSlowInEasing))
    val ciclo = rememberInfiniteTransition()
    val respiro by ciclo.animateFloat(1f, 1.04f, infiniteRepeatable(tween(900), RepeatMode.Reverse))
    val barra by ciclo.animateFloat(0f, 1f, infiniteRepeatable(tween(1_200, easing = FastOutSlowInEasing), RepeatMode.Restart))

    Box(
        Modifier.fillMaxSize().background(Brush.verticalGradient(listOf(Color(0xFFF7F7F7), Color.White, Color(0xFFEFF5FF)))),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.spacedBy(18.dp)) {
            Image(
                painter = painterResource(Res.drawable.logo_ecolacteos),
                contentDescription = "Ecolácteos Huata",
                modifier = Modifier.width(280.dp).alpha(aparicion).scale((0.8f + 0.2f * aparicion) * respiro)
            )
            // Barra de carga con los colores del logo
            Box(Modifier.width(160.dp).height(5.dp).clip(RoundedCornerShape(99.dp)).background(Color(0xFFE2E8F0)).alpha(aparicion)) {
                Box(
                    Modifier.fillMaxWidth(barra).height(5.dp).clip(RoundedCornerShape(99.dp))
                        .background(Brush.horizontalGradient(listOf(Color(0xFF1D3F9E), Color(0xFFE53935), Color(0xFFF2B705))))
                )
            }
            Text(
                "ProLech · Acopio de leche",
                fontSize = 13.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF64748B),
                modifier = Modifier.alpha(aparicion).padding(top = 2.dp)
            )
        }
    }
}
