package com.example.myapplicationvacas.data

// ── Estado de calidad ─────────────────────────────────────────────────────────
enum class EstadoAcopio { APROBADO, OBSERVADO, RECHAZADO }

// ── Modelos de datos ──────────────────────────────────────────────────────────
data class Acopio(
    val id: Int,
    val ticketNumero: String,
    val nombreGanadero: String,
    val dniGanadero: String,
    val litros: Float,
    val temperatura: Float = 4.2f,
    val porcentajeAgua: Float,
    val acidezPh: Float,
    val hora: String,
    val fecha: String = "07/02/2026",
    val estado: EstadoAcopio,
    val motivoRechazo: String? = null,
    val pendienteSinc: Boolean = true
)

data class Ganadero(
    val dni: String,
    val nombre: String,
    val sector: String
)

data class Conductor(val id: Int, val nombre: String)
data class Camion(val id: Int, val placa: String, val descripcion: String)
data class Ruta(val id: Int, val nombre: String, val descripcion: String)

data class EstadoCisterna(
    val litrosActuales: Float,
    val capacidadTotal: Float
) {
    val porcentaje: Float get() = (litrosActuales / capacidadTotal).coerceIn(0f, 1f)
}

// ── Mock Data ─────────────────────────────────────────────────────────────────
object MockData {

    val conductores = listOf(
        Conductor(1, "Carlos Mendoza Choque"),
        Conductor(2, "Pedro Quispe Mamani"),
        Conductor(3, "Juan Flores Ccopa")
    )

    val camiones = listOf(
        Camion(1, "FMX – EO-492", "Cisterna 1,200L"),
        Camion(2, "M2M – AB-381", "Cisterna 800L"),
        Camion(3, "TRK – ZZ-101", "Cisterna 1,500L")
    )

    val rutas = listOf(
        Ruta(1, "Ruta A-1: Huata Centro – Costa", "15 paradas · 42 km"),
        Ruta(2, "Ruta B-2: Sectores Norte",       "10 paradas · 28 km"),
        Ruta(3, "Ruta C-3: Capachica",            "12 paradas · 35 km")
    )

    val ganaderos = listOf(
        Ganadero("45281093", "Zenón Quispe Condori",  "Huata Centro"),
        Ganadero("29301852", "Berta Mamani Quispe",   "Sector B-2"),
        Ganadero("40512763", "Pedro Apaza Flores",    "Capachica Sec 2"),
        Ganadero("31874920", "Sofía Callo Beltrán",   "Huata Centro"),
        Ganadero("48920341", "Justo Calloapaza",      "Sector D-1"),
        Ganadero("52834901", "Martina Choque Flores", "Lote Bajo"),
        Ganadero("37291840", "Pedro Huaman Mamani",   "Sector Norte")
    )

    val historialAcopios: MutableList<Acopio> = mutableListOf(
        Acopio(
            id             = 1,
            ticketNumero   = "ACO-000241",
            nombreGanadero = "Zenón Quispe Condori",
            dniGanadero    = "45281093",
            litros         = 45.5f,
            temperatura    = 4.2f,
            porcentajeAgua = 2.1f,
            acidezPh       = 4.2f,
            hora           = "06:14 AM",
            estado         = EstadoAcopio.APROBADO,
            pendienteSinc  = true
        ),
        Acopio(
            id             = 2,
            ticketNumero   = "ACO-000242",
            nombreGanadero = "Martina Choque Flores",
            dniGanadero    = "52834901",
            litros         = 120.0f,
            temperatura    = 3.8f,
            porcentajeAgua = 1.8f,
            acidezPh       = 4.5f,
            hora           = "07:02 AM",
            estado         = EstadoAcopio.APROBADO,
            pendienteSinc  = true
        ),
        Acopio(
            id             = 3,
            ticketNumero   = "ACO-000243",
            nombreGanadero = "Pedro Huaman Mamani",
            dniGanadero    = "37291840",
            litros         = 35.0f,
            temperatura    = 5.1f,
            porcentajeAgua = 5.9f,
            acidezPh       = 5.9f,
            hora           = "07:30 AM",
            estado         = EstadoAcopio.RECHAZADO,
            motivoRechazo  = "Presencia de grumos y acidez elevada.",
            pendienteSinc  = true
        ),
        Acopio(
            id             = 4,
            ticketNumero   = "ACO-000244",
            nombreGanadero = "Sofía Callo Beltrán",
            dniGanadero    = "31874920",
            litros         = 80.0f,
            temperatura    = 4.5f,
            porcentajeAgua = 3.2f,
            acidezPh       = 4.1f,
            hora           = "07:55 AM",
            estado         = EstadoAcopio.OBSERVADO,
            pendienteSinc  = false
        ),
        Acopio(
            id             = 5,
            ticketNumero   = "ACO-000245",
            nombreGanadero = "Justo Calloapaza",
            dniGanadero    = "48920341",
            litros         = 60.0f,
            temperatura    = 4.0f,
            porcentajeAgua = 1.5f,
            acidezPh       = 4.3f,
            hora           = "08:10 AM",
            estado         = EstadoAcopio.APROBADO,
            pendienteSinc  = false
        )
    )

    val cisterna = EstadoCisterna(litrosActuales = 850f, capacidadTotal = 4500f)
}
