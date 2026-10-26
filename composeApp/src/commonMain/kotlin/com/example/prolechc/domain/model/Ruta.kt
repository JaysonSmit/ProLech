package com.example.prolechc.domain.model

data class Ruta(
    val asignacionId: Int,
    val rutaCodigo: String,
    val rutaNombre: String,
    val vehiculoPlaca: String,
    val vehiculoMarca: String,
    val capacidadLitros: Double,
    val fecha: String,
    val paradas: List<Parada>,
    /** Punto de partida y regreso del recorrido. */
    val planta: Coordenada? = null,
    /** "zona" (rotación mensual), "manual", "historial" o "" si el backend es antiguo. */
    val origenRuta: String = "",
    /** "titular", "volante" o "sin_asignar" en la rotación del mes. */
    val rolMes: String = "",
    /** Camino que el administrador trazó en el panel; vacío = la app calcula uno. */
    val trazado: List<Coordenada> = emptyList()
)

data class Parada(
    val id: Int,
    val orden: Int,
    val ganaderoId: Int,
    val ganaderoNombre: String,
    val ganaderoNii: String,
    val direccion: String?,
    val geoLat: Double?,
    val geoLon: Double?,
    val atendido: Boolean,
    /** true cuando el proveedor está en estado "Suspendido" por faltas de calidad */
    val suspendido: Boolean = false
)
