<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Produccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProduccionController extends Controller
{
    public function index(): View
    {
        $lotes = Produccion::with('registrador:id,name')
                           ->orderByDesc('fecha_lote')
                           ->paginate(20);

        return view('admin.produccion.index', compact('lotes'));
    }

    public function create(): View
    {
        return view('admin.produccion.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'litros_usados'     => ['required', 'numeric', 'min:1'],
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

        return redirect()->route('admin.produccion.index')
                         ->with('success', 'Lote de producción registrado correctamente.');
    }

    public function show(Produccion $produccion): View
    {
        return view('admin.produccion.show', compact('produccion'));
    }
}
