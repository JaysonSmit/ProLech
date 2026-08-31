package com.example.prolechc.data

// ── Enums ────────────────────────────────────────────────────────────────────
enum class EstadoAcopio { APROBADO, RECHAZADO, OBSERVADO }
enum class EstadoSync   { PENDIENTE, ENVIANDO, ENVIADO }

// ── Modelos ───────────────────────────────────────────────────────────────────
data class AcopioRecord(
    val id: Int,
    val ganaderoNombre: String,
    val dniGanadero: String,
    val litros: Double,
    val porcentajeAgua: Double,
    val acidezPh: Double,
    val hora: String,
    val estado: EstadoAcopio,
    val syncEstado: EstadoSync = EstadoSync.PENDIENTE
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
    ConductorInfo("Juan Pérez Ramos",      "Mercedes EO-115 (Cisterna 800L)",       800.0, "A-2: Huata Sur – Capachica", "Sector Sur"),
    ConductorInfo("Pedro Quispe Mamani",   "Isuzu NPR · EO-237 (Cisterna 1,000L)", 1000.0, "B-1: Coata – Caracoto",     "Sector Este"),
)

val mockAcopios: List<AcopioRecord> = listOf(
    AcopioRecord(1, "Juan Quispe Condori",     "40201653", 45.5,  1.2, 6.5, "06:14 AM", EstadoAcopio.APROBADO,  EstadoSync.ENVIADO),
    AcopioRecord(2, "Martina Choque Flores",   "29301823", 120.0, 1.8, 6.7, "06:45 AM", EstadoAcopio.APROBADO,  EstadoSync.PENDIENTE),
    AcopioRecord(3, "Pedro Huaman Mamani",     "40021145", 35.0,  8.3, 5.9, "07:05 AM", EstadoAcopio.RECHAZADO, EstadoSync.PENDIENTE),
    AcopioRecord(4, "Sofía Belisario Colque",  "29641702", 80.0,  4.5, 6.4, "07:22 AM", EstadoAcopio.OBSERVADO, EstadoSync.ENVIANDO),
    AcopioRecord(5, "Justo Calloapa Quispe",   "42718830", 60.0,  1.5, 6.6, "07:51 AM", EstadoAcopio.APROBADO,  EstadoSync.PENDIENTE),
    AcopioRecord(6, "Belisaria Colque Sofía",  "38110025", 80.0,  1.9, 6.5, "07:43 AM", EstadoAcopio.APROBADO,  EstadoSync.PENDIENTE),
)

// DNI → nombre (autocompletado)
val dniAutocompletado: Map<String, String> = mapOf(
    "40201653" to "Juan Quispe Condori",
    "29301823" to "Martina Choque Flores",
    "40021145" to "Pedro Huaman Mamani",
    "29641702" to "Sofía Belisario Colque",
    "42718830" to "Justo Calloapa Quispe",
    "45281093" to "Zenón Quispe Mamani",
    "38110025" to "Belisaria Colque Sofía",
)

// Cisterna del conductor actual
const val CISTERNA_ACTUAL   = 850.0
const val CISTERNA_CAPACIDAD = 1200.0

// Totales del día (mock calculado)
val totalLitrosHoy   get() = mockAcopios.sumOf { it.litros }
val totalAcopiosHoy  get() = mockAcopios.size
val pendientesSync   get() = mockAcopios.count { it.syncEstado == EstadoSync.PENDIENTE }
