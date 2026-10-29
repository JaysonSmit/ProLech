package com.example.prolechc.data.archivos

import java.io.File

actual fun leerArchivo(ruta: String): ByteArray? = File(ruta).takeIf { it.exists() }?.readBytes()

actual fun borrarArchivo(ruta: String) {
    File(ruta).delete()
}
