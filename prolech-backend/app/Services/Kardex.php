<?php

namespace App\Services;

use App\Models\Acopio;
use App\Models\Insumo;
use App\Models\MovimientoInventario;
use App\Models\PedidoDetalle;
use App\Models\ProduccionLote;
use App\Models\ProductoTienda;
use App\Models\VentaDetalle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Kárdex valorizado por el método del costo promedio ponderado.
 *
 *   Entrada: saldo_cant += cant ; saldo_valor += cant × costo_unit ; costo_prom = saldo_valor ÷ saldo_cant
 *   Salida:  se valoriza al costo promedio vigente ; saldo_cant −= cant ; saldo_valor −= cant × costo_prom
 *
 * Insumos: «Leche Cruda» entra por los acopios aprobados/observados del día (al costo real por litro)
 * y sale a los lotes de producción; los demás insumos usan los movimientos de inventario.
 * Productos terminados: entran por los lotes producidos (costo del lote ÷ unidades) y salen por las
 * ventas en planta y los pedidos de la tienda web no cancelados.
 */
class Kardex
{
    /** @return Collection<int, array> filas ordenadas por fecha con saldos */
    public function insumo(Insumo $insumo, ?Carbon $desde = null, ?Carbon $hasta = null): Collection
    {
        $costo = $insumo->costoVigente();
        $movs = collect();

        if (CostoLecheService::esLecheCruda($insumo)) {
            Acopio::where('estado', '!=', 'rechazado')
                ->selectRaw('DATE(fecha) dia, SUM(litros) litros, COUNT(*) n')
                ->groupByRaw('DATE(fecha)')->get()
                ->each(fn ($d) => $movs->push([
                    'fecha' => Carbon::parse($d->dia)->setTime(23, 0), 'tipo' => 'entrada',
                    'concepto' => "Acopio del día ({$d->n} entregas)", 'documento' => 'ACO-' . Carbon::parse($d->dia)->format('Ymd'),
                    'cantidad' => (float) $d->litros, 'costo' => $costo,
                ]));
        }

        MovimientoInventario::where('insumo_id', $insumo->id)->orderBy('created_at')->orderBy('id')->get()
            ->each(function (MovimientoInventario $m) use ($movs, $costo, $insumo) {
                // La leche ya entró por los acopios: sus «ingresos» manuales son ajustes de stock inicial
                $esEntrada = in_array($m->tipo, ['ingreso', 'entrada', 'ajuste_positivo'], true);
                $movs->push([
                    'fecha' => $m->created_at, 'tipo' => $esEntrada ? 'entrada' : 'salida',
                    'concepto' => $m->motivo ?: ucfirst($m->tipo),
                    'documento' => $m->referencia_tipo === ProduccionLote::class
                        ? (ProduccionLote::find($m->referencia_id)?->codigo_lote ?? 'LOTE-' . $m->referencia_id)
                        : 'MOV-' . $m->id,
                    'cantidad' => (float) $m->cantidad, 'costo' => $costo,
                ]);
            });

        return $this->valorizar($movs, $desde, $hasta, (float) $insumo->stock_actual);
    }

    public function producto(ProductoTienda $producto, ?Carbon $desde = null, ?Carbon $hasta = null): Collection
    {
        $movs = collect();
        if ($producto->receta_id) {
            ProduccionLote::where('receta_id', $producto->receta_id)->orderBy('fecha_produccion')->get()
                ->each(fn (ProduccionLote $l) => $movs->push([
                    'fecha' => $l->fecha_produccion->copy()->setTime(12, 0), 'tipo' => 'entrada',
                    'concepto' => 'Lote producido' . ($l->fecha_vencimiento ? ' · vence ' . $l->fecha_vencimiento->format('d/m/Y') : ''),
                    'documento' => $l->codigo_lote,
                    'cantidad' => (float) max(0, $l->cantidad_producida - (int) $l->merma),
                    'costo' => $l->cantidad_producida > 0 ? (float) $l->costo_total / $l->cantidad_producida : 0.0,
                ]));
        }
        VentaDetalle::with('venta')->where('producto_tienda_id', $producto->id)->get()
            ->reject(fn ($d) => $d->venta?->anulada_at)
            ->each(fn ($d) => $movs->push([
                'fecha' => $d->venta?->fecha_venta ?? $d->created_at, 'tipo' => 'salida',
                'concepto' => 'Venta en planta · ' . ($d->venta?->cliente ?? ''), 'documento' => 'V-' . str_pad((string) $d->venta_id, 5, '0', STR_PAD_LEFT),
                'cantidad' => (float) $d->cantidad, 'costo' => null,
            ]));
        PedidoDetalle::with('pedido')->where('producto_tienda_id', $producto->id)->get()
            ->reject(fn ($d) => $d->pedido?->estado_pedido === 'Cancelado')
            ->each(fn ($d) => $movs->push([
                'fecha' => $d->pedido?->created_at ?? $d->created_at, 'tipo' => 'salida',
                'concepto' => 'Tienda web · ' . ($d->pedido?->cliente_nombre ?? ''), 'documento' => $d->pedido?->codigo_pedido ?? 'WEB',
                'cantidad' => (float) $d->cantidad, 'costo' => null,
            ]));

        return $this->valorizar($movs, $desde, $hasta, (float) $producto->stock_disponible);
    }

    /** Aplica el promedio ponderado; filas fuera del rango solo alimentan el saldo inicial. */
    private function valorizar(Collection $movs, ?Carbon $desde, ?Carbon $hasta, float $stockSistema): Collection
    {
        $cant = 0.0; $valor = 0.0; $filas = collect();
        // Por fecha y, a igual fecha, en el orden en que se registraron
        $ordenados = $movs->values()->map(fn ($m, $i) => $m + ['_i' => $i])
            ->sortBy([fn ($a, $b) => Carbon::parse($a['fecha'])->timestamp <=> Carbon::parse($b['fecha'])->timestamp, fn ($a, $b) => $a['_i'] <=> $b['_i']]);
        foreach ($ordenados->values() as $m) {
            unset($m['_i']);
            $prom = $cant > 0 ? $valor / $cant : (float) ($m['costo'] ?? 0);
            if ($m['tipo'] === 'entrada') {
                $unit = (float) $m['costo'];
                $total = $m['cantidad'] * $unit;
                $cant += $m['cantidad']; $valor += $total;
            } else {
                $unit = $prom;
                $total = $m['cantidad'] * $unit;
                $cant -= $m['cantidad']; $valor = $cant > 0 ? $valor - $total : 0.0;
            }
            $fecha = Carbon::parse($m['fecha']);
            if (($desde && $fecha->lt($desde->copy()->startOfDay())) || ($hasta && $fecha->gt($hasta->copy()->endOfDay()))) continue;
            $filas->push($m + [
                'costo_unit' => round($unit, 4), 'total' => round($total, 2),
                'saldo_cant' => round($cant, 3), 'saldo_valor' => round(max(0, $valor), 2),
                'costo_prom' => $cant > 0 ? round($valor / $cant, 4) : 0.0,
            ]);
        }
        return $filas;
    }
}
