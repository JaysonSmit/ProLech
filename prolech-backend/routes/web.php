<?php

use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminTiendaController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\InsumoController;
use App\Http\Controllers\Web\ProduccionLoteController;
use App\Http\Controllers\Web\RecetaController;
use App\Http\Controllers\Web\TiendaController;
use App\Http\Controllers\Web\TwoFactorController;
use App\Http\Controllers\Web\ConfiguracionController;
use App\Http\Controllers\Web\GanaderoController;
use App\Http\Controllers\Web\PlanCalidadController;
use App\Http\Controllers\Web\CalidadPlantaController;
use App\Http\Controllers\Web\CapacitacionAtosController;
use App\Http\Controllers\Web\PortalProveedorController;
use App\Http\Controllers\Web\ReclamoLitrajeController;
use App\Http\Controllers\Web\LiquidacionController;
use App\Http\Controllers\Web\MapaGpsController;
use App\Http\Controllers\Web\ReportesController;
use App\Http\Controllers\Web\ReunionController;
use App\Http\Controllers\Web\RotacionController;
use App\Http\Controllers\Web\RutasController;
use App\Http\Controllers\Web\ComunicadoController;
use App\Http\Controllers\Web\UsuarioController;
use Illuminate\Http\Request;
use App\Support\RolWeb;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── Raíz: redirigir según rol ─────────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        $rol = auth()->user()->rol;
        if ($rol === 'proveedor') {
            return redirect()->route('proveedor.dashboard');
        }

        return redirect()->route(RolWeb::rutaInicio($rol));
    }
    return redirect()->route('login');
});

// ══════════════════════════════════════════════════════════════════════════════
// AUTENTICACIÓN DE DOS FACTORES (2FA) — requiere auth, sin 2fa aún verificado
// ══════════════════════════════════════════════════════════════════════════════
Route::middleware('auth')->prefix('2fa')->name('2fa.')->group(function () {
    // Setup: registrar teléfono + apikey CallMeBot
    Route::get('setup',              [TwoFactorController::class, 'setup'])->name('setup');
    Route::post('setup',             [TwoFactorController::class, 'confirmarSetup'])->name('setup.post');
    Route::get('setup/confirmar',    [TwoFactorController::class, 'mostrarConfirmar'])->name('setup.confirmar');
    Route::post('setup/confirmar',   [TwoFactorController::class, 'procesarConfirmar'])->name('setup.confirmar.post');
    // Verificación en cada login
    Route::get('verificar',          [TwoFactorController::class, 'mostrarVerificacion'])->name('verificar');
    Route::post('verificar',         [TwoFactorController::class, 'verificar'])->name('verificar.post');
    Route::post('reenviar',          [TwoFactorController::class, 'reenviar'])->name('reenviar');
    // Admin desactiva 2FA de otro usuario
    Route::post('desactivar',        [TwoFactorController::class, 'desactivar'])->name('desactivar');
});

// ══════════════════════════════════════════════════════════════════════════════
// PORTAL PROVEEDOR — protegido con auth + rol:proveedor
// ══════════════════════════════════════════════════════════════════════════════
Route::middleware(['auth', 'rol:proveedor'])
     ->prefix('proveedor')
     ->name('proveedor.')
     ->group(function () {
    Route::get('dashboard', [PortalProveedorController::class, 'dashboard'])->name('dashboard');
    Route::get('historial', [PortalProveedorController::class, 'historial'])->name('historial');
    Route::get('pagos',     [PortalProveedorController::class, 'pagos'])->name('pagos');
    Route::post('reclamo',  [PortalProveedorController::class, 'storeReclamo'])->name('reclamo.store');
});
// ── Autenticación ─────────────────────────────────────────────────────────────
Route::get('/login', fn () => view('auth.login'))->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        $user = Auth::user();
        $destino = $user->rol === 'proveedor'
            ? route('proveedor.dashboard')
            : route($user->rutaInicioWeb());

        return redirect()->intended($destino);
    }

    return back()->withErrors(['email' => 'Las credenciales son incorrectas.'])->onlyInput('email');
})->middleware('throttle:login-web')->name('login.post');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');

// ══════════════════════════════════════════════════════════════════════════════
// PANEL WEB — roles de planta (no chofer ni proveedor)
// ══════════════════════════════════════════════════════════════════════════════
Route::middleware(['auth', 'rol:admin,calidad,asistente,jefe_produccion,despacho', '2fa'])
     ->prefix('admin')
     ->name('admin.')
     ->group(function () {

    Route::middleware('seccion:dashboard')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('acopios', fn () => redirect()->route('admin.dashboard'))->name('acopios');
    });

    Route::middleware('seccion:produccion')->group(function () {
        // La ruta vieja redirige al nuevo módulo ERP
        Route::get('produccion', function () {
            return redirect()->route('admin.produccion.lotes.create');
        })->name('produccion');
        Route::post('produccion', [AdminDashboardController::class, 'produccionStore'])->name('produccion.store');

        // ── Módulo ERP: Lotes de Producción por Receta ───────────────────────
        Route::get('produccion/lotes/create',  [ProduccionLoteController::class, 'create'])->name('produccion.lotes.create');
        Route::post('produccion/lotes',        [ProduccionLoteController::class, 'store'])->name('produccion.lotes.store');
        Route::get('produccion/lotes/{lote}',  [ProduccionLoteController::class, 'show'])->name('produccion.lotes.show');

        // ── Gestión de Recetas ───────────────────────────────────────────────
        Route::get('recetas',                       [RecetaController::class, 'index'])->name('recetas.index');
        Route::get('recetas/create',                [RecetaController::class, 'create'])->name('recetas.create');
        Route::post('recetas',                      [RecetaController::class, 'store'])->name('recetas.store');
        Route::post('recetas/publicar',             [RecetaController::class, 'publicar'])->name('recetas.publicar');
        Route::get('recetas/{receta}/edit',         [RecetaController::class, 'edit'])->name('recetas.edit');
        Route::put('recetas/{receta}',              [RecetaController::class, 'update'])->name('recetas.update');
        Route::post('recetas/{receta}/toggle',      [RecetaController::class, 'toggle'])->name('recetas.toggle');
    });

    // ── Módulo de Inventario de Insumos ───────────────────────────────────────
    Route::middleware('seccion:produccion')->group(function () {
        Route::get('inventario',                              [InsumoController::class, 'index'])->name('inventario.index');
        Route::post('inventario/ingreso',                     [InsumoController::class, 'ingreso'])->name('inventario.ingreso');
        Route::get('inventario/{insumo}/movimientos',         [InsumoController::class, 'movimientos'])->name('inventario.movimientos');
    });    Route::middleware('seccion:ventas')->group(function () {
        Route::get('ventas',  [AdminDashboardController::class, 'ventas'])->name('ventas');
        Route::post('ventas', [AdminDashboardController::class, 'ventasStore'])->name('ventas.store');
        Route::post('ventas/{venta}/despacho', [AdminDashboardController::class, 'actualizarDespacho'])->name('ventas.despacho.update');
        Route::get('ventas/{venta}/comprobante', [AdminDashboardController::class, 'comprobanteVenta'])->name('ventas.comprobante');
        Route::post('ventas/{venta}/anular', [AdminDashboardController::class, 'anularVenta'])->name('ventas.anular');
    });

    Route::middleware('seccion:liquidaciones')->group(function () {
        Route::get('liquidaciones',                          [LiquidacionController::class, 'index'])->name('liquidaciones.index');
        Route::get('liquidaciones/{liquidacion}',            [LiquidacionController::class, 'show'])->name('liquidaciones.show');
        Route::get('liquidaciones/{liquidacion}/sobre-pdf',  [LiquidacionController::class, 'sobrePdf'])->name('liquidaciones.sobre-pdf');
    });

    Route::middleware('seccion:reuniones')->group(function () {
        Route::get('reuniones', [ReunionController::class, 'index'])->name('reuniones');
    });

    Route::middleware('seccion:ganaderos')->group(function () {
        Route::get('ganaderos', [GanaderoController::class, 'index'])->name('ganaderos.index');
    });

    Route::middleware('seccion:reclamos')->group(function () {
        Route::get('reclamos', [ReclamoLitrajeController::class, 'index'])->name('reclamos.index');
    });

    Route::middleware('seccion:comunicados')->group(function () {
        Route::get('comunicados', [ComunicadoController::class, 'index'])->name('comunicados.index');
        Route::post('comunicados', [ComunicadoController::class, 'store'])->name('comunicados.store');
        Route::delete('comunicados/{comunicado}', [ComunicadoController::class, 'destroy'])->name('comunicados.destroy');
    });

    Route::middleware('seccion:reportes')->group(function () {
        Route::get('reportes', [ReportesController::class, 'index'])->name('reportes');
    });

    // Plan inopinado — visible para admin y personal de calidad (RF-CAL-08)
    Route::middleware('seccion:calidad_plan')->group(function () {
        Route::get('calidad/plan', [PlanCalidadController::class, 'index'])->name('calidad.plan.index');
    });

    Route::middleware('seccion:calidad_planta')->group(function () {
        Route::get('calidad/planta', [CalidadPlantaController::class, 'index'])->name('calidad.planta.index');
    });

    Route::middleware('seccion:calidad_capacitaciones')->group(function () {
        Route::get('calidad/capacitaciones', [CapacitacionAtosController::class, 'index'])->name('calidad.capacitaciones.index');
    });
});

// Rutas solo administrador
Route::middleware(['auth', 'rol:admin', '2fa'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('mapa',             [MapaGpsController::class, 'index'])->name('mapa');
    Route::get('mapa/ubicaciones', [MapaGpsController::class, 'ubicaciones'])->name('mapa.ubicaciones');

    // ── Operaciones: panel en vivo, recorridos, zonas en el mapa y exportación ──
    Route::controller(\App\Http\Controllers\Web\OperacionesController::class)->group(function () {
        Route::get('en-vivo',                              'enVivo')->name('operaciones.en-vivo');
        Route::get('en-vivo/datos',                        'enVivoDatos')->name('operaciones.en-vivo.datos');
        Route::get('operaciones/recorrido',                'recorrido')->name('operaciones.recorrido');
        Route::get('operaciones/recorrido/{chofer}/datos', 'recorridoDatos')->name('operaciones.recorrido.datos');
        Route::get('zonas/mapa',                           'zonasMapa')->name('operaciones.zonas');
        Route::post('zonas/mapa/{ganadero}',               'zonasMapaGuardar')->name('operaciones.zonas.guardar');
        Route::get('exportar',                             'exportar')->name('operaciones.exportar');
        Route::get('exportar/acopios.csv',                 'exportarAcopiosCsv')->name('operaciones.exportar.acopios');
        Route::get('exportar/acopios/imprimir',            'exportarAcopiosImprimir')->name('operaciones.exportar.acopios.imprimir');
        Route::get('exportar/liquidaciones.csv',           'exportarLiquidacionesCsv')->name('operaciones.exportar.liquidaciones');
        Route::get('exportar/rutas.csv',                   'exportarRutasCsv')->name('operaciones.exportar.rutas');
    });

    Route::get('rutas/solicitudes', [RutasController::class, 'index'])->name('rutas.solicitudes');
    Route::post('rutas/solicitudes/{solicitud}/responder', [RutasController::class, 'responderSolicitud'])->name('rutas.responder');

    Route::post('reuniones', [ReunionController::class, 'store'])->name('reuniones.store');
    Route::post('reuniones/{reunion}/cancelar', [ReunionController::class, 'cancelar'])->name('reuniones.cancelar');

    Route::post('liquidaciones/generar',                 [LiquidacionController::class, 'generar'])->name('liquidaciones.generar');
    Route::post('liquidaciones/{liquidacion}/confirmar', [LiquidacionController::class, 'confirmar'])->name('liquidaciones.confirmar');
    Route::post('liquidaciones/{liquidacion}/pagar',     [LiquidacionController::class, 'marcarPagada'])->name('liquidaciones.pagar');

    Route::get('rotacion',                     [RotacionController::class, 'index'])->name('rotacion.index');
    Route::post('rotacion/generar-automatico', [RotacionController::class, 'generarAutomatico'])->name('rotacion.generar');
    Route::post('rotacion/asignar-manual',     [RotacionController::class, 'asignarManual'])->name('rotacion.asignar');
    Route::delete('rotacion/{asignacion}',     [RotacionController::class, 'destruir'])->name('rotacion.destruir');

    Route::post('ganaderos',                         [GanaderoController::class, 'store'])->name('ganaderos.store');
    Route::post('ganaderos/{ganadero}/actualizar', [GanaderoController::class, 'actualizar'])->name('ganaderos.actualizar');
    Route::post('ganaderos/{ganadero}/toggle',       [GanaderoController::class, 'toggle'])->name('ganaderos.toggle');
    Route::post('ganaderos/{ganadero}/crear-acceso', [GanaderoController::class, 'crearAcceso'])->name('ganaderos.crear-acceso');

    Route::get('usuarios',                             [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('usuarios',                            [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::post('usuarios/{usuario}/toggle',           [UsuarioController::class, 'toggle'])->name('usuarios.toggle');
    Route::post('usuarios/{usuario}/cambiar-password', [UsuarioController::class, 'cambiarPassword'])->name('usuarios.password');

    Route::get('configuracion',  [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');

    Route::get('auditorias', [AdminDashboardController::class, 'auditorias'])->name('auditorias.index');

    Route::delete('calidad/plan/{plan}', [PlanCalidadController::class, 'destroy'])->name('calidad.plan.destroy');
});

// Calidad + admin — operaciones de registro (RF-CAL-07, RF-CAL-08, RF-CAL-10)
Route::middleware(['auth', 'rol:admin,calidad', '2fa'])
     ->prefix('admin')
     ->name('admin.')
     ->group(function () {
    Route::middleware('seccion:calidad_plan')->group(function () {
        Route::post('calidad/plan', [PlanCalidadController::class, 'store'])->name('calidad.plan.store');
        Route::post('calidad/plan/{plan}/ejecutado', [PlanCalidadController::class, 'marcarEjecutado'])->name('calidad.plan.ejecutado');
    });
    Route::middleware('seccion:calidad_planta')->group(function () {
        Route::post('calidad/planta', [CalidadPlantaController::class, 'store'])->name('calidad.planta.store');
    });
    Route::middleware('seccion:calidad_capacitaciones')->group(function () {
        Route::post('calidad/capacitaciones', [CapacitacionAtosController::class, 'store'])->name('calidad.capacitaciones.store');
    });
});

// Asistente puede responder reclamos
Route::middleware(['auth', 'rol:admin,asistente', 'seccion:reclamos', '2fa'])
     ->prefix('admin')
     ->name('admin.')
     ->group(function () {
    Route::post('reclamos/{reclamo}/responder', [ReclamoLitrajeController::class, 'responder'])->name('reclamos.responder');
});


// ══════════════════════════════════════════════════════════════════════════════
// TIENDA PÚBLICA — Sin autenticación requerida
// ══════════════════════════════════════════════════════════════════════════════
Route::prefix('tienda')->name('tienda.')->group(function () {

    // Catálogo y detalle de producto
    Route::get('/',           [TiendaController::class, 'index'])->name('index');
    Route::get('/show/{slug}', [TiendaController::class, 'show'])->name('show');

    // Checkout
    Route::get('/checkout',        [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout',       [CheckoutController::class, 'store'])->name('checkout.store');

    // Confirmación de pedido
    Route::get('/confirmacion/{codigo}', [CheckoutController::class, 'confirmacion'])->name('confirmacion');

    // Seguimiento público del pedido (código + celular para proteger los datos)
    Route::get('/seguimiento', [CheckoutController::class, 'seguimiento'])
         ->middleware('throttle:20,1')
         ->name('seguimiento');

    // Boleta de venta (enlace firmado)
    Route::get('/boleta/{codigo}', [CheckoutController::class, 'boleta'])
         ->middleware('signed')
         ->name('boleta');
});

// ══════════════════════════════════════════════════════════════════════════════
// ADMIN — Módulo Tienda (solo admin)
// ══════════════════════════════════════════════════════════════════════════════
Route::middleware(['auth', 'rol:admin', '2fa'])
     ->prefix('admin/tienda')
     ->name('admin.tienda.')
     ->group(function () {

    // ── Productos ──────────────────────────────────────────────────────────
    Route::get('productos',                         [AdminTiendaController::class, 'productosIndex'])->name('productos.index');
    Route::get('productos/create',                  [AdminTiendaController::class, 'productosCreate'])->name('productos.create');
    Route::post('productos',                        [AdminTiendaController::class, 'productosStore'])->name('productos.store');
    Route::get('productos/{producto}/edit',         [AdminTiendaController::class, 'productosEdit'])->name('productos.edit');
    Route::put('productos/{producto}',              [AdminTiendaController::class, 'productosUpdate'])->name('productos.update');
    Route::delete('productos/{producto}',           [AdminTiendaController::class, 'productosDestroy'])->name('productos.destroy');
    Route::post('productos/{producto}/toggle',      [AdminTiendaController::class, 'productosToggle'])->name('productos.toggle');

    // ── Pedidos web ────────────────────────────────────────────────────────
    Route::get('pedidos',                           [AdminTiendaController::class, 'pedidosIndex'])->name('pedidos.index');
    Route::get('pedidos/{pedido}',                  [AdminTiendaController::class, 'pedidosShow'])->name('pedidos.show');
    Route::post('pedidos/{pedido}/estado',          [AdminTiendaController::class, 'pedidosEstado'])->name('pedidos.estado');
    Route::post('pedidos/{pedido}/sunat',           [AdminTiendaController::class, 'pedidosSunat'])->name('pedidos.sunat');
});
