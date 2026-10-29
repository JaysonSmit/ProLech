# ProLech — Evaluación U2: Persistencia local y Offline-First

Módulo: `composeApp` (Kotlin Multiplatform: Android + iOS).
Backend: `DESARROLLO DE APLICACIONES MOVILES/backend prolech/prolech-backend` (Laravel 11).

## Arquitectura

```
UI (Compose)  ──►  AcopioViewModel  ──►  Casos de uso  ──►  AcopioOfflineRepository
                        ▲                                         │ escribe
                        │ Flow (asFlow().mapToList())              ▼
                        └──────────────────────────────  SQLDelight (única fuente de verdad)
                                                                  ▲  │
                         MonitorConectividad ──► SyncManager ─────┘  │ Push / Pull
                                                      └──────────────► Ktor ──► API Laravel
```

- La UI **nunca** lee de la red: solo observa SQLDelight.
- Toda escritura (crear, editar, eliminar) se guarda primero en el celular con un
  `estadoSync` pendiente y luego el `SyncManager` la sube.
- Al recuperar la red (`MonitorConectividad.enLinea` pasa a `true`) se sincroniza solo.

## Fases y archivos

| Fase | Qué | Dónde |
|---|---|---|
| 1 | Plugin y drivers SQLDelight 2.1.0 | `gradle/libs.versions.toml`, `composeApp/build.gradle.kts` |
| 1 | Esquema `AcopioEntity` + consultas | `composeApp/src/commonMain/sqldelight/pe/edu/upeu/prolech/db/Acopio.sq` |
| 1 | Catálogo offline de ganaderos | `.../db/Ganadero.sq` |
| 2 | `expect/actual DriverFactory` | `commonMain/.../data/local/db/`, `androidMain`, `iosMain` |
| 2 | `EstadoSync`, `Acopio`, `EstadoCalidad` | `commonMain/.../domain/model/AcopioCampo.kt` |
| 2 | `AcopioLocal` + `AcopioLocalSqlDelight` | `commonMain/.../data/local/` |
| 2 | Koin (`databaseModule`, casos de uso, VMs) | `commonMain/.../di/AppModules.kt`, `PlatformModule.*.kt` |
| 3 | `MonitorConectividad` (Android `NetworkCallback`, iOS `NWPathMonitor`) | `.../data/network/` |
| 4 | `SyncManager` (Push, Pull, Last-Write-Wins) | `.../data/sync/SyncManager.kt` |
| 4 | `AcopioRemotoKtor`, `GanaderoRemotoKtor` | `.../data/remote/datasource/` |
| 5 | Login, `AcopioViewModel`, `AcopioScreen` | `.../presentation/login/`, `.../presentation/acopios/` |
| 6 | Pruebas unitarias | `composeApp/src/commonTest/kotlin/com/example/prolechc/offline/` |

## Reglas implementadas

- **Borrado:** si el registro está `PENDIENTE_CREAR` (nunca subió) se borra físicamente;
  si ya estaba sincronizado pasa a `PENDIENTE_ELIMINAR` y se oculta de la lista.
- **Edición:** un registro sincronizado pasa a `PENDIENTE_ACTUALIZAR`; uno que aún no
  subió sigue como `PENDIENTE_CREAR`.
- **Last-Write-Wins:** gana el `actualizadoEn` (ms) más reciente, tanto en la app
  (`SyncManager.debeAplicarServidor`) como en Laravel (`AcopioCampoController::guardar`).
- **Errores de red:** un registro que falla queda pendiente y se reintenta en la
  siguiente sincronización; nada se pierde.
- **Sesión:** sin token no se llama a la API; al iniciar sesión se suben los pendientes.
  No se permite cerrar sesión con cambios sin sincronizar.
- **Catálogo:** los ganaderos de `/api/mi-ruta` se guardan en el celular; los
  suspendidos aparecen deshabilitados.
- **Calidad:** > 3 % de agua → RECHAZADO; algo de agua o densidad fuera de
  1.028–1.034 → OBSERVADO; si no → APROBADO. *(Ajustar a la norma real del proyecto.)*

## Endpoints usados (ya existen en el backend)

| Método | Ruta | Uso |
|---|---|---|
| POST | `/api/login` | Token Sanctum |
| GET | `/api/mi-ruta` | Catálogo de ganaderos |
| GET | `/api/acopios-campo` | Pull |
| POST | `/api/acopios-campo` | Push de creaciones (UPSERT por `uuid`) |
| PUT | `/api/acopios-campo/{uuid}` | Push de ediciones |
| DELETE | `/api/acopios-campo/{uuid}` | Push de eliminaciones (404 = ya borrado) |

## Cómo ejecutar las pruebas

```bash
./gradlew :composeApp:testDebugUnitTest
```

29 pruebas en `commonTest` sobre SQLite real en memoria:

- `AcopioLocalSqlDelightTest` (6): consultas `.sq`, orden, upsert y reglas de borrado.
- `SyncManagerTest` (12): Modo Avión, sincronización automática al volver la red, Push,
  fallos del servidor, Pull, Last-Write-Wins en ambos sentidos y regla de calidad.
- `CatalogoYSesionTest` (4): descarga del catálogo, catálogo conservado si falla,
  sincronización bloqueada sin sesión y subida tras el login.
- `AcopioViewModelTest` (7): registro offline, validaciones, ganadero suspendido,
  calidad estimada, edición y cierre de sesión.

## Demostración en clase (Modo Avión)

1. Configurar `ApiConfig.BASE_URL` con la IP de la PC donde corre `php artisan serve --host=0.0.0.0`.
2. Iniciar sesión con un usuario de rol `chofer` (con internet).
3. Activar **Modo Avión**: el aviso superior se pone rojo ("Modo sin conexión").
4. Registrar 2 acopios, editar uno y eliminar otro: la lista se actualiza al instante
   y cada tarjeta muestra "⏳ Pendiente…".
5. Cerrar y volver a abrir la app: los datos siguen ahí (SQLite).
6. Desactivar Modo Avión: se sincroniza solo y las tarjetas pasan a "✓ Sincronizado".
7. Verificar en Laravel: `select * from acopios_campo;`.
