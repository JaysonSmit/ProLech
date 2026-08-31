package com.example.myapplicationvacas.web.data

// ── Modelos de datos para la web ──────────────────────────────────────────────

enum class EstadoAcopio { APROBADO, OBSERVADO, RECHAZADO }
enum class EstadoInventario { EN_STOCK, AGOTADO, EN_PROCESO }

data class AcopioWeb(
    val id: Int,
    val hora: String,
    val ganadero: String,
    val ruta: String,
    val litros: Double,
    val calidad: EstadoAcopio,
    val sector: String
)

data class GanaderoWeb(
    val id: Int,
    val codigo: String,
    val nombre: String,
    val dni: String,
    val sector: String,
    val litrosMes: Double,
    val estado: String = "Activo"
)

data class ProductoInventario(
    val id: Int,
    val nombre: String,
    val lote: String,
    val cantidad: String,
    val unidad: String,
    val fechaProduccion: String,
    val fechaVencimiento: String,
    val responsable: String,
    val estado: EstadoInventario
)

data class LiquidacionGanadero(
    val id: Int,
    val ganadero: String,
    val codigo: String,
    val totalLitros: Double,
    val precioPorLitro: Double,
    val bonificacion: Double,
    val descuento: Double,
    val montoLiquidar: Double,
    val estado: String  // "Pendiente" | "Pagado"
)

data class PuntoMapa(
    val lat: Double,
    val lng: Double,
    val intensidad: Double,   // 0..1
    val ganadero: String
)

data class RutaMonitoreo(
    val nombre: String,
    val conductor: String,
    val camion: String,
    val litros: Int,
    val paradas: Int,
    val totalParadas: Int,
    val km: Int
)

data class Reunion(
    val id: Int,
    val titulo: String,
    val tipo: String,
    val fecha: String,
    val hora: String,
    val lugar: String,
    val participantes: Int,
    val tieneVideoconferencia: Boolean,
    val esExtraordinaria: Boolean = false
)

data class ProduccionSemanal(
    val dia: String,
    val rutaA: Double,
    val rutaB: Double
)

data class CategoriaCalidad(
    val nombre: String,
    val porcentaje: Double,
    val color: String
)

// ── Mock Data Web ─────────────────────────────────────────────────────────────
object WebMockData {

    val acopiosRecientes = listOf(
        AcopioWeb(1, "05:55 AM", "Juan Choque Condori",      "Ruta A-1: Costa", 85.0,  EstadoAcopio.APROBADO,  "Huata Centro"),
        AcopioWeb(2, "07:02 AM", "Bento Morani Quispe",      "Ruta A-1: Costa", 120.0, EstadoAcopio.APROBADO,  "Sectores Norte"),
        AcopioWeb(3, "07:30 AM", "Pedro Apaza Flores",       "Ruta B-1: Capachica", 65.0, EstadoAcopio.OBSERVADO, "Capachica Sec 2"),
        AcopioWeb(4, "07:12 AM", "Sofía Callo Beltrán",      "Ruta B-1: Costa", 94.0,  EstadoAcopio.APROBADO,  "Huata Centro"),
        AcopioWeb(5, "06:45 AM", "Francisco Vargas Laque",   "Ruta B-9: Capachica", 110.0, EstadoAcopio.RECHAZADO, "Lote Bajo"),
        AcopioWeb(6, "08:10 AM", "Martina Choque Flores",    "Ruta A-1: Costa", 75.0,  EstadoAcopio.APROBADO,  "Sectores Norte"),
        AcopioWeb(7, "08:33 AM", "Justo Calloapaza",         "Ruta C-3",        60.0,  EstadoAcopio.APROBADO,  "Sector D-1")
    )

    val ganaderos = listOf(
        GanaderoWeb(1, "GN-001", "Juan Choque Condori",      "45281093", "Huata Centro",    320.0),
        GanaderoWeb(2, "GN-002", "Bento Morani Quispe",      "29301852", "Sectores Norte",  480.0),
        GanaderoWeb(3, "GN-003", "Pedro Apaza Flores",       "40512763", "Capachica Sec 2", 210.0),
        GanaderoWeb(4, "GN-004", "Sofía Callo Beltrán",      "31874920", "Huata Centro",    390.0),
        GanaderoWeb(5, "GN-005", "Francisco Vargas Laque",   "52834901", "Lote Bajo",       175.0),
        GanaderoWeb(6, "GN-006", "Martina Choque Flores",    "37291840", "Sectores Norte",  560.0),
        GanaderoWeb(7, "GN-007", "Justo Calloapaza",         "48920341", "Sector D-1",      290.0)
    )

    val inventario = listOf(
        ProductoInventario(1, "Queso Andino Tradicional",  "LOTE QA-993",  "380",  "kg",      "01/02/2026", "05/04/2026", "Carlos Mendoza",  EstadoInventario.EN_STOCK),
        ProductoInventario(2, "Yogurt Natural Premium",    "LOTE YN-910",  "15",   "botellas", "24/02/2026", "26/03/2026", "Juana Callo",     EstadoInventario.EN_PROCESO),
        ProductoInventario(3, "Mantequilla Pasteurizada",  "LOTE MP-391",  "450",  "unidades", "19/02/2026", "14/03/2026", "Carlos Mendoza",  EstadoInventario.EN_STOCK),
        ProductoInventario(4, "Queso Paria Andino",        "LOTE QP-021",  "0",    "kg",       "09/02/2026", "09/04/2026", "María Quispe",    EstadoInventario.AGOTADO)
    )

    val produccionEnCurso = listOf(
        Triple("Queso Andino Tradicional", "LOTE QA-993", mapOf("Entrada" to "3,880 L", "Producido" to "380 kg",  "Fecha" to "03/02/2026", "Responsable" to "Carlos Mendoza", "Estado" to "Completado")),
        Triple("Yogurt Natural Premium",   "LOTE YN-910", mapOf("Entrada" to "450 L",   "Producido" to "15 bot.", "Fecha" to "24/02/2026", "Responsable" to "Juana Callo",    "Estado" to "En Proceso")),
        Triple("Mantequilla Pasteurizada", "LOTE MP-391", mapOf("Entrada" to "800 L",   "Producido" to "450 u.",  "Fecha" to "14/02/2026", "Responsable" to "Carlos Mendoza", "Estado" to "Completado"))
    )

    val liquidacion = listOf(
        LiquidacionGanadero(1, "Juan Choque Condori",    "06802713", 1240.0, 1.35, 124.0, 0.0,   1798.0, "Pendiente"),
        LiquidacionGanadero(2, "Benta Mamani Quispe",    "20221900", 2150.0, 1.35, 210.0, 45.00, 3107.59,"Pagado"),
        LiquidacionGanadero(3, "Pedro Apaza Flores",     "12641700", 800.0,  1.35, 60.0,  30.00, 1081.59,"Pagado"),
        LiquidacionGanadero(4, "Sofía Callo Beltrán",    "00902533", 1450.0, 1.35, 145.0, 0.0,   2302.50,"Pendiente")
    )

    val produccionSemanal = listOf(
        ProduccionSemanal("Lun", 520.0, 310.0),
        ProduccionSemanal("Mar", 680.0, 290.0),
        ProduccionSemanal("Mié", 490.0, 380.0),
        ProduccionSemanal("Jue", 750.0, 420.0),
        ProduccionSemanal("Vie", 610.0, 350.0),
        ProduccionSemanal("Sáb", 880.0, 460.0),
        ProduccionSemanal("Dom", 520.0, 280.0)
    )

    val distribucionCalidad = listOf(
        CategoriaCalidad("Aprobada (85%)", 85.0, "#00C853"),
        CategoriaCalidad("Observada (12%)", 12.0, "#FFD600"),
        CategoriaCalidad("Rechazada (3%)", 3.0,  "#FF5252")
    )

    val puntosMapa = listOf(
        PuntoMapa(-15.8322, -70.1042, 0.9, "Zona Alta Huata"),
        PuntoMapa(-15.8400, -70.0985, 0.7, "Capachica Norte"),
        PuntoMapa(-15.8250, -70.1150, 0.5, "Sector B"),
        PuntoMapa(-15.8500, -70.1100, 0.3, "Lote Bajo")
    )

    val rutaMonitoreo = RutaMonitoreo(
        nombre       = "Ruta A-1: Centro",
        conductor    = "Carlos Mendoza",
        camion       = "EO-492",
        litros       = 560,
        paradas      = 18,
        totalParadas = 25,
        km           = 46
    )

    val reuniones = listOf(
        Reunion(1, "Capacitación en Higiene y Control de Calidad", "Capacitación", "Martes, 10 de Febrero", "10:00 AM", "Local Comunal Huata Centro", 8, true),
        Reunion(2, "Asamblea General: Liquidación de Precios de Lluvia", "Ordinaria", "Jueves, 12 de Febrero", "02:20 PM", "Salón de Actos Municipalidad", 45, false),
        Reunion(3, "Reunión Extraordinaria: Mantenimiento de Cisterna Valvo", "Extraordinaria", "Sábado, 14 de Febrero", "10:00 AM", "Taller Mecánico Central", 5, true, true)
    )
}
