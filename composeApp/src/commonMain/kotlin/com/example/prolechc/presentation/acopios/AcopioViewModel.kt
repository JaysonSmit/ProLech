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
import com.example.prolechc.domain.model.EstadoSync
import com.example.prolechc.domain.model.GanaderoCampo
import com.example.prolechc.domain.model.Observacion
import com.example.prolechc.domain.ticket.Ticket
import com.example.prolechc.domain.usecase.ActualizarAcopioUseCase
import com.example.prolechc.domain.usecase.EliminarAcopioUseCase
import com.example.prolechc.domain.usecase.LogoutUseCase
import com.example.prolechc.domain.usecase.ObservarAcopiosUseCase
import com.example.prolechc.domain.usecase.ObservarGanaderosUseCase
import com.example.prolechc.domain.usecase.RegistrarAcopioUseCase
import com.example.prolechc.domain.usecase.SincronizarAcopiosUseCase
import com.example.prolechc.presentation.NivelCarga
import com.example.prolechc.presentation.formato
import com.example.prolechc.presentation.nivelCarga
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
    /** Lo que el chofer ve a simple vista; la calidad la mide la planta con muestras sorpresa. */
    val observacion: Observacion = Observacion.NORMAL,
    val observacionNota: String  = "",
    /** Frasco que la planta pidió llenar en esta entrega (null = hoy no toca). */
    val muestraCodigo: String? = null,
    /** Foto de evidencia tomada para este acopio (ruta local). */
    val fotoLocal: String?     = null,
    /** Unidad con Lactoscan portátil: el chofer puede anotar (o leer por OCR) agua, densidad y grasa. */
    val conLactoscan: Boolean  = false,
    val porcentajeAgua: String = "",
    val densidad: String       = "",
    val grasa: String          = "",
    /** Acopio que se está editando; null = registro nuevo. */
    val editando: Acopio?      = null
)

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
    private val accionesTicket: AccionesTicket? = null,
    private val lector: LectorLactoscan? = null,
    contexto: kotlinx.coroutines.flow.Flow<ContextoRuta> = flowOf(ContextoRuta())
) : ViewModel() {

    private val mensaje = MutableStateFlow<String?>(null)
    private val formulario = MutableStateFlow(FormularioAcopio())
    private val leyendo = MutableStateFlow(false)
    private var nivelAvisado = NivelCarga.NORMAL

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
        motivoNoEntrega(ganadero)?.let { mensaje.value = it; return }
        formulario.update {
            it.copy(ganaderoId = ganadero.id.toString(), ganaderoNombre = ganadero.nombre, muestraCodigo = ganadero.muestra)
        }
        ganadero.muestra?.let { mensaje.value = "🧪 Hoy toca muestra: llena el frasco $it con su leche" }
    }

    private fun motivoNoEntrega(g: GanaderoCampo): String? = when {
        g.suspendido    -> "${g.nombre} está suspendido por calidad: no se puede acopiar"
        g.bloqueado > 0 -> "${g.nombre} no puede entregar por sanción de calidad (faltan ${g.bloqueado} entrega(s))"
        else            -> null
    }

    fun onGanaderoId(valor: String) = formulario.update { it.copy(ganaderoId = valor) }
    fun onGanaderoNombre(valor: String) = formulario.update { it.copy(ganaderoNombre = valor) }
    fun onLitros(valor: String) = formulario.update { it.copy(litros = valor) }
    fun onObservacion(valor: Observacion) = formulario.update { it.copy(observacion = valor) }
    fun onObservacionNota(valor: String) = formulario.update { it.copy(observacionNota = valor) }

    /** Foto de evidencia del balde / leche rechazada. */
    fun onFotoEvidencia(ruta: String?) {
        if (ruta == null) return
        formulario.update { it.copy(fotoLocal = ruta) }
        mensaje.value = "📷 Foto adjuntada"
    }

    fun quitarFoto() = formulario.update { it.copy(fotoLocal = null) }

    // ── Lactoscan portátil (opcional) ─────────────────────────────────────────

    fun onConLactoscan(valor: Boolean) = formulario.update { it.copy(conLactoscan = valor) }
    fun onPorcentajeAgua(valor: String) = formulario.update { it.copy(porcentajeAgua = valor) }
    fun onDensidad(valor: String) = formulario.update { it.copy(densidad = valor) }
    fun onGrasa(valor: String) = formulario.update { it.copy(grasa = valor) }

    /** Foto de la pantalla del Lactoscan → OCR en el celular (sin señal) → agua, densidad y grasa. */
    fun onFotoLactoscan(ruta: String?) {
        if (ruta == null) return
        val ocr = lector ?: run { onFotoEvidencia(ruta); return }
        viewModelScope.launch {
            leyendo.value = true
            ocr.leer(ruta)
                .onSuccess { r ->
                    if (r.vacio) {
                        mensaje.value = "No se reconocieron valores. Acerca la cámara y evita reflejos."
                    } else {
                        formulario.update {
                            it.copy(
                                conLactoscan   = true,
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
            observacion    = Observacion.de(acopio.observacion) ?: Observacion.NORMAL,
            observacionNota = acopio.observacionNota.orEmpty(),
            muestraCodigo  = acopio.muestraCodigo,
            fotoLocal      = acopio.fotoLocal,
            conLactoscan   = acopio.porcentajeAgua > 0 || acopio.densidad > 0,
            porcentajeAgua = if (acopio.porcentajeAgua > 0) acopio.porcentajeAgua.toString() else "",
            densidad       = if (acopio.densidad > 0) acopio.densidad.toString() else "",
            grasa          = acopio.grasa?.toString().orEmpty(),
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
        if (id == null || lts == null) {
            mensaje.value = "Elige el ganadero y escribe los litros (ej. 40,5)"
            return
        }
        if (form.observacion == Observacion.OTRO && form.observacionNota.isBlank()) {
            mensaje.value = "Escribe qué notaste raro en la leche"
            return
        }
        if (form.editando == null) {
            uiState.value.ganaderos.firstOrNull { it.id == id }?.let(::motivoNoEntrega)?.let { mensaje.value = it; return }
        }
        val observacion = form.observacion.clave
        val nota = form.observacionNota.trim().takeIf { it.isNotEmpty() }
        // Lactoscan portátil: opcional; vacío = 0 («sin medir», lo mide la planta)
        val agua = if (form.conLactoscan && form.porcentajeAgua.isNotBlank()) form.porcentajeAgua.aNumero() else 0.0
        val dens = if (form.conLactoscan && form.densidad.isNotBlank()) form.densidad.aNumero() else 0.0
        val grasa = if (form.conLactoscan && form.grasa.isNotBlank()) form.grasa.aNumero() else null
        if (agua == null || dens == null || (form.conLactoscan && form.grasa.isNotBlank() && grasa == null)) {
            mensaje.value = "Los valores del Lactoscan deben ser números (ej. 0,5 · 1,030 · 3,5)"
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
                registrarAcopio(
                    id, form.ganaderoNombre, lts, agua, dens, grasa, fotoLocal = form.fotoLocal,
                    observacion = observacion, observacionNota = nota, muestraCodigo = form.muestraCodigo
                )
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
                        observacion    = observacion,
                        observacionNota = nota,
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
                    mensaje.value = avisoCapacidad() ?: if (acopio.muestraCodigo != null) {
                        "Acopio $accion · frasco ${acopio.muestraCodigo} tomado: entrégalo en la planta"
                    } else if (uiState.value.enLinea) {
                        "Acopio $accion"
                    } else {
                        "Acopio $accion sin conexión: se subirá al recuperar la red"
                    }
                }
                .onFailure { mensaje.value = it.message ?: "No se pudo guardar el acopio" }
        }
    }

    /** Avisa una vez al pasar el 85 % y otra al llenarse el camión. */
    private fun avisoCapacidad(): String? {
        val estado = uiState.value
        val ocupacion = estado.ocupacionCamion ?: return null
        val nivel = nivelCarga(ocupacion)
        if (nivel <= nivelAvisado) return null
        nivelAvisado = nivel
        return when (nivel) {
            NivelCarga.LLENO -> "🚛 Camión LLENO (${formato(estado.litrosHoy, 0)} L): vuelve a la planta antes de recoger más"
            else -> "🚛 Camión al ${(ocupacion * 100).toInt()} %: quedan " +
                "${formato((estado.contexto.capacidadLitros - estado.litrosHoy).coerceAtLeast(0.0), 0)} L de capacidad"
        }
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
