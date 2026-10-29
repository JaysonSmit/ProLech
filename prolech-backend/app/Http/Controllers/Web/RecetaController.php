<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Insumo;
use App\Models\ProductoTienda;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecetaController extends Controller
{
    // ── GET /admin/recetas ────────────────────────────────────────────────────
    public function index()
    {
        $recetas = Receta::withCount('lotes')
            ->with(['detalles.insumo'])
            ->orderBy('nombre')
            ->get();

        // Mapa receta_id → producto_tienda_id para mostrar badges "En Tienda"
        $publicadasIds = ProductoTienda::whereNotNull('receta_id')
            ->pluck('id', 'receta_id')
            ->all();
        $preciosTienda = ProductoTienda::whereNotNull('receta_id')->pluck('precio_venta', 'receta_id')->all();
        $margenObjetivo = Receta::margenObjetivo();

        return view('admin.recetas.index', compact('recetas', 'publicadasIds', 'preciosTienda', 'margenObjetivo'));
    }

    // ── POST /admin/recetas/publicar ──────────────────────────────────────────
    /**
     * Crea un ProductoTienda a partir de una Receta con un solo clic.
     * El admin solo necesita poner el precio (y opcionalmente una imagen).
     */
    public function publicar(Request $request)
    {
        $request->validate([
            'receta_id'    => ['required', 'integer', 'exists:recetas,id'],
            'precio_venta' => ['required', 'numeric', 'min:0.50'],
            'descripcion'  => ['nullable', 'string'],
            'imagen'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $receta = Receta::findOrFail($request->receta_id);

        // Si ya existe un producto para esta receta, redirigir a edición
        $existente = ProductoTienda::where('receta_id', $receta->id)->first();
        if ($existente) {
            return redirect()
                ->route('admin.tienda.productos.edit', $existente)
                ->with('success', "⚠️ \"{$receta->nombre}\" ya está en la tienda. Aquí puedes editar su precio e imagen.");
        }

        $imagenUrl = null;
        if ($request->hasFile('imagen')) {
            $imagenUrl = $request->file('imagen')->store('tienda/productos', 'public');
        }

        ProductoTienda::create([
            'nombre'           => $receta->nombre,
            'descripcion'      => $request->descripcion ?: $receta->descripcion,
            'precio_venta'     => $request->precio_venta,
            // Lo ya producido de esta receta (lotes vigentes) entra directo al stock
            'stock_disponible' => app(\App\Services\StockLotes::class)->disponibleProducido($receta->id),
            'imagen_url'       => $imagenUrl,
            'estado'           => 'activo',
            'receta_id'        => $receta->id,
        ]);

        return redirect()
            ->route('admin.recetas.index')
            ->with('success', "🛒 \"{$receta->nombre}\" publicado en la tienda. Registra lotes de producción para generar stock.");
    }

    // ── GET /admin/recetas/create ─────────────────────────────────────────────
    public function create()
    {
        $insumos    = Insumo::activos()->orderBy('nombre')->get();
        $insumosJs  = $insumos->map(fn($i) => [
            'id'     => $i->id,
            'nombre' => $i->nombre,
            'unidad' => $i->unidad_medida,
            'stock'  => (float) $i->stock_actual,
        ])->values()->all();
        $receta = null;

        return view('admin.recetas.form', compact('insumos', 'insumosJs', 'receta'));
    }

    // ── POST /admin/recetas ───────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $this->validar($request);

        try {
            DB::transaction(function () use ($validated) {
                $receta = Receta::create([
                    'nombre'                        => $validated['nombre'],
                    'rendimiento_esperado_unidades' => $validated['rendimiento_esperado_unidades'],
                    'dias_vida_util'                => $validated['dias_vida_util'] ?? 15,
                    'descripcion'                   => $validated['descripcion'] ?? null,
                    'activa'                        => true,
                ]);

                $this->sincronizarDetalles($receta, $validated['insumos'] ?? []);
            });

            return redirect()
                ->route('admin.recetas.index')
                ->with('success', "Receta \"{$validated['nombre']}\" creada correctamente.");

        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()
                ->withErrors(['general' => 'Error al guardar la receta. Intenta nuevamente.']);
        }
    }

    // ── GET /admin/recetas/{receta}/edit ──────────────────────────────────────
    public function edit(Receta $receta)
    {
        $insumos   = Insumo::activos()->orderBy('nombre')->get();
        $insumosJs = $insumos->map(fn($i) => [
            'id'     => $i->id,
            'nombre' => $i->nombre,
            'unidad' => $i->unidad_medida,
            'stock'  => (float) $i->stock_actual,
        ])->values()->all();
        $receta->load('detalles.insumo');

        return view('admin.recetas.form', compact('insumos', 'insumosJs', 'receta'));
    }

    // ── PUT /admin/recetas/{receta} ───────────────────────────────────────────
    public function update(Request $request, Receta $receta)
    {
        $validated = $this->validar($request, $receta->id);

        try {
            DB::transaction(function () use ($receta, $validated) {
                $receta->update([
                    'nombre'                        => $validated['nombre'],
                    'rendimiento_esperado_unidades' => $validated['rendimiento_esperado_unidades'],
                    'dias_vida_util'                => $validated['dias_vida_util'] ?? 15,
                    'descripcion'                   => $validated['descripcion'] ?? null,
                ]);

                $this->sincronizarDetalles($receta, $validated['insumos'] ?? []);
            });

            return redirect()
                ->route('admin.recetas.index')
                ->with('success', "Receta \"{$receta->nombre}\" actualizada.");

        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()
                ->withErrors(['general' => 'Error al actualizar la receta. Intenta nuevamente.']);
        }
    }

    // ── POST /admin/recetas/{receta}/toggle ───────────────────────────────────
    public function toggle(Receta $receta)
    {
        $receta->update(['activa' => ! $receta->activa]);

        $estado = $receta->activa ? 'activada' : 'desactivada';

        return back()->with('success', "Receta \"{$receta->nombre}\" {$estado}.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validar(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'nombre' => [
                'required', 'string', 'max:120',
                \Illuminate\Validation\Rule::unique('recetas', 'nombre')
                    ->ignore($ignorarId),
            ],
            'rendimiento_esperado_unidades' => ['required', 'integer', 'min:1', 'max:99999'],
            'dias_vida_util'                => ['nullable', 'integer', 'min:1', 'max:3650'],
            'descripcion'                   => ['nullable', 'string', 'max:1000'],

            // Detalle de ingredientes
            'insumos'                       => ['required', 'array', 'min:1'],
            'insumos.*.insumo_id'           => ['required', 'integer', 'exists:insumos,id'],
            'insumos.*.cantidad_requerida'  => ['required', 'numeric', 'min:0.0001'],
        ], [
            'nombre.required'                              => 'El nombre del producto es obligatorio.',
            'nombre.unique'                                => 'Ya existe una receta con ese nombre.',
            'rendimiento_esperado_unidades.required'       => 'El rendimiento base es obligatorio.',
            'rendimiento_esperado_unidades.min'            => 'El rendimiento debe ser al menos 1.',
            'insumos.required'                             => 'Debes agregar al menos un ingrediente.',
            'insumos.min'                                  => 'Debes agregar al menos un ingrediente.',
            'insumos.*.insumo_id.required'                 => 'Selecciona un insumo.',
            'insumos.*.cantidad_requerida.required'        => 'Ingresa la cantidad.',
            'insumos.*.cantidad_requerida.min'             => 'La cantidad debe ser mayor a 0.',
        ]);
    }

    /**
     * Borra todos los detalles existentes y recrea con los nuevos.
     * Simple y predecible — para recetas no es un problema de rendimiento.
     */
    private function sincronizarDetalles(Receta $receta, array $insumos): void
    {
        $receta->detalles()->delete();

        $vistos = [];
        foreach ($insumos as $item) {
            $id = (int) $item['insumo_id'];

            // Ignorar duplicados enviados en el form
            if (in_array($id, $vistos, true)) {
                continue;
            }
            $vistos[] = $id;

            RecetaDetalle::create([
                'receta_id'          => $receta->id,
                'insumo_id'          => $id,
                'cantidad_requerida' => $item['cantidad_requerida'],
            ]);
        }
    }
}
