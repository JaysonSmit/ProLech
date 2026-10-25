<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Insumo;
use App\Models\MovimientoInventario;
use App\Services\ProduccionService;
use Illuminate\Http\Request;

/**
 * InsumoController — Módulo de Inventario ProLech
 *
 * Rutas:
 *   GET  /admin/inventario                 → index()   Lista de insumos + alertas de stock
 *   POST /admin/inventario/ingreso         → ingreso() Ingreso masivo de uno o más insumos
 *   GET  /admin/inventario/{insumo}/movimientos → movimientos() Historial de un insumo
 */
class InsumoController extends Controller
{
    public function __construct(
        private readonly ProduccionService $produccionService
    ) {}

    // ── GET /admin/inventario ─────────────────────────────────────────────────
    public function index()
    {
        $insumos = Insumo::orderBy('nombre')->get();

        // Últimos 10 movimientos globales para el panel de actividad reciente
        $ultimosMovimientos = MovimientoInventario::with(['insumo', 'registrador'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Contadores para las tarjetas de métricas
        $totalInsumos      = $insumos->count();
        $stockBajo         = $insumos->filter(fn($i) => (float)$i->stock_actual <= (float)$i->stock_minimo)->count();
        $sinStock          = $insumos->filter(fn($i) => (float)$i->stock_actual <= 0)->count();
        $insumosPorUnidad  = $insumos->groupBy('unidad_medida')->map->count();

        return view('admin.inventario.index', compact(
            'insumos',
            'ultimosMovimientos',
            'totalInsumos',
            'stockBajo',
            'sinStock',
            'insumosPorUnidad'
        ));
    }

    // ── POST /admin/inventario/ingreso ────────────────────────────────────────
    /**
     * Ingreso masivo: recibe un array de insumos con sus cantidades.
     * Solo procesa los que tienen cantidad > 0.
     *
     * Request esperado (formulario con checkboxes + inputs):
     *   insumos[]          → array de insumo_ids seleccionados
     *   cantidades[{id}]   → cantidad a ingresar por cada insumo
     *   motivo             → texto descriptivo (ej: "Compra proveedor Arequipa")
     */
    public function ingreso(Request $request)
    {
        $validated = $request->validate([
            'insumos'          => ['required', 'array', 'min:1'],
            'insumos.*'        => ['integer', 'exists:insumos,id'],
            'cantidades'       => ['required', 'array'],
            'cantidades.*'     => ['numeric', 'min:0'],
            'motivo'           => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $procesados = 0;
        $errores    = [];

        foreach ($validated['insumos'] as $insumoId) {
            $cantidad = (float) ($validated['cantidades'][$insumoId] ?? 0);

            if ($cantidad <= 0) {
                continue; // Saltar los que tienen 0 o vacío
            }

            try {
                $this->produccionService->registrarIngreso(
                    insumoId:    (int) $insumoId,
                    cantidad:    $cantidad,
                    motivo:      $validated['motivo'],
                    responsable: $request->user(),
                );
                $procesados++;
            } catch (\Throwable $e) {
                $insumo = Insumo::find($insumoId);
                $errores[] = "Error en \"{$insumo?->nombre}\": {$e->getMessage()}";
                report($e);
            }
        }

        if ($procesados === 0 && empty($errores)) {
            return back()->withErrors(['ingreso' => 'Ingresa al menos una cantidad mayor a 0.']);
        }

        if (!empty($errores)) {
            return back()
                ->with('warning', "Se procesaron $procesados insumo(s). Errores: " . implode(' | ', $errores));
        }

        return redirect()
            ->route('admin.inventario.index')
            ->with('success', "✅ Ingreso registrado — $procesados insumo(s) actualizados · motivo: \"{$validated['motivo']}\"");
    }

    // ── GET /admin/inventario/{insumo}/movimientos ────────────────────────────
    /**
     * Historial completo de movimientos de un insumo específico.
     * Paginado, con filtros opcionales de tipo y fechas.
     */
    public function movimientos(Request $request, Insumo $insumo)
    {
        $tipo       = $request->query('tipo');          // 'ingreso' | 'egreso' | null
        $desde      = $request->query('desde');         // fecha YYYY-MM-DD
        $hasta      = $request->query('hasta');

        $query = $insumo->movimientos()
            ->with(['registrador'])
            ->orderByDesc('created_at');

        if ($tipo && in_array($tipo, ['ingreso', 'egreso'])) {
            $query->where('tipo', $tipo);
        }
        if ($desde) {
            $query->whereDate('created_at', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('created_at', '<=', $hasta);
        }

        $movimientos = $query->paginate(25)->withQueryString();

        // Estadísticas del insumo para el header
        $totalIngresos = $insumo->movimientos()->ingresos()->sum('cantidad');
        $totalEgresos  = $insumo->movimientos()->egresos()->sum('cantidad');

        return view('admin.inventario.movimientos', compact(
            'insumo',
            'movimientos',
            'totalIngresos',
            'totalEgresos',
            'tipo',
            'desde',
            'hasta'
        ));
    }
}
