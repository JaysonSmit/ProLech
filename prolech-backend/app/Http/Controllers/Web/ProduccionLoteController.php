<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProduccionLote;
use App\Models\Receta;
use App\Services\ProduccionService;
use Illuminate\Http\Request;

class ProduccionLoteController extends Controller
{
    public function __construct(
        private readonly ProduccionService $produccionService
    ) {}

    // ── GET /admin/produccion/lotes/create ────────────────────────────────────
    public function create()
    {
        $recetas = Receta::activas()
            ->with('detalles.insumo')
            ->orderBy('nombre')
            ->get();

        $lotes = ProduccionLote::with(['receta', 'responsable'])
            ->orderByDesc('fecha_produccion')
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.produccion.create', compact('recetas', 'lotes'));
    }

    // ── POST /admin/produccion/lotes ──────────────────────────────────────────
    public function store(Request $request)
    {
        // Forma antigua (un solo producto) → se convierte en una orden de una línea
        if (! $request->has('lineas') && $request->filled('receta_id')) {
            $request->merge(['lineas' => [[
                'receta_id' => $request->input('receta_id'),
                'cantidad'  => $request->input('cantidad_producida'),
            ]]]);
        }

        $validated = $request->validate([
            'lineas'             => ['required', 'array', 'min:1', 'max:20'],
            'lineas.*.receta_id' => ['required', 'integer', 'exists:recetas,id'],
            'lineas.*.cantidad'  => ['required', 'integer', 'min:1', 'max:99999'],
            'observaciones'      => ['nullable', 'string', 'max:1000'],
        ], [
            'lineas.required'           => 'Agrega al menos un producto a la orden.',
            'lineas.*.cantidad.min'     => 'Cada producto necesita una cantidad mayor a 0.',
            'lineas.*.receta_id.exists' => 'Uno de los productos no existe.',
        ]);

        try {
            $lotes = $this->produccionService->registrarOrden(
                lineas:        $validated['lineas'],
                responsable:   $request->user(),
                observaciones: $validated['observaciones'] ?? null,
            );

            $resumen = $lotes->map(fn ($l) => "{$l->receta->nombre} × {$l->cantidad_producida}")->implode(', ');
            $titulo = $lotes->count() > 1
                ? "Orden {$lotes->first()->orden_codigo} registrada: {$lotes->count()} lotes"
                : "Lote {$lotes->first()->codigo_lote} registrado";

            return redirect()
                ->route('admin.produccion.lotes.create')
                ->with('success', "✅ {$titulo} — {$resumen} · S/ " . number_format($lotes->sum('costo_total'), 2));

        } catch (\RuntimeException $e) {
            // Stock insuficiente u otra regla de negocio → error visible al usuario
            return back()
                ->withInput()
                ->withErrors(['stock' => $e->getMessage()]);

        } catch (\Throwable $e) {
            // Error inesperado → log interno, mensaje genérico al usuario
            report($e);

            return back()
                ->withInput()
                ->withErrors(['stock' => 'Ocurrió un error al procesar el lote. Intenta nuevamente.']);
        }
    }

    // ── GET /admin/produccion/lotes/{lote} ────────────────────────────────────
    public function show(ProduccionLote $lote)
    {
        // 'movimientos' usa morph (referencia_tipo/id); si la columna aún no está
        // en la BD se omite para no lanzar QueryException.
        $lote->load(['receta.detalles.insumo', 'responsable']);

        return view('admin.produccion.show', compact('lote'));
    }

    // ── GET /api/produccion/proyeccion ────────────────────────────────────────
    // Endpoint JSON consumido por el JS de la vista create para mostrar
    // la tarjeta de proyección en tiempo real.
    public function proyeccion(Request $request)
    {
        $request->validate([
            'receta_id'          => ['required', 'integer', 'exists:recetas,id'],
            'cantidad_producida'  => ['required', 'integer', 'min:1'],
        ]);

        $datos = $this->produccionService->proyectarConsumo(
            (int) $request->receta_id,
            (int) $request->cantidad_producida
        );

        $costoTotal  = collect($datos)->sum('costo_parcial');
        $hayProblema = collect($datos)->contains('alcanza', false);

        return response()->json([
            'insumos'     => $datos,
            'costo_total' => round($costoTotal, 2),
            'ok'          => ! $hayProblema,
        ]);
    }
}
