<?php

use App\Http\Controllers\Api\AcopioApiController;
use App\Http\Controllers\Api\AcopioCampoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChoferAppController;
use App\Http\Controllers\Web\ProduccionLoteController;
use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════════════════
// RUTAS PÚBLICAS — Sin autenticación
// ══════════════════════════════════════════════════════════════════════════════

// POST /api/login  ← URL que usa la App Kotlin (BACKEND_API_CONTRACT.md)
Route::post('login', [AuthController::class, 'login']);

// POST /api/auth/login  ← alias para compatibilidad con clientes web / futuros
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// ══════════════════════════════════════════════════════════════════════════════
// RUTAS PROTEGIDAS — Requieren token Sanctum válido
// ══════════════════════════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ──────────────────────────────────────────────────────────────────
    // POST /api/logout  ← URL del contrato de la App Kotlin
    Route::post('logout',      [AuthController::class, 'logout']);
    // Alias con prefijo para compatibilidad
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me',      [AuthController::class, 'me']);

    // ── Acopios (chofer + admin) ───────────────────────────────────────────────
    Route::middleware('rol:chofer,admin')->group(function () {
        Route::get('mi-ruta',           [AcopioApiController::class, 'miRuta']);
        Route::post('acopios',          [AcopioApiController::class, 'store']);
        Route::get('acopios/historial', [AcopioApiController::class, 'historial']);

        // Acopios de campo offline-first (uuid generado en el celular)
        Route::get('acopios-campo',            [AcopioCampoController::class, 'index']);
        Route::post('acopios-campo',           [AcopioCampoController::class, 'store']);
        Route::put('acopios-campo/{uuid}',     [AcopioCampoController::class, 'update']);
        Route::delete('acopios-campo/{uuid}',  [AcopioCampoController::class, 'destroy']);
        Route::post('acopios-campo/{uuid}/foto', [AcopioCampoController::class, 'foto']);
    });

    // ── GPS Tracking (chofer + admin) ─────────────────────────────────────────
    // POST /api/tracking
    // Llamado cada ~30 s por el WorkManager / ForegroundService de Kotlin.
    Route::middleware('rol:chofer,admin')->group(function () {
        Route::post('tracking', [ChoferAppController::class, 'tracking']);
        // Lote de puntos del recorrido (la app los acumula sin señal)
        Route::post('tracking/lote', [ChoferAppController::class, 'trackingLote']);
        // El chofer fija la ubicación GPS del predio desde la app
        Route::post('ganaderos/{ganadero}/ubicacion', [ChoferAppController::class, 'fijarUbicacionGanadero'])
             ->whereNumber('ganadero');
    });

    // ── Solicitudes de cambio de ruta (chofer) ────────────────────────────────
    // GET  /api/estado-solicitud-ruta  ← consulta el estado de la última solicitud
    // POST /api/solicitar-ruta         ← crea una nueva solicitud
    Route::middleware('rol:chofer')->group(function () {
        Route::get('estado-solicitud-ruta', [ChoferAppController::class, 'estadoSolicitudRuta']);
        Route::post('solicitar-ruta',       [ChoferAppController::class, 'solicitarRuta']);
    });

    // ── Reuniones (todos los roles) ───────────────────────────────────────────
    // GET /api/reuniones
    Route::get('reuniones', [ChoferAppController::class, 'reuniones']);

    // ── Notificaciones (todos los roles) ─────────────────────────────────────
    // GET  /api/notificaciones
    // POST /api/notificaciones/{id}/leer
    // POST /api/notificaciones/leer-todas
    Route::get('notificaciones',                  [ChoferAppController::class, 'notificaciones']);
    Route::post('notificaciones/leer-todas',      [ChoferAppController::class, 'marcarTodasLeidas']);
    Route::post('notificaciones/{id}/leer',       [ChoferAppController::class, 'marcarLeida'])
         ->whereNumber('id');

    // ── Proyección de consumo de insumos (admin / jefe_produccion) ────────────
    // GET /api/produccion/proyeccion?receta_id=1&cantidad_producida=1300
    // Consumido por el JS de la vista create para la Tarjeta de Proyección en tiempo real.
    Route::middleware('rol:admin,jefe_produccion,calidad,asistente')
         ->get('produccion/proyeccion', [ProduccionLoteController::class, 'proyeccion']);
});
