package com.example.myapplicationvacas.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val ProLechDarkColors = darkColorScheme(
    primary            = GreenPrimary,
    onPrimary          = Color.Black,
    primaryContainer   = GreenContainer,
    onPrimaryContainer = GreenPrimary,
    secondary          = GreenDark,
    onSecondary        = Color.Black,
    error              = RedAlert,
    onError            = Color.White,
    errorContainer     = RedContainer,
    onErrorContainer   = RedAlertLight,
    background         = BackgroundDark,
    onBackground       = TextPrimary,
    surface            = SurfaceDark,
    onSurface          = TextPrimary,
    surfaceVariant     = SurfaceVariantDark,
    onSurfaceVariant   = TextSecondary,
    outline            = OutlineDefault,
    outlineVariant     = OutlineHighlight
)

@Composable
fun ProLechTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = ProLechDarkColors,
        typography  = ProLechTypography,
        content     = content
    )
}
