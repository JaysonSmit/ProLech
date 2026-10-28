<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Acopio;
use App\Models\ControlCalidad;
use App\Models\Ganadero;
use App\Services\ControlCalidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Control de calidad sorpresa (admin y personal de calidad). Confidencial: los choferes no lo ven. */
class ControlSorpresaController extends Controller
{
    public function __construct(private readonly ControlCalidadService $calidad) {}

    public function index(): View
    {
        $hoy = ControlCalidad::with(['ganadero:id,nombre,dni,zona_id', 'zona:id,nombre', 'acopio:id,litros,fecha'])
            ->whereDate('fecha', '<=', today())->pendientes()
            ->orderByRaw("CASE tipo WHEN 'predio' THEN 0 WHEN 'muestra' THEN 1 ELSE 2 END")->orderBy('fecha')->get();
        $proximos = ControlCalidad::with('ganadero:id,nombre')->whereDate('fecha', '>', today())->pendientes()->orderBy('fecha')->get();
        $semaforo = $this->calidad->semaforo();
        $historial = ControlCalidad::with(['ganadero:id,nombre', 'zona:id,nombre', 'inspector:id,name'])
            ->where('estado', 'analizado')->latest('analizado_at')->paginate(15);
        $alertas = Acopio::with('ganadero:id,nombre', 'chofer:id,name')->whereDate('fecha', '>=', today()->subDays(7))
            ->whereNotNull('observacion')->where('observacion', '!=', 'normal')->latest('created_at')->take(12)->get();
        $bloqueados = Ganadero::where(fn ($q) => $q->where('entregas_bloqueadas', '>', 0)->orWhere('estado', Ganadero::ESTADO_SUSPENDIDO))
            ->orderByDesc('entregas_bloqueadas')->get();

        $resumen = [
            'hoy'        => $hoy->count(),
            'rojos'      => $semaforo->where('nivel', 'rojo')->count(),
            'amarillos'  => $semaforo->where('nivel', 'amarillo')->count(),
            'bloqueados' => $bloqueados->count(),
            'mes'        => ControlCalidad::where('estado', 'analizado')->whereMonth('fecha', now()->month)->whereYear('fecha', now()->year)
                ->selectRaw("COUNT(*) total, SUM(CASE WHEN resultado = 'grave' THEN 1 ELSE 0 END) graves, SUM(CASE WHEN resultado = 'leve' THEN 1 ELSE 0 END) leves")->first(),
        ];
        $yaSorteado = ControlCalidad::whereDate('fecha', today())->where('motivo', 'like', 'Sorteo%')->exists();

        return view('admin.calidad.control_sorpresa', [
            'hoy' => $hoy, 'proximos' => $proximos, 'semaforo' => $semaforo, 'historial' => $historial, 'alertas' => $alertas,
            'bloqueados' => $bloqueados, 'resumen' => $resumen, 'yaSorteado' => $yaSorteado,
            'observaciones' => ControlCalidadService::OBSERVACIONES,
        ]);
    }

    public function sortear(): RedirectResponse
    {
        $controles = $this->calidad->sortear();
        return back()->with($controles->isEmpty() ? 'error' : 'success', $controles->isEmpty()
            ? 'Hoy ya se hizo el sorteo (o no hay rutas con chofer asignado este mes).'
            : "🎲 Sorteo listo: {$controles->count()} controles para hoy. Los choferes se enterarán recién en el predio.");
    }

    public function programar(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'ganadero_id' => ['required', 'exists:ganaderos,id'],
            'tipo'        => ['required', Rule::in(['predio', 'muestra'])],
            'fecha'       => ['nullable', 'date', 'after_or_equal:today'],
            'seguimiento' => ['nullable', 'boolean'],
        ]);
        $g = Ganadero::findOrFail($v['ganadero_id']);

        if (! empty($v['seguimiento'])) {
            $this->calidad->seguimiento($g);
            return back()->with('success', "🔍 Seguimiento de {$g->nombre}: frasco en sus próximas 3 entregas.");
        }
        $this->calidad->programar($g, $v['tipo'], 'Manual', isset($v['fecha']) ? \Illuminate\Support\Carbon::parse($v['fecha']) : null);
        return back()->with('success', "📌 Control programado para {$g->nombre}.");
    }

    public function resultado(Request $request, ControlCalidad $control): RedirectResponse
    {
        abort_if($control->estado === 'analizado', 422, 'Este control ya tiene resultado.');
        $v = $request->validate([
            'agua'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'densidad'    => ['nullable', 'numeric', 'min:0.9', 'max:1.1'],
            'grasa'       => ['nullable', 'numeric', 'min:0', 'max:15'],
            'ph'          => ['nullable', 'numeric', 'min:0', 'max:14'],
            'temperatura' => ['nullable', 'numeric', 'min:-5', 'max:60'],
            'resultado'   => ['nullable', Rule::in(['normal', 'leve', 'grave'])],
            'notas'       => ['nullable', 'string', 'max:1000'],
            'foto'        => ['nullable', 'image', 'max:6144'],
        ]);
        if ($request->hasFile('foto')) {
            $v['foto_path'] = $request->file('foto')->store('controles-calidad', 'public');
        }
        unset($v['foto']);

        $control = $this->calidad->registrarResultado($control, array_filter($v, fn ($x) => $x !== null && $x !== ''), $request->user());

        $nombre = $control->ganadero?->nombre ?? ('tanque de ' . $control->zona?->nombre);
        return back()->with('success', "✅ Resultado de {$nombre}: " . ControlCalidad::RESULTADOS[$control->resultado]
            . ($control->sancion ? " · {$control->sancion}" : '') . '. Se avisó al administrador y al chofer.');
    }

    public function cancelar(ControlCalidad $control): RedirectResponse
    {
        abort_if($control->estado === 'analizado', 422);
        $control->update(['estado' => 'cancelado']);
        return back()->with('success', 'Control cancelado.');
    }

    /** Levantar un bloqueo o una suspensión por calidad (solo administrador). */
    public function desbloquear(Request $request, Ganadero $ganadero): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $ganadero->update(['entregas_bloqueadas' => 0, 'bloqueo_motivo' => null, 'estado' => Ganadero::ESTADO_ACTIVO]);
        return back()->with('success', "🔓 {$ganadero->nombre} puede volver a entregar.");
    }
}
