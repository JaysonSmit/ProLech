package com.example.prolechc.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.prolechc.ui.theme.GreenPrimary
import com.example.prolechc.ui.theme.TextMuted

enum class BottomNavRoute { DASHBOARD, ACOPIO, RUTA, SYNC }

@Composable
fun ProLechBottomBar(
    current: BottomNavRoute,
    onDashboard: () -> Unit,
    onAcopio: () -> Unit,
    onRuta: () -> Unit,
    onSync: () -> Unit
) {
    val items = listOf(
        Triple(BottomNavRoute.DASHBOARD, "⌂", "Inicio"),
        Triple(BottomNavRoute.ACOPIO,    "☰", "Acopios"),
        Triple(BottomNavRoute.RUTA,      "◎", "Ruta"),
        Triple(BottomNavRoute.SYNC,      "↑", "Sync"),
    )
    val actions = listOf(onDashboard, onAcopio, onRuta, onSync)

    Column(modifier = Modifier.fillMaxWidth()) {
        HorizontalDivider(color = Color(0xFFE0E0E0), thickness = 0.5.dp)
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(Color.White)
                .padding(horizontal = 8.dp, vertical = 8.dp),
            horizontalArrangement = Arrangement.SpaceEvenly
        ) {
            items.forEachIndexed { i, (route, icon, label) ->
                val active = current == route
                Column(
                    modifier = Modifier
                        .weight(1f)
                        .clickable { actions[i]() },
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text(
                        text       = icon,
                        color      = if (active) GreenPrimary else TextMuted,
                        fontSize   = 20.sp,
                        fontWeight = if (active) FontWeight.Bold else FontWeight.Normal
                    )
                    Text(
                        text     = label,
                        color    = if (active) GreenPrimary else TextMuted,
                        fontSize = 10.sp,
                        fontWeight = if (active) FontWeight.Bold else FontWeight.Normal
                    )
                }
            }
        }
    }
}
