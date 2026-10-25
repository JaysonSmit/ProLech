<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PedidoWeb;
use App\Models\ProductoTienda;
use App\Models\Receta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminTiendaController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    // PRODUCTOS — CRUD
    // ══════════════════════════════════════════════════════════════════════════

    /** GET /admin/tienda/productos */
    public function productosIndex(Request $request): View
    {
        $estado = $request->query('estado');

        $productos = ProductoTienda::with('receta:id,nombre')
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tienda.productos.index', compact('productos', 'estado'));
    }

    /** GET /admin/tienda/productos/create */
    public function productosCreate(): View
    {
        $recetas = Receta::where('activa', true)->orderBy('nombre')->get(['id', 'nombre']);

        return view('admin.tienda.productos.create', compact('recetas'));
    }

    /** POST /admin/tienda/productos */
    public function productosStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'           => ['required', 'string', 'max:150'],
            'slug'             => ['nullable', 'string', 'max:160', 'unique:productos_tienda,slug'],
            'descripcion'      => ['nullable', 'string', 'max:2000'],
            'precio_venta'     => ['required', 'numeric', 'min:0.01'],
            'stock_disponible' => ['required', 'integer', 'min:0'],
            'estado'           => ['required', Rule::in(['activo', 'inactivo'])],
            'receta_id'        => ['nullable', 'integer', 'exists:recetas,id'],
            'imagen'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Subir imagen al disco público si viene
        $validated['imagen_url'] = null;
        if ($request->hasFile('imagen')) {
            $validated['imagen_url'] = $request->file('imagen')
                ->store('tienda/productos', 'public');
        }

        // El slug se auto-genera en el boot() si viene vacío
        if (empty($validated['slug'])) {
            unset($validated['slug']);
        }

        ProductoTienda::create($validated);

        return redirect()
            ->route('admin.tienda.productos.index')
            ->with('success', '✅ Producto creado correctamente.');
    }

    /** GET /admin/tienda/productos/{producto}/edit */
    public function productosEdit(ProductoTienda $producto): View
    {
        $recetas = Receta::where('activa', true)->orderBy('nombre')->get(['id', 'nombre']);

        return view('admin.tienda.productos.edit', compact('producto', 'recetas'));
    }

    /** PUT /admin/tienda/productos/{producto} */
    public function productosUpdate(Request $request, ProductoTienda $producto): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'           => ['required', 'string', 'max:150'],
            'slug'             => ['nullable', 'string', 'max:160', Rule::unique('productos_tienda', 'slug')->ignore($producto->id)],
            'descripcion'      => ['nullable', 'string', 'max:2000'],
            'precio_venta'     => ['required', 'numeric', 'min:0.01'],
            'stock_disponible' => ['required', 'integer', 'min:0'],
            'estado'           => ['required', Rule::in(['activo', 'inactivo'])],
            'receta_id'        => ['nullable', 'integer', 'exists:recetas,id'],
            'imagen'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Reemplazar imagen si viene una nueva
        if ($request->hasFile('imagen')) {
            // Borrar la anterior
            if ($producto->imagen_url) {
                Storage::disk('public')->delete($producto->imagen_url);
            }
            $validated['imagen_url'] = $request->file('imagen')
                ->store('tienda/productos', 'public');
        }

        // Mantener el slug actual si no se cambia
        if (empty($validated['slug'])) {
            unset($validated['slug']);
        }

        $producto->update($validated);

        return redirect()
            ->route('admin.tienda.productos.index')
            ->with('success', '✅ Producto actualizado correctamente.');
    }

    /** DELETE /admin/tienda/productos/{producto} */
    public function productosDestroy(ProductoTienda $producto): RedirectResponse
    {
        // No borrar si tiene pedidos asociados
        if ($producto->pedidoDetalles()->exists()) {
            return back()->with('error', '⚠️ No se puede eliminar: el producto tiene pedidos asociados. Márcalo como inactivo.');
        }

        if ($producto->imagen_url) {
            Storage::disk('public')->delete($producto->imagen_url);
        }

        $producto->delete();

        return redirect()
            ->route('admin.tienda.productos.index')
            ->with('success', '🗑️ Producto eliminado.');
    }

    /** POST /admin/tienda/productos/{producto}/toggle */
    public function productosToggle(ProductoTienda $producto): RedirectResponse
    {
        $nuevo = $producto->estado === 'activo' ? 'inactivo' : 'activo';
        $producto->update(['estado' => $nuevo]);

        $msg = $nuevo === 'activo'
            ? "✅ Producto \"{$producto->nombre}\" activado."
            : "⏸ Producto \"{$producto->nombre}\" desactivado.";

        return back()->with('success', $msg);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PEDIDOS WEB — Gestión y seguimiento
    // ══════════════════════════════════════════════════════════════════════════

    /** GET /admin/tienda/pedidos */
    public function pedidosIndex(Request $request): View
    {
        $estadoFiltro  = $request->query('estado');
        $metodoPago    = $request->query('metodo_pago');

        $pedidos = PedidoWeb::with('detalles.producto:id,nombre')
            ->when($estadoFiltro, fn ($q) => $q->where('estado_pedido', $estadoFiltro))
            ->when($metodoPago,   fn ($q) => $q->where('metodo_pago', $metodoPago))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // Contadores por estado para el resumen rápido
        $contadores = PedidoWeb::selectRaw('estado_pedido, COUNT(*) as total')
            ->groupBy('estado_pedido')
            ->pluck('total', 'estado_pedido');

        return view('admin.tienda.pedidos.index', compact('pedidos', 'estadoFiltro', 'metodoPago', 'contadores'));
    }

    /** GET /admin/tienda/pedidos/{pedido} */
    public function pedidosShow(PedidoWeb $pedido): View
    {
        $pedido->load('detalles.producto');

        return view('admin.tienda.pedidos.show', compact('pedido'));
    }

    /** POST /admin/tienda/pedidos/{pedido}/estado */
    public function pedidosEstado(Request $request, PedidoWeb $pedido): RedirectResponse
    {
        $request->validate([
            'estado_pedido' => ['required', Rule::in(['Pendiente', 'Pagado', 'Enviado', 'Cancelado'])],
        ]);

        $pedido->update(['estado_pedido' => $request->estado_pedido]);

        return back()->with(
            'success',
            "✅ Pedido {$pedido->codigo_pedido} marcado como {$request->estado_pedido}."
        );
    }
}
