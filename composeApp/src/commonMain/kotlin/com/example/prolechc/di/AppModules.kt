package com.example.prolechc.di

import com.example.prolechc.data.local.AcopioLocal
import com.example.prolechc.data.local.AcopioLocalSqlDelight
import com.example.prolechc.data.local.GanaderoLocal
import com.example.prolechc.data.local.GanaderoLocalSqlDelight
import com.example.prolechc.data.local.db.DriverFactory
import com.example.prolechc.data.remote.HttpClientFactory
import com.example.prolechc.data.remote.datasource.AcopioRemoto
import com.example.prolechc.data.remote.datasource.AcopioRemotoKtor
import com.example.prolechc.data.remote.datasource.GanaderoRemoto
import com.example.prolechc.data.remote.datasource.GanaderoRemotoKtor
import com.example.prolechc.data.repository.AcopioOfflineRepositoryImpl
import com.example.prolechc.data.sync.SyncManager
import com.example.prolechc.domain.repository.AcopioOfflineRepository
import com.example.prolechc.domain.usecase.ActualizarAcopioUseCase
import com.example.prolechc.domain.usecase.EliminarAcopioUseCase
import com.example.prolechc.domain.usecase.LogoutUseCase
import com.example.prolechc.domain.usecase.ObservarAcopiosUseCase
import com.example.prolechc.domain.usecase.ObservarGanaderosUseCase
import com.example.prolechc.domain.usecase.RegistrarAcopioUseCase
import com.example.prolechc.domain.usecase.SincronizarAcopiosUseCase
import com.example.prolechc.domain.usecase.VerificarSesionUseCase
import com.example.prolechc.presentation.acopios.AcopioViewModel
import com.example.prolechc.presentation.login.LoginViewModel
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import kotlinx.coroutines.flow.map
import pe.edu.upeu.prolech.db.AppDatabase
import com.example.prolechc.data.remote.datasource.AcopioRemoteDataSource
import com.example.prolechc.data.remote.datasource.AuthRemoteDataSource
import com.example.prolechc.data.remote.datasource.NotificacionRemoteDataSource
import com.example.prolechc.data.remote.datasource.RutaRemoteDataSource
import com.example.prolechc.data.repository.AcopioRepositoryImpl
import com.example.prolechc.data.repository.AuthRepositoryImpl
import com.example.prolechc.data.repository.NotificacionRepositoryImpl
import com.example.prolechc.data.repository.RutaRepositoryImpl
import com.example.prolechc.domain.repository.AcopioRepository
import com.example.prolechc.domain.repository.AuthRepository
import com.example.prolechc.domain.repository.NotificacionRepository
import com.example.prolechc.domain.repository.RutaRepository
import com.example.prolechc.domain.usecase.CrearAcopioUseCase
import com.example.prolechc.domain.usecase.GetHistorialAcopiosUseCase
import com.example.prolechc.domain.usecase.GetMiRutaUseCase
import com.example.prolechc.domain.usecase.GetNotificacionesUseCase
import com.example.prolechc.domain.usecase.LoginUseCase
import com.example.prolechc.presentation.productos.AcopioListViewModel
import org.koin.core.module.dsl.viewModelOf
import org.koin.core.module.dsl.viewModel
import org.koin.core.qualifier.named
import com.example.prolechc.data.remote.datasource.RutaVialOsrm
import com.example.prolechc.data.remote.datasource.RutaVialRemoto
import com.example.prolechc.data.remote.datasource.TrackingRemoto
import com.example.prolechc.data.remote.datasource.TrackingRemotoKtor
import com.example.prolechc.data.repository.RutaDiaRepositorio
import com.example.prolechc.data.tracking.RastreoGps
import com.example.prolechc.presentation.ruta.MiRutaViewModel
import io.ktor.client.HttpClient
import io.ktor.client.plugins.HttpTimeout
import org.koin.core.module.dsl.factoryOf
import org.koin.core.module.dsl.singleOf
import org.koin.dsl.bind
import org.koin.dsl.module

// ── Network ───────────────────────────────────────────────────────────────────

val networkModule = module {
    single {
        val tokenStorage = get<com.example.prolechc.data.local.TokenStorage>()
        val estadoSesion = get<com.example.prolechc.data.local.EstadoSesion>()
        val scope = get<CoroutineScope>(named(SCOPE_APP))
        HttpClientFactory.create(
            tokenProvider = { kotlinx.coroutines.runBlocking { tokenStorage.getToken() } },
            // 401 en cualquier llamada: se olvida el token y la app pide el login (datos offline intactos)
            alSesionVencida = {
                estadoSesion.marcarVencida()
                scope.launch { tokenStorage.clearToken() }
            }
        )
    }
    // Cliente SIN token para servicios de terceros (OSRM): nunca se filtran credenciales
    single(named(CLIENTE_EXTERNO)) {
        HttpClient {
            install(HttpTimeout) {
                requestTimeoutMillis = 15_000
                connectTimeoutMillis = 8_000
            }
        }
    }
    // Ámbito de la app para tareas en segundo plano (sync, GPS)
    single(named(SCOPE_APP)) { CoroutineScope(SupervisorJob() + Dispatchers.Default) }
}

const val CLIENTE_EXTERNO = "cliente_externo"
const val SCOPE_APP = "scope_app"

// ── Base de datos local (SQLDelight) + Offline-First ──────────────────────────

val databaseModule = module {
    single { AppDatabase(get<DriverFactory>().createDriver()) }
    single<AcopioLocal> { AcopioLocalSqlDelight(get()) }
    single { com.example.prolechc.data.local.ConfigLocal(get()) }
    single { com.example.prolechc.data.local.LimpiezaSesion(database = get(), rastreo = get(), control = get()) }
    single<GanaderoLocal> { GanaderoLocalSqlDelight(get()) }
    single<AcopioRemoto> { AcopioRemotoKtor(get()) }
    single<GanaderoRemoto> { GanaderoRemotoKtor(get()) }

    // ── Ruta del día, GPS y trazado ──────────────────────────────────────────
    single<TrackingRemoto> { TrackingRemotoKtor(get()) }
    single<com.example.prolechc.data.remote.datasource.GanaderoUbicacionRemoto> {
        com.example.prolechc.data.remote.datasource.GanaderoUbicacionKtor(get())
    }
    single<RutaVialRemoto> { RutaVialOsrm(get(named(CLIENTE_EXTERNO))) }
    single {
        val rutaRemota = get<RutaRemoteDataSource>()
        RutaDiaRepositorio(database = get(), descargarJson = { rutaRemota.getMiRutaJson() })
    }
    single {
        RastreoGps(
            database  = get(),
            proveedor = get(),
            remoto    = get(),
            monitor   = get(),
            scope     = get(named(SCOPE_APP))
        )
    }

    single { com.example.prolechc.data.local.EstadoSesion() }
    single<com.example.prolechc.data.sync.SubidorFotos> {
        com.example.prolechc.data.remote.datasource.FotoEvidenciaKtor(get())
    }

    single {
        val authRepository = get<AuthRepository>()
        val tokenStorage = get<com.example.prolechc.data.local.TokenStorage>()
        val estadoSesion = get<com.example.prolechc.data.local.EstadoSesion>()
        val rutaDia = get<RutaDiaRepositorio>()
        val scope = get<CoroutineScope>(named(SCOPE_APP))
        SyncManager(
            local          = get(),
            remoto         = get(),
            monitor        = get(),
            scope          = scope,
            ganaderoLocal  = get(),
            ganaderoRemoto = get(),
            sesionActiva   = { authRepository.isLoggedIn() },
            subidorGps     = get<RastreoGps>(),
            refrescarRuta  = { rutaDia.refrescar().getOrThrow() },
            subidorFotos   = get(),
            // 401: se olvida el token (los datos offline se conservan) y la app pide el login
            alSesionVencida = {
                estadoSesion.marcarVencida()
                scope.launch { tokenStorage.clearToken() }
            }
        )
    }
    single<AcopioOfflineRepository> {
        AcopioOfflineRepositoryImpl(local = get(), syncManager = get(), ganaderoLocal = get())
    }
}

// ── DataSources ───────────────────────────────────────────────────────────────

val dataSourceModule = module {
    singleOf(::AuthRemoteDataSource)
    singleOf(::RutaRemoteDataSource)
    singleOf(::AcopioRemoteDataSource)
    singleOf(::NotificacionRemoteDataSource)
}

// ── Repositories ─────────────────────────────────────────────────────────────

val repositoryModule = module {
    singleOf(::AuthRepositoryImpl)         bind AuthRepository::class
    singleOf(::RutaRepositoryImpl)         bind RutaRepository::class
    singleOf(::AcopioRepositoryImpl)       bind AcopioRepository::class
    singleOf(::NotificacionRepositoryImpl) bind NotificacionRepository::class
}

// ── Use Cases ─────────────────────────────────────────────────────────────────

val useCaseModule = module {
    factoryOf(::LoginUseCase)
    factoryOf(::GetMiRutaUseCase)
    factoryOf(::CrearAcopioUseCase)
    factoryOf(::GetHistorialAcopiosUseCase)
    factoryOf(::GetNotificacionesUseCase)
    factoryOf(::ObservarAcopiosUseCase)
    factoryOf(::ObservarGanaderosUseCase)
    factoryOf(::VerificarSesionUseCase)
    factory {
        val limpieza = get<com.example.prolechc.data.local.LimpiezaSesion>()
        LogoutUseCase(repository = get(), antesDeSalir = { limpieza.ejecutar() })
    }
    factoryOf(::RegistrarAcopioUseCase)
    factoryOf(::ActualizarAcopioUseCase)
    factoryOf(::EliminarAcopioUseCase)
    factoryOf(::SincronizarAcopiosUseCase)
}

// ── ViewModels ────────────────────────────────────────────────────────────────

val viewModelModule = module {
    viewModelOf(::AcopioListViewModel)
    // Constructores con parámetros por defecto: se declaran explícitos para que Koin no los pida
    viewModel {
        AcopioViewModel(
            observarAcopios    = get(),
            observarGanaderos  = get(),
            registrarAcopio    = get(),
            actualizarAcopio   = get(),
            eliminarAcopio     = get(),
            sincronizarAcopios = get(),
            logout             = get(),
            monitor            = get(),
            syncManager        = get(),
            accionesTicket     = get(),
            contexto           = run {
                val config = get<com.example.prolechc.data.local.ConfigLocal>()
                get<RutaDiaRepositorio>().observar().map { guardada ->
                    com.example.prolechc.presentation.acopios.ContextoRuta(
                        chofer          = config.nombreChofer(),
                        ruta            = guardada?.ruta?.rutaNombre.orEmpty(),
                        capacidadLitros = guardada?.ruta?.capacidadLitros ?: 0.0
                    )
                }
            }
        )
    }
    viewModel {
        LoginViewModel(
            loginUseCase = get(), monitor = get(), syncManager = get(),
            config = get(), estadoSesion = get(), limpieza = get()
        )
    }
    viewModel {
        MiRutaViewModel(
            repositorio     = get(),
            rastreo         = get(),
            control         = get(),
            proveedor       = get(),
            rutaVial        = get(),
            monitor         = get(),
            observarAcopios = get(),
            ubicacionRemota = get()
        )
    }
}

/** Lista consolidada de todos los módulos commonMain. */
val appModules = listOf(
    networkModule,
    databaseModule,
    dataSourceModule,
    repositoryModule,
    useCaseModule,
    viewModelModule
)
