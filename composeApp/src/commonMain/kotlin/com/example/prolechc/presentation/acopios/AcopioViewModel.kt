package com.example.prolechc.presentation.acopios

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.archivos.borrarArchivo
import com.example.prolechc.data.dispositivo.AccionesTicket
import com.example.prolechc.data.dispositivo.LectorLactoscan
import com.example.prolechc.data.network.MonitorConectividad
import com.example.prolechc.data.sync.SyncManager
import com.example.prolechc.domain.Tiempo
import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.domain.model.EstadoSync
import com.example.prolechc.domain.model.GanaderoCampo
import com.example.prolechc.domain.ticket.Ticket
import com.example.prolechc.domain.usecase.ActualizarAcopioUseCase
import com.example.prolechc.domain.usecase.EliminarAcopioUseCase
import com.example.prolechc.domain.usecase.LogoutUseCase
import com.example.prolechc.domain.usecase.ObservarAcopiosUseCase
import com.example.prolechc.domain.usecase.ObservarGanaderosUseCase
import com.example.prolechc.domain.usecase.RegistrarAcopioUseCase
import com.example.prolechc.domain.usecase.SincronizarAcopiosUseCase
import com.example.prolechc.presentation.formato
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

/** Campos del formulario tal como los escribe el chofer (texto crudo). */
data class FormularioAcopio(
    val ganaderoId: String     = "",
    val ganaderoNombre: String = "",
    val litros: String         = "",
    val porcentajeAgua: String = "0",
    val densidad: String       = "1.030",
    val grasa: String          = "",
    /** Foto de evidencia tomada para este acopio (ruta local). */
    val fotoLocal: String?     = null,
    /** Acopio que se está editando; null = registro nuevo. */
    val editando: Acopio?      = null
) {
    /** Vista previa de la calidad mientras se escriben los datos del Lactoscan. */
    val calidadPrevia: EstadoCalidad?
        get() {
            val agua = porcentajeAgua.aNumero() ?: return null
            val dens = densidad.aNumero() ?: return null
            return EstadoCalidad.evaluar(agua, dens)
        }
}

/** Datos de la ruta que necesita la pantalla (capacidad del camión y nombres del ticket). */
data class ContextoRuta(
    val chofer: String = "",
    val ruta: String = "",
    val capacidadLitros: Double = 0.0
)

/** Estado de la pantalla de acopios en campo. */
data class AcopioUiState(
    val acopios: List<Acopio>          = emptyList(),
    val ganaderos: List<GanaderoCampo> = emptyList(),
    val formulario: FormularioAcopio   = FormularioAcopio(),
    val enLinea: Boolean               = false,
    val sincronizando: Boolean         = false,
    val mensaje: String?               = null,
    val contexto: ContextoRuta         = ContextoRuta(),
    val inicioHoy: Long                = 0,
    val leyendoLactoscan: Boolean      = false
) {
    val pendientes: Int get() = acopios.count { it.estadoSync != EstadoSync.SINCRONIZADO }
    val conError: Int get() = acopios.count { it.errorSync != null }
    /** Solo lo de HOY: la base local también guarda el historial bajado del servidor. */
    val acopiosHoy: List<Acopio> get() = acopios.filter { it.actualizadoEn >= inicioHoy }
    val litrosHoy: Double get() = acopiosHoy.sumOf { it.litros }
    /** 0..1 de la capacidad del camión (null si no se conoce la capacidad). */
    val ocupacionCamion: Double?
        get() = contexto.capacidadLitros.takeIf { it > 0 }?.let { (litrosHoy / it).coerceAtMost(1.5) }
}

class AcopioViewModel(
    observarAcopios: ObservarAcopiosUseCase,
    observarGanaderos: ObservarGanaderosUseCase,
    private val registrarAcopio: RegistrarAcopioUseCase,
    private val actualizarAcopio: ActualizarAcopioUseCase,
    private val eliminarAcopio: EliminarAcopioUseCase,
    private val sincronizarAcopios: SincronizarAcopiosUseCase,
    private val logout: LogoutUseCase,
    monitor: MonitorConectividad,
    syncManager: SyncManager,
    private val reloj: () -> Long = { Tiempo.ahoraMs() },
    private val lector: LectorLactoscan? = null,
    private val accionesTicket: AccionesTicket? = null,
    contexto: kotlinx.coroutines.flow.Flow<ContextoRuta> = flowOf(ContextoRuta())
) : ViewModel() {

    private val mensaje = MutableStateFlow<String?>(null)
    private val formulario = MutableStateFlow(FormularioAcopio())
    private val leyendo = MutableStateFlow(false)
    private var avisoCapacidadDado = false

    /** La UI solo lee de SQLDelight (Single Source of Truth). */
    val uiState: StateFlow<AcopioUiState> = combine(
        observarAcopios(),
        observarGanaderos(),
        combine(monitor.enLinea, syncManager.sincronizando, leyendo, ::Triple),
        contexto,
        combine(formulario, mensaje) { form, msg -> form to msg }
    ) { acopios, ganaderos, (enLinea, sincronizando, leyendoOcr), ctx, (form, msg) ->
        AcopioUiState(
            acopios, ganaderos, form, enLinea, sincronizando, msg, ctx,
            inicioHoy = Tiempo.inicioDelDiaMs(reloj()),
            leyendoLactoscan = leyendoOcr
        )
    }.stateIn(
        scope        = viewModelScope,
        started      = SharingStarted.WhileSubscribed(5_000),
        initialValue = AcopioUiState(enLinea = monitor.hayConexion())
    )

    // ── Formulario ────────────────────────────────────────────────────────────

    fun onGanaderoSeleccionado(ganadero: GanaderoCampo) {
        if (ganadero.suspendido) {
            mensaje.value = "${ganadero.nombre} está suspendido por calidad: no se puede acopiar"
            return
        }
        formulario.update {
            it.copy(ganaderoId = ganadero.id.toString(), ganaderoNombre = ganadero.nombre)
        }
    }

    fun onGanaderoId(valor: String) = formulario.update { it.copy(ganaderoId = valor) }
    fun onGanaderoNombre(valor: String) = formulario.update { it.copy(ganaderoNombre = valor) }
    fun onLitros(valor: String) = formulario.update { it.copy(litros = valor) }
    fun onPorcentajeAgua(valor: String) = formulario.update { it.copy(porcentajeAgua = valor) }
    fun onDensidad(valor: String) = formulario.update { it.copy(densidad = valor) }
    fun onGrasa(valor: String) = formulario.update { it.copy(grasa = valor) }

    /** Foto de evidencia del balde / leche rechazada. */
    fun onFotoEvidencia(ruta: String?) {
        if (ruta == null) return
        formulario.update { it.copy(fotoLocal = ruta) }
        mensaje.value = "📷 Foto adjuntada"
    }

    fun quitarFoto() = formulario.update { it.copy(fotoLocal = null) }

    /**
     * Foto de la pantalla/ticket del Lactoscan → OCR → llena agua, densidad y grasa.
     * La misma foto queda como evidencia del acopio.
     */
    fun onFotoLactoscan(ruta: String?) {
        if (ruta == null) return
        val lectorOcr = lector ?: run { onFotoEvidencia(ruta); return }
        viewModelScope.launch {
            leyendo.value = true
            lectorOcr.leer(ruta)
                .onSuccess { r ->
                    if (r.vacio) {
                        mensaje.value = "No se reconocieron valores. Acerca la cámara y evita reflejos."
                    } else {
                        formulario.update {
                            it.copy(
                                porcentajeAgua = r.aguaAniadida?.let { v -> formato(v, 1) } ?: it.porcentajeAgua,
                                densidad       = r.densidad?.let { v -> formato(v, 3) } ?: it.densidad,
                                grasa          = r.grasa?.let { v -> formato(v, 1) } ?: it.grasa,
                                fotoLocal      = ruta
                            )
                        }
                        mensaje.value = "🔎 Lactoscan leído: revisa los valores antes de guardar"
                    }
                }
                .onFailure { mensaje.value = "No se pudo leer el Lactoscan: ${it.message}" }
            leyendo.value = false
        }
    }

    fun editar(acopio: Acopio) {
        formulario.value = FormularioAcopio(
            ganaderoId     = acopio.ganaderoId.toString(),
            ganaderoNombre = acopio.ganaderoNombre,
            litros         = acopio.litros.toString(),
            porcentajeAgua = acopio.porcentajeAgua.toString(),
            densidad       = acopio.densidad.toString(),
            grasa          = acopio.grasa?.toString().orEmpty(),
            fotoLocal      = acopio.fotoLocal,
            editando       = acopio
        )
        acopio.errorSync?.let { mensaje.value = "Corrige el dato: $it" }
    }

    fun cancelarEdicion() {
        formulario.value = FormularioAcopio()
    }

    fun guardar() {
        val form = formulario.value
        val id   = form.ganaderoId.trim().toLongOrNull()
        val lts  = form.litros.aNumero()
        val agua = form.porcentajeAgua.aNumero()
        val dens = form.densidad.aNumero()
        val grasa = form.grasa.takeIf { it.isNotBlank() }?.aNumero()
        if (id == null || lts == null || agua == null || dens == null) {
            mensaje.value = "Completa todos los campos con valores numéricos"
            return
        }
        if (form.grasa.isNotBlank() && grasa == null) {
            mensaje.value = "La grasa debe ser un número (ej. 3.5)"
            return
        }
        if (uiState.value.ganaderos.any { it.id == id && it.suspendido }) {
            mensaje.value = "El ganadero está suspendido por calidad: no se puede acopiar"
            return
        }
        // Regla del negocio (igual que POST /api/acopios): una entrega por ganadero al día
        if (form.editando == null) {
            val inicioHoy = Tiempo.inicioDelDiaMs(reloj())
            uiState.value.acopios.firstOrNull { it.ganaderoId == id && it.actualizadoEn >= inicioHoy }?.let { previo ->
                mensaje.value = "${previo.ganaderoNombre} ya entregó hoy (${previo.litros} L). " +
                    "Toca ese registro para corregirlo."
                return
            }
        }
        viewModelScope.launch {
            val editando = form.editando
            val resultado = if (editando == null) {
                registrarAcopio(id, form.ganaderoNombre, lts, agua, dens, grasa, form.fotoLocal)
            } else {
                // Si se cambió la foto, la anterior ya no sirve
                if (editando.fotoLocal != null && editando.fotoLocal != form.fotoLocal) borrarArchivo(editando.fotoLocal)
                actualizarAcopio(
                    editando.copy(
                        ganaderoId     = id,
                        ganaderoNombre = form.ganaderoNombre.trim(),
                        litros         = lts,
                        porcentajeAgua = agua,
                        densidad       = dens,
                        grasa          = grasa,
                        fotoLocal      = form.fotoLocal
                    )
                )
            }
            resultado
                .onSuccess { acopio ->
                    // Se conserva el ganadero para registros seguidos de la misma parada
                    formulario.value = FormularioAcopio(
                        ganaderoId     = form.ganaderoId,
                        ganaderoNombre = form.ganaderoNombre
                    ).takeIf { editando == null } ?: FormularioAcopio()
                    val accion = if (editando == null) "guardado" else "actualizado"
                    mensaje.value = avisoCapacidad() ?: if (uiState.value.enLinea) {
                        "Acopio $accion (${acopio.estadoCalidad.name})"
                    } else {
                        "Acopio $accion sin conexión: se subirá al recuperar la red"
                    }
                }
                .onFailure { mensaje.value = it.message ?: "No se pudo guardar el acopio" }
        }
    }

    /** Avisa una sola vez cuando el camión pasa el 90 % de su capacidad. */
    private fun avisoCapacidad(): String? {
        val estado = uiState.value
        val ocupacion = estado.ocupacionCamion ?: return null
        if (ocupacion < 0.9 || avisoCapacidadDado) return null
        avisoCapacidadDado = true
        return "🚛 Camión al ${(ocupacion * 100).toInt()} %: quedan " +
            "${formato((estado.contexto.capacidadLitros - estado.litrosHoy).coerceAtLeast(0.0), 0)} L de capacidad"
    }

    // ── Ticket para el ganadero ──────────────────────────────────────────────

    private fun ticketDe(acopio: Acopio): Ticket {
        val ctx = uiState.value.contexto
        return Ticket.de(acopio, chofer = ctx.chofer.ifBlank { "Chofer ProLech" }, ruta = ctx.ruta.ifBlank { "-" })
    }

    fun compartirTicket(acopio: Acopio) {
        val acciones = accionesTicket ?: return
        viewModelScope.launch {
            acciones.compartir(ticketDe(acopio))
                .onFailure { mensaje.value = "No se pudo generar el ticket: ${it.message}" }
        }
    }

    fun imprimirTicket(acopio: Acopio) {
        val acciones = accionesTicket ?: return
        viewModelScope.launch {
            mensaje.value = "🖨️ Imprimiendo…"
            acciones.imprimir(ticketDe(acopio))
                .onSuccess { mensaje.value = "Ticket impreso" }
                .onFailure { mensaje.value = "No se pudo imprimir: ${it.message}" }
        }
    }

    val puedeImprimir: Boolean get() = accionesTicket?.puedeImprimir == true

    // ── Acciones de la lista ─────────────────────────────────────────────────

    fun eliminar(id: String) {
        viewModelScope.launch {
            if (formulario.value.editando?.id == id) cancelarEdicion()
            eliminarAcopio(id)
                .onSuccess { mensaje.value = "Acopio eliminado" }
                .onFailure { mensaje.value = it.message ?: "No se pudo eliminar" }
        }
    }

    fun sincronizar() {
        viewModelScope.launch {
            val r = sincronizarAcopios()
            mensaje.value = when {
                r.sinConexion   -> "Sin conexión: los cambios siguen guardados en el celular"
                r.sesionVencida -> "Tu sesión venció: vuelve a ingresar (no se perdió nada)"
                r.sinSesion     -> "Sesión expirada: vuelve a iniciar sesión"
                r.conError > 0  -> "${r.conError} registro(s) rechazado(s) por el servidor: tócalos para corregirlos"
                r.fallidos > 0  -> "Sincronización parcial: se reintentará ${r.fallidos} registro(s)"
                else            -> "Sincronizado: ${r.subidos} subidos, ${r.bajados} actualizados"
            }
        }
    }

    /** No deja salir con cambios sin subir: se perderían para el servidor. */
    fun cerrarSesion(onSesionCerrada: () -> Unit) {
        val pendientes = uiState.value.pendientes
        if (pendientes > 0) {
            mensaje.value = "Tienes $pendientes cambios sin sincronizar. Sincroniza antes de salir."
            return
        }
        viewModelScope.launch {
            logout()
            onSesionCerrada()
        }
    }

    fun limpiarMensaje() {
        mensaje.update { null }
    }
}

/** Acepta coma o punto decimal (teclados en español). */
internal fun String.aNumero(): Double? = trim().replace(',', '.').toDoubleOrNull()
