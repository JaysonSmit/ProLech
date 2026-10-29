<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function index(): View
    {
        $ventas = Venta::with('registrador:id,name')
                       ->orderByDesc('fecha_venta')
                       ->paginate(20);

        $resumenMes = Venta::whereMonth('fecha_venta', now()->month)
                           ->selectRaw('tipo_cliente, SUM(total) as total_tipo, SUM(cantidad_kg) as kg_tipo')
                           ->groupBy('tipo_cliente')
                           ->get()
                           ->keyBy('tipo_cliente');

        return view('admin.ventas.index', compact('ventas', 'resumenMes'));
    }

    public function create(): View
    {
        $precios = [
            'mayorista' => Venta::PRECIO_MAYORISTA,
            'publico'   => Venta::PRECIO_PUBLICO,
        ];

        return view('admin.ventas.create', compact('precios'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente'      => ['required', 'string', 'max:150'],
            'tipo_cliente' => ['required', Rule::in(['mayorista', 'publico'])],
            'cantidad_kg'  => ['required', 'numeric', 'min:0.001'],
            'fecha_venta'  => ['required', 'date', 'before_or_equal:today'],
        ]);

        // Precio determinado por tipo (no editable por el usuario)
        $validated['precio_unitario'] = Venta::precioParaTipo($validated['tipo_cliente']);
        $validated['registrado_por']  = $request->user()->id;

        Venta::create($validated);

        return redirect()->route('admin.ventas.index')
                         ->with('success', 'Venta registrada correctamente.');
    }
}
