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
        $choferes = User::conRol('chofer')->where('activo', true)->get(['id', 'name', 'email']);

        $asignaciones = AsignacionZona::with(['chofer:id,name', 'zona', 'zonaExtra'])
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->get();

        // Mapa zona_id → asignacion para la vista (titular) y zona → quien la cubre como 2.ª ruta
        $mapaZona  = $asignaciones->whereNotNull('zona_id')->keyBy('zona_id');
        $mapaExtra = $asignaciones->whereNotNull('zona_extra_id')->keyBy('zona_extra_id');
        $volante   = $asignaciones->firstWhere('rol_mes', 'volante');
        $libres    = AsignacionZona::zonasSinChofer($mes, $anio);
        $litrosZona = $zonas->mapWithKeys(fn ($z) => [$z->id => AsignacionZona::litrosDiariosZona($z->id)]);
        // Carga diaria estimada de cada chofer con 2 rutas
        $cargas = $asignaciones->whereNotNull('zona_extra_id')->mapWithKeys(fn ($a) => [$a->chofer_id => $a->cargaEstimada()]);

        // Historial de rotaciones (últimos 6 meses)
        $historial = AsignacionZona::with(['chofer:id,name', 'zona', 'zonaExtra'])
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
            'zonas', 'choferes', 'asignaciones', 'mapaZona', 'mapaExtra', 'volante', 'libres', 'litrosZona', 'cargas',
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

        $choferes = User::conRol('chofer')
                        ->where('activo', true)
                        ->orderBy('id')
                        ->pluck('id')
                        ->toArray();

        // Con más rutas que choferes ya no falla: las que sobran se reparten como segunda ruta
        try {
            $creadas = AsignacionZona::generarRotacion($choferes, $mes, $anio);
            $dobles = AsignacionZona::where('mes', $mes)->where('anio', $anio)->whereNotNull('zona_extra_id')->count();
            return redirect()
                ->route('admin.rotacion.index', ['mes' => $mes, 'anio' => $anio])
                ->with('success', "✅ Rotación generada para {$mes}/{$anio}: " . count($creadas) . ' asignaciones'
                    . ($dobles ? " · {$dobles} chofer(es) con 2 rutas porque hay más rutas que choferes." : '.'));
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

        // Cada zona tiene un solo titular por mes: si ya la ocupa otro chofer, intercambian
        $intercambio = AsignacionZona::asignar((int) $validated['chofer_id'], $validated['zona_id'] ? (int) $validated['zona_id'] : null,
            (int) $mes, (int) $anio, $validated['notas'] ?? null);

        $chofer = User::find($validated['chofer_id']);
        $zona   = $validated['zona_id'] ? Zona::find($validated['zona_id'])?->nombre : 'Volante';
        $extra  = $intercambio ? " Intercambio: {$intercambio}." : '';

        return redirect()
            ->route('admin.rotacion.index', compact('mes', 'anio'))
            ->with('success', "✅ {$chofer->name} asignado a {$zona} para {$mes}/{$anio}.{$extra}");
    }

    /** POST /admin/rotacion/segunda-ruta — da (o quita) una segunda ruta a un chofer en el mes. */
    public function segundaRuta(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'chofer_id'     => ['required', 'exists:users,id'],
            'zona_extra_id' => ['nullable', 'exists:zonas,id'],
            'mes'           => ['required', 'integer', 'min:1', 'max:12'],
            'anio'          => ['required', 'integer'],
        ]);
        $mes = (int) $v['mes']; $anio = (int) $v['anio'];
        try {
            $detalle = AsignacionZona::asignarSegundaRuta((int) $v['chofer_id'], $v['zona_extra_id'] ? (int) $v['zona_extra_id'] : null, $mes, $anio);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        $chofer = User::find($v['chofer_id'])->name;
        $zona = $v['zona_extra_id'] ? Zona::find($v['zona_extra_id'])->nombre : null;
        $this->avisarChofer((int) $v['chofer_id'], $zona
            ? "Este mes también recorres la ruta {$zona}. Abre «Mi ruta» para ver todas tus paradas."
            : 'Ya no recorres una segunda ruta este mes.');

        return redirect()->route('admin.rotacion.index', compact('mes', 'anio'))
            ->with('success', $zona ? "✅ {$chofer} también recorre {$zona}. {$detalle}" : "✅ {$chofer}: {$detalle}");
    }

    /** POST /admin/rotacion/cubrir-libres — reparte las rutas sin chofer (primero al volante). */
    public function cubrirLibres(Request $request): RedirectResponse
    {
        $v = $request->validate(['mes' => ['required', 'integer', 'min:1', 'max:12'], 'anio' => ['required', 'integer']]);
        $mes = (int) $v['mes']; $anio = (int) $v['anio'];
        $hechas = AsignacionZona::cubrirRutasLibres($mes, $anio);
        foreach (AsignacionZona::with('zonaExtra')->where('mes', $mes)->where('anio', $anio)->whereNotNull('zona_extra_id')->get() as $a) {
            if (collect($hechas)->contains(fn ($h) => str_starts_with($h, $a->zonaExtra->nombre . ' →'))) {
                $this->avisarChofer($a->chofer_id, "Este mes también recorres la ruta {$a->zonaExtra->nombre}. Abre «Mi ruta» para ver todas tus paradas.");
            }
        }
        $quedan = AsignacionZona::zonasSinChofer($mes, $anio)->pluck('nombre');

        return redirect()->route('admin.rotacion.index', compact('mes', 'anio'))->with(
            $hechas ? 'success' : 'error',
            $hechas ? '✅ Rutas cubiertas: ' . implode(' · ', $hechas) . ($quedan->isNotEmpty() ? '. Aún sin chofer: ' . $quedan->join(', ') . ' (todos ya tienen 2 rutas).' : '.')
                    : 'No hay choferes con espacio para otra ruta este mes. Asigna primero los choferes del mes.'
        );
    }

    private function avisarChofer(int $choferId, string $mensaje): void
    {
        \App\Models\Notificacion::enviar(userId: $choferId, titulo: '🗺️ Cambió tu ruta del mes', mensaje: $mensaje, tipo: 'ruta_cambiada');
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
