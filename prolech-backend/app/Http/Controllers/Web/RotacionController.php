<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AsignacionZona;
use App\Models\Zona;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RotacionController extends Controller
{
    /**
     * GET /admin/rotacion
     * Vista principal: muestra asignaciones del mes actual y permite gestionar.
     */
    public function index(Request $request): View
    {
        $mes  = (int) $request->query('mes',  now()->month);
        $anio = (int) $request->query('anio', now()->year);

        $zonas    = Zona::where('activa', true)->orderBy('orden')->get();
        $choferes = User::where('rol', 'chofer')->where('activo', true)->get(['id', 'name', 'email']);

        $asignaciones = AsignacionZona::with(['chofer:id,name', 'zona'])
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->get();

        // Mapa zona_id → asignacion para la vista
        $mapaZona = $asignaciones->whereNotNull('zona_id')->keyBy('zona_id');
        $volante  = $asignaciones->firstWhere('rol_mes', 'volante');

        // Historial de rotaciones (últimos 6 meses)
        $historial = AsignacionZona::with(['chofer:id,name', 'zona'])
            ->whereIn('chofer_id', $choferes->pluck('id'))
            ->where(function ($q) use ($mes, $anio) {
                $q->where('anio', '<', $anio)
                  ->orWhere(function ($q2) use ($mes, $anio) {
                      $q2->where('anio', $anio)->where('mes', '<=', $mes);
                  });
            })
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->take(30)
            ->get()
            ->groupBy(fn ($a) => "{$a->anio}-{$a->mes}");

        return view('admin.rotacion.index', compact(
            'zonas', 'choferes', 'asignaciones', 'mapaZona', 'volante',
            'mes', 'anio', 'historial'
        ));
    }

    /**
     * POST /admin/rotacion/generar-automatico
     * Genera la rotación automática del próximo mes.
     */
    public function generarAutomatico(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mes'  => ['required', 'integer', 'min:1', 'max:12'],
            'anio' => ['required', 'integer', 'min:2024', 'max:2100'],
        ]);

        $mes  = (int) $validated['mes'];
        $anio = (int) $validated['anio'];

        // Verificar que no existan asignaciones para ese mes
        if (AsignacionZona::where('mes', $mes)->where('anio', $anio)->exists()) {
            return back()->with('error', "Ya existen asignaciones para {$mes}/{$anio}. Bórralas primero si deseas regenerar.");
        }

        $choferes = User::where('rol', 'chofer')
                        ->where('activo', true)
                        ->orderBy('id')
                        ->pluck('id')
                        ->toArray();

        if (count($choferes) < 5) {
            return back()->with('error', 'Se necesitan al menos 5 choferes activos para generar la rotación.');
        }

        try {
            $creadas = AsignacionZona::generarRotacion($choferes, $mes, $anio);
            return redirect()
                ->route('admin.rotacion.index', ['mes' => $mes, 'anio' => $anio])
                ->with('success', "✅ Rotación generada para {$mes}/{$anio}: " . count($creadas) . " asignaciones.");
        } catch (\Exception $e) {
            return back()->with('error', "Error al generar rotación: " . $e->getMessage());
        }
    }

    /**
     * POST /admin/rotacion/asignar-manual
     * Asignación manual de un chofer a una zona en un mes específico.
     */
    public function asignarManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chofer_id' => ['required', 'exists:users,id'],
            'zona_id'   => ['nullable', 'exists:zonas,id'],
            'mes'       => ['required', 'integer', 'min:1', 'max:12'],
            'anio'      => ['required', 'integer'],
            'rol_mes'   => ['required', Rule::in(['titular', 'volante'])],
            'notas'     => ['nullable', 'string', 'max:255'],
        ]);

        $mes  = $validated['mes'];
        $anio = $validated['anio'];

        // Si es volante, zona_id debe ser null
        if ($validated['rol_mes'] === 'volante') {
            $validated['zona_id'] = null;
        }

        // Actualizar o crear
        AsignacionZona::updateOrCreate(
            ['chofer_id' => $validated['chofer_id'], 'mes' => $mes, 'anio' => $anio],
            [
                'zona_id'      => $validated['zona_id'],
                'rol_mes'      => $validated['rol_mes'],
                'fecha_inicio' => \Carbon\Carbon::createFromDate($anio, $mes, 1)->toDateString(),
                'fecha_fin'    => \Carbon\Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->toDateString(),
                'notas'        => $validated['notas'],
            ]
        );

        $chofer = User::find($validated['chofer_id']);
        $zona   = $validated['zona_id'] ? Zona::find($validated['zona_id'])?->nombre : 'Volante';

        return redirect()
            ->route('admin.rotacion.index', compact('mes', 'anio'))
            ->with('success', "✅ {$chofer->name} asignado a {$zona} para {$mes}/{$anio}.");
    }

    /**
     * DELETE /admin/rotacion/{asignacion}
     * Elimina una asignación específica.
     */
    public function destruir(AsignacionZona $asignacion): RedirectResponse
    {
        $mes  = $asignacion->mes;
        $anio = $asignacion->anio;
        $asignacion->delete();

        return redirect()
            ->route('admin.rotacion.index', compact('mes', 'anio'))
            ->with('success', 'Asignación eliminada.');
    }
}
