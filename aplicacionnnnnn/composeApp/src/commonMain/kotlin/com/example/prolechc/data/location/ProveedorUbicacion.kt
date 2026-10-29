package com.example.prolechc.data.location

import com.example.prolechc.domain.model.LecturaGps
import kotlinx.coroutines.flow.Flow

/** Acceso al GPS del celular. Cada plataforma lo implementa (Fused / CoreLocation). */
interface ProveedorUbicacion {
    /** true si el usuario ya concedió el permiso de ubicación. */
    fun tienePermiso(): Boolean

    /** Lecturas continuas mientras alguien esté recolectando el Flow. */
    fun lecturas(intervaloMs: Long = 5_000): Flow<LecturaGps>

    /** Última ubicación conocida (rápida, puede ser null). */
    suspend fun ultimaConocida(): LecturaGps?
}

/** Inicia/detiene el rastreo en segundo plano (servicio en primer plano en Android). */
interface ControlRastreoSegundoPlano {
    fun iniciar()
    fun detener()
}
