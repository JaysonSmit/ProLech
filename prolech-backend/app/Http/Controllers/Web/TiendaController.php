<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProductoTienda;
use Illuminate\View\View;

class TiendaController extends Controller
{
    /** GET /tienda — Catálogo público */
    public function index(): View
    {
        $productos = ProductoTienda::activos()
            ->orderByDesc('stock_disponible')
            ->orderBy('nombre')
            ->get();

        return view('tienda.index', compact('productos'));
    }

    /** GET /tienda/{slug} — Detalle del producto */
    public function show(string $slug): View
    {
        $producto = ProductoTienda::where('slug', $slug)
            ->where('estado', 'activo')
            ->firstOrFail();

        // Productos relacionados (misma sección, distintos al actual, máx 4)
        $relacionados = ProductoTienda::activos()
            ->where('id', '!=', $producto->id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('tienda.show', compact('producto', 'relacionados'));
    }
}
