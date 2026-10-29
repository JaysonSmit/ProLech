package com.example.prolechc.presentation.ruta

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import com.example.prolechc.domain.model.Coordenada
import com.example.prolechc.domain.model.Parada

enum class EstadoParada { SIGUIENTE, PENDIENTE, ATENDIDO, SUSPENDIDO }

/** Parada lista para dibujar: [numero] es su turno en el plan (null si ya no se visita). */
data class MarcadorParada(
    val parada: Parada,
    val numero: Int?,
    val estado: EstadoParada
)

/**
 * Mapa nativo de cada plataforma (osmdroid/OpenStreetMap en Android, MapKit en iOS).
 *
 * @param trazoPlan  camino sugerido a seguir (azul).
 * @param recorrido  camino realmente recorrido hoy según el GPS (caramelo).
 * @param centrarEn  cada vez que cambia el valor, el mapa vuelve a encuadrar.
 */
@Composable
expect fun MapaRuta(
    modifier: Modifier,
    planta: Coordenada?,
    marcadores: List<MarcadorParada>,
    trazoPlan: List<Coordenada>,
    recorrido: List<Coordenada>,
    miUbicacion: Coordenada?,
    centrarEn: Int,
    onParadaClick: (Parada) -> Unit
)

/** Devuelve una función que pide el permiso de ubicación; [onResultado] recibe si se concedió. */
@Composable
expect fun rememberSolicitarPermisoUbicacion(onResultado: (Boolean) -> Unit): () -> Unit
