<?php

namespace App\Services;

use App\Models\PedidoWeb;
use App\Models\ProductoTienda;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Números del dashboard de ventas: planta (ventas registradas en el panel) + tienda web
 * (pedidos no cancelados). Las ventas anuladas no cuentan.
 */
class VentasDashboard
{
    public function datos(): array
    {
        $hoy = today();
        $inicioMes = $hoy->copy()->startOfMonth();
        $inicioMesAnterior = $inicioMes->copy()->subMonth();
        $hace30 = $hoy->copy()->subDays(29);

        $mes = $this->total($inicioMes, $hoy);
        $mesAnterior = $this->total($inicioMesAnterior, $inicioMesAnterior->copy()->endOfMonth());
        $hoyTot = $this->total($hoy, $hoy);

        return [
            'hoy'            => $hoyTot,
            'mes'            => $mes,
            'variacion'      => $mesAnterior['monto'] > 0 ? round(($mes['monto'] - $mesAnterior['monto']) / $mesAnterior['monto'] * 100, 1) : null,
            'ticket'         => $mes['num'] ? round($mes['monto'] / $mes['num'], 2) : 0,
            'pendientesWeb'  => PedidoWeb::where('estado_pedido', 'Pendiente')->selectRaw('COUNT(*) n, COALESCE(SUM(total),0) monto')->first(),
            'serie'          => $this->serie($hace30, $hoy),
            'topProductos'   => $this->topProductos($hace30, $hoy),
            'topClientes'    => $this->topClientes($inicioMes, $hoy),
            'ultimas'        => $this->ultimas(),
            'stockBajo'      => ProductoTienda::where('estado', 'activo')->where('stock_disponible', '<=', 5)->orderBy('stock_disponible')->get(),
            'porVencer'      => app(StockLotes::class)->porVencer(),
        ];
    }

    /** @return array{monto: float, num: int, planta: float, web: float} */
    private function total(Carbon $desde, Carbon $hasta): array
    {
        $planta = Venta::vigentes()->whereDate('fecha_venta', '>=', $desde)->whereDate('fecha_venta', '<=', $hasta)
            ->selectRaw('COUNT(*) n, COALESCE(SUM(total),0) monto')->first();
        $web = PedidoWeb::where('estado_pedido', '!=', 'Cancelado')
            ->whereBetween('created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->selectRaw('COUNT(*) n, COALESCE(SUM(total),0) monto')->first();

        return [
            'monto'  => round((float) $planta->monto + (float) $web->monto, 2),
            'num'    => (int) $planta->n + (int) $web->n,
            'planta' => round((float) $planta->monto, 2),
            'web'    => round((float) $web->monto, 2),
        ];
    }

    /** Ventas por día (planta y web) para el gráfico. */
    private function serie(Carbon $desde, Carbon $hasta): array
    {
        $planta = Venta::vigentes()->whereDate('fecha_venta', '>=', $desde)->whereDate('fecha_venta', '<=', $hasta)
            ->get(['fecha_venta', 'total'])->groupBy(fn ($v) => $v->fecha_venta->toDateString())->map->sum('total');
        $web = PedidoWeb::where('estado_pedido', '!=', 'Cancelado')
            ->whereBetween('created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->get(['created_at', 'total'])->groupBy(fn ($p) => $p->created_at->toDateString())->map->sum('total');

        $serie = ['etiquetas' => [], 'planta' => [], 'web' => []];
        for ($d = $desde->copy(); $d->lte($hasta); $d->addDay()) {
            $k = $d->toDateString();
            $serie['etiquetas'][] = $d->format('d/m');
            $serie['planta'][] = round((float) ($planta[$k] ?? 0), 2);
            $serie['web'][] = round((float) ($web[$k] ?? 0), 2);
        }
        return $serie;
    }

    /** Productos más vendidos (unidades) sumando planta y tienda web. */
    private function topProductos(Carbon $desde, Carbon $hasta): Collection
    {
        $planta = DB::table('venta_detalles as d')->join('ventas as v', 'v.id', '=', 'd.venta_id')
            ->whereNull('v.anulada_at')->whereNotNull('d.producto_tienda_id')
            ->whereDate('v.fecha_venta', '>=', $desde)->whereDate('v.fecha_venta', '<=', $hasta)
            ->groupBy('d.producto_tienda_id')
            ->selectRaw('d.producto_tienda_id id, SUM(d.cantidad) unidades, SUM(d.subtotal) monto')->get();
        $web = DB::table('pedido_detalles as d')->join('pedidos_web as p', 'p.id', '=', 'd.pedido_web_id')
            ->where('p.estado_pedido', '!=', 'Cancelado')
            ->whereBetween('p.created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->groupBy('d.producto_tienda_id')
            ->selectRaw('d.producto_tienda_id id, SUM(d.cantidad) unidades, SUM(d.subtotal) monto')->get();

        $suma = $planta->concat($web)->groupBy('id')->map(fn ($g, $id) => [
            'id' => (int) $id, 'unidades' => (float) $g->sum('unidades'), 'monto' => (float) $g->sum('monto'),
        ]);
        $productos = ProductoTienda::whereIn('id', $suma->keys())->get()->keyBy('id');

        return $suma->filter(fn ($f) => $productos->has($f['id']))
            ->map(fn ($f) => $f + ['producto' => $productos[$f['id']]])
            ->sortByDesc('unidades')->values()->take(10);
    }

    /** Clientes con más compras en el mes (planta + web). */
    private function topClientes(Carbon $desde, Carbon $hasta): Collection
    {
        $planta = Venta::vigentes()->whereDate('fecha_venta', '>=', $desde)->whereDate('fecha_venta', '<=', $hasta)
            ->groupBy('cliente')->selectRaw('cliente nombre, SUM(total) monto, COUNT(*) n')->get();
        $web = PedidoWeb::where('estado_pedido', '!=', 'Cancelado')
            ->whereBetween('created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->groupBy('cliente_nombre')->selectRaw('cliente_nombre nombre, SUM(total) monto, COUNT(*) n')->get();

        return $planta->concat($web)
            ->groupBy(fn ($c) => mb_strtolower(trim($c->nombre)))
            ->map(fn ($g) => ['nombre' => $g->first()->nombre, 'monto' => (float) $g->sum('monto'), 'n' => (int) $g->sum('n')])
            ->sortByDesc('monto')->values()->take(5);
    }

    /** Últimos movimientos de venta de ambos canales. */
    private function ultimas(): Collection
    {
        $planta = Venta::vigentes()->latest('created_at')->take(6)->get()->map(fn (Venta $v) => [
            'canal' => 'planta', 'cliente' => $v->cliente, 'monto' => (float) $v->total,
            'cuando' => $v->created_at, 'detalle' => $v->numero_comprobante . ' · ' . $v->destino,
        ]);
        $web = PedidoWeb::where('estado_pedido', '!=', 'Cancelado')->latest()->take(6)->get()->map(fn (PedidoWeb $p) => [
            'canal' => 'web', 'cliente' => $p->cliente_nombre, 'monto' => (float) $p->total,
            'cuando' => $p->created_at, 'detalle' => $p->codigo_pedido . ' · ' . $p->metodo_pago . ' · ' . $p->estado_pedido,
        ]);

        return $planta->concat($web)->sortByDesc('cuando')->values()->take(6);
    }
}
