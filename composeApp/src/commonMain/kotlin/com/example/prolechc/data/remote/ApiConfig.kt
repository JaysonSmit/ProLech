package com.example.prolechc.data.remote

object ApiConfig {
    /** Valor inicial; el chofer puede cambiarlo en el login ("Servidor") sin recompilar. */
    const val SERVIDOR_POR_DEFECTO = "http://192.168.1.5:8000"

    /** URL del backend Laravel en uso (se carga de SQLDelight al iniciar la app). */
    var BASE_URL: String = SERVIDOR_POR_DEFECTO
        private set

    /** Normaliza lo que escribe el usuario: agrega http:// y quita la "/" final. */
    fun normalizar(url: String): String {
        val limpia = url.trim().trimEnd('/')
        return if (limpia.startsWith("http://") || limpia.startsWith("https://")) limpia else "http://$limpia"
    }

    fun cambiarServidor(url: String) {
        BASE_URL = normalizar(url)
    }

    // Endpoints
    const val LOGIN            = "/api/login"
    const val LOGOUT           = "/api/logout"
    const val MI_RUTA          = "/api/mi-ruta"
    const val ACOPIOS          = "/api/acopios"
    const val HISTORIAL        = "/api/acopios/historial"
    const val TRACKING         = "/api/tracking"
    const val TRACKING_LOTE    = "/api/tracking/lote"
    const val SOLICITAR_RUTA   = "/api/solicitar-ruta"
    const val NOTIFICACIONES   = "/api/notificaciones"
    fun notificacionLeer(id: Int)  = "/api/notificaciones/$id/leer"
    const val NOTIFICACIONES_LEER_TODAS = "/api/notificaciones/leer-todas"

    // Acopios en campo (offline-first, id = UUID generado en el celular)
    const val ACOPIOS_CAMPO = "/api/acopios-campo"
    fun acopioCampo(uuid: String) = "/api/acopios-campo/$uuid"
    fun ubicacionGanadero(id: Int) = "/api/ganaderos/$id/ubicacion"
    fun fotoAcopio(uuid: String) = "/api/acopios-campo/$uuid/foto"
}
