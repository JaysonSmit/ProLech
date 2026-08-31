package com.example.myapplicationvacas.web.components

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.myapplicationvacas.web.data.EstadoAcopio
import com.example.myapplicationvacas.web.theme.*

// ── Sidebar ───────────────────────────────────────────────────────────────────
data class NavItem(
    val route: String,
    val label: String,
    val icon: ImageVector,
    val badge: String? = null
)

val sidebarItems = listOf(
    NavItem("dashboard",    "Dashboard",    Icons.Filled.Dashboard),
    NavItem("acopios",      "Acopios",      Icons.Filled.Water),
    NavItem("ganaderos",    "Ganaderos",    Icons.Filled.People),
    NavItem("inventario",   "Inventario",   Icons.Filled.Inventory),
    NavItem("liquidacion",  "Liquidación",  Icons.Filled.Payments),
    NavItem("mapa",         "Mapa GPS",     Icons.Filled.Map),
    NavItem("reuniones",    "Reuniones",    Icons.Filled.Groups),
    NavItem("reportes",     "Reportes",     Icons.Filled.BarChart),
    NavItem("configuracion","Configuración",Icons.Filled.Settings)
)

@Composable
fun WebSidebar(
    currentRoute: String,
    onNavigate: (String) -> Unit
) {
    Column(
        modifier = Modifier
            .width(220.dp)
            .fillMaxHeight()
            .background(WebSurface)
            .border(width = 1.dp, color = WebBorder, shape = RoundedCornerShape(0.dp))
    ) {
        // Logo
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .background(WebGreenBg)
                .padding(horizontal = 20.dp, vertical = 18.dp)
        ) {
            Row(
                verticalAlignment     = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Box(
                    modifier         = Modifier.size(36.dp).clip(CircleShape).background(WebGreen),
                    contentAlignment = Alignment.Center
                ) {
                    Text("PL", style = MaterialTheme.typography.labelLarge.copy(color = Color.Black, fontWeight = FontWeight.ExtraBold, fontSize = 13.sp))
                }
                Column {
                    Text("ProLech", style = MaterialTheme.typography.titleMedium.copy(color = WebGreen, fontWeight = FontWeight.ExtraBold))
                    Text("ACOPIO DIGITAL RURAL", style = MaterialTheme.typography.labelSmall.copy(color = WebTextSecondary, letterSpacing = 1.sp, fontSize = 9.sp))
                }
            }
        }

        // Ubicación
        Row(
            modifier = Modifier.fillMaxWidth().background(WebSurface2).padding(horizontal = 16.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Icon(Icons.Filled.LocationOn, null, tint = WebTextSecondary, modifier = Modifier.size(12.dp))
            Text("Municipalidad Distrital de Huata · Cusco", style = MaterialTheme.typography.labelSmall.copy(fontSize = 10.sp), color = WebTextSecondary)
        }

        HorizontalDivider(color = WebBorder)

        // Nav items
        Column(modifier = Modifier.weight(1f).padding(vertical = 8.dp)) {
            sidebarItems.forEach { item ->
                SidebarNavItem(
                    item       = item,
                    isSelected = currentRoute == item.route,
                    onClick    = { onNavigate(item.route) }
                )
            }
        }

        HorizontalDivider(color = WebBorder)

        // Footer de usuario
        Row(
            modifier = Modifier.fillMaxWidth().padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Box(
                modifier         = Modifier.size(32.dp).clip(CircleShape).background(WebGreenBg),
                contentAlignment = Alignment.Center
            ) {
                Text("MQ", style = MaterialTheme.typography.labelSmall.copy(color = WebGreen, fontWeight = FontWeight.Bold))
            }
            Column {
                Text("Ing. Mario Quispe", style = MaterialTheme.typography.labelLarge.copy(fontSize = 12.sp, color = WebTextPrimary))
                Text("@admincentral", style = MaterialTheme.typography.labelSmall.copy(fontSize = 10.sp), color = WebTextSecondary)
            }
        }
    }
}

@Composable
private fun SidebarNavItem(item: NavItem, isSelected: Boolean, onClick: () -> Unit) {
    val bg     = if (isSelected) WebGreenBg else Color.Transparent
    val tint   = if (isSelected) WebGreen   else WebTextSecondary
    val border = if (isSelected) RoundedCornerShape(8.dp) else RoundedCornerShape(8.dp)

    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 8.dp, vertical = 2.dp)
            .clip(border)
            .background(bg)
            .border(if (isSelected) 1.dp else 0.dp, if (isSelected) WebGreen.copy(0.3f) else Color.Transparent, border)
            .clickable(onClick = onClick)
            .padding(horizontal = 12.dp, vertical = 10.dp),
        verticalAlignment     = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(10.dp)
    ) {
        Icon(imageVector = item.icon, contentDescription = null, tint = tint, modifier = Modifier.size(17.dp))
        Text(
            text  = item.label,
            style = MaterialTheme.typography.bodyMedium.copy(
                color      = if (isSelected) WebGreen else WebTextSecondary,
                fontWeight = if (isSelected) FontWeight.SemiBold else FontWeight.Normal,
                fontSize   = 13.sp
            ),
            modifier = Modifier.weight(1f)
        )
        if (item.badge != null) {
            Surface(shape = RoundedCornerShape(10.dp), color = WebOrange) {
                Text(
                    text     = item.badge,
                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                    style    = MaterialTheme.typography.labelSmall.copy(color = Color.Black, fontWeight = FontWeight.Bold, fontSize = 9.sp)
                )
            }
        }
    }
}

// ── Top bar compartido ────────────────────────────────────────────────────────
@Composable
fun WebTopBar(title: String, subtitle: String = "Municipalidad Distrital de Huata · Cusco") {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .background(WebSurface)
            .border(width = 1.dp, color = WebBorder)
            .padding(horizontal = 24.dp, vertical = 14.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment     = Alignment.CenterVertically
    ) {
        Column {
            Text(title,    style = MaterialTheme.typography.titleLarge)
            Text(subtitle, style = MaterialTheme.typography.bodyMedium)
        }
        Row(
            verticalAlignment     = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Buscador
            OutlinedTextField(
                value         = "",
                onValueChange = {},
                placeholder   = { Text("Buscar ganadero, info...", style = MaterialTheme.typography.bodyMedium) },
                leadingIcon   = { Icon(Icons.Filled.Search, null, tint = WebTextSecondary, modifier = Modifier.size(16.dp)) },
                modifier      = Modifier.width(220.dp).height(44.dp),
                singleLine    = true,
                shape         = RoundedCornerShape(8.dp),
                colors        = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor        = WebGreen,
                    unfocusedBorderColor      = WebBorder,
                    focusedTextColor          = WebTextPrimary,
                    unfocusedTextColor        = WebTextPrimary,
                    focusedContainerColor     = WebSurface2,
                    unfocusedContainerColor   = WebSurface2,
                    focusedPlaceholderColor   = WebTextSecondary,
                    unfocusedPlaceholderColor = WebTextSecondary
                )
            )
            // Notificaciones
            IconButton(onClick = {}) {
                Icon(Icons.Filled.Notifications, null, tint = WebTextSecondary)
            }
            // Estado central
            Surface(shape = RoundedCornerShape(8.dp), color = WebGreenBg) {
                Row(
                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    Box(modifier = Modifier.size(7.dp).clip(CircleShape).background(WebGreen))
                    Text("CENTRAL ONLINE", style = MaterialTheme.typography.labelSmall.copy(color = WebGreen, fontWeight = FontWeight.Bold, fontSize = 10.sp))
                }
            }
        }
    }
}

// ── KPI Card ──────────────────────────────────────────────────────────────────
@Composable
fun KpiCard(
    modifier: Modifier = Modifier,
    label: String,
    value: String,
    change: String,
    changePositive: Boolean = true,
    icon: ImageVector,
    iconColor: Color = WebGreen
) {
    Surface(
        modifier = modifier,
        shape    = RoundedCornerShape(12.dp),
        color    = WebCard
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .border(1.dp, WebBorder, RoundedCornerShape(12.dp))
                .padding(20.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                Text(label, style = MaterialTheme.typography.bodyMedium)
                Text(value, style = MaterialTheme.typography.headlineMedium.copy(fontWeight = FontWeight.ExtraBold, color = WebTextPrimary))
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    Icon(
                        imageVector = if (changePositive) Icons.Filled.TrendingUp else Icons.Filled.TrendingDown,
                        contentDescription = null,
                        tint = if (changePositive) WebGreen else WebRedLight,
                        modifier = Modifier.size(14.dp)
                    )
                    Text(
                        text  = change,
                        style = MaterialTheme.typography.labelSmall.copy(
                            color = if (changePositive) WebGreen else WebRedLight
                        )
                    )
                }
            }
            Box(
                modifier         = Modifier.size(48.dp).clip(RoundedCornerShape(12.dp)).background(iconColor.copy(alpha = 0.15f)),
                contentAlignment = Alignment.Center
            ) {
                Icon(imageVector = icon, contentDescription = null, tint = iconColor, modifier = Modifier.size(24.dp))
            }
        }
    }
}

// ── Tabla de acopios ─────────────────────────────────────────────────────────
@Composable
fun CalidadBadgeWeb(estado: EstadoAcopio) {
    val (text, color, bg) = when (estado) {
        EstadoAcopio.APROBADO  -> Triple("Aprobada",  WebGreen,    WebGreenBg)
        EstadoAcopio.OBSERVADO -> Triple("Observada", WebYellow,   WebYellowBg)
        EstadoAcopio.RECHAZADO -> Triple("Rechazada", WebRedLight, WebRedBg)
    }
    Surface(shape = RoundedCornerShape(6.dp), color = bg) {
        Text(
            text     = text,
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
            style    = MaterialTheme.typography.labelSmall.copy(color = color, fontWeight = FontWeight.Bold)
        )
    }
}

// ── Barra simple chart ────────────────────────────────────────────────────────
@Composable
fun SimpleBarChart(
    modifier: Modifier = Modifier,
    values: List<Pair<String, Double>>,
    color: Color = WebGreen
) {
    val maxVal = values.maxOfOrNull { it.second } ?: 1.0
    Row(
        modifier = modifier,
        horizontalArrangement = Arrangement.spacedBy(4.dp),
        verticalAlignment = Alignment.Bottom
    ) {
        values.forEach { (label, v) ->
            Column(
                modifier              = Modifier.weight(1f),
                horizontalAlignment   = Alignment.CenterHorizontally,
                verticalArrangement   = Arrangement.Bottom
            ) {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(((v / maxVal) * 100).dp)
                        .clip(RoundedCornerShape(topStart = 4.dp, topEnd = 4.dp))
                        .background(color.copy(alpha = 0.8f))
                )
                Spacer(Modifier.height(4.dp))
                Text(label, style = MaterialTheme.typography.labelSmall.copy(fontSize = 10.sp, textAlign = TextAlign.Center), maxLines = 1)
            }
        }
    }
}

// ── Donut chart mock ──────────────────────────────────────────────────────────
@Composable
fun DonutLegend(items: List<Triple<String, Double, Color>>) {
    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
        items.forEach { (label, pct, color) ->
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Box(modifier = Modifier.size(10.dp).clip(CircleShape).background(color))
                Text(label, style = MaterialTheme.typography.bodyMedium.copy(fontSize = 12.sp))
            }
        }
    }
}
