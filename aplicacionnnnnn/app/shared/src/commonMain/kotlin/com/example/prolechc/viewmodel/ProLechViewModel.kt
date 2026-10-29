@file:OptIn(kotlin.time.ExperimentalTime::class)

package com.example.prolechc.viewmodel

import kotlin.time.Clock
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.*
import com.example.prolechc.data.repositories.IAcopioRepository
import com.example.prolechc.data.repositories.IAuthRepository
import com.example.prolechc.data.repositories.IMiRutaRepository
import com.example.prolechc.data.repositories.ParadaInfo
import com.example.prolechc.data.repositories.RepoResult
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

// ══════════════════════════════════════════════════════════════════════════════
//  Estados
// ══════════════════════════════════════════════════════════════════════════════

data class LoginState(
    val conductorNombre: String = "",
    val vehiculo: String        = "",
    val ruta: String            = "",
    val rutaIniciada: Boolean   = false,
    val isLoading: Boolean      = false,
    val error: String?          = null,
    val email: String           = "",
    val password: String        = ""
)

data class AcopioFormState(
    val ganaderoId: Int              = 0,
    val ganaderoNombre: String       = "",
    val dniGanadero: String          = "",
    val litros: Double               = 0.0,
    val litrosTexto: String          = "0.0",
    // ── Tacho ─────────────────────────────────────────────────────────────────
    val capacidadTachoTexto: String  = "",   // litros máx del tacho
    val alertaTacho: Boolean         = false, // true si litros > capacidad tacho
    val medicionBaldeConfirmada: Boolean = false, // RF-ACO-07
    val equipoLactoscan: Int?        = null,  // RF-CAL-01: 1, 2 o 3
    // ─────────────────────────────────────────────────────────────────────────
    val porcentajeAgua: String       = "",
    val acidezPh: String             = "",
    val densidad: String             = "",
    // ── Campos LACTOMAT completos ─────────────────────────────────────────────
    val grasa: String             = "",
    val proteina: String          = "",
    val lactosa: String           = "",
    val sng: String               = "",
    val sales: String             = "",
    val totalSolidos: String      = "",
    val puntoCongelacion: String  = "",
    // ─────────────────────────────────────────────────────────────────────────
    val lechRechazada: Boolean       = false,
    val tieneDescuento: Boolean      = false,
    // ── Evidencia de rechazo ──────────────────────────────────────────────────
    /** Foto de evidencia en JPEG codificado Base64 (null = sin foto) */
    val fotoEvidencia: String?       = null,
    val observacionesRechazo: String = "Presencia de adulteración detectada por Lactoscan.",
    /** true cuando el ganadero seleccionado está suspendido por 2 strikes */
    val ganaderoSuspendido: Boolean  = false,
    val guardadoExitoso: Boolean     = false,
    val ticketNumero: String         = "",
    val isLoading: Boolean           = false,
    val error: String?               = null,
    val gpsLat: Double?              = null,
    val gpsLon: Double?              = null
)

data class SyncState(
    val pendientes: Int        = 0,
    val sincronizando: Boolean = false,
    val exitoso: Boolean       = false,
    val progreso: Float        = 0f,
    /** Resultado del último intento para mostrar al chofer */
    val mensaje: String?       = null
)

/** Entrada individual en el modo rápido (batch) */
data class EntradaBatch(
    val ganaderoId: Int,
    val ganaderoNombre: String,
    val dniGanadero: String,
    val litrosTexto: String = "",
    val guardado: Boolean   = false,
    val error: String?      = null
)

data class BatchState(
    val entradas: List<EntradaBatch> = emptyList(),
    val isLoading: Boolean           = false,
    val totalGuardados: Int          = 0,
    val medicionBaldeConfirmada: Boolean = false
)

data class MiRutaState(
    val asignacionId: Int         = 0,
    val rutaCodigo: String        = "",
    val rutaNombre: String        = "",
    val capacidadLitros: Double   = 1200.0,
    val paradas: List<ParadaInfo> = emptyList(),
    val isLoading: Boolean        = false,
    val error: String?            = null
)

// ══════════════════════════════════════════════════════════════════════════════
//  ProLechViewModel
// ══════════════════════════════════════════════════════════════════════════════

class ProLechViewModel(
    private val authRepo: IAuthRepository?     = null,
    private val miRutaRepo: IMiRutaRepository? = null,
    private val acopioRepo: IAcopioRepository? = null
) : ViewModel() {

    // ── Login ─────────────────────────────────────────────────────────────────

    private val _loginState = MutableStateFlow(LoginState())
    val loginState: StateFlow<LoginState> = _loginState.asStateFlow()

    fun onEmailChange(v: String)    { _loginState.update { it.copy(email = v, error = null) } }
    fun onPasswordChange(v: String) { _loginState.update { it.copy(password = v, error = null) } }
    fun clearLoginError()           { _loginState.update { it.copy(error = null) } }

    fun login() {
        val repo = authRepo ?: run {
            val c = mockConductores[0]
            _loginState.update { it.copy(conductorNombre = c.nombre, vehiculo = c.vehiculo, ruta = c.ruta, rutaIniciada = true) }
            // Cargar acopios mock en el historial
            _acopiosSession.update { mockAcopios.toMutableList() }
            return
        }
        viewModelScope.launch {
            _loginState.update { it.copy(isLoading = true, error = null) }
            when (val result = repo.login(_loginState.value.email, _loginState.value.password)) {
                is RepoResult.Success -> {
                    _loginState.update { it.copy(isLoading = false, conductorNombre = result.data.nombreCompleto, rutaIniciada = true) }
                    cargarMiRuta()
                    cargarHistorial()   // Cargar historial del servidor al iniciar sesión
                }
                is RepoResult.Error -> {
                    val msg = when (result.code) { 401 -> "Credenciales incorrectas"; 422 -> "Verifica tu email y contraseña"; else -> result.message }
                    _loginState.update { it.copy(isLoading = false, error = msg) }
                }
                is RepoResult.NetworkError -> _loginState.update { it.copy(isLoading = false, error = "Sin conexión.") }
            }
        }
    }

    fun logout() {
        viewModelScope.launch {
            authRepo?.logout()
            _loginState.value    = LoginState()
            _form.value          = AcopioFormState()
            _miRuta.value        = MiRutaState()
            _acopiosSession.value = mutableListOf()
        }
    }

    // ── Mi Ruta ───────────────────────────────────────────────────────────────

    private val _miRuta = MutableStateFlow(MiRutaState())
    val miRutaState: StateFlow<MiRutaState> = _miRuta.asStateFlow()

    fun cargarMiRuta() {
        val repo = miRutaRepo ?: return
        viewModelScope.launch {
            _miRuta.update { it.copy(isLoading = true, error = null) }
            when (val result = repo.getMiRuta()) {
                is RepoResult.Success -> {
                    val ruta = result.data
                    _miRuta.value = MiRutaState(
                        asignacionId    = ruta.asignacionId,
                        rutaCodigo      = ruta.rutaCodigo,
                        rutaNombre      = ruta.rutaNombre,
                        capacidadLitros = ruta.capacidadLitros,
                        paradas         = ruta.paradas,
                        isLoading       = false
                    )
                    _loginState.update { it.copy(
                        vehiculo = ruta.vehiculoMarca.ifBlank { "Sin vehículo" },
                        ruta     = ruta.rutaNombre.ifBlank { ruta.rutaCodigo }
                    )}
                }
                is RepoResult.Error -> _miRuta.update { it.copy(
                    isLoading = false,
                    error = if (result.code == 404 || result.code == 422) "Sin ruta asignada hoy" else result.message
                )}
                is RepoResult.NetworkError -> _miRuta.update { it.copy(isLoading = false, error = "Sin conexión") }
            }
        }
    }

    // ── Cargar historial desde el servidor ───────────────────────────────────

    /**
     * Carga el historial de acopios desde el servidor y puebla _acopiosSession.
     * Se llama al iniciar sesión (para mostrar acopios previos) y al entrar
     * a HistorialScreen (para actualizar).
     */
    fun cargarHistorial(fecha: String? = null) {
        val repo = acopioRepo ?: return
        viewModelScope.launch {
            when (val result = repo.getHistorial(fecha = fecha)) {
                is RepoResult.Success -> {
                    val acopiosServidor = result.data.map { h ->
                        val estadoEnum = when (h.estado.uppercase()) {
                            "RECHAZADO" -> EstadoAcopio.RECHAZADO
                            "OBSERVADO" -> EstadoAcopio.OBSERVADO
                            else        -> EstadoAcopio.APROBADO
                        }
                        AcopioRecord(
                            id               = h.id,
                            ganaderoNombre   = h.ganaderoNombre,
                            dniGanadero      = h.ganaderoNii,
                            litros           = h.litros,
                            porcentajeAgua   = h.porcentajeAgua ?: 0.0,
                            acidezPh         = h.acidezPh ?: 0.0,
                            densidad         = h.densidad,
                            grasa            = h.grasa,
                            proteina         = h.proteina,
                            lactosa          = h.lactosa,
                            sng              = h.sng,
                            sales            = h.sales,
                            totalSolidos     = h.totalSolidos,
                            puntoCongelacion = h.puntoCongelacion,
                            hora             = h.createdAt.take(16).replace("T", " "),
                            estado           = estadoEnum,
                            syncEstado       = EstadoSync.ENVIADO,
                            ticketNumero     = "#PRO-${h.id}",
                            tieneDescuento   = h.tieneDescuento
                        )
                    }
                    // Combinar: acopios del servidor + pendientes guardados en el celular
                    val pendientesLocales = leerPendientes(repo)
                    _acopiosSession.value = (pendientesLocales + acopiosServidor)
                        .distinctBy { it.ticketNumero }
                        .toMutableList()
                    _sync.update { it.copy(pendientes = pendientesLocales.size) }
                }
                is RepoResult.Error        -> { /* historial no disponible, mantener lo que hay */ }
                is RepoResult.NetworkError -> { /* sin red, mantener lo que hay */ }
            }
        }
    }

    // ── Ganaderos disponibles para el formulario de Acopio ───────────────────

    /**
     * Lista de ganaderos para mostrar en el selector del AcopioScreen.
     * Con backend: solo las paradas reales de la ruta asignada.
     * Sin backend (modo demo): nombres de ejemplo.
     */
    val ganaderosDisponibles: List<Pair<Int, String>> get() {
        val desdeRuta = _miRuta.value.paradas.map { it.ganaderoId to it.ganaderoNombre }
        if (miRutaRepo != null) return desdeRuta
        return desdeRuta.ifEmpty {
            dniAutocompletado.entries.mapIndexed { i, e -> (i + 1) to e.value }
        }
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    /** Litros recogidos hoy (en modo demo, valor de ejemplo). */
    val cisternaActual: Double get() {
        if (authRepo == null) return CISTERNA_ACTUAL
        val hoy = fechaHoy()
        return _acopiosSession.value
            .filter { it.hora.startsWith(hoy) && it.estado != EstadoAcopio.RECHAZADO }
            .sumOf { it.litros }
    }
    val cisternaTotal    get() = _miRuta.value.capacidadLitros.takeIf { it > 0 } ?: CISTERNA_CAPACIDAD
    val cisternaProgress get() = (cisternaActual / cisternaTotal).toFloat().coerceIn(0f, 1f)

    // ── Historial de la sesión ────────────────────────────────────────────────

    /**
     * Lista mutable de acopios registrados durante esta sesión.
     * Incluye los guardados con éxito (reales o offline).
     * Se inicializa vacía; en mock se rellena con mockAcopios.
     */
    private val _acopiosSession = MutableStateFlow<MutableList<AcopioRecord>>(mutableListOf())
    val acopiosSession: StateFlow<List<AcopioRecord>> = MutableStateFlow<List<AcopioRecord>>(emptyList()).also { flow ->
        viewModelScope.launch {
            _acopiosSession.collect { flow.emit(it.toList()) }
        }
    }.asStateFlow()

    // Combinado: solo acopios de sesión real (sin mocks)
    // Los mocks se ven cuando el usuario no está logueado (authRepo == null)
    val historialCompleto: List<AcopioRecord> get() {
        val session = _acopiosSession.value.toList()
        // Solo inyecta mocks si no hay repositorio (modo demo sin login)
        return if (session.isEmpty() && authRepo == null) mockAcopios else session
    }

    val totalLitrosSession: Double  get() = historialCompleto.sumOf { it.litros }
    val totalAcopioSession: Int     get() = historialCompleto.size
    val pendientesSyncSession: Int  get() = historialCompleto.count { it.syncEstado == EstadoSync.PENDIENTE }

    // ── Formulario Acopio ─────────────────────────────────────────────────────

    private val _form = MutableStateFlow(AcopioFormState())
    val formState: StateFlow<AcopioFormState> = _form.asStateFlow()

    /** Selecciona un ganadero por su ID desde la lista de paradas/mock */
    fun seleccionarGanadero(id: Int, nombre: String) {
        val parada = _miRuta.value.paradas.find { it.ganaderoId == id }
        val dni    = parada?.ganaderoNii ?: dniAutocompletado.entries.find { it.value == nombre }?.key ?: ""
        _form.update { it.copy(
            ganaderoId         = id,
            ganaderoNombre     = nombre,
            dniGanadero        = dni,
            ganaderoSuspendido = parada?.suspendido == true,
            error              = if (parada?.suspendido == true)
                                     "No se puede recibir leche: Proveedor suspendido"
                                 else null
        )}
    }

    /** Llamado cuando el chofer intenta seleccionar un ganadero suspendido */
    fun onGanaderoSuspendidoIntentado() {
        _form.update { it.copy(error = "No se puede recibir leche: Proveedor suspendido por 2 adulteraciones esta semana.") }
    }

    /** Búsqueda manual por nombre (filtra en la lista disponible) */
    fun onNombreChange(v: String) {
        val paradas = _miRuta.value.paradas

        // 1. Coincidencia exacta (case-insensitive)
        val exacto = paradas.find { it.ganaderoNombre.equals(v, ignoreCase = true) }
        if (exacto != null) {
            _form.update { it.copy(
                ganaderoNombre = v,
                ganaderoId     = exacto.ganaderoId,
                dniGanadero    = exacto.ganaderoNii,
                error          = null
            )}
            return
        }

        // 2. Si el texto escrito está contenido en algún nombre (búsqueda parcial)
        val parcial = if (v.length >= 3) {
            paradas.find { it.ganaderoNombre.contains(v, ignoreCase = true) }
        } else null

        _form.update { it.copy(
            ganaderoNombre = v,
            ganaderoId     = parcial?.ganaderoId ?: 0,
            dniGanadero    = parcial?.ganaderoNii ?: "",
            error          = null
        )}
    }

    fun incrementarLitros() {
        val nuevo = (_form.value.litros + 0.5).coerceAtMost(9999.0)
        _form.update { it.copy(litros = nuevo, litrosTexto = "%.1f".format(nuevo)) }
    }

    fun decrementarLitros() {
        val nuevo = (_form.value.litros - 0.5).coerceAtLeast(0.0)
        _form.update { it.copy(litros = nuevo, litrosTexto = "%.1f".format(nuevo)) }
    }

    fun onLitrosTextoChange(v: String) {
        val num = v.toDoubleOrNull() ?: 0.0
        val capacidad = _form.value.capacidadTachoTexto.toDoubleOrNull()
        val alerta = capacidad != null && capacidad > 0 && num > capacidad
        _form.update { it.copy(litros = num, litrosTexto = v, alertaTacho = alerta) }
    }

    fun onCapacidadTachoChange(v: String) {
        val capacidad = v.toDoubleOrNull()
        val alerta = capacidad != null && capacidad > 0 && _form.value.litros > capacidad
        _form.update { it.copy(capacidadTachoTexto = v, alertaTacho = alerta) }
    }

    fun onMedicionBaldeChange(confirmada: Boolean) {
        _form.update { it.copy(medicionBaldeConfirmada = confirmada) }
    }

    fun onEquipoLactoscanChange(equipo: Int?) {
        _form.update { it.copy(equipoLactoscan = equipo) }
    }

    /** Recibe la foto de evidencia (JPEG Base64) tomada con la cámara. */
    fun onFotoEvidencia(base64: String?) {
        _form.update { it.copy(fotoEvidencia = base64) }
    }

    fun onObservacionesRechazoChange(v: String) {
        _form.update { it.copy(observacionesRechazo = v.take(500)) }
    }

    fun onBatchMedicionBaldeChange(confirmada: Boolean) {
        _batch.update { it.copy(medicionBaldeConfirmada = confirmada) }
    }

    private fun tieneParametrosCalidad(f: AcopioFormState): Boolean {
        val agua = f.porcentajeAgua.toDoubleOrNull() ?: 0.0
        val ph   = f.acidezPh.toDoubleOrNull() ?: 0.0
        if (agua > 0 || ph > 0) return true
        return listOf(f.densidad, f.grasa, f.proteina, f.lactosa, f.sng, f.sales, f.totalSolidos, f.puntoCongelacion)
            .any { it.isNotBlank() && (it.toDoubleOrNull() ?: 0.0) != 0.0 }
    }

    fun onPorcentajeAguaChange(v: String) {
        val agua = v.toDoubleOrNull() ?: 0.0
        // >= 10% → rechazado inmediato (bloqueado)
        // 1% exacto o entre 0.5-1.5% → descuento en liquidación
        val rechazada   = agua >= 10.0
        val descuento   = !rechazada && agua in 0.5..1.5
        _form.update { it.copy(porcentajeAgua = v, lechRechazada = rechazada, tieneDescuento = descuento) }
    }

    fun onAcidezPhChange(v: String)  { _form.update { it.copy(acidezPh = v) } }
    fun onDensidadChange(v: String)  { _form.update { it.copy(densidad = v) } }
    // ── Campos LACTOMAT completos ─────────────────────────────────────────────
    fun onGrasaChange(v: String)            { _form.update { it.copy(grasa = v) } }
    fun onProteinaChange(v: String)         { _form.update { it.copy(proteina = v) } }
    fun onLactosaChange(v: String)          { _form.update { it.copy(lactosa = v) } }
    fun onSngChange(v: String)              { _form.update { it.copy(sng = v) } }
    fun onSalesChange(v: String)            { _form.update { it.copy(sales = v) } }
    fun onTotalSolidosChange(v: String)     { _form.update { it.copy(totalSolidos = v) } }
    fun onPuntoCongelacionChange(v: String) { _form.update { it.copy(puntoCongelacion = v) } }

    /**
     * Aplica los valores leídos por OCR del ticket LACTOMAT al formulario.
     * Solo sobreescribe los campos que el OCR encontró (no borra lo ya ingresado).
     */
    fun aplicarResultadoOcr(
        grasa: Double?, sng: Double?, densidad: Double?,
        proteina: Double?, lactosa: Double?, sales: Double?,
        totalSolidos: Double?, aguaAniadida: Double?,
        puntoCongelacion: Double?, ph: Double?
    ) {
        _form.update { f ->
            f.copy(
                grasa            = grasa?.toString()            ?: f.grasa,
                sng              = sng?.toString()              ?: f.sng,
                densidad         = densidad?.toString()         ?: f.densidad,
                proteina         = proteina?.toString()         ?: f.proteina,
                lactosa          = lactosa?.toString()          ?: f.lactosa,
                sales            = sales?.toString()            ?: f.sales,
                totalSolidos     = totalSolidos?.toString()     ?: f.totalSolidos,
                porcentajeAgua   = aguaAniadida?.toString()     ?: f.porcentajeAgua,
                puntoCongelacion = puntoCongelacion?.toString() ?: f.puntoCongelacion,
                acidezPh         = ph?.toString()               ?: f.acidezPh
            )
        }
        // Recalcular estado de rechazo si llegó % agua
        aguaAniadida?.let { onPorcentajeAguaChange(it.toString()) }
    }

    fun guardarAcopio(gpsLat: Double? = null, gpsLon: Double? = null) {
        val repo = acopioRepo

        // Sin repositorio (modo demo puro sin login): guarda mock local
        if (repo == null) {
            val ticket = "DEMO-${(1000..9999).random()}"
            val estado = calcularEstado(_form.value.porcentajeAgua, _form.value.acidezPh)
            val nuevo = AcopioRecord(
                id                      = _acopiosSession.value.size + 1,
                ganaderoId              = _form.value.ganaderoId,
                ganaderoNombre          = _form.value.ganaderoNombre.ifBlank { "Ganadero" },
                dniGanadero             = _form.value.dniGanadero,
                litros                  = _form.value.litros,
                porcentajeAgua          = _form.value.porcentajeAgua.toDoubleOrNull() ?: 0.0,
                acidezPh                = _form.value.acidezPh.toDoubleOrNull() ?: 0.0,
                hora                    = obtenerHoraActual(),
                estado                  = estado,
                syncEstado              = EstadoSync.PENDIENTE,
                ticketNumero            = ticket,
                gpsLat                  = gpsLat,
                gpsLon                  = gpsLon,
                medicionBaldeConfirmada = _form.value.medicionBaldeConfirmada,
                equipoLactoscan         = _form.value.equipoLactoscan,
                capacidadTachoLitros    = _form.value.capacidadTachoTexto.toDoubleOrNull()
            )
            _acopiosSession.update { it.apply { add(0, nuevo) }.toMutableList() }
            _form.update { it.copy(guardadoExitoso = true, ticketNumero = ticket) }
            return
        }

        // Validar que haya un ganadero seleccionado
        if (_form.value.ganaderoId == 0) {
            _form.update { it.copy(error = "Selecciona un productor de la lista") }
            return
        }

        // Bloquear si el ganadero está suspendido
        if (_form.value.ganaderoSuspendido) {
            _form.update { it.copy(error = "No se puede recibir leche: Proveedor suspendido por reincidencia. Contacta al administrador.") }
            return
        }

        // RF-ACO-03 — litros no pueden superar capacidad del tacho
        if (_form.value.alertaTacho) {
            _form.update {
                it.copy(error = "Los litros superan la capacidad del tacho. Corrige la medición o la capacidad configurada.")
            }
            return
        }

        // RF-ACO-07 — confirmación medición con balde
        if (!_form.value.medicionBaldeConfirmada) {
            _form.update { it.copy(error = "Debes confirmar que mediste con el balde estándar antes de guardar.") }
            return
        }

        // RF-CAL-01 — equipo LactoScan si hay parámetros de calidad
        if (tieneParametrosCalidad(_form.value) && _form.value.equipoLactoscan == null) {
            _form.update { it.copy(error = "Selecciona qué equipo LactoScan (1, 2 o 3) se usó.") }
            return
        }

        // Bloquear si >= 10% agua — el botón ya debería estar deshabilitado, pero por seguridad
        if (_form.value.lechRechazada && (_form.value.porcentajeAgua.toDoubleOrNull() ?: 0.0) >= 10.0) {
            _form.update { it.copy(error = "Leche rechazada: % agua >= 10%. No se puede registrar.") }
            return
        }

        // ── Llamada HTTP real al backend ──────────────────────────────────────
        viewModelScope.launch {
            _form.update { it.copy(isLoading = true, error = null) }

            // Verificar duplicado antes de enviar
            val duplicado = acopioRepo?.yaEntregoHoy(_form.value.ganaderoId)
            if (duplicado is RepoResult.Success && duplicado.data) {
                _form.update { it.copy(isLoading = false, error = "Este productor ya entregó leche hoy. Solo se permite una entrega por día.") }
                return@launch
            }

            when (val result = repo.crearAcopio(
                asignacionId            = _miRuta.value.asignacionId,
                ganaderoId              = _form.value.ganaderoId,
                litros                  = _form.value.litros,
                porcentajeAgua          = _form.value.porcentajeAgua.toDoubleOrNull() ?: 0.0,
                acidezPh                = _form.value.acidezPh.toDoubleOrNull() ?: 0.0,
                densidad                = _form.value.densidad.toDoubleOrNull(),
                grasa                   = _form.value.grasa.toDoubleOrNull(),
                proteina                = _form.value.proteina.toDoubleOrNull(),
                lactosa                 = _form.value.lactosa.toDoubleOrNull(),
                sng                     = _form.value.sng.toDoubleOrNull(),
                sales                   = _form.value.sales.toDoubleOrNull(),
                totalSolidos            = _form.value.totalSolidos.toDoubleOrNull(),
                puntoCongelacion        = _form.value.puntoCongelacion.toDoubleOrNull(),
                gpsLat                  = gpsLat,
                gpsLon                  = gpsLon,
                litrosTina              = null,
                medicionBaldeConfirmada = _form.value.medicionBaldeConfirmada,
                equipoLactoscan         = _form.value.equipoLactoscan,
                capacidadTachoLitros    = _form.value.capacidadTachoTexto.toDoubleOrNull(),
                observaciones           = _form.value.observacionesRechazo
                                              .takeIf { _form.value.lechRechazada && it.isNotBlank() },
                fotoEvidencia           = _form.value.fotoEvidencia
                                              .takeIf { _form.value.lechRechazada },
                ganaderoNombre          = _form.value.ganaderoNombre,
                dniGanadero             = _form.value.dniGanadero
            )) {
                is RepoResult.Success -> {
                    val ticket = result.data.ticketNumero
                    val estadoStr = result.data.estado.uppercase()
                    val estado = when (estadoStr) {
                        "RECHAZADO" -> EstadoAcopio.RECHAZADO
                        "OBSERVADO" -> EstadoAcopio.OBSERVADO
                        else        -> EstadoAcopio.APROBADO
                    }
                    val nuevo = AcopioRecord(
                        id                      = result.data.id,
                        ganaderoId              = _form.value.ganaderoId,
                        ganaderoNombre          = _form.value.ganaderoNombre.ifBlank { "Ganadero" },
                        dniGanadero             = _form.value.dniGanadero,
                        litros                  = _form.value.litros,
                        porcentajeAgua          = _form.value.porcentajeAgua.toDoubleOrNull() ?: 0.0,
                        acidezPh                = _form.value.acidezPh.toDoubleOrNull() ?: 0.0,
                        densidad                = _form.value.densidad.toDoubleOrNull(),
                        grasa                   = _form.value.grasa.toDoubleOrNull(),
                        proteina                = _form.value.proteina.toDoubleOrNull(),
                        lactosa                 = _form.value.lactosa.toDoubleOrNull(),
                        sng                     = _form.value.sng.toDoubleOrNull(),
                        sales                   = _form.value.sales.toDoubleOrNull(),
                        totalSolidos            = _form.value.totalSolidos.toDoubleOrNull(),
                        puntoCongelacion        = _form.value.puntoCongelacion.toDoubleOrNull(),
                        hora                    = obtenerHoraActual(),
                        estado                  = estado,
                        syncEstado              = EstadoSync.ENVIADO,
                        ticketNumero            = ticket,
                        tieneDescuento          = _form.value.tieneDescuento,
                        gpsLat                  = gpsLat,
                        gpsLon                  = gpsLon,
                        medicionBaldeConfirmada = _form.value.medicionBaldeConfirmada,
                        equipoLactoscan         = _form.value.equipoLactoscan,
                        capacidadTachoLitros    = _form.value.capacidadTachoTexto.toDoubleOrNull()
                    )
                    _acopiosSession.update { it.apply { add(0, nuevo) }.toMutableList() }
                    _form.update { it.copy(isLoading = false, guardadoExitoso = true, ticketNumero = ticket) }
                }
                is RepoResult.Error -> {
                    val msg = when (result.code) {
                        401  -> "Sesión expirada. Vuelve a iniciar sesión."
                        422  -> "Datos inválidos. Verifica los campos."
                        else -> "Error del servidor (${result.code})"
                    }
                    _form.update { it.copy(isLoading = false, error = msg) }
                }
                is RepoResult.NetworkError -> {
                    // Sin red — el repositorio ya lo guardó en el celular y lo enviará
                    // solo cuando vuelva la conexión
                    val pendientes = leerPendientes(repo)
                    aplicarPendientes(pendientes)
                    val ticket = pendientes.firstOrNull()?.ticketNumero ?: "OFFLINE"
                    _form.update { it.copy(isLoading = false, guardadoExitoso = true, ticketNumero = ticket) }
                }
            }
        }
    }

    fun resetForm() { _form.value = AcopioFormState() }

    // ── Sync ──────────────────────────────────────────────────────────────────

    private val _sync = MutableStateFlow(SyncState(pendientes = if (authRepo == null) pendientesSync else 0))
    val syncState: StateFlow<SyncState> = _sync.asStateFlow()

    fun sincronizarAhora() {
        val repo = acopioRepo ?: run {
            // Sin repositorio: solo actualizar UI (modo demo)
            viewModelScope.launch {
                _sync.update { it.copy(sincronizando = true) }
                kotlinx.coroutines.delay(500L)
                _acopiosSession.update { lista ->
                    lista.map { if (it.syncEstado == EstadoSync.PENDIENTE) it.copy(syncEstado = EstadoSync.ENVIADO) else it }.toMutableList()
                }
                _sync.update { it.copy(sincronizando = false, exitoso = true, pendientes = 0, progreso = 1f) }
            }
            return
        }

        viewModelScope.launch {
            _sync.update { it.copy(sincronizando = true, exitoso = false, progreso = 0f, mensaje = null) }

            val r = repo.sincronizarPendientes()
            val pendientes = leerPendientes(repo)
            aplicarPendientes(pendientes)
            if (r.enviados > 0) cargarHistorial()

            val mensaje = when {
                r.sesionExpirada  -> "Sesión expirada. Inicia sesión para enviar los pendientes."
                r.sinRed          -> "Sin conexión. Se enviarán solos cuando vuelva la señal."
                r.rechazados > 0  -> "${r.rechazados} acopio(s) rechazado(s) por el servidor. Revisa el detalle."
                r.restantes > 0   -> "El servidor no respondió. Se reintentará automáticamente."
                r.enviados > 0    -> "✓ ${r.enviados} acopio(s) enviado(s)."
                else              -> null
            }
            _sync.update { it.copy(
                sincronizando = false,
                exitoso       = pendientes.isEmpty(),
                pendientes    = pendientes.size,
                progreso      = 1f,
                mensaje       = mensaje
            ) }
        }
    }

    // ── Offline: acopios guardados en el celular ─────────────────────────────

    /**
     * Relee los pendientes del celular. La llama MainActivity cuando el
     * sincronizador automático termina, para que la UI refleje lo enviado.
     */
    fun refrescarPendientes() {
        val repo = acopioRepo ?: return
        viewModelScope.launch { aplicarPendientes(leerPendientes(repo)) }
    }

    /** Elimina un pendiente que el servidor rechazó (p. ej. productor suspendido). */
    fun descartarPendiente(acopio: AcopioRecord) {
        val repo = acopioRepo ?: return
        val uuid = acopio.clientUuid ?: return
        viewModelScope.launch {
            repo.descartarPendiente(uuid)
            aplicarPendientes(leerPendientes(repo))
        }
    }

    /** Pendientes del celular como AcopioRecord, del más reciente al más antiguo. */
    private suspend fun leerPendientes(repo: IAcopioRepository): List<AcopioRecord> =
        repo.obtenerPendientes().reversed().map { p ->
            AcopioRecord(
                id             = -(p.clientUuid.hashCode() and Int.MAX_VALUE),
                ganaderoId     = p.ganaderoId,
                ganaderoNombre = p.ganaderoNombre,
                dniGanadero    = p.dniGanadero,
                litros         = p.litros,
                porcentajeAgua = p.porcentajeAgua,
                acidezPh       = p.acidezPh,
                densidad       = p.densidad,
                hora           = p.fechaHora,
                estado         = calcularEstado(p.porcentajeAgua.toString(), p.acidezPh.takeIf { it > 0 }?.toString() ?: ""),
                syncEstado     = EstadoSync.PENDIENTE,
                ticketNumero   = "OFF-${p.clientUuid.take(8).uppercase()}",
                clientUuid     = p.clientUuid,
                errorSync      = if (p.rechazado) p.error else null
            )
        }

    /** Reemplaza los pendientes de la lista por los que hay realmente en el celular. */
    private fun aplicarPendientes(pendientes: List<AcopioRecord>) {
        _acopiosSession.update { lista ->
            (pendientes + lista.filter { it.syncEstado != EstadoSync.PENDIENTE }).toMutableList()
        }
        _sync.update { it.copy(pendientes = pendientes.size) }
    }

    fun resetSync() { _sync.value = SyncState(pendientes = pendientesSyncSession) }

    // ── Modo Rápido (Batch) ───────────────────────────────────────────────────

    private val _batch = MutableStateFlow(BatchState())
    val batchState: StateFlow<BatchState> = _batch.asStateFlow()

    /** Inicializa el modo batch con los ganaderos de la ruta actual */
    fun iniciarBatch() {
        val ganaderos = ganaderosDisponibles
            .map { (id, nombre) ->
                val parada = _miRuta.value.paradas.find { it.ganaderoId == id }
                EntradaBatch(
                    ganaderoId     = id,
                    ganaderoNombre = nombre,
                    dniGanadero    = parada?.ganaderoNii ?: ""
                )
            }
        _batch.value = BatchState(entradas = ganaderos)
    }

    fun onBatchLitrosChange(ganaderoId: Int, litros: String) {
        _batch.update { state ->
            state.copy(entradas = state.entradas.map { e ->
                if (e.ganaderoId == ganaderoId) e.copy(litrosTexto = litros) else e
            })
        }
    }

    /** Guarda todos los acopios batch que tienen litros > 0 y no están guardados */
    fun guardarBatch(gpsLat: Double? = null, gpsLon: Double? = null) {
        val repo = acopioRepo ?: return
        if (!_batch.value.medicionBaldeConfirmada) {
            _batch.update { it.copy(isLoading = false) }
            return
        }
        viewModelScope.launch {
            _batch.update { it.copy(isLoading = true) }
            var guardados = 0

            _batch.value.entradas.forEach { entrada ->
                if (entrada.guardado) return@forEach
                val litros = entrada.litrosTexto.toDoubleOrNull() ?: return@forEach
                if (litros <= 0) return@forEach

                when (val result = repo.crearAcopio(
                    asignacionId            = _miRuta.value.asignacionId,
                    ganaderoId              = entrada.ganaderoId,
                    litros                  = litros,
                    porcentajeAgua          = 0.0,
                    acidezPh                = 0.0,
                    densidad                = null,
                    grasa                   = null, proteina = null, lactosa = null,
                    sng                     = null, sales = null, totalSolidos = null,
                    puntoCongelacion        = null,
                    gpsLat                  = gpsLat,
                    gpsLon                  = gpsLon,
                    litrosTina              = null,
                    medicionBaldeConfirmada = _batch.value.medicionBaldeConfirmada,
                    equipoLactoscan         = null,
                    capacidadTachoLitros    = null,
                    ganaderoNombre          = entrada.ganaderoNombre,
                    dniGanadero             = entrada.dniGanadero
                )) {
                    is RepoResult.Success -> {
                        guardados++
                        val nuevo = AcopioRecord(
                            id             = result.data.id,
                            ganaderoNombre = entrada.ganaderoNombre,
                            dniGanadero    = entrada.dniGanadero,
                            litros         = litros,
                            porcentajeAgua = 0.0,
                            acidezPh       = 0.0,
                            hora           = obtenerHoraActual(),
                            estado         = EstadoAcopio.APROBADO,
                            syncEstado     = EstadoSync.ENVIADO,
                            ticketNumero   = result.data.ticketNumero,
                            gpsLat         = gpsLat,
                            gpsLon         = gpsLon
                        )
                        _acopiosSession.update { it.apply { add(0, nuevo) }.toMutableList() }
                        _batch.update { state ->
                            state.copy(entradas = state.entradas.map { e ->
                                if (e.ganaderoId == entrada.ganaderoId)
                                    e.copy(guardado = true, error = null)
                                else e
                            })
                        }
                    }
                    is RepoResult.Error -> {
                        _batch.update { state ->
                            state.copy(entradas = state.entradas.map { e ->
                                if (e.ganaderoId == entrada.ganaderoId)
                                    e.copy(error = "Error ${result.code}")
                                else e
                            })
                        }
                    }
                    is RepoResult.NetworkError -> {
                        // Guardado en el celular por el repositorio; se envía solo
                        guardados++
                        _batch.update { state ->
                            state.copy(entradas = state.entradas.map { e ->
                                if (e.ganaderoId == entrada.ganaderoId)
                                    e.copy(guardado = true, error = null)
                                else e
                            })
                        }
                    }
                }
            }
            refrescarPendientes()
            _batch.update { it.copy(isLoading = false, totalGuardados = guardados) }
        }
    }

    fun resetBatch() { _batch.value = BatchState() }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private fun calcularEstado(agua: String, ph: String): EstadoAcopio {
        val pAgua = agua.toDoubleOrNull() ?: 0.0
        val pPh   = ph.toDoubleOrNull() ?: 6.5
        return when {
            pAgua > 5.0               -> EstadoAcopio.RECHAZADO
            pPh < 6.0 || pPh > 7.0   -> EstadoAcopio.OBSERVADO
            else                      -> EstadoAcopio.APROBADO
        }
    }

    /** Fecha y hora local en el mismo formato que el historial del servidor: "YYYY-MM-DD HH:mm". */
    private fun obtenerHoraActual(): String {
        val ahora = Clock.System.now().toLocalDateTime(TimeZone.currentSystemDefault())
        return "${ahora.date} ${ahora.hour.toString().padStart(2, '0')}:${ahora.minute.toString().padStart(2, '0')}"
    }

    private fun fechaHoy(): String =
        Clock.System.now().toLocalDateTime(TimeZone.currentSystemDefault()).date.toString()

    // ── Factory ───────────────────────────────────────────────────────────────

    companion object {
        fun factory(
            auth: IAuthRepository,
            miRuta: IMiRutaRepository,
            acopio: IAcopioRepository
        ): ViewModelProvider.Factory = object : ViewModelProvider.Factory {
            @Suppress("UNCHECKED_CAST")
            override fun <T : ViewModel> create(modelClass: Class<T>): T =
                ProLechViewModel(auth, miRuta, acopio) as T
        }
    }
}
