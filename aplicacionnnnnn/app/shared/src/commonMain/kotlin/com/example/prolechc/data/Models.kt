package com.example.prolechc.data

// ── Enums ────────────────────────────────────────────────────────────────────
enum class EstadoAcopio { APROBADO, RECHAZADO, OBSERVADO }
enum class EstadoSync   { PENDIENTE, ENVIANDO, ENVIADO }

// ── Modelo de un acopio (sesión actual + mock) ────────────────────────────────
data class AcopioRecord(
    val id: Int,
    val ganaderoId: Int           = 0,
    val ganaderoNombre: String,
    val dniGanadero: String,
    val litros: Double,
    val porcentajeAgua: Double,
    val acidezPh: Double,
    val densidad: Double?         = null,
    // ── Campos LACTOMAT completos ─────────────────────────────────────────────
    val grasa: Double?            = null,
    val proteina: Double?         = null,
    val lactosa: Double?          = null,
    val sng: Double?              = null,
    val sales: Double?            = null,
    val totalSolidos: Double?     = null,
    val puntoCongelacion: Double? = null,
    // ─────────────────────────────────────────────────────────────────────────
    val hora: String,
    val estado: EstadoAcopio,
    val syncEstado: EstadoSync  = EstadoSync.PENDIENTE,
    val ticketNumero: String    = "",
    val tieneDescuento: Boolean = false,
    val gpsLat: Double?         = null,
    val gpsLon: Double?         = null,
    val medicionBaldeConfirmada: Boolean = true,
    val equipoLactoscan: Int?   = null,
    val capacidadTachoLitros: Double? = null,
    /** UUID del acopio guardado offline en el celular (null = ya está en el servidor) */
    val clientUuid: String?     = null,
    /** Último error al enviarlo; si no es null, el servidor lo rechazó */
    val errorSync: String?      = null
)

data class ConductorInfo(
    val nombre: String,
    val vehiculo: String,
    val capacidadL: Double,
    val ruta: String,
    val sector: String
)

// ── Mock Data ─────────────────────────────────────────────────────────────────
val mockConductores = listOf(
    ConductorInfo("Carlos Mendoza Choque", "Volvo FMX · EO-492 (Cisterna 1,200L)", 1200.0, "A-1: Huata Centro – Costa", "Sector Norte"),
    ConductorInfo("Juan Pérez Ramos",      "Mercedes EO-115 (Cisterna 800L)",       800.0,  "A-2: Huata Sur – Capachica", "Sector Sur"),
    ConductorInfo("Pedro Quispe Mamani",   "Isuzu NPR · EO-237 (Cisterna 1,000L)", 1000.0, "B-1: Coata – Caracoto",      "Sector Este"),
)

// Acopios mock — solo para diseño/demo, el historial real viene del ViewModel
val mockAcopios: List<AcopioRecord> = listOf(
    AcopioRecord(
        id = 1, ganaderoNombre = "Juan Quispe Condori",    dniGanadero = "40201653",
        litros = 45.5,  porcentajeAgua = 1.2, acidezPh = 6.5, densidad = 1.030,
        hora = "06:14 AM", estado = EstadoAcopio.APROBADO,
        syncEstado = EstadoSync.ENVIADO,   ticketNumero = "TK-001",
        gpsLat = -15.8322, gpsLon = -70.1042
    ),
    AcopioRecord(
        id = 2, ganaderoNombre = "Martina Choque Flores",  dniGanadero = "29301823",
        litros = 120.0, porcentajeAgua = 1.8, acidezPh = 6.7, densidad = 1.029,
        hora = "06:45 AM", estado = EstadoAcopio.APROBADO,
        syncEstado = EstadoSync.PENDIENTE, ticketNumero = "TK-002"
    ),
    AcopioRecord(
        id = 3, ganaderoNombre = "Pedro Huaman Mamani",    dniGanadero = "40021145",
        litros = 35.0,  porcentajeAgua = 8.3, acidezPh = 5.9, densidad = 1.026,
        hora = "07:05 AM", estado = EstadoAcopio.RECHAZADO,
        syncEstado = EstadoSync.PENDIENTE, ticketNumero = "TK-003"
    ),
    AcopioRecord(
        id = 4, ganaderoNombre = "Sofía Belisario Colque", dniGanadero = "29641702",
        litros = 80.0,  porcentajeAgua = 4.5, acidezPh = 6.4, densidad = 1.031,
        hora = "07:22 AM", estado = EstadoAcopio.OBSERVADO,
        syncEstado = EstadoSync.ENVIANDO,  ticketNumero = "TK-004"
    ),
    AcopioRecord(
        id = 5, ganaderoNombre = "Justo Calloapa Quispe",  dniGanadero = "42718830",
        litros = 60.0,  porcentajeAgua = 1.5, acidezPh = 6.6, densidad = 1.030,
        hora = "07:51 AM", estado = EstadoAcopio.APROBADO,
        syncEstado = EstadoSync.PENDIENTE, ticketNumero = "TK-005",
        tieneDescuento = true
    ),
    AcopioRecord(
        id = 6, ganaderoNombre = "Belisaria Colque Sofía", dniGanadero = "38110025",
        litros = 80.0,  porcentajeAgua = 1.9, acidezPh = 6.5, densidad = 1.032,
        hora = "07:43 AM", estado = EstadoAcopio.APROBADO,
        syncEstado = EstadoSync.PENDIENTE, ticketNumero = "TK-006"
    ),
)

// DNI → nombre (autocompletado fallback cuando no hay ruta asignada)
val dniAutocompletado: Map<String, String> = mapOf(
    "40201653" to "Juan Quispe Condori",
    "29301823" to "Martina Choque Flores",
    "40021145" to "Pedro Huaman Mamani",
    "29641702" to "Sofía Belisario Colque",
    "42718830" to "Justo Calloapa Quispe",
    "45281093" to "Zenón Quispe Mamani",
    "38110025" to "Belisaria Colque Sofía",
)

const val CISTERNA_ACTUAL    = 850.0
const val CISTERNA_CAPACIDAD = 1200.0

val totalLitrosHoy  get() = mockAcopios.sumOf { it.litros }
val totalAcopiosHoy get() = mockAcopios.size
val pendientesSync  get() = mockAcopios.count { it.syncEstado == EstadoSync.PENDIENTE }
