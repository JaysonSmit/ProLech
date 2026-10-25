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
        $validated = $request->validate([
            'receta_id'          => ['required', 'integer', 'exists:recetas,id'],
            'cantidad_producida'  => ['required', 'integer', 'min:1', 'max:99999'],
            'observaciones'       => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $lote = $this->produccionService->registrarLote(
                recetaId:          (int)   $validated['receta_id'],
                cantidadProducida: (int)   $validated['cantidad_producida'],
                responsable:       $request->user(),
                observaciones:     $validated['observaciones'] ?? null,
            );

            return redirect()
                ->route('admin.produccion.lotes.create')
                ->with('success', "✅ Lote {$lote->codigo_lote} registrado — {$lote->cantidad_producida} unidades · S/ " . number_format($lote->costo_total, 2));

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
