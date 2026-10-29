package com.example.prolechc.data.archivos

/** Lee un archivo local del celular (null si ya no existe). */
expect fun leerArchivo(ruta: String): ByteArray?

/** Borra un archivo local (fotos ya subidas o descartadas). */
expect fun borrarArchivo(ruta: String)
