<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CapacitacionAtos;
use App\Models\Ganadero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CapacitacionAtosController extends Controller
{
    public function index(): View
    {
        $registros = CapacitacionAtos::with(['ganadero:id,nombre,dni', 'registrador:id,name'])
            ->orderByDesc('fecha')
            ->paginate(20);

        $ganaderos = Ganadero::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'dni']);

        $stats = [
            'mes_capacitaciones' => CapacitacionAtos::where('tipo', 'capacitacion')
                ->whereMonth('fecha', now()->month)->whereYear('fecha', now()->year)->count(),
            'mes_visitas' => CapacitacionAtos::where('tipo', 'visita')
                ->whereMonth('fecha', now()->month)->whereYear('fecha', now()->year)->count(),
        ];

        return view('admin.calidad.capacitaciones_index', compact('registros', 'ganaderos', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fecha'         => ['required', 'date', 'before_or_equal:today'],
            'tipo'          => ['required', 'in:capacitacion,visita'],
            'ganadero_id'   => ['nullable', 'integer', 'exists:ganaderos,id'],
            'tema'          => ['required', 'string', 'max:200'],
            'asistentes'    => ['required', 'integer', 'min:1', 'max:500'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        CapacitacionAtos::create([
            ...$validated,
            'registrado_por' => $request->user()->id,
        ]);

        return back()->with('success', '✅ Registro ATOS guardado correctamente.');
    }
}
