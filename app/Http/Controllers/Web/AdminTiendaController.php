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
        // Con costo y precio sugerido para la ayuda del formulario
        $recetas = Receta::where('activa', true)->with('detalles.insumo')->orderBy('nombre')->get()
            ->each(fn (Receta $r) => $r->setAttribute('costo', $r->costoUnitario())->setAttribute('sugerido', $r->precioSugerido())
                ->setAttribute('producido', app(\App\Services\StockLotes::class)->disponibleProducido($r->id)));

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
            'stock_disponible' => ['nullable', 'integer', 'min:0'],
            'estado'           => ['required', Rule::in(['activo', 'inactivo'])],
            'receta_id'        => ['nullable', 'integer', 'exists:recetas,id'],
            'imagen'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Con receta y sin stock escrito: entra lo producido que sigue vigente en sus lotes
        if (($validated['stock_disponible'] ?? null) === null) {
            $validated['stock_disponible'] = ! empty($validated['receta_id'])
                ? app(\App\Services\StockLotes::class)->disponibleProducido((int) $validated['receta_id'])
                : 0;
        }

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
        $recetas = Receta::where('activa', true)->with('detalles.insumo')->orderBy('nombre')->get()
            ->each(function (Receta $r) {
                $r->setAttribute('costo', $r->costoUnitario());
                $r->setAttribute('sugerido', $r->precioSugerido());
            });

        $desglose = $producto->receta_id
            ? app(\App\Services\StockLotes::class)->desglose($producto->receta_id, $producto->stock_disponible)
            : collect();

        return view('admin.tienda.productos.edit', compact('producto', 'recetas', 'desglose'));
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

        $anterior = $pedido->estado_pedido;
        $nuevo    = $request->estado_pedido;

        \Illuminate\Support\Facades\DB::transaction(function () use ($pedido, $anterior, $nuevo) {
            $pedido->load('detalles');
            // Antes al cancelar el stock NO se devolvía: los productos quedaban "vendidos"
            if ($nuevo === 'Cancelado' && $anterior !== 'Cancelado') {
                $pedido->moverStock(+1);
            } elseif ($anterior === 'Cancelado' && $nuevo !== 'Cancelado') {
                foreach ($pedido->detalles as $d) {
                    $stock = \App\Models\ProductoTienda::whereKey($d->producto_tienda_id)->lockForUpdate()->value('stock_disponible');
                    if ($stock < $d->cantidad) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'estado_pedido' => 'No hay stock suficiente para reactivar este pedido.',
                        ]);
                    }
                }
                $pedido->moverStock(-1);
            }
            $pedido->update(['estado_pedido' => $nuevo]);
        });

        // Aviso automático al cliente (Ultramsg; sin credenciales solo se registra en el log)
        if ($anterior !== $nuevo) {
            \App\Services\WhatsAppService::enviar($pedido->cliente_telefono, $pedido->fresh()->mensajeEstado());
        }

        return back()->with(
            'success',
            "✅ Pedido {$pedido->codigo_pedido} marcado como {$nuevo}. Se avisó al cliente por WhatsApp."
        );
    }

    /** POST /admin/tienda/pedidos/{pedido}/sunat — reintenta el envío de la boleta. */
    public function pedidosSunat(PedidoWeb $pedido, \App\Services\FacturacionElectronicaService $sunat): RedirectResponse
    {
        $sunat->emitirBoleta($pedido);
        return back()->with('success', "Boleta {$pedido->numero_comprobante}: " . ($pedido->sunat_mensaje ?? $pedido->sunat_estado));
    }
}
