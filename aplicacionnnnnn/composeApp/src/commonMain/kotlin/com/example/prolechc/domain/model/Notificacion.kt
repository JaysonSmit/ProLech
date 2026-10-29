package com.example.prolechc.domain.model

data class Notificacion(
    val id: Int,
    val titulo: String,
    val mensaje: String,
    val tipo: String,
    val icono: String,
    val referenciaId: Int?,
    val createdAt: String,
    val leida: Boolean = false
) {
    /** "2026-10-29T15:26:54.000000Z" → "29/10 15:26" (hora del servidor). */
    val fechaCorta: String
        get() = Regex("""(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})""").find(createdAt)
            ?.destructured?.let { (_, m, d, h, mi) -> "$d/$m $h:$mi" } ?: ""
}
