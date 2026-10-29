package com.example.prolechc.data.archivos

import kotlinx.cinterop.ExperimentalForeignApi
import kotlinx.cinterop.addressOf
import kotlinx.cinterop.usePinned
import platform.Foundation.NSData
import platform.Foundation.NSFileManager
import platform.Foundation.dataWithContentsOfFile
import platform.posix.memcpy

@OptIn(ExperimentalForeignApi::class)
actual fun leerArchivo(ruta: String): ByteArray? {
    val datos = NSData.dataWithContentsOfFile(ruta) ?: return null
    val bytes = ByteArray(datos.length.toInt())
    if (bytes.isNotEmpty()) {
        bytes.usePinned { memcpy(it.addressOf(0), datos.bytes, datos.length) }
    }
    return bytes
}

actual fun borrarArchivo(ruta: String) {
    NSFileManager.defaultManager.removeItemAtPath(ruta, null)
}
