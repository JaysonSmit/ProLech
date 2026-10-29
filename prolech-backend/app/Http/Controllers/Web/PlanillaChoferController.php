<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PlanillaChofer;
use App\Services\CostoLecheService;
use App\Services\PlanillaChoferService;
use App\Support\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Planilla de pago a choferes (solo administrador). */
class PlanillaChoferController extends Controller
{
    public function __construct(private readonly PlanillaChoferService $planillas) {}

    /** @return array{0: string, 1: Carbon, 2: Carbon} */
    private function periodoDe(Request $request): array
    {
        $tipo = $request->query('tipo', $request->input('tipo')) === 'mensual' ? 'mensual' : 'semanal';
        $fecha = $request->filled('fecha') ? Carbon::parse($request->input('fecha')) : now();
        [$desde, $hasta] = PlanillaChoferService::periodo($tipo, $fecha);
        return [$tipo, $desde, $hasta];
    }

    public function index(Request $request): View
    {
        [$tipo, $desde, $hasta] = $this->periodoDe($request);

        $guardadas = PlanillaChofer::with('chofer:id,name,email')
            ->whereDate('periodo_inicio', $desde)->whereDate('periodo_fin', $hasta)->get()->keyBy('chofer_id');

        // Vista previa en vivo para los choferes que aún no tienen planilla guardada
        $filas = $this->planillas->choferes()->map(function ($chofer) use ($guardadas, $desde, $hasta) {
            $g = $guardadas->get($chofer->id);
            return (object) [
                'chofer'   => $chofer,
                'planilla' => $g,
                'datos'    => $g ? $g->toArray() : $this->planillas->calcular($chofer, $desde, $hasta),
            ];
        });

        $totales = [
            'dias'        => $filas->sum(fn ($f) => $f->datos['dias_trabajados']),
            'litros'      => $filas->sum(fn ($f) => $f->datos['litros_recolectados']),
            'km'          => $filas->sum(fn ($f) => $f->datos['km_recorridos']),
            'bruto'       => $filas->sum(fn ($f) => $f->datos['total_bruto']),
            'descuentos'  => $filas->sum(fn ($f) => $f->datos['descuentos']),
            'neto'        => $filas->sum(fn ($f) => $f->datos['total_neto']),
            'combustible' => $filas->sum(fn ($f) => $f->datos['costo_combustible']),
            'pagadas'     => $guardadas->where('estado', 'pagada')->count(),
            'borradores'  => $guardadas->where('estado', 'borrador')->count(),
        ];

        $historial = PlanillaChofer::with('chofer:id,name')->where('estado', 'pagada')
            ->latest('pagada_at')->take(10)->get();

        return view('admin.planilla.index', [
            'tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta, 'filas' => $filas, 'totales' => $totales,
            'historial' => $historial, 'parametros' => PlanillaChoferService::parametros(),
            'costoLeche' => CostoLecheService::actual(),
        ]);
    }

    public function generar(Request $request): RedirectResponse
    {
        [$tipo, $desde, $hasta] = $this->periodoDe($request);
        $n = $this->planillas->generar($tipo, $desde, $hasta, $request->user()->id)->count();
        app(CostoLecheService::class)->aplicarAInsumo();

        return redirect()->route('admin.planilla.index', ['tipo' => $tipo, 'fecha' => $desde->toDateString()])
            ->with('success', "⚙️ Planilla {$tipo} generada: {$n} chofer(es) del {$desde->format('d/m')} al {$hasta->format('d/m/Y')}.");
    }

    public function adelantos(Request $request, PlanillaChofer $planilla): RedirectResponse
    {
        abort_if($planilla->estaPagada(), 422, 'La planilla ya está pagada.');
        $v = $request->validate(['adelantos' => ['required', 'numeric', 'min:0', 'max:5000']]);
        $this->planillas->actualizarAdelantos($planilla, (float) $v['adelantos']);
        return back()->with('success', "Adelantos de {$planilla->chofer->name} actualizados.");
    }

    public function pagar(Request $request, PlanillaChofer $planilla): RedirectResponse
    {
        abort_if($planilla->estaPagada(), 422, 'La planilla ya está pagada.');
        $v = $request->validate([
            'metodo_pago'     => ['required', Rule::in(array_keys(PlanillaChofer::METODOS))],
            'referencia_pago' => ['nullable', 'string', 'max:60'],
        ]);
        $this->planillas->pagar($planilla, $v['metodo_pago'], $v['referencia_pago'] ?? null);
        app(CostoLecheService::class)->aplicarAInsumo();
        return back()->with('success', "💵 Pago a {$planilla->chofer->name} confirmado: S/ " . number_format($planilla->total_neto, 2));
    }

    public function boleta(PlanillaChofer $planilla): Response
    {
        $planilla->load('chofer:id,name,email', 'generador:id,name');
        return Pdf::loadView('admin.planilla.boleta_pdf', ['p' => $planilla])
            ->setPaper('a5', 'portrait')
            ->download("boleta_{$planilla->codigo()}.pdf");
    }

    public function exportar(Request $request): Response
    {
        [$tipo, $desde, $hasta] = $this->periodoDe($request);
        $filas = PlanillaChofer::with('chofer:id,name')
            ->whereDate('periodo_inicio', $desde)->whereDate('periodo_fin', $hasta)->get()
            ->map(fn ($p) => [
                'Chofer' => $p->chofer->name, 'Ruta' => $p->ruta, 'Días' => $p->dias_trabajados,
                'Litros' => $p->litros_recolectados, 'Km' => $p->km_recorridos,
                'Jornal' => $p->tarifa_base_dia, 'Base' => $p->monto_base, 'Bono' => $p->monto_bono,
                'Bruto' => $p->total_bruto, 'Pensión' => $p->descuento_pension, 'Adelantos' => $p->adelantos,
                'Neto' => $p->total_neto, 'Combustible (planta)' => $p->costo_combustible,
                'Estado' => $p->estado, 'Pagado' => $p->pagada_at?->format('d/m/Y H:i') ?? '',
            ]);
        $nombre = "planilla_choferes_{$tipo}_{$desde->format('Ymd')}";

        if ($request->query('formato') === 'pdf') {
            return Pdf::loadView('admin.operaciones.tabla-pdf', [
                'titulo'  => "Planilla de choferes — {$desde->format('d/m/Y')} al {$hasta->format('d/m/Y')}",
                'filas'   => $filas,
                'totales' => collect(['Días', 'Litros', 'Km', 'Base', 'Bono', 'Bruto', 'Pensión', 'Adelantos', 'Neto', 'Combustible (planta)'])
                    ->mapWithKeys(fn ($c) => [$c => $filas->sum($c)])->all(),
                'grupos'  => null,
            ])->setPaper('a4', 'landscape')->download("{$nombre}.pdf");
        }

        return response(
            Xlsx::generar('Planilla', $filas->isEmpty() ? ['Genera la planilla del periodo primero'] : array_keys($filas->first()), $filas),
            200,
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$nombre}.xlsx\"",
            ]
        );
    }

    /** Recalcula el costo real por litro y lo aplica al insumo «Leche Cruda». */
    public function recalcularCostoLeche(): RedirectResponse
    {
        $costo = app(CostoLecheService::class)->aplicarAInsumo();
        return back()->with($costo ? 'success' : 'error', $costo
            ? '🥛 Costo real por litro actualizado: S/ ' . number_format($costo, 2) . ' — recetas y precios sugeridos recalculados.'
            : 'No hay acopios en los últimos 30 días para calcular el costo real.');
    }
}
