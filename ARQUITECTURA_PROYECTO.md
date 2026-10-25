# ProLech Android — Arquitectura y Estructura de Carpetas

## Visión General

El proyecto es un **Kotlin Multiplatform (KMP) + Jetpack Compose** estructurado en
capas según **Clean Architecture**. La separación entre módulos garantiza que la
lógica de negocio (commonMain) sea independiente de las APIs Android (androidApp).

---

## Estructura de Módulos

```
ProLech/
├── app/
│   ├── androidApp/          ← APK Android (minSdk 24, compileSdk 36)
│   ├── shared/              ← Lógica de negocio + UI Compose multiplataforma
│   ├── desktopApp/          ← Scaffolding (no activo)
│   ├── webApp/              ← Scaffolding (no activo)
│   └── iosApp/              ← Scaffolding (no activo)
├── core/                    ← Utilidades puras KMP
├── server/                  ← Servidor Ktor (eliminado del APK)
└── gradle/
    └── libs.versions.toml   ← Version Catalog centralizado
```

---

## Estructura Detallada: `androidApp/src/main/kotlin/com/example/prolechc/`

```
com.example.prolechc/
│
├── MainActivity.kt                      ← Entry point; instancia AppNavGraph
│
├── navigation/
│   └── AppNavGraph.kt                   ← Grafo completo (9 rutas); inyección DI
│
├── data/
│   ├── api/
│   │   ├── ApiModels.kt                 ← DTOs Request/Response (Gson @SerializedName)
│   │   ├── ProLechApiService.kt         ← Interfaz Retrofit (6 endpoints)
│   │   └── RetrofitClient.kt            ← Singleton OkHttp + AuthInterceptor
│   │
│   ├── local/
│   │   └── TokenDataStore.kt            ← DataStore Preferences (Bearer Token)
│   │
│   ├── repository/
│   │   ├── AuthRepository.kt            ← POST /api/login, logout, isLoggedIn
│   │   ├── MiRutaRepository.kt          ← GET  /api/mi-ruta
│   │   ├── AcopioRepository.kt          ← POST /api/acopios
│   │   └── RepositoryAdapters.kt        ← Adapta repos Android → interfaces KMP
│   │
│   └── services/
│       └── GpsTrackingService.kt        ← Foreground Service GPS (15 s)
│
└── ui/
    ├── viewmodels/
    │   ├── MapaChoferViewModel.kt        ← Estado jornada + posición GPS
    │   └── SolicitarRutaViewModel.kt     ← Formulario solicitud de ruta
    │
    └── screens/
        ├── MapaChoferScreen.kt           ← GoogleMap + Accompanist + FAB
        └── SolicitarRutaScreen.kt        ← Dropdown + TextField + Toast
```

---

## Estructura Detallada: `shared/src/commonMain/kotlin/com/example/prolechc/`

```
com.example.prolechc/
│
├── App.kt                               ← @Composable raíz (obsoleto; reemplazado por AppNavGraph)
│
├── data/
│   ├── Models.kt                        ← Enums, data classes, mock data
│   └── repositories/
│       └── IRepositories.kt             ← Interfaces KMP + DTOs compartidos
│
├── navigation/
│   └── NavGraph.kt                      ← ProLechNavGraph (solo KMP, sin Maps)
│
├── ui/
│   ├── components/
│   │   └── BottomNav.kt                 ← ProLechBottomBar (4 tabs)
│   ├── screens/
│   │   ├── LoginScreen.kt               ← Email + Password + LaunchedEffect
│   │   ├── DashboardScreen.kt           ← Cisterna + grid de acciones
│   │   ├── AcopioScreen.kt              ← Formulario Lactoscan
│   │   ├── RegistroExitosoScreen.kt     ← Ticket de confirmación
│   │   ├── HistorialScreen.kt           ← LazyColumn filtrable
│   │   ├── SyncScreen.kt                ← Estado de sincronización
│   │   └── MiRutaScreen.kt              ← Lista de paradas de la ruta
│   └── theme/
│       └── ProLechTheme.kt              ← MaterialTheme + paleta ProLech
│
└── viewmodel/
    └── ProLechViewModel.kt              ← ViewModel KMP; acepta interfaces repo
```

---

## Capas de Clean Architecture

```
┌──────────────────────────────────────────────────────────────────────┐
│  CAPA UI  (ui/screens + ui/viewmodels)                               │
│  • Composables: estado inmutable, sin lógica de negocio              │
│  • ViewModels: StateFlow<UiState>, no instancian Retrofit             │
└─────────────────────────────────┬────────────────────────────────────┘
                                  │ observa StateFlow / llama funciones
┌─────────────────────────────────▼────────────────────────────────────┐
│  CAPA DOMINIO  (data/repositories/IRepositories.kt)                  │
│  • Interfaces KMP: IAuthRepository, IMiRutaRepository, IAcopio...    │
│  • DTOs compartidos: RepoResult<T>, RutaInfo, AcopioCreado, etc.     │
│  • Sin dependencias Android ni Retrofit                              │
└─────────────────────────────────┬────────────────────────────────────┘
                                  │ implementa interfaces
┌─────────────────────────────────▼────────────────────────────────────┐
│  CAPA DATOS  (data/api + data/local + data/repository)               │
│  • RetrofitClient: OkHttp singleton + AuthInterceptor Bearer Token   │
│  • Repositories: mapean ApiResult → RepoResult                       │
│  • TokenDataStore: DataStore Preferences (token + datos usuario)     │
│  • GpsTrackingService: Foreground Service independiente              │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Flujo de Datos: Login → GPS Tracking

```
1. LoginScreen
     └─ viewModel.login(email, password)
           └─ AuthRepository.login()
                 └─ RetrofitClient → POST /api/login
                       └─ TokenDataStore.saveSession(token, ...)

2. MapaChoferScreen (permisos OK)
     └─ FAB "INICIAR JORNADA"
           └─ viewModel.iniciarJornada()
           └─ context.startForegroundService(GpsTrackingService)
                 └─ FusedLocationProviderClient.requestLocationUpdates()
                       └─ cada 15 s → serviceScope.launch { POST /api/tracking }

3. FAB "TERMINAR JORNADA"
     └─ viewModel.terminarJornada()
     └─ context.stopService(GpsTrackingService)
           └─ onDestroy() → removeLocationUpdates + scope.cancel()
```

---

## Endpoints de la API

| Método | Ruta                   | Auth   | Pantalla que lo consume       |
|--------|------------------------|--------|-------------------------------|
| POST   | `/api/login`           | ❌      | LoginScreen                   |
| POST   | `/api/logout`          | Bearer | (logout global)               |
| GET    | `/api/mi-ruta`         | Bearer | Dashboard / MiRutaScreen      |
| POST   | `/api/acopios`         | Bearer | AcopioScreen                  |
| POST   | `/api/tracking`        | Bearer | GpsTrackingService (automático)|
| POST   | `/api/solicitar-ruta`  | Bearer | SolicitarRutaScreen           |
| GET    | `/api/estado-solicitud-ruta` | Bearer | SolicitarRutaScreen (tarjeta de estado) |
| GET    | `/api/reuniones`       | Bearer | ReunionesScreen               |
| GET    | `/api/notificaciones`  | Bearer | NotificacionesScreen          |
| GET/POST/PUT/DELETE | `/api/acopios-campo[/{uuid}]` | Bearer | SyncManager (composeApp, offline-first) |

`POST /api/acopios` acepta además `foto_evidencia` (JPEG en Base64) para la
leche rechazada; el backend la guarda en `storage/app/public/evidencias/`.

---

## Dependencias Clave (libs.versions.toml)

| Librería                          | Versión   | Uso                              |
|-----------------------------------|-----------|----------------------------------|
| `retrofit`                        | 2.11.0    | Cliente HTTP REST                |
| `okhttp`                          | 4.12.0    | Interceptor Bearer + Logging     |
| `gson`                            | 2.11.0    | Deserialización JSON             |
| `datastore-preferences`           | 1.1.7     | Token Sanctum persistente        |
| `play-services-location`          | 21.3.0    | FusedLocationProviderClient GPS  |
| `maps-compose`                    | 6.4.1     | `GoogleMap()` nativo en Compose  |
| `accompanist-permissions`         | 0.37.2    | Permisos en Compose              |
| `kotlinx-coroutines-android`      | 1.11.0    | viewModelScope + serviceScope    |

---

## Permisos Android (AndroidManifest.xml)

| Permiso                              | Para qué                                   |
|--------------------------------------|--------------------------------------------|
| `INTERNET`                           | Llamadas a la API Laravel                  |
| `ACCESS_NETWORK_STATE`               | Verificar conectividad antes de sync       |
| `ACCESS_FINE_LOCATION`               | GPS preciso en primer plano                |
| `ACCESS_COARSE_LOCATION`             | Alternativa por red / batería baja         |
| `ACCESS_BACKGROUND_LOCATION`         | Rastreo continuo (solicitar por separado)  |
| `FOREGROUND_SERVICE`                 | Iniciar GpsTrackingService                 |
| `FOREGROUND_SERVICE_LOCATION`        | Acceso a GPS dentro del Foreground Service |
| `WAKE_LOCK`                          | Mantener CPU activa durante el rastreo     |

---

## Google Maps API Key

La URL del backend y la clave de Maps se leen de `local.properties`
(no se sube al repositorio) en `app/androidApp/build.gradle.kts`:

```
# local.properties
prolech.apiUrl=http://192.168.1.5:8000/   # IP de la PC con Laravel (default: 10.0.2.2 del emulador)
MAPS_API_KEY=AIzaSy...                     # Google Cloud Console → Maps SDK for Android
```

- `prolech.apiUrl` → `BuildConfig.API_URL` → `RetrofitClient.BASE_URL`
- `MAPS_API_KEY` → `manifestPlaceholders` → `${MAPS_API_KEY}` en `AndroidManifest.xml`

Sin `MAPS_API_KEY` la app compila, pero el mapa del chofer se ve en blanco.

---

## Modo Offline (acopios sin señal)

```
AcopioScreen → AcopioRepository.crearAcopio()
      │  sin red
      ▼
ColaAcopiosOffline  → filesDir/acopios_pendientes/<client_uuid>.json  (incluye foto)
      │
      ▼
SyncAcopiosWorker (WorkManager, requiere red, backoff exponencial 30 s)
      └─ POST /api/acopios con client_uuid → el backend no duplica reintentos
```

- Los pendientes sobreviven al cierre de la app y a reinicios del celular.
- Se envían solos al volver la red; el botón "Sincronizar" fuerza el envío.
- Si el servidor rechaza uno (4xx, p. ej. productor suspendido), queda marcado
  "✗ Rechazado" en la pantalla Sync con el motivo y la opción "Descartar".
- Cada pendiente guarda el chofer que lo registró: solo se envía con su sesión.
