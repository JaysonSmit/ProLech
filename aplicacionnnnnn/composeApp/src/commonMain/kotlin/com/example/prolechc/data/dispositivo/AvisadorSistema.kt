package com.example.prolechc.data.dispositivo

/** Muestra un aviso del administrador (reunión, comunicado…) como notificación del celular. */
interface AvisadorSistema {
    fun mostrar(id: Int, titulo: String, mensaje: String)
}
