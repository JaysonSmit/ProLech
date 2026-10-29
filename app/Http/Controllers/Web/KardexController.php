<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Insumo;
use App\Models\ProductoTienda;
use App\Services\Kardex;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** Kárdex valorizado (costo promedio ponderado) de insumos y de productos terminados. */
class KardexController extends Controller
{
    public function __construct(private readonly Kardex $kardex) {}

    public function insumos(Request $request): View
    {
        $items = Insumo::orderByRaw("CASE WHEN LOWER(nombre) = 'leche cruda' THEN 0 ELSE 1 END")->orderBy('nombre')->get();
        $item = $items->firstWhere('id', (int) $request->query('id')) ?? $items->first();
        [$desde, $hasta] = $this->rango($request);
        $filas = $item ? $this->kardex->insumo($item, $desde, $hasta) : collect();

        return view('admin.kardex.index', $this->datos($request, 'insumos', $items, $item, $filas, $desde, $hasta) + [
            'unidad' => $item?->unidad_medida ?? '',
        ]);
    }

    public function productos(Request $request): View
    {
        $items = ProductoTienda::orderBy('nombre')->get();
        $item = $items->firstWhere('id', (int) $request->query('id')) ?? $items->first();
        [$desde, $hasta] = $this->rango($request);
        $filas = $item ? $this->kardex->producto($item, $desde, $hasta) : collect();

        return view('admin.kardex.index', $this->datos($request, 'productos', $items, $item, $filas, $desde, $hasta) + ['unidad' => 'und']);
    }

    private function rango(Request $request): array
    {
        return [
            $request->filled('desde') ? Carbon::parse($request->query('desde')) : null,
            $request->filled('hasta') ? Carbon::parse($request->query('hasta')) : null,
        ];
    }

    private function datos(Request $request, string $tipo, Collection $items, $item, Collection $filas, $desde, $hasta): array
    {
        $porPagina = 15;
        $pagina = LengthAwarePaginator::resolveCurrentPage();
        $paginadas = new LengthAwarePaginator(
            $filas->reverse()->values()->forPage($pagina, $porPagina)->values(), $filas->count(), $porPagina, $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $ultimo = $filas->last();

        return [
            'tipo' => $tipo, 'items' => $items, 'item' => $item, 'filas' => $paginadas, 'desde' => $desde, 'hasta' => $hasta,
            'resumen' => [
                'entradas'       => $filas->where('tipo', 'entrada')->sum('cantidad'),
                'valor_entradas' => $filas->where('tipo', 'entrada')->sum('total'),
                'salidas'        => $filas->where('tipo', 'salida')->sum('cantidad'),
                'valor_salidas'  => $filas->where('tipo', 'salida')->sum('total'),
                'saldo'          => $ultimo['saldo_cant'] ?? 0,
                'saldo_valor'    => $ultimo['saldo_valor'] ?? 0,
                'costo_prom'     => $ultimo['costo_prom'] ?? 0,
            ],
        ];
    }
}
