package com.example.prolechc.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

// ── Paleta ProLech Light (igual al Figma) ───────────────────────────────────
val White          = Color(0xFFFFFFFF)
val BgPage         = Color(0xFFF4F6F4)   // fondo general gris muy claro
val BgCard         = Color(0xFFFFFFFF)   // cards blancas
val BgCardAlt      = Color(0xFFF0F4F0)   // cards alternadas

val TextPrimary    = Color(0xFF0D1A0D)   // casi negro
val TextSecondary  = Color(0xFF4A5E4A)   // gris verdoso
val TextMuted      = Color(0xFF8A9E8A)   // gris claro
val TextDim        = Color(0xFFB0BEB0)
val Divider        = Color(0xFFE0E8E0)

// Verdes
val GreenPrimary   = Color(0xFF1E8A3C)
val GreenDark      = Color(0xFF0F5C26)
val GreenBorder    = Color(0xFF27AE60)
val GreenBg        = Color(0xFFE8F5EE)
val GreenText      = Color(0xFF1E8A3C)
val GreenAccent    = Color(0xFF2ECC71)

// Rojos
val RedPrimary     = Color(0xFFB71C1C)
val RedBorder      = Color(0xFFC62828)
val RedBg          = Color(0xFFFFEBEE)
val RedText        = Color(0xFFB71C1C)

// Ámbar
val AmberPrimary   = Color(0xFFF57F17)
val AmberBg        = Color(0xFFFFF8E1)
val AmberText      = Color(0xFFF57F17)

// Azul
val BluePrimary    = Color(0xFF1565C0)
val BlueLight      = Color(0xFF1976D2)
val BlueBg         = Color(0xFFE3F2FD)

// Cyan
val CyanAccent     = Color(0xFF00796B)
val CyanBg         = Color(0xFFE0F2F1)

// Surface
val SurfaceDark    = Color(0xFF1A2E1A)   // topbar oscura
val SurfaceCard    = Color(0xFFFFFFFF)
val SurfaceElevated= Color(0xFFF8FBF8)
val GrayDark       = Color(0xFFCFD8CF)

private val ProLechLightColors = lightColorScheme(
    primary            = GreenPrimary,
    onPrimary          = Color(0xFFFFFFFF),
    primaryContainer   = GreenBg,
    onPrimaryContainer = GreenDark,
    secondary          = CyanAccent,
    onSecondary        = Color(0xFFFFFFFF),
    background         = BgPage,
    onBackground       = TextPrimary,
    surface            = BgCard,
    onSurface          = TextPrimary,
    surfaceVariant     = BgCardAlt,
    onSurfaceVariant   = TextSecondary,
    error              = RedPrimary,
    onError            = Color(0xFFFFFFFF),
    outline            = Divider,
)

@Composable
fun ProLechTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = ProLechLightColors,
        content     = content
    )
}
