package com.example.myapplicationvacas.web.theme

import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp

// ── Colores Web ProLech (dark) ────────────────────────────────────────────────
val WebBackground   = Color(0xFF0A0A0A)
val WebSurface      = Color(0xFF141414)
val WebSurface2     = Color(0xFF1C1C1C)
val WebCard         = Color(0xFF1A1A1A)
val WebBorder       = Color(0xFF2A2A2A)
val WebBorderHigh   = Color(0xFF3A3A3A)

val WebGreen        = Color(0xFF00C853)
val WebGreenDark    = Color(0xFF00A846)
val WebGreenBg      = Color(0xFF002E14)
val WebGreenLight   = Color(0xFF33D473)

val WebRed          = Color(0xFFD32F2F)
val WebRedLight     = Color(0xFFFF5252)
val WebRedBg        = Color(0xFF2E0808)

val WebYellow       = Color(0xFFFFD600)
val WebYellowBg     = Color(0xFF2E2800)
val WebOrange       = Color(0xFFFF6D00)
val WebBlue         = Color(0xFF1565C0)
val WebBlueBg       = Color(0xFF0A1A2E)
val WebPurple       = Color(0xFF7B1FA2)

val WebTextPrimary   = Color(0xFFF0F0F0)
val WebTextSecondary = Color(0xFFAAAAAA)
val WebTextDisabled  = Color(0xFF555555)

val WebDarkColors = darkColorScheme(
    primary            = WebGreen,
    onPrimary          = Color.Black,
    primaryContainer   = WebGreenBg,
    onPrimaryContainer = WebGreen,
    background         = WebBackground,
    onBackground       = WebTextPrimary,
    surface            = WebSurface,
    onSurface          = WebTextPrimary,
    surfaceVariant     = WebSurface2,
    onSurfaceVariant   = WebTextSecondary,
    error              = WebRed,
    onError            = Color.White,
    outline            = WebBorder,
    outlineVariant     = WebBorderHigh
)

val WebTypography = Typography(
    headlineLarge  = TextStyle(fontWeight = FontWeight.ExtraBold, fontSize = 28.sp, color = WebTextPrimary),
    headlineMedium = TextStyle(fontWeight = FontWeight.Bold,      fontSize = 22.sp, color = WebTextPrimary),
    titleLarge     = TextStyle(fontWeight = FontWeight.SemiBold,  fontSize = 18.sp, color = WebTextPrimary),
    titleMedium    = TextStyle(fontWeight = FontWeight.Medium,    fontSize = 15.sp, color = WebTextPrimary),
    bodyLarge      = TextStyle(fontWeight = FontWeight.Normal,    fontSize = 14.sp, color = WebTextPrimary),
    bodyMedium     = TextStyle(fontWeight = FontWeight.Normal,    fontSize = 13.sp, color = WebTextSecondary),
    labelLarge     = TextStyle(fontWeight = FontWeight.SemiBold,  fontSize = 13.sp, color = WebTextPrimary),
    labelMedium    = TextStyle(fontWeight = FontWeight.Medium,    fontSize = 12.sp, color = WebTextSecondary),
    labelSmall     = TextStyle(fontWeight = FontWeight.Medium,    fontSize = 11.sp, color = WebTextSecondary)
)

@Composable
fun ProLechWebTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = WebDarkColors,
        typography  = WebTypography,
        content     = content
    )
}
