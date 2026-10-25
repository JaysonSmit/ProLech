package com.example.prolechc.presentation.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Shapes
import androidx.compose.material3.Typography
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/** Paleta "Leche & Lago" — la misma de la tienda web y el panel. */
object ProLechColores {
    val Azul       = Color(0xFF1F6FEB)
    val AzulOscuro = Color(0xFF1858C4)
    val Celeste    = Color(0xFF38BDF8)
    val Marino     = Color(0xFF0B1F44)
    val Marino2    = Color(0xFF163A7A)
    val Caramelo   = Color(0xFFF2A541)
    val Crema      = Color(0xFFEEF5FF)
    val Fondo      = Color(0xFFF6F9FE)
    val Borde      = Color(0xFFE3E9F3)
    val Exito      = Color(0xFF16A34A)
    val ExitoSuave = Color(0xFFDCFCE7)
    val Alerta     = Color(0xFFD97706)
    val AlertaSuave = Color(0xFFFEF3C7)
    val Error      = Color(0xFFDC2626)
    val ErrorSuave = Color(0xFFFEE2E2)

    val Degradado      = Brush.linearGradient(listOf(Azul, Celeste))
    val DegradadoOscuro = Brush.linearGradient(listOf(Marino, Marino2))
}

private val Claro = lightColorScheme(
    primary              = ProLechColores.Azul,
    onPrimary            = Color.White,
    primaryContainer     = ProLechColores.Crema,
    onPrimaryContainer   = ProLechColores.Marino,
    secondary            = ProLechColores.Marino,
    onSecondary          = Color.White,
    secondaryContainer   = Color(0xFFDBE9FF),
    onSecondaryContainer = ProLechColores.Marino,
    tertiary             = ProLechColores.Caramelo,
    onTertiary           = ProLechColores.Marino,
    tertiaryContainer    = Color(0xFFFDE4BC),
    onTertiaryContainer  = Color(0xFF7C4A0B),
    background           = ProLechColores.Fondo,
    onBackground         = Color(0xFF0F172A),
    surface              = Color.White,
    onSurface            = Color(0xFF0F172A),
    surfaceVariant       = Color(0xFFF1F5FB),
    onSurfaceVariant     = Color(0xFF64748B),
    outline              = ProLechColores.Borde,
    error                = ProLechColores.Error,
    errorContainer       = ProLechColores.ErrorSuave
)

private val Oscuro = darkColorScheme(
    primary              = Color(0xFF7CB4FF),
    onPrimary            = ProLechColores.Marino,
    primaryContainer     = ProLechColores.Marino2,
    onPrimaryContainer   = Color(0xFFDBE9FF),
    secondary            = Color(0xFFDBE9FF),
    onSecondary          = ProLechColores.Marino,
    tertiary             = ProLechColores.Caramelo,
    onTertiary           = ProLechColores.Marino,
    background           = Color(0xFF07142E),
    onBackground         = Color(0xFFE6EDF8),
    surface              = Color(0xFF0E1F42),
    onSurface            = Color(0xFFE6EDF8),
    surfaceVariant       = Color(0xFF16294F),
    onSurfaceVariant     = Color(0xFF9FB2D1),
    outline              = Color(0xFF26406E)
)

private val Tipografia = Typography().run {
    copy(
        headlineMedium = headlineMedium.copy(fontWeight = FontWeight.ExtraBold, letterSpacing = (-0.5).sp),
        headlineSmall  = headlineSmall.copy(fontWeight = FontWeight.ExtraBold, letterSpacing = (-0.3).sp),
        titleLarge     = titleLarge.copy(fontWeight = FontWeight.ExtraBold),
        titleMedium    = titleMedium.copy(fontWeight = FontWeight.Bold),
        titleSmall     = titleSmall.copy(fontWeight = FontWeight.Bold),
        labelLarge     = labelLarge.copy(fontWeight = FontWeight.Bold)
    )
}

private val Formas = Shapes(
    extraSmall = RoundedCornerShape(8.dp),
    small      = RoundedCornerShape(12.dp),
    medium     = RoundedCornerShape(18.dp),
    large      = RoundedCornerShape(24.dp),
    extraLarge = RoundedCornerShape(30.dp)
)

@Composable
fun ProLechTema(oscuro: Boolean = isSystemInDarkTheme(), contenido: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = if (oscuro) Oscuro else Claro,
        typography  = Tipografia,
        shapes      = Formas,
        content     = contenido
    )
}

/** Estilo para cifras grandes (litros, km, totales). */
val EstiloCifra = TextStyle(fontWeight = FontWeight.ExtraBold, fontSize = 26.sp, letterSpacing = (-0.5).sp)
