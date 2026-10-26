package com.example.prolechc.presentation.ruta

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.prolechc.data.location.ControlRastreoSegundoPlano
import com.example.prolechc.data.location.ProveedorUbicacion
import com.example.prolechc.data.network.MonitorConectividad
import com.example.prolechc.data.remote.datasource.ErrorHttp
import com.example.prolechc.data.remote.datasource.GanaderoUbicacionRemoto
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.drop
import kotlinx.coroutines.flow.filterNotNull
import com.example.prolechc.data.remote.datasource.RutaVialRemoto
import com.example.prolechc.data.repository.RutaDiaRepositorio
import com.example.prolechc.data.repository.RutaGuardada
import com.example.prolechc.data.tracking.RastreoGps
import com.example.prolechc.domain.model.Coordenada
import com.example.prolechc.domain.model.Parada
import com.example.prolechc.domain.model.PlanRuta
import com.example.prolechc.domain.model.Ruta
import com.example.prolechc.domain.ruta.Geo
import com.example.prolechc.domain.ruta.PlanificadorRuta
import com.example.prolechc.domain.ruta.ResumenRuta
import com.example.prolechc.domain.ruta.coordenada
import com.example.prolechc.domain.usecase.ObservarAcopiosUseCase
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.flow.filter
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.onEach
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import com.example.prolechc.domain.Tiempo

data class MiRutaUiState(
    val cargando: Boolean = true,
    val ruta: RutaGuardada? = null,
    val marcadores: List<MarcadorParada> = emptyList(),
    val plan: PlanRuta? = null,
    val recorrido: List<Coordenada> = emptyList(),
    val miUbicacion: Coordenada? = null,
    val rastreando: Boolean = false,
    val enLinea: Boolean = false,
    val tienePermiso: Boolean = false,
    val atendidos: Int = 0,
    val total: Int = 0,
    val recorridoKm: Double = 0.0,
    val mensaje: String? = null,
    /** Se muestra al finalizar la ruta. */
    val resumen: ResumenRuta? = null,
    val capacidadLitros: Double = 0.0,
    val litrosHoy: Double = 0.0
) {
    val siguiente: Parada? get() = plan?.orden?.firstOrNull()
    val distanciaSiguienteKm: Double?
        get() {
            val yo = miUbicacion ?: return null
            val destino = siguiente?.coordenada ?: return null
            return Geo.distanciaKm(yo, destino)
        }
}

class MiRutaViewModel(
    private val repositorio: RutaDiaRepositorio,
    private val rastreo: RastreoGps,
    private val control: ControlRastreoSegundoPlano,
    private val proveedor: ProveedorUbicacion,
    private val rutaVial: RutaVialRemoto,
    private val monitor: MonitorConectividad,
    observarAcopios: ObservarAcopiosUseCase,
    private val ubicacionRemota: GanaderoUbicacionRemoto,
    private val planificador: PlanificadorRuta = PlanificadorRuta(),
    private val reloj: () -> Long = { Tiempo.ahoraMs() }
) : ViewModel() {

    private val mensaje = MutableStateFlow<String?>(null)
    private val resumen = MutableStateFlow<ResumenRuta?>(null)
    private val plan = MutableStateFlow<PlanRuta?>(null)
    private val cargando = MutableStateFlow(true)
    private val permiso = MutableStateFlow(proveedor.tienePermiso())
    private val inicioDelDia = inicioDelDia()
    private var trabajoPlan: Job? = null
    private var ultimaDescarga = 0L

    /** Acopios de HOY registrados en el celular (aunque aún no se hayan sincronizado). */
    private val acopiosHoy = observarAcopios().map { acopios -> acopios.filter { it.actualizadoEn >= inicioDelDia } }
        .stateIn(viewModelScope, SharingStarted.Eagerly, emptyList())

    private val atendidosLocal = acopiosHoy.map { acopios -> acopios.map { it.ganaderoId.toInt() }.toSet() }

    private val rutaConAtendidos = combine(repositorio.observar(), atendidosLocal) { guardada, locales ->
        guardada?.let {
            it.copy(ruta = it.ruta.copy(paradas = it.ruta.paradas.map { p ->
                if (p.ganaderoId in locales) p.copy(atendido = true) else p
            }))
        }
    }

    val uiState: StateFlow<MiRutaUiState> = combine(
        combine(rutaConAtendidos, plan, cargando, ::Triple),
        rastreo.recorrido(inicioDelDia),
        rastreo.ultimaLectura,
        combine(rastreo.activo, monitor.enLinea, permiso, ::Triple),
        combine(mensaje, resumen, acopiosHoy, ::Triple)
    ) { (guardada, planActual, estaCargando), recorrido, lectura, (activo, enLinea, conPermiso), (msg, res, hoy) ->
        val paradas = guardada?.ruta?.paradas.orEmpty()
        val turnos = planActual?.orden?.mapIndexed { i, p -> p.ganaderoId to i + 1 }?.toMap().orEmpty()
        val siguienteId = planActual?.orden?.firstOrNull()?.ganaderoId
        MiRutaUiState(
            cargando     = estaCargando && guardada == null,
            ruta         = guardada,
            marcadores   = paradas.map { p ->
                MarcadorParada(
                    parada = p,
                    numero = turnos[p.ganaderoId],
                    estado = when {
                        p.suspendido               -> EstadoParada.SUSPENDIDO
                        p.atendido                 -> EstadoParada.ATENDIDO
                        p.ganaderoId == siguienteId -> EstadoParada.SIGUIENTE
                        else                       -> EstadoParada.PENDIENTE
                    }
                )
            },
            plan         = planActual,
            recorrido    = recorrido,
            miUbicacion  = lectura?.coordenada,
            rastreando   = activo,
            enLinea      = enLinea,
            tienePermiso = conPermiso,
            atendidos    = paradas.count { it.atendido },
            total        = paradas.count { !it.suspendido },
            recorridoKm  = Geo.distanciaTotalKm(recorrido),
            mensaje      = msg,
            resumen      = res,
            capacidadLitros = guardada?.ruta?.capacidadLitros ?: 0.0,
            litrosHoy    = hoy.sumOf { it.litros }
        )
    }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), MiRutaUiState())

    init {
        refrescar(silencioso = true)

        // Recalcula el plan cuando cambian las paradas atendidas o el chofer avanza ~300 m
        combine(
            rutaConAtendidos.map { it?.ruta }.distinctUntilChanged(),
            rastreo.ultimaLectura.map { it?.coordenada?.redondeada() }.distinctUntilChanged()
        ) { ruta, _ -> ruta }
            .onEach { ruta -> if (ruta != null) recalcularPlan(ruta) }
            .launchIn(viewModelScope)

        // Al RECUPERAR la señal se descarga la ruta (drop(1): el valor inicial ya lo cubre refrescar())
        monitor.enLinea.drop(1).filter { it }.onEach { refrescar(silencioso = true) }.launchIn(viewModelScope)

        // Si el GPS falla (permiso revocado, GPS apagado) se avisa al chofer
        rastreo.error.filterNotNull().onEach { mensaje.value = "GPS: $it" }.launchIn(viewModelScope)

        viewModelScope.launch {
            proveedor.ultimaConocida()?.let { rastreo.registrar(it) }
        }
    }

    /**
     * El chofer vuelve a la pantalla (o a la app): se descarga de nuevo la ruta para ver
     * los cambios del administrador (otra ruta, proveedores, camino trazado). Como mucho
     * una vez cada 30 s para no repetir la descarga del inicio.
     */
    fun alVolverAPantalla() {
        if (reloj() - ultimaDescarga >= 30_000) refrescar(silencioso = true)
    }

    fun refrescar(silencioso: Boolean = false) {
        ultimaDescarga = reloj()
        viewModelScope.launch {
            if (!monitor.hayConexion()) {
                cargando.value = false
                if (!silencioso) mensaje.value = "Sin señal: mostrando la última ruta descargada"
                return@launch
            }
            cargando.value = true
            repositorio.refrescar()
                .onSuccess { if (!silencioso) mensaje.value = "Ruta actualizada" }
                .onFailure { e ->
                    mensaje.value = (e as? ErrorHttp)?.detalle ?: "No se pudo descargar la ruta"
                }
            cargando.value = false
        }
    }

    fun onPermisoResultado(concedido: Boolean) {
        permiso.value = concedido || proveedor.tienePermiso()
        if (permiso.value) {
            viewModelScope.launch { proveedor.ultimaConocida()?.let { rastreo.registrar(it) } }
        } else {
            mensaje.value = "Sin permiso de ubicación no se puede trazar tu recorrido"
        }
    }

    fun iniciarRuta() {
        if (!proveedor.tienePermiso()) {
            mensaje.value = "Activa el permiso de ubicación para iniciar la ruta"
            return
        }
        control.iniciar()   // en Android levanta el servicio en primer plano
        rastreo.iniciar()   // idempotente: si el servicio ya lo inició no pasa nada
        mensaje.value = "Ruta iniciada: registrando tu recorrido 🚛"
    }

    fun finalizarRuta() {
        control.detener()
        rastreo.detener()
        viewModelScope.launch {
            val estado = uiState.value
            resumen.value = ResumenRuta.calcular(
                acopiosHoy = acopiosHoy.value,
                paradas    = estado.ruta?.ruta?.paradas.orEmpty(),
                km         = estado.recorridoKm,
                inicioMs   = rastreo.inicioRecorrido(inicioDelDia),
                ahoraMs    = reloj()
            )
        }
    }

    fun cerrarResumen() {
        resumen.value = null
        mensaje.value = "Ruta finalizada. El recorrido se sube al panel."
    }

    fun limpiarMensaje() = mensaje.update { null }

    /**
     * Guarda la ubicación actual como la del predio del ganadero (para paradas "sin GPS"
     * o mal ubicadas). Requiere señal: el dato vive en el servidor.
     */
    fun fijarUbicacion(parada: Parada) {
        viewModelScope.launch {
            val lectura = rastreo.ultimaLectura.value ?: proveedor.ultimaConocida()
            when {
                lectura == null ->
                    mensaje.value = "Aún no hay señal GPS. Sal al aire libre y espera unos segundos."
                (lectura.precisionM ?: 0.0) > 50 ->
                    mensaje.value = "GPS impreciso (±${lectura.precisionM?.toInt()} m). Espera un momento e inténtalo de nuevo."
                !monitor.hayConexion() ->
                    mensaje.value = "Necesitas señal para guardar la ubicación de ${parada.ganaderoNombre}."
                else -> runCatching { ubicacionRemota.fijar(parada.ganaderoId, lectura) }
                    .onSuccess {
                        mensaje.value = "📍 Ubicación de ${parada.ganaderoNombre} guardada"
                        refrescar(silencioso = true)
                    }
                    .onFailure { e -> mensaje.value = (e as? ErrorHttp)?.detalle ?: "No se pudo guardar la ubicación" }
            }
        }
    }

    private fun recalcularPlan(ruta: Ruta) {
        // Cancela el cálculo anterior: una respuesta vieja de OSRM no debe pisar el plan nuevo
        trabajoPlan?.cancel()
        trabajoPlan = viewModelScope.launch {
            // Ruta trazada por el administrador: manda sobre el cálculo automático
            if (ruta.trazado.size >= 2) {
                plan.value = planificador.planTrazado(ruta.trazado, ruta.paradas)
                return@launch
            }
            val desde = rastreo.ultimaLectura.value?.coordenada ?: ruta.planta
            val enRecta = planificador.planEnLineaRecta(desde, ruta.paradas, ruta.planta)
            plan.value = enRecta
            if (!monitor.hayConexion() || enRecta.trazo.size < 2) return@launch

            // Con señal se pide el camino real por las trochas (OSRM)
            runCatching { rutaVial.trazar(enRecta.trazo) }.getOrNull()?.let { vial ->
                plan.value = enRecta.copy(
                    trazo        = vial.puntos,
                    distanciaKm  = vial.distanciaKm,
                    duracionMin  = planificador.duracionMin(vial.distanciaKm, enRecta.orden.size),
                    porCarretera = true
                )
            }
        }
    }

    private fun inicioDelDia(): Long = Tiempo.inicioDelDiaMs(reloj())

    /** ~300 m de resolución: evita recalcular el plan con cada lectura del GPS. */
    private fun Coordenada.redondeada() = Coordenada(
        (lat * 300).toLong() / 300.0,
        (lon * 300).toLong() / 300.0
    )
}
