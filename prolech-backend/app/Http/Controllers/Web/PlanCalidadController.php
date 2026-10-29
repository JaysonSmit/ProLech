<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PlanCalidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PlanCalidadController — RF-CAL-08
 *
 * Gestiona el plan de control de calidad inopinado.
 * ACCESO RESTRINGIDO: middleware rol:admin aplicado en rutas.
 * Los conductores/choferes no tienen acceso a estas rutas.
 */
class PlanCalidadController extends Controller
{
    public function index(): View
    {
        $planes = PlanCalidad::with('creadoPor:id,name')
            ->orderByDesc('fecha')
            ->paginate(20);

        $proximos = PlanCalidad::where('fecha', '>=', now()->toDateString())
            ->where('ejecutado', false)
            ->orderBy('fecha')
            ->get();

        return view('admin.calidad.plan_index', compact('planes', 'proximos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fecha'                 => ['required', 'date', 'after_or_equal:today'],
            'ruta_objetivo'         => ['required', 'string', 'max:100'],
            'responsable'           => ['nullable', 'string', 'max:150'],
            'num_equipos_lactoscan' => ['required', 'integer', 'min:1', 'max:3'],
            'observaciones'         => ['nullable', 'string', 'max:500'],
        ]);

        PlanCalidad::create([
            ...$validated,
            'creado_por' => $request->user()->id,
        ]);

        return back()->with('success', '✅ Plan de control de calidad creado. Información CONFIDENCIAL.');
    }

    public function marcarEjecutado(Request $request, PlanCalidad $plan): RedirectResponse
    {
        $validated = $request->validate([
            'resultado_resumen' => ['nullable', 'string', 'max:1000'],
        ]);

        $plan->update([
            'ejecutado'         => true,
            'resultado_resumen' => $validated['resultado_resumen'] ?? null,
        ]);

        return back()->with('success', '✅ Operativo marcado como ejecutado.');
    }

    public function destroy(PlanCalidad $plan): RedirectResponse
    {
        $plan->delete();
        return back()->with('success', 'Plan eliminado.');
    }
}
