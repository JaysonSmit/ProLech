package com.example.myapplicationvacas.data

import kotlinx.serialization.Serializable

// ── Modelos serializables para la API ─────────────────────────────────────────

@Serializable
enum class EstadoAcopio { APROBADO, OBSERVADO, RECHAZADO }

@Serializable
data class AcopioDto(
    val id: Int,
    val ticketNumero: String,
    val nombreGanadero: String,
    val dniGanadero: String,
    val litros: Double,
    val temperatura: Double,
    val porcentajeAgua: Double,
    val acidezPh: Double,
    val hora: String,
    val fecha: String,
    val coordenadasGps: String,
    val estado: EstadoAcopio,
    val motivoRechazo: String?    = null,
    val rutaId: Int               = 1,
    val pendienteSinc: Boolean    = false
)

@Serializable
data class GanaderoDto(
    val id: Int,
    val dni: String,
    val nombre: String,
    val sector: String,
    val totalLitrosMes: Double    = 0.0,
    val estado: String            = "Activo"
)

@Serializable
data class ConductorDto(
    val id: Int,
    val nombre: String,
    val cargo: String             = "Chofer / Transportista"
)

@Serializable
data class CamionDto(
    val id: Int,
    val placa: String,
    val descripcion: String,
    val capacidadLitros: Int
)

@Serializable
data class RutaDto(
    val id: Int,
    val nombre: String,
    val descripcion: String
)

@Serializable
data class DashboardStats(
    val litrosHoy: Double,
    val acopiosHoy: Int,
    val ganaderosActivos: Int,
    val calidadPromedio: Double,
    val aprobados: Int,
    val observados: Int,
    val rechazados: Int
)

@Serializable
data class LiquidacionDto(
    val id: Int,
    val ganadero: String,
    val codigo: String,
    val totalLitros: Double,
    val precioPorLitro: Double,
    val bonificacion: Double,
    val descuento: Double,
    val montoLiquidar: Double,
    val estado: String
)

@Serializable
data class NuevoAcopioRequest(
    val dniGanadero: String,
    val litros: Double,
    val temperatura: Double,
    val porcentajeAgua: Double,
    val acidezPh: Double,
    val coordenadasGps: String    = "",
    val motivoRechazo: String?    = null
)

@Serializable
data class ApiResponse<T>(
    val success: Boolean,
    val data: T? = null,
    val message: String = "",
    val total: Int = 0
)

// ── Base de datos en memoria ──────────────────────────────────────────────────
object ServerData {

    val conductores = mutableListOf(
        ConductorDto(1, "Carlos Mendoza Choque"),
        ConductorDto(2, "Pedro Quispe Mamani"),
        ConductorDto(3, "Juan Flores Ccopa")
    )

    val camiones = mutableListOf(
        CamionDto(1, "FMX-EO-492", "Cisterna 1,200L", 1200),
        CamionDto(2, "M2M-AB-381", "Cisterna 800L",   800),
        CamionDto(3, "TRK-ZZ-101", "Cisterna 1,500L", 1500)
    )

    val rutas = mutableListOf(
        RutaDto(1, "Ruta A-1: Huata Centro – Costa", "15 paradas · 42 km"),
        RutaDto(2, "Ruta B-2: Sectores Norte",        "10 paradas · 28 km"),
        RutaDto(3, "Ruta C-3: Capachica",             "12 paradas · 35 km")
    )

    val ganaderos = mutableListOf(
        GanaderoDto(1, "45281093", "Zenón Quispe Condori",    "Huata Centro",    320.0),
        GanaderoDto(2, "29301852", "Berta Mamani Quispe",     "Sector B-2",      480.0),
        GanaderoDto(3, "40512763", "Pedro Apaza Flores",      "Capachica Sec 2", 210.0),
        GanaderoDto(4, "31874920", "Sofía Callo Beltrán",     "Huata Centro",    390.0),
        GanaderoDto(5, "48920341", "Justo Calloapaza",        "Sector D-1",      175.0),
        GanaderoDto(6, "52834901", "Martina Choque Flores",   "Lote Bajo",       560.0),
        GanaderoDto(7, "37291840", "Pedro Huaman Mamani",     "Sector Norte",    290.0)
    )

    val acopios = mutableListOf(
        AcopioDto(1, "ACO-000241", "Zenón Quispe Condori",   "45281093", 45.5,  4.2, 2.1, 4.2, "06:14 AM", "07/02/2026", "-15.8322,-70.1042", EstadoAcopio.APROBADO),
        AcopioDto(2, "ACO-000242", "Martina Choque Flores",  "52834901", 120.0, 3.8, 1.8, 4.5, "07:02 AM", "07/02/2026", "-15.8412,-70.0985", EstadoAcopio.APROBADO),
        AcopioDto(3, "ACO-000243", "Pedro Huaman Mamani",    "37291840", 35.0,  5.1, 5.9, 5.9, "07:30 AM", "07/02/2026", "-15.8500,-70.1100", EstadoAcopio.RECHAZADO, "Acidez elevada"),
        AcopioDto(4, "ACO-000244", "Sofía Callo Beltrán",    "31874920", 80.0,  4.5, 3.2, 4.1, "07:55 AM", "07/02/2026", "-15.8300,-70.1000", EstadoAcopio.OBSERVADO),
        AcopioDto(5, "ACO-000245", "Justo Calloapaza",       "48920341", 60.0,  4.0, 1.5, 4.3, "08:10 AM", "07/02/2026", "-15.8450,-70.1080", EstadoAcopio.APROBADO),
        AcopioDto(6, "ACO-000246", "Berta Mamani Quispe",    "29301852", 88.0,  4.1, 2.5, 4.4, "08:33 AM", "07/02/2026", "-15.8380,-70.1020", EstadoAcopio.APROBADO)
    )

    val liquidaciones = mutableListOf(
        LiquidacionDto(1, "Juan Choque Condori",   "06802713", 1240.0, 1.35, 124.0,  0.0,   1798.0,  "Pendiente"),
        LiquidacionDto(2, "Benta Mamani Quispe",   "20221900", 2150.0, 1.35, 210.0,  45.00, 3107.59, "Pagado"),
        LiquidacionDto(3, "Pedro Apaza Flores",    "12641700", 800.0,  1.35, 60.0,   30.00, 1081.59, "Pagado"),
        LiquidacionDto(4, "Sofía Callo Beltrán",   "00902533", 1450.0, 1.35, 145.0,  0.0,   2302.50, "Pendiente")
    )

    private var nextAcopioId = 7

    fun addAcopio(req: NuevoAcopioRequest): AcopioDto {
        val ganadero = ganaderos.find { it.dni == req.dniGanadero }
            ?: throw IllegalArgumentException("Ganadero no encontrado: ${req.dniGanadero}")

        val rechazado = req.porcentajeAgua > 5.0 || req.acidezPh > 5.5
        val estado = if (rechazado) EstadoAcopio.RECHAZADO else EstadoAcopio.APROBADO

        val nuevo = AcopioDto(
            id             = nextAcopioId++,
            ticketNumero   = "ACO-${nextAcopioId.toString().padStart(6, '0')}",
            nombreGanadero = ganadero.nombre,
            dniGanadero    = ganadero.dni,
            litros         = req.litros,
            temperatura    = req.temperatura,
            porcentajeAgua = req.porcentajeAgua,
            acidezPh       = req.acidezPh,
            hora           = java.text.SimpleDateFormat("hh:mm a", java.util.Locale.getDefault()).format(java.util.Date()),
            fecha          = java.text.SimpleDateFormat("dd/MM/yyyy").format(java.util.Date()),
            coordenadasGps = req.coordenadasGps,
            estado         = estado,
            motivoRechazo  = req.motivoRechazo ?: if (rechazado) "Parámetros fuera de rango" else null
        )
        acopios.add(0, nuevo)
        return nuevo
    }

    fun getDashboardStats(): DashboardStats {
        val litrosHoy  = acopios.sumOf { it.litros }
        val aprobados  = acopios.count { it.estado == EstadoAcopio.APROBADO }
        val observados = acopios.count { it.estado == EstadoAcopio.OBSERVADO }
        val rechazados = acopios.count { it.estado == EstadoAcopio.RECHAZADO }
        val calidad    = if (acopios.isNotEmpty()) (aprobados.toDouble() / acopios.size) * 100 else 0.0

        return DashboardStats(
            litrosHoy        = litrosHoy,
            acopiosHoy       = acopios.size,
            ganaderosActivos = ganaderos.size,
            calidadPromedio  = calidad,
            aprobados        = aprobados,
            observados       = observados,
            rechazados       = rechazados
        )
    }
}
