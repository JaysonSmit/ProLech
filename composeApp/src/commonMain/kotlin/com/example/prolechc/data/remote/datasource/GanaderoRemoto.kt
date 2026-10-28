package com.example.prolechc.data.remote.datasource

import com.example.prolechc.data.mapper.toDomain
import com.example.prolechc.domain.model.GanaderoCampo

/** Fuente remota del catálogo de ganaderos de la ruta. */
fun interface GanaderoRemoto {
    suspend fun obtenerGanaderos(): List<GanaderoCampo>
}

/** Reutiliza GET /api/mi-ruta, que ya devuelve los ganaderos de la ruta del chofer. */
class GanaderoRemotoKtor(private val rutaRemote: RutaRemoteDataSource) : GanaderoRemoto {
    override suspend fun obtenerGanaderos(): List<GanaderoCampo> =
        rutaRemote.getMiRuta().toDomain().paradas.map { parada ->
            GanaderoCampo(
                id         = parada.ganaderoId.toLong(),
                nombre     = parada.ganaderoNombre,
                dni        = parada.ganaderoNii,
                suspendido = parada.suspendido,
                bloqueado  = parada.bloqueado,
                muestra    = parada.muestra
            )
        }
}
