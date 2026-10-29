<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Acopio;
use App\Models\ControlCalidadPlanta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalidadPlantaController extends Controller
{
    public function index(): View
    {
        $lotes = ControlCalidadPlanta::with('registrador:id,name')
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->paginate(20);

        $hoy = now()->toDateString();
        $resumenHoy = [
            'lotes'  => ControlCalidadPlanta::whereDate('fecha', $hoy)->count(),
            'tachos' => ControlCalidadPlanta::whereDate('fecha', $hoy)->sum('cantidad_tachos'),
            'litros' => ControlCalidadPlanta::whereDate('fecha', $hoy)->sum('litros_total'),
        ];

        $litrosCampoHoy = (float) Acopio::whereDate('fecha', $hoy)->sum('litros');
        $comparacionCampo = [
            'litros_campo'  => $litrosCampoHoy,
            'litros_planta' => (float) $resumenHoy['litros'],
            'diferencia'    => $litrosCampoHoy - (float) $resumenHoy['litros'],
        ];

        return view('admin.calidad.planta_index', compact('lotes', 'resumenHoy', 'comparacionCampo'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fecha'            => ['required', 'date', 'before_or_equal:today'],
            'codigo_lote'      => ['required', 'string', 'max:40'],
            'cantidad_tachos'  => ['required', 'integer', 'min:1', 'max:500'],
            'litros_total'     => ['required', 'numeric', 'min:0.1', 'max:50000'],
            'equipo_lactoscan' => ['nullable', 'integer', 'between:1,3'],
            'agua_promedio'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ph_promedio'      => ['nullable', 'numeric', 'min:0', 'max:14'],
            'resultado'        => ['required', 'in:aprobado,observado,rechazado'],
            'observaciones'    => ['nullable', 'string', 'max:2000'],
        ]);

        ControlCalidadPlanta::create([
            ...$validated,
            'registrado_por' => $request->user()->id,
        ]);

        return back()->with('success', '✅ Control de calidad en planta registrado.');
    }
}
