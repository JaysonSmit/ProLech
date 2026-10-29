<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Acopio;
use App\Models\Ganadero;
use App\Models\Produccion;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // DASHBOARD
    // ──────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $hoy  = now()->toDateString();
        $mes  = now()->month;
        $anio = now()->year;

        // "Hoy" para litros: usa los últimos 2 días para no mostrar 0
        // cuando el último acopio fue ayer (jornada matutina).
        $ultimaFechaConAcopio = Acopio::where('estado', 'aprobado')
                                      ->max('fecha');

        $stats = [
            // Litros del día con acopios más reciente (hoy o ayer si aún no hay de hoy)
            'total_litros_hoy'   => Acopio::where('fecha', $ultimaFechaConAcopio ?? $hoy)
                                          ->where('estado', 'aprobado')
                                          ->sum('litros'),

            'fecha_litros_hoy'   => $ultimaFechaConAcopio ?? $hoy,

            'total_litros_mes'   => Acopio::whereMonth('fecha', $mes)
                                          ->whereYear('fecha', $anio)
                                          ->where('estado', 'aprobado')
                                          ->sum('litros'),

            'acopios_rechazados' => Acopio::whereMonth('fecha', $mes)
                                          ->whereYear('fecha', $anio)
                                          ->where('estado', 'rechazado')
                                          ->count(),

            'acopios_observados' => Acopio::whereMonth('fecha', $mes)
                                          ->whereYear('fecha', $anio)
                                          ->where('estado', 'observado')
                                          ->count(),

            'ventas_mes'         => Venta::vigentes()->whereMonth('fecha_venta', $mes)
                                         ->whereYear('fecha_venta', $anio)
                                         ->sum('total'),

            'kg_producidos_mes'  => Produccion::whereMonth('fecha_lote', $mes)
                                              ->whereYear('fecha_lote', $anio)
                                              ->sum('kg_queso_estimado'),

            'total_ganaderos'    => Ganadero::where('activo', true)->count(),
        ];

        // RF-STK-01 — Stock de quesos en planta vs reserva mínima
        $stockActualQuesos = Produccion::stockActualMoldes();
        $stockMinimoQuesos = (float) (\App\Models\Configuracion::get('stock_minimo_venta_directa') ?? \App\Models\Configuracion::get('stock_minimo_quesos', 15));
        $alertaStockQuesos = Produccion::alertaStockMinimo();

        $stats['stock_actual_quesos'] = $stockActualQuesos;
        $stats['stock_minimo_quesos'] = $stockMinimoQuesos;
        $stats['alerta_stock_quesos'] = $alertaStockQuesos;

        // Últimos 10 acopios ordenados por fecha y hora de creación (más reciente primero)
        $acopiosRecientes = Acopio::with(['ganadero:id,nombre', 'chofer:id,name'])
                                  ->orderByDesc('fecha')
                                  ->orderByDesc('created_at')
                                  ->limit(10)
                                  ->get();

        // Adulteraciones del día con acopios más reciente
        $fechaAdulteraciones = $ultimaFechaConAcopio ?? $hoy;
        $adulteracionesHoy = Acopio::with(['ganadero:id,nombre,dni', 'chofer:id,name'])
                                   ->where('fecha', $fechaAdulteraciones)
                                   ->whereIn('estado', ['rechazado', 'observado'])
                                   ->orderByDesc('porcentaje_agua')
                                   ->get();

        // RF-ACO-07 — acopios del día sin confirmación de balde (auditoría)
        $acopiosSinBalde = Acopio::with(['ganadero:id,nombre', 'chofer:id,name'])
            ->where('fecha', $hoy)
            ->where('medicion_balde_confirmada', false)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.dashboard', compact('stats', 'acopiosRecientes', 'adulteracionesHoy', 'acopiosSinBalde'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRODUCCIÓN
    // ──────────────────────────────────────────────────────────────────────────

    public function produccion(): View
    {
        $lotes = Produccion::with('registrador:id,name')
                           ->orderByDesc('fecha_lote')
                           ->paginate(15);

        // Totales del mes para el resumen
        $mes  = now()->month;
        $anio = now()->year;

        $totalMes = [
            'litros'   => Produccion::whereMonth('fecha_lote', $mes)->whereYear('fecha_lote', $anio)->sum('litros_usados'),
            'kg_queso' => Produccion::whereMonth('fecha_lote', $mes)->whereYear('fecha_lote', $anio)->sum('kg_queso_estimado'),
            'costo'    => Produccion::whereMonth('fecha_lote', $mes)->whereYear('fecha_lote', $anio)->sum('costo_total'),
        ];

        // RF-PROD-02/04 — Campo vs planta (hoy) y rendimiento en moldes/100L
        $hoy = now()->toDateString();
        $litrosCampoHoy = (float) \App\Models\Acopio::whereDate('fecha', $hoy)->sum('litros');
        $prodHoy        = Produccion::whereDate('fecha_lote', $hoy)->get();
        $litrosPlantaHoy = (float) $prodHoy->sum('litros_usados');
        $moldesHoy       = (float) $prodHoy->sum('kg_queso_estimado'); // ~1 kg por molde
        $quesosPor100L   = (float) \App\Models\Configuracion::get('rendimiento_quesos_100L', 11.5);
        $moldesEsperados = $litrosCampoHoy > 0
            ? round($litrosCampoHoy * ($quesosPor100L / 100), 1)
            : 0.0;
        $rendimientoReal = $litrosPlantaHoy > 0 && $moldesHoy > 0
            ? round($moldesHoy / ($litrosPlantaHoy / 100), 2)
            : null;
        $alertaRendimiento = $rendimientoReal !== null && $rendimientoReal < ($quesosPor100L * 0.85);

        $stockActualQuesos = Produccion::stockActualMoldes();
        $stockMinimoQuesos = (float) (\App\Models\Configuracion::get('stock_minimo_venta_directa') ?? \App\Models\Configuracion::get('stock_minimo_quesos', 15));
        $alertaStockQuesos = Produccion::alertaStockMinimo();

        $campoVsPlanta = compact(
            'litrosCampoHoy', 'litrosPlantaHoy', 'moldesHoy',
            'moldesEsperados', 'quesosPor100L', 'rendimientoReal', 'alertaRendimiento'
        );

        return view('admin.produccion', compact(
            'lotes', 'totalMes', 'campoVsPlanta',
            'stockActualQuesos', 'stockMinimoQuesos', 'alertaStockQuesos'
        ));
    }

    public function produccionStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'litros_usados'     => ['required', 'numeric', 'min:1'],
            'precio_litro'      => ['required', 'numeric', 'min:0'],
            'costo_cuajo'       => ['required', 'numeric', 'min:0'],
            'costo_sal'         => ['required', 'numeric', 'min:0'],
            'costo_otros'       => ['nullable', 'numeric', 'min:0'],
            'kg_queso_estimado' => ['required', 'numeric', 'min:0.001'],
            'fecha_lote'        => ['required', 'date', 'before_or_equal:today'],
            'notas'             => ['nullable', 'string', 'max:1000'],
        ]);

        Produccion::create([
            ...$validated,
            'costo_otros'    => $validated['costo_otros'] ?? 0,
            'registrado_por' => $request->user()->id,
        ]);

        return redirect()->route('admin.produccion')
                         ->with('success', '✅ Lote de producción registrado correctamente.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // VENTAS
    // ──────────────────────────────────────────────────────────────────────────

    public function ventas(Request $request): View
    {
        $destinoFiltro  = $request->query('destino');
        $despachoFiltro = $request->query('despacho');

        $ventas = Venta::with(['registrador:id,name', 'anulador:id,name', 'detalles'])
                       ->when($destinoFiltro, fn ($q) => $q->where('destino', $destinoFiltro))
                       ->when($despachoFiltro, fn ($q) => $q->where('estado_despacho', $despachoFiltro))
                       ->orderByDesc('fecha_venta')
                       ->paginate(15)
                       ->withQueryString();

        $mes  = now()->month;
        $anio = now()->year;

        $resumenMes = Venta::vigentes()->whereMonth('fecha_venta', $mes)
                           ->whereYear('fecha_venta', $anio)
                           ->selectRaw('tipo_cliente, SUM(total) as total_tipo, SUM(cantidad_kg) as kg_tipo, COUNT(*) as num_ventas')
                           ->groupBy('tipo_cliente')
                           ->get()
                           ->keyBy('tipo_cliente');

        // RF-DESP — Resumen de envíos por destino geográfico
        $resumenDestino = Venta::vigentes()->selectRaw('destino, SUM(total) as total_monto, SUM(cantidad_kg) as total_kg, COUNT(*) as num_envios')
                               ->groupBy('destino')
                               ->orderByDesc('total_kg')
                               ->get();

        $despachosPendientes = Venta::vigentes()->where('estado_despacho', 'preparacion')->count();
        $despachosEnCamino   = Venta::vigentes()->where('estado_despacho', 'en_camino')->count();
        $despachosEntregados = Venta::vigentes()->where('estado_despacho', 'entregado')->count();

        $precios = [
            'mayorista' => Venta::precioParaTipo('mayorista'),
            'publico'   => Venta::precioParaTipo('publico'),
        ];

        $stockActualQuesos = Produccion::stockActualMoldes();
        $stockMinimoQuesos = (float) (\App\Models\Configuracion::get('stock_minimo_venta_directa') ?? \App\Models\Configuracion::get('stock_minimo_quesos', 15));
        $alertaStockQuesos = Produccion::alertaStockMinimo();

        $catalogo = app(\App\Services\VentaService::class)->catalogo();

        return view('admin.ventas', compact(
            'ventas', 'catalogo', 'resumenMes', 'resumenDestino', 'precios',
            'stockActualQuesos', 'stockMinimoQuesos', 'alertaStockQuesos',
            'despachosPendientes', 'despachosEnCamino', 'despachosEntregados',
            'destinoFiltro', 'despachoFiltro'
        ));
    }

    public function ventasStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente'        => ['required', 'string', 'max:150'],
            'tipo_cliente'   => ['required', Rule::in(['mayorista', 'publico'])],
            'destino'        => ['required', 'string', 'max:100'],
            'fecha_venta'    => ['required', 'date', 'before_or_equal:today'],
            'guia_remision'  => ['nullable', 'string', 'max:60'],
            'notas_despacho' => ['nullable', 'string', 'max:500'],
            'lineas'            => ['required', 'array', 'min:1', 'max:30'],
            'lineas.*.producto' => ['required', 'string', 'max:20'],
            'lineas.*.cantidad' => ['required', 'numeric', 'min:0.001', 'max:99999'],
        ], [
            'lineas.required'       => 'Agrega al menos un producto al carrito.',
            'lineas.*.cantidad.min' => 'Cada producto necesita una cantidad mayor a 0.',
        ]);

        $lineas = $validated['lineas'];
        unset($validated['lineas']);

        try {
            $venta = app(\App\Services\VentaService::class)->registrar($validated, $lineas, $request->user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['lineas' => $e->getMessage()]);
        }

        \App\Services\AuditoriaService::registrar(
            user:            $request->user(),
            entidadTipo:     'Venta',
            entidadId:       $venta->id,
            accion:          'creacion',
            motivo:          "Registro de venta a {$venta->cliente} con destino {$venta->destino}",
            campoModificado: 'total',
            valorNuevo:      (string) $venta->total
        );

        return redirect()->route('admin.ventas')
                         ->with('success', "✅ Venta #{$venta->id} registrada: {$venta->detalles->count()} producto(s) · S/ "
                             . number_format($venta->total, 2) . " con destino {$venta->destino}.");
    }

    /** Comprobante imprimible (nota de venta interna, no es un comprobante electrónico SUNAT). */
    public function comprobanteVenta(Venta $venta): View
    {
        $venta->load(['detalles', 'registrador:id,name', 'anulador:id,name']);

        return view('admin.ventas-comprobante', compact('venta'));
    }

    /** Anula la venta y devuelve el stock. Solo administrador y jefe de producción. */
    public function anularVenta(Request $request, Venta $venta): RedirectResponse
    {
        abort_unless(in_array($request->user()->rol, ['admin', 'jefe_produccion'], true), 403,
            'Solo el administrador o el jefe de producción pueden anular ventas.');

        $validated = $request->validate([
            'motivo_anulacion' => ['required', 'string', 'min:5', 'max:250'],
        ], [
            'motivo_anulacion.required' => 'Escribe el motivo de la anulación.',
            'motivo_anulacion.min'      => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        try {
            $venta = app(\App\Services\VentaService::class)->anular($venta, $validated['motivo_anulacion'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['anulacion' => $e->getMessage()]);
        }

        \App\Services\AuditoriaService::registrar(
            user:            $request->user(),
            entidadTipo:     'Venta',
            entidadId:       $venta->id,
            accion:          'anulacion',
            motivo:          $validated['motivo_anulacion'],
            campoModificado: 'anulada_at',
            valorAnterior:   null,
            valorNuevo:      $venta->anulada_at->toDateTimeString()
        );

        return back()->with('success', "🚫 Venta #{$venta->id} anulada. Se devolvió el stock de sus productos.");
    }

    public function actualizarDespacho(Request $request, Venta $venta): RedirectResponse
    {
        $validated = $request->validate([
            'estado_despacho' => ['required', 'in:preparacion,en_camino,entregado'],
            'guia_remision'   => ['nullable', 'string', 'max:60'],
            'fecha_despacho'  => ['nullable', 'date'],
            'notas_despacho'  => ['nullable', 'string', 'max:500'],
            'motivo_cambio'   => ['nullable', 'string', 'max:250'],
        ]);

        if ($venta->anulada) {
            return back()->withErrors(['despacho' => "La venta #{$venta->id} está anulada: no se puede despachar."]);
        }

        $estadoAnterior = $venta->estado_despacho;

        if ($validated['estado_despacho'] === 'en_camino' && empty($validated['fecha_despacho'])) {
            $validated['fecha_despacho'] = now()->toDateString();
        }

        $venta->update($validated);

        if ($estadoAnterior !== $validated['estado_despacho'] || ! empty($validated['motivo_cambio'])) {
            \App\Services\AuditoriaService::registrar(
                user:            $request->user(),
                entidadTipo:     'Venta',
                entidadId:       $venta->id,
                accion:          'cambio_estado_despacho',
                motivo:          $validated['motivo_cambio'] ?? "Cambio de estado de {$estadoAnterior} a {$validated['estado_despacho']}",
                campoModificado: 'estado_despacho',
                valorAnterior:   $estadoAnterior,
                valorNuevo:      $validated['estado_despacho']
            );
        }

        return back()->with('success', "✅ Estado de despacho de la venta #{$venta->id} actualizado a {$venta->estado_despacho_badge['label']}.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // AUDITORÍA Y TRAZABILIDAD
    // ──────────────────────────────────────────────────────────────────────────

    public function auditorias(Request $request): View
    {
        $entidadFiltro = $request->query('entidad');
        $accionFiltro  = $request->query('accion');

        $auditorias = \App\Models\Auditoria::with('usuario:id,name,rol')
            ->when($entidadFiltro, fn ($q) => $q->where('entidad_tipo', $entidadFiltro))
            ->when($accionFiltro,  fn ($q) => $q->where('accion', $accionFiltro))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $entidades = \App\Models\Auditoria::distinct()->pluck('entidad_tipo')->filter()->values();
        $acciones  = \App\Models\Auditoria::distinct()->pluck('accion')->filter()->values();

        return view('admin.auditorias', compact('auditorias', 'entidades', 'acciones', 'entidadFiltro', 'accionFiltro'));
    }
}

