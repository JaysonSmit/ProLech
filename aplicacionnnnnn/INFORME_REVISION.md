# ProLech — Informe de revisión completa (app Android + web)

Fecha: 25/10/2026 · Alcance: app móvil KMP (`composeApp` + `composeAndroidApp`), backend Laravel
(`backend prolech/prolech-backend`) y su integración real.

---

## 1. Resultado de las pruebas

| Suite | Dónde corre | Resultado |
|---|---|---|
| Backend Laravel (`php artisan test`) | SQLite en memoria | **82 / 82** ✅ (eran 50) |
| App: `commonTest` en Android (`:composeApp:testAndroidHostTest`) | JVM del host Android | **58 / 58** ✅ (eran 45) |
| **E2E completo contra el servidor real con los datos del seeder** | Laravel `:8001` + MySQL | **70 / 70 peticiones** ✅ |
| **APK release (R8 + firma) en emulador Android 14** | Pixel x86_64, Google APIs | ✅ login, ruta, mapa, acopio sin señal y sincronización |
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

## 6. Segunda ronda: las 18 mejoras

| # | Mejora | Qué se hizo | Estado |
|---|---|---|---|
| 1 | Servidor con HTTPS | `deploy/`: Docker (PHP-FPM 8.5 + MySQL 8.4) con **Caddy**, que saca y renueva el certificado Let's Encrypt solo; cabeceras HSTS/nosniff; `.env.production.example` y guía `DEPLOY.md`. El APK release solo acepta HTTPS. | ✅ listo; falta **tu** VPS y dominio |
| 2 | Git | Repos de app y backend con historial; `.gitignore` excluye llaves, zips y logs | ✅ |
| 3 | Prueba en emulador | Ver sección 6.1 | ✅ |
| 4 | Registros rechazados | Un 4xx definitivo marca el acopio con **error** (tarjeta roja con el motivo) y deja de reintentarse para siempre | ✅ |
| 5 | Sesión vencida | Un 401 al sincronizar lleva al login con aviso, **sin borrar** lo pendiente; no deja entrar a otro chofer con datos sin subir | ✅ |
| 6 | OCR del Lactoscan | Botón 📷 lee grasa, densidad, agua y temperatura de la pantalla (ML Kit, sin internet) | ✅ |
| 7 | Ticket | Ticket de 32 columnas: **PDF por WhatsApp** o **impresora Bluetooth** (ESC/POS) | ✅ |
| 8 | Capacidad del camión | Barra de llenado (`capacidad_camion_litros`, 1200 L por defecto) y aviso al 90 % | ✅ |
| 9 | Foto de evidencia | Foto del porongo/balde: se guarda offline y se sube al recuperar señal | ✅ |
| 10 | Resumen de ruta | Al terminar: litros, entregas, km, tiempo, ganaderos faltantes | ✅ |
| 11 | Panel en vivo | `/admin/en-vivo`: mapa con los camiones que se refresca cada 15 s | ✅ |
| 12 | Repetir recorrido | `/admin/operaciones/recorrido`: reproduce el trazo del día y calcula los km | ✅ |
| 13 | Zonas desde el mapa | `/admin/zonas/mapa`: arrastrar al ganadero para fijar su ubicación | ✅ |
| 14 | Exportar | CSV para Excel (acopios, liquidaciones, rutas) y versión imprimible / PDF | ✅ |
| 15 | Tienda | Seguimiento por código + celular, WhatsApp por cada estado, stock devuelto al cancelar, **boleta SUNAT vía Nubefact** | ✅ listo; falta **tu** cuenta Nubefact y Ultramsg |
| 16 | CI | GitHub Actions: backend (PHPUnit + imágenes Docker) y app (pruebas + APK como artefacto) | ✅ corre al subir a GitHub |
| 17 | Seguridad | Login: 5 intentos/min por correo (+30 por IP) en API y web. Las contraseñas demo ya no se publican fuera de `local` | ✅ |
| 18 | APK release | Firmado (`firma/prolech-release.jks`, fuera de git) con **R8** (minify + shrinkResources) | ✅ |

### 6.1 Prueba en emulador
Emulador Android 14 (API 34, Google APIs) con el **APK release firmado y con R8** (`pe.prolech.acopio` 2.1),
contra el Laravel de la PC (`http://10.0.2.2:8001`) y los datos del seeder. Capturas en `docs/capturas/`.

| Paso | Resultado |
|---|---|
| Arranque y login (chofer Carlos) | ✅ sin cierres; el HTTP solo funciona hacia el emulador (release = solo HTTPS) |
| Mi ruta | ✅ Collana 1, 3/5 atendidos, 23.6 km por recorrer, camión 106/1500 L, mapa OpenStreetMap con el camino por carretera |
| Acopio en **modo avión** | ✅ "Acopio guardado sin conexión", 1 por subir, 154 L |
| Volver la señal | ✅ se subió solo; en el servidor quedó con el UUID del celular, 48.5 L, grasa 3.7 % |
| Token revocado (sesión iniciada en otro equipo) | ✅ vuelve al login con aviso, sin perder datos |

**E2E del servidor (70 peticiones, 0 fallas)**: API del chofer (login, 422 con clave mala, `me`, `mi-ruta`, crear/editar/borrar
acopio, 422 con litros negativos, foto multipart, GPS en lote e individual, historial, notificaciones, reuniones, logout y 401
después), tienda (catálogo con sus 7 imágenes, checkout real → boleta B001, seguimiento con y sin el celular correcto),
35 páginas del panel con sesión de administrador (+ `/proveedor/*` correctamente prohibido), panel en vivo y las
descargas Excel/PDF/CSV. El límite de intentos del login también se comprobó en vivo (429 al 6.º intento).
Los datos de prueba se borraron al terminar (el stock se devolvió).

### 6.2 Bugs encontrados en esta ronda
- **El mapa no se veía en la app** ("Access blocked 403"): osmdroid identifica la app con el nombre del paquete y
  OpenStreetMap bloquea los `com.example.*`. Nuevo `applicationId` **`pe.prolech.acopio`** (Google Play tampoco acepta `com.example`).
- **La ruta no se descargaba**: `Configuracion::set()` guardaba toda clave nueva como número, y el nombre de la planta llegaba como `0`.
  Ahora el tipo se deduce del valor + migración que corrige las claves existentes (RUC, Yape, teléfonos…).
- **Un token vencido solo se detectaba al sincronizar**: en "Mi ruta" se veía "Unauthenticated." sin salida. Ahora cualquier 401 lleva al login.
- **Regla de 2 strikes contaba la semana equivocada** de domingo a miércoles (restaba una semana de más).
- **`migrate:fresh --seed` fallaba en SQLite** (CHECK del rol y del método de pago).
- **Dependencias con vulnerabilidades** (league/commonmark, symfony/http-foundation) → actualizadas; `composer audit` limpio.
- **Las contraseñas demo se publicaban** en el HTML del login (cualquiera podía verlas con "ver código fuente") → solo en `local`.
- **Cancelar un pedido no devolvía el stock**, y reactivarlo no lo verificaba → se corrigieron las dos cosas.
- El "**total de litros**" sumaba todo el historial y no solo lo de hoy.
- Un registro que el servidor rechaza (422) se **reintentaba para siempre** y bloqueaba la cola.
- Tras cerrar sesión, el login **volvía a entrar solo** (se reutilizaba el ViewModel anterior).

### 6.3 Datos de demostración (`ProlechDemoSeeder`)
`php artisan db:seed --class=ProlechDemoSeeder` (idempotente, no borra nada; también lo llama `DatabaseSeeder`):
planta en Huata (-15.633, -70.038), **4 zonas con polígono**, **5 choferes** (Carlos, Miguel, Julio, Wilber = titulares de
Collana 1–4; Fredy = volante; clave `prolech2024`), **20 ganaderos** con GPS dentro de su zona, **14 días de acopios** (aprobados,
observados, rechazados) con **Valentín Coaquira suspendido** por 2 strikes, ruta de hoy con trazo GPS en Collana 1 y 2,
liquidaciones de la semana pasada pagadas, stock amplio de insumos, **7 lotes de producción**, **7 productos** con imagen
propia (SVG, nunca rota) y **pedidos** Pendiente/Pagado/Enviado.

### 6.4 Exportar y desplegar
- Excel **.xlsx** real (encabezado fijo, números sumables), **PDF** (acopios con totales, planilla de liquidaciones con firmas,
  hoja de ruta por zona con casillas) y CSV.
- Despliegue: **Docker + Caddy** (HTTPS automático) o **Nginx + certbot** con `deploy/instalar-vps.sh`.
- CI: `.github/workflows/ci.yml` en ambos repositorios.

### 6.5 Llaves y respaldo
`keystore.properties` y `firma/prolech-release.jks` **no están en git**. Guarda una copia en un lugar
seguro (USB o nube privada). Si se pierden, no se pueden publicar actualizaciones con la misma firma.

---

## 7. Límites conocidos

- **En esta PC el puerto 8000 lo ocupa el Apache de Laragon** (otro sitio). Para probar con el celular usa
  `php artisan serve --host=0.0.0.0 --port=8001` y en la app `http://IP-DE-LA-PC:8001`.
- **El disco C: está lleno** (el archivo de paginación creció a 19.7 GB). Reiniciar la PC lo reduce; el emulador quedó en `D:\AndroidEmu`.
- Como cambió el `applicationId`, la versión 2.1 se instala **como app nueva**: desinstala la anterior ("ProLech Acopio" 2.0).

- **iOS** no se puede compilar en Windows (requiere Mac). Agregar a `Info.plist`: `NSLocationWhenInUseUsageDescription`, `NSCameraUsageDescription`. En iOS el OCR, el ticket y la impresora Bluetooth están como "no disponible".
- El servidor público de **OSRM** es para uso moderado; en producción conviene uno propio.
- La boleta tiene validez SUNAT solo cuando se configuren `NUBEFACT_URL` y `NUBEFACT_TOKEN`; sin ellos queda como boleta interna.
- El OCR depende de la nitidez de la foto: el chofer siempre revisa los valores antes de guardar.
