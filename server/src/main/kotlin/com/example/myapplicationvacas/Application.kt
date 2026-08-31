package com.example.myapplicationvacas

import com.example.myapplicationvacas.data.NuevoAcopioRequest
import com.example.myapplicationvacas.data.ApiResponse
import com.example.myapplicationvacas.data.ServerData
import io.ktor.http.*
import io.ktor.serialization.kotlinx.json.*
import io.ktor.server.application.*
import io.ktor.server.engine.*
import io.ktor.server.netty.*
import io.ktor.server.plugins.contentnegotiation.*
import io.ktor.server.plugins.cors.routing.*
import io.ktor.server.plugins.statuspages.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import kotlinx.serialization.json.Json

fun main() {
    embeddedServer(Netty, port = 8080, host = "0.0.0.0", module = Application::module)
        .start(wait = true)
}

fun Application.module() {
    // ── Content Negotiation (JSON) ────────────────────────────────────────────
    install(ContentNegotiation) {
        json(Json {
            prettyPrint        = true
            isLenient          = true
            ignoreUnknownKeys  = true
            encodeDefaults     = true
        })
    }

    // ── CORS (permitir peticiones desde la web) ───────────────────────────────
    install(CORS) {
        anyHost()
        allowHeader(HttpHeaders.ContentType)
        allowHeader(HttpHeaders.Authorization)
        allowMethod(HttpMethod.Options)
        allowMethod(HttpMethod.Get)
        allowMethod(HttpMethod.Post)
        allowMethod(HttpMethod.Put)
        allowMethod(HttpMethod.Delete)
    }

    // ── Manejo de errores ────────────────────────────────────────────────────
    install(StatusPages) {
        exception<IllegalArgumentException> { call, cause ->
            call.respond(
                HttpStatusCode.BadRequest,
                ApiResponse<Unit>(success = false, message = cause.message ?: "Bad request")
            )
        }
        exception<Throwable> { call, cause ->
            call.respond(
                HttpStatusCode.InternalServerError,
                ApiResponse<Unit>(success = false, message = cause.message ?: "Internal error")
            )
        }
    }

    // ── Rutas ─────────────────────────────────────────────────────────────────
    routing {

        // Health check
        get("/") {
            call.respond(ApiResponse(success = true, message = "ProLech API v1.0 — Online"))
        }

        // ── Dashboard ─────────────────────────────────────────────────────────
        route("/api/v1") {

            get("/dashboard") {
                val stats = ServerData.getDashboardStats()
                call.respond(ApiResponse(success = true, data = stats))
            }

            // ── Acopios ───────────────────────────────────────────────────────
            route("/acopios") {
                // GET todos los acopios (con filtros opcionales)
                get {
                    val estado = call.request.queryParameters["estado"]
                    val dni    = call.request.queryParameters["dni"]
                    val fecha  = call.request.queryParameters["fecha"]

                    var lista = ServerData.acopios.toList()
                    if (!estado.isNullOrBlank()) {
                        lista = lista.filter { it.estado.name.equals(estado, ignoreCase = true) }
                    }
                    if (!dni.isNullOrBlank()) {
                        lista = lista.filter { it.dniGanadero == dni }
                    }
                    if (!fecha.isNullOrBlank()) {
                        lista = lista.filter { it.fecha == fecha }
                    }

                    call.respond(ApiResponse(success = true, data = lista, total = lista.size))
                }

                // GET acopio por ID
                get("/{id}") {
                    val id = call.parameters["id"]?.toIntOrNull()
                        ?: return@get call.respond(HttpStatusCode.BadRequest, ApiResponse<Unit>(success = false, message = "ID inválido"))
                    val acopio = ServerData.acopios.find { it.id == id }
                        ?: return@get call.respond(HttpStatusCode.NotFound, ApiResponse<Unit>(success = false, message = "Acopio no encontrado"))
                    call.respond(ApiResponse(success = true, data = acopio))
                }

                // POST nuevo acopio
                post {
                    val req = call.receive<NuevoAcopioRequest>()
                    val nuevo = ServerData.addAcopio(req)
                    call.respond(HttpStatusCode.Created, ApiResponse(success = true, data = nuevo, message = "Acopio registrado correctamente"))
                }

                // POST sincronización batch (offline → online)
                post("/sync") {
                    val lista = call.receive<List<NuevoAcopioRequest>>()
                    val creados = lista.map { ServerData.addAcopio(it) }
                    call.respond(ApiResponse(success = true, data = creados, total = creados.size, message = "${creados.size} acopios sincronizados"))
                }
            }

            // ── Ganaderos ─────────────────────────────────────────────────────
            route("/ganaderos") {
                get {
                    call.respond(ApiResponse(success = true, data = ServerData.ganaderos, total = ServerData.ganaderos.size))
                }
                get("/{dni}") {
                    val dni = call.parameters["dni"]
                        ?: return@get call.respond(HttpStatusCode.BadRequest, ApiResponse<Unit>(success = false, message = "DNI requerido"))
                    val ganadero = ServerData.ganaderos.find { it.dni == dni }
                        ?: return@get call.respond(HttpStatusCode.NotFound, ApiResponse<Unit>(success = false, message = "Ganadero no encontrado"))
                    call.respond(ApiResponse(success = true, data = ganadero))
                }
                get("/{dni}/acopios") {
                    val dni = call.parameters["dni"] ?: return@get call.respond(HttpStatusCode.BadRequest, ApiResponse<Unit>(success = false, message = "DNI requerido"))
                    val acopios = ServerData.acopios.filter { it.dniGanadero == dni }
                    call.respond(ApiResponse(success = true, data = acopios, total = acopios.size))
                }
            }

            // ── Conductores ───────────────────────────────────────────────────
            get("/conductores") {
                call.respond(ApiResponse(success = true, data = ServerData.conductores, total = ServerData.conductores.size))
            }

            // ── Camiones ──────────────────────────────────────────────────────
            get("/camiones") {
                call.respond(ApiResponse(success = true, data = ServerData.camiones, total = ServerData.camiones.size))
            }

            // ── Rutas ─────────────────────────────────────────────────────────
            get("/rutas") {
                call.respond(ApiResponse(success = true, data = ServerData.rutas, total = ServerData.rutas.size))
            }

            // ── Liquidación ───────────────────────────────────────────────────
            route("/liquidacion") {
                get {
                    val periodo = call.request.queryParameters["periodo"] ?: "quincenal"
                    call.respond(ApiResponse(success = true, data = ServerData.liquidaciones, total = ServerData.liquidaciones.size))
                }
                post("/{id}/pagar") {
                    val id = call.parameters["id"]?.toIntOrNull()
                        ?: return@post call.respond(HttpStatusCode.BadRequest, ApiResponse<Unit>(success = false, message = "ID inválido"))
                    val item = ServerData.liquidaciones.find { it.id == id }
                        ?: return@post call.respond(HttpStatusCode.NotFound, ApiResponse<Unit>(success = false, message = "Liquidación no encontrada"))
                    val index = ServerData.liquidaciones.indexOf(item)
                    ServerData.liquidaciones[index] = item.copy(estado = "Pagado")
                    call.respond(ApiResponse(success = true, data = ServerData.liquidaciones[index], message = "Pago registrado"))
                }
            }

            // ── Stats y reportes ──────────────────────────────────────────────
            get("/reportes/produccion") {
                val dataSemanal = listOf(
                    mapOf("dia" to "Lun", "rutaA" to 520.0, "rutaB" to 310.0),
                    mapOf("dia" to "Mar", "rutaA" to 680.0, "rutaB" to 290.0),
                    mapOf("dia" to "Mié", "rutaA" to 490.0, "rutaB" to 380.0),
                    mapOf("dia" to "Jue", "rutaA" to 750.0, "rutaB" to 420.0),
                    mapOf("dia" to "Vie", "rutaA" to 610.0, "rutaB" to 350.0),
                    mapOf("dia" to "Sáb", "rutaA" to 880.0, "rutaB" to 460.0),
                    mapOf("dia" to "Dom", "rutaA" to 520.0, "rutaB" to 280.0)
                )
                call.respond(ApiResponse(success = true, data = dataSemanal))
            }
        }
    }
}
