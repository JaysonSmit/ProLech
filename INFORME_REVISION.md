# ProLech — Informe de revisión completa (app Android + web)

Fecha: 25/10/2026 · Alcance: app móvil KMP (`composeApp` + `composeAndroidApp`), backend Laravel
(`backend prolech/prolech-backend`) y su integración real.

---

## 1. Resultado de las pruebas

| Suite | Dónde corre | Resultado |
|---|---|---|
| Backend Laravel (`php artisan test`) | SQLite en memoria | **50 / 50** ✅ |
| App: `commonTest` en Android (`:composeApp:testAndroidHostTest`) | JVM del host Android | **45 / 45** ✅ |
| App: `commonTest` + grafo de Koin en JVM | proyecto de verificación | **47 / 47** ✅ |
| **E2E app ↔ web** (código Kotlin real contra Laravel real) | `http://127.0.0.1:8000` + MySQL | **12 / 12 pasos** ✅ |
| Compilación APK nuevo `:composeAndroidApp` | AGP 9.3.3 / SDK 36 | ✅ 17,7 MB |
| Compilación APK antiguo `:app:androidApp` | — | ✅ (sin regresiones) |
| Barrido del panel admin (todas las páginas GET `/admin/*`) | Laravel test | ✅ ninguna da error 500 |

### 1.1 Prueba E2E (la app hablando con el servidor de verdad)

Se ejecutaron las clases reales de la app (`AuthRepositoryImpl`, `SyncManager`, `AcopioRemotoKtor`,
`RutaDiaRepositorio`, `RastreoGps`, `GanaderoUbicacionKtor`) contra el Laravel local, con un chofer y
ganaderos de prueba aislados que luego se borraron:

1. ✓ Login (y rechazo de contraseña incorrecta con mensaje claro)
2. ✓ `mi-ruta` devuelve la **zona asignada** (Collana 1) con las paradas **ordenadas por cercanía**
3. ✓ Acopio registrado en **Modo Avión** queda `PENDIENTE_CREAR`
4. ✓ Al volver la señal: `POST /acopios-campo` con el UUID generado en el celular
5. ✓ Catálogo de ganaderos guardado offline y la parada aparece **atendida** en la ruta
6. ✓ Edición: `PUT /acopios-campo/{uuid}`
7. ✓ Conflicto **Last-Write-Wins**: gana la versión más reciente en servidor y celular
8. ✓ Pull: un registro creado en otro equipo llega al celular
9. ✓ Eliminación: `DELETE /acopios-campo/{uuid}`
10. ✓ GPS: 3 puntos guardados **sin señal** y subidos en lote (`/tracking/lote`), sin duplicar
11. ✓ Fijar la ubicación GPS de un ganadero desde la app (y **403** si no es de su ruta)
12. ✓ Logout revoca el token en el servidor (la siguiente llamada recibe 401)

Verificado además en el lado web: el JSON del **Mapa GPS del panel** mostró la zona del chofer,
los 3 puntos del recorrido y el estado "finalizado".

---

## 2. Bugs encontrados y corregidos

### Críticos (la app no funcionaba o se cerraba)

| # | Bug | Efecto | Corrección |
|---|---|---|---|
| 1 | **`composeApp` estaba desactivado** en `settings.gradle.kts` (AGP 9 no admite `kotlin.multiplatform` + `com.android.application` en el mismo módulo) | La app offline-first, GPS y mapa **no entraban en ningún APK** | `composeApp` → librería KMP (`com.android.kotlin.multiplatform.library`); nuevo módulo `:composeAndroidApp` genera el APK "ProLech Acopio" |
| 2 | `kotlinx.datetime.Clock` no existe en la versión real usada (0.7.1) | **Crash** al registrar un acopio (`NoClassDefFoundError`) — también en `app/shared` | Helper `Tiempo` con `kotlin.time.Clock`; versión fijada en 0.7.1; prueba de regresión con el reloj real |
| 3 | IP del servidor **fija** en el código (`192.168.1.5`), distinta a la de la PC (`192.168.0.105`) | La app no podía conectarse | Campo **"Servidor" en el login**, guardado en SQLDelight (`ConfigLocal`) y aplicado al iniciar |
| 4 | `network_security_config` solo permitía HTTP hacia `192.168.1.5` | Con otra IP, Android **bloqueaba** las llamadas | Se permite HTTP en desarrollo (documentado: en producción usar HTTPS) |
| 5 | `HttpClientFactory` usaba mal `defaultRequest` | No compilaba y **el token nunca se enviaba** | Uso correcto de `defaultRequest { header(...) }` |
| 6 | `viewModelOf(::AcopioViewModel)` con parámetro por defecto (`reloj`) | Koin intentaría inyectar una función → **crash al abrir Acopios** | ViewModels con defaults declarados explícitos + **test que arranca Koin con todos los módulos reales** |
| 7 | "Añadir al carrito" en el detalle de producto (web) | Botón sin efecto (`json_encode` dentro de `{{ }}` rompía el JS) | `@json(...)` + `addMany()` |

### Importantes

| # | Bug | Corrección |
|---|---|---|
| 8 | `/api/mi-ruta` ignoraba la **rotación de zonas**: adivinaba la ruta por el último acopio | `RutaChoferService`: zona del mes → ruta manual → historial; paradas ordenadas (vecino más cercano), atendidos y suspendidos |
| 9 | El tracking solo guardaba la **última** posición: imposible trazar el recorrido | Tabla `tracking_gps_puntos` + `POST /api/tracking/lote` idempotente; el Mapa GPS del panel dibuja el trazo |
| 10 | Al cerrar sesión, el siguiente chofer **heredaba** puntos GPS pendientes, ruta y catálogo del anterior | `LimpiezaSesion`: apaga el GPS, sube lo pendiente y borra los datos locales |
| 11 | La app permitía **dos entregas del mismo ganadero el mismo día** (el backend clásico lo prohíbe) | Validación en `AcopioViewModel` con mensaje "ya entregó hoy" |
| 12 | Condición de carrera: una respuesta vieja de OSRM podía **pisar el plan nuevo** | Se cancela el cálculo anterior (`Job`) |
| 13 | La ruta se descargaba **dos veces** al abrir "Mi ruta" | `enLinea.drop(1)` |
| 14 | Si el GPS fallaba (permiso revocado/GPS apagado) no se avisaba | El error del GPS se muestra al chofer |
| 15 | `initKoin()` en iOS fallaría al recrear el ViewController (doble `startKoin`) | `initKoin` idempotente |
| 16 | Nuevas tablas sin migración: celulares con la BD v1 no las tendrían | Migraciones SQLDelight `1.sqm` y `2.sqm` |
| 17 | `getMiRuta` con 422/401 devolvía una ruta **vacía** en silencio | Lanza `ErrorHttp` con el mensaje del backend |
| 18 | Login fallido mostraba un error técnico de serialización | Mensajes claros: credenciales, sin acceso, servidor inalcanzable |
| 19 | Faltaban los íconos `mipmap` en el módulo de la app | Copiados al módulo del APK |
| 20 | Reportes usaban `MONTH()` (solo MySQL) | SQL portable (MySQL y SQLite) |
| 21 | `String.format` en `AcopioListScreen` (no existe en iOS) | Formateador común |
| 22 | Logcat imprimía el token Bearer | `sanitizeHeader(Authorization)` |
| 23 | Imágenes de la tienda sin cargar (`public/storage` no era enlace) y placeholder inexistente | Junction `storage:link` + `producto-placeholder.png` |
| 24 | Caché de mapas en almacenamiento externo | Carpeta privada de la app (sin permisos extra) |

---

## 3. Mejoras agregadas

**App Android**
- 🗺️ Pestaña **Mi ruta**: mapa OpenStreetMap (sin API key), camino sugerido por carretera (OSRM) o en línea recta sin señal, recorrido real del día, siguiente parada, "Cómo llegar" y km/tiempo estimado.
- 📍 **"Fijar aquí"**: el chofer guarda la ubicación GPS del predio de ganaderos nuevos (solo los de su ruta; rechaza GPS impreciso > 50 m en la app y > 100 m en el servidor).
- 🛰️ Rastreo en **segundo plano** con servicio en primer plano; los puntos se guardan offline y se suben en lote.
- 🖥️ **Servidor configurable** desde el login.
- 🎨 Tema "Leche & Lago" (mismo de la web), login rediseñado, tarjeta de resumen del día, barra de navegación con contador de pendientes.

**Web**
- Tienda rediseñada, checkout en 3 pasos (Yape con QR y captura, tarjeta con validación Luhn —solo se guardan los últimos 4 dígitos—, efectivo), **boleta B001-NNNNNNNN** con IGV e importe en letras, enlace firmado.
- Panel: nueva paleta, **Mapa GPS con el trazo de cada chofer y su zona**, formulario de ganaderos con **latitud/longitud**.

---

## 4. Pruebas automáticas nuevas

- **Backend** (+26): `RutaYTrackingApiTest` (zona, orden por cercanía, atendidos, suspendidos, 422, 401, lote idempotente, finalizado, validaciones, permisos, fijar ubicación), `TiendaCheckoutTest` (boleta correlativa, stock, tarjeta, Yape, DNI ≥ S/ 700, firma de la boleta, IGV, importe en letras), `PanelAdminSmokeTest` (barrido de todas las páginas del panel).
- **App** (+): reloj real (regresión), duplicado del mismo día, planificador de ruta, OSRM, rastreo GPS offline, caché de ruta, grafo completo de Koin, E2E contra el servidor.

---

## 5. Cómo ejecutarlo

```bash
# 1. Backend accesible desde el celular (misma red Wi-Fi)
php artisan serve --host=0.0.0.0 --port=8000

# 2. Instalar la app (celular con depuración USB)
./gradlew :composeAndroidApp:installDebug

# 3. Pruebas
php artisan test
./gradlew :composeApp:testAndroidHostTest
```

En el login, toca **"▸ Servidor"** y escribe la IP de la PC (hoy `192.168.0.105:8000`).

### Configuración pendiente en el panel (datos, no código)
1. **Proveedores**: asignar zona (y GPS si se conoce) a cada ganadero.
2. **Rotación Zonas**: generar las asignaciones del mes → define la ruta de cada chofer.
3. **Configuración** (tabla `configuracion`): `empresa_ruc`, `empresa_razon_social`, `yape_numero`, `planta_lat`, `planta_lon`.

---

## 6. Límites conocidos (no verificados aquí)

- **No probado en un celular físico ni emulador**: GPS real, mapa osmdroid y servicio en segundo plano.
- **iOS** no se puede compilar en Windows (requiere Mac). Agregar a `Info.plist`: `NSLocationWhenInUseUsageDescription`.
- El servidor público de **OSRM** es para uso moderado; en producción conviene uno propio.
- La boleta es una representación interna; para validez tributaria se necesita un proveedor de facturación electrónica (SUNAT).
- Producción: publicar la API con **HTTPS** y desactivar el HTTP en `network_security_config.xml`.
