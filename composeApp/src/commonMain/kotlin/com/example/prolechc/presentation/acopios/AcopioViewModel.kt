package com.example.prolechc.presentation.acopios

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.network.MonitorConectividad
import com.example.prolechc.data.sync.SyncManager
import com.example.prolechc.domain.Tiempo
import com.example.prolechc.domain.model.Acopio
import com.example.prolechc.domain.model.EstadoCalidad
import com.example.prolechc.domain.model.EstadoSync
import com.example.prolechc.domain.model.GanaderoCampo
import com.example.prolechc.domain.usecase.ActualizarAcopioUseCase
import com.example.prolechc.domain.usecase.EliminarAcopioUseCase
import com.example.prolechc.domain.usecase.LogoutUseCase
import com.example.prolechc.domain.usecase.ObservarAcopiosUseCase
import com.example.prolechc.domain.usecase.ObservarGanaderosUseCase
import com.example.prolechc.domain.usecase.RegistrarAcopioUseCase
import com.example.prolechc.domain.usecase.SincronizarAcopiosUseCase
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
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

/** Estado de la pantalla de acopios en campo. */
data class AcopioUiState(
    val acopios: List<Acopio>          = emptyList(),
    val ganaderos: List<GanaderoCampo> = emptyList(),
    val formulario: FormularioAcopio   = FormularioAcopio(),
    val enLinea: Boolean               = false,
    val sincronizando: Boolean         = false,
    val mensaje: String?               = null
) {
    val pendientes: Int get() = acopios.count { it.estadoSync != EstadoSync.SINCRONIZADO }
    val totalLitros: Double get() = acopios.sumOf { it.litros }
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
    private val reloj: () -> Long = { Tiempo.ahoraMs() }
) : ViewModel() {

    private val mensaje = MutableStateFlow<String?>(null)
    private val formulario = MutableStateFlow(FormularioAcopio())

    /** La UI solo lee de SQLDelight (Single Source of Truth). */
    val uiState: StateFlow<AcopioUiState> = combine(
        observarAcopios(),
        observarGanaderos(),
        monitor.enLinea,
        syncManager.sincronizando,
        combine(formulario, mensaje) { form, msg -> form to msg }
    ) { acopios, ganaderos, enLinea, sincronizando, (form, msg) ->
        AcopioUiState(acopios, ganaderos, form, enLinea, sincronizando, msg)
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

    fun editar(acopio: Acopio) {
        formulario.value = FormularioAcopio(
            ganaderoId     = acopio.ganaderoId.toString(),
            ganaderoNombre = acopio.ganaderoNombre,
            litros         = acopio.litros.toString(),
            porcentajeAgua = acopio.porcentajeAgua.toString(),
            densidad       = acopio.densidad.toString(),
            editando       = acopio
        )
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
        if (id == null || lts == null || agua == null || dens == null) {
            mensaje.value = "Completa todos los campos con valores numéricos"
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
                registrarAcopio(id, form.ganaderoNombre, lts, agua, dens)
            } else {
                actualizarAcopio(
                    editando.copy(
                        ganaderoId     = id,
                        ganaderoNombre = form.ganaderoNombre.trim(),
                        litros         = lts,
                        porcentajeAgua = agua,
                        densidad       = dens
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
                    mensaje.value = if (uiState.value.enLinea) {
                        "Acopio $accion (${acopio.estadoCalidad.name})"
                    } else {
                        "Acopio $accion sin conexión: se subirá al recuperar la red"
                    }
                }
                .onFailure { mensaje.value = it.message ?: "No se pudo guardar el acopio" }
        }
    }

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
                r.sinConexion  -> "Sin conexión: los cambios siguen guardados en el celular"
                r.sinSesion    -> "Sesión expirada: vuelve a iniciar sesión"
                r.fallidos > 0 -> "Sincronización parcial: ${r.fallidos} con error"
                else           -> "Sincronizado: ${r.subidos} subidos, ${r.bajados} actualizados"
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
