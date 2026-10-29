<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\ProduccionLote;
use App\Models\ProductoTienda;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Stock de la tienda explicado por lotes de producción.
 *
 * El número de unidades sigue viviendo en productos_tienda.stock_disponible (lo mueven
 * ventas, pedidos y producción). Aquí se reparte ese número entre los lotes de la receta
 * suponiendo que se vende primero lo que vence antes: las unidades en stock son las de
 * los lotes más nuevos. Así se sabe qué hay por vencer y qué ya venció.
 */
class StockLotes
{
    /** Días antes del vencimiento en que un lote pasa a «por vencer». */
    public const DIAS_AVISO = 3;

    /**
     * @return Collection<int, array{lote: ProduccionLote, en_stock: int, dias: int, estado: string}>
     *         lotes con unidades en stock, del que vence primero al último
     */
    public function desglose(int $recetaId, int $stock): Collection
    {
        $hoy = now()->startOfDay();
        $filas = collect();
        $restante = max(0, $stock);

        // Del más nuevo al más antiguo: el stock que queda es el de lo último producido
        $lotes = ProduccionLote::where('receta_id', $recetaId)
            ->orderByDesc('fecha_vencimiento')->orderByDesc('id')->get();

        foreach ($lotes as $lote) {
            if ($restante <= 0) break;
            $capacidad = max(0, $lote->cantidad_producida - $lote->merma);
            $enStock = min($capacidad, $restante);
            if ($enStock <= 0) continue;
            $restante -= $enStock;
            $dias = $lote->fecha_vencimiento ? (int) $hoy->diffInDays($lote->fecha_vencimiento, false) : 999;
            $filas->push([
                'lote'     => $lote,
                'en_stock' => $enStock,
                'dias'     => $dias,
                'estado'   => $dias < 0 ? 'vencido' : ($dias <= self::DIAS_AVISO ? 'por_vencer' : 'vigente'),
            ]);
        }

        return $filas->reverse()->values();
    }

    /**
     * Unidades de la receta que siguen vigentes en sus lotes y que no están en ninguna
     * tienda: es el stock inicial de un producto nuevo creado o publicado desde la receta.
     */
    public function disponibleProducido(int $recetaId): int
    {
        return (int) ProduccionLote::where('receta_id', $recetaId)
            ->where(fn ($q) => $q->whereNull('fecha_vencimiento')->orWhereDate('fecha_vencimiento', '>=', today()))
            ->sum(DB::raw('cantidad_producida - merma'));
    }

    /** Lotes con unidades en stock que vencen en los próximos días (o ya vencieron). */
    public function porVencer(): Collection
    {
        return ProductoTienda::whereNotNull('receta_id')->where('stock_disponible', '>', 0)->get()
            ->flatMap(fn (ProductoTienda $p) => $this->desglose($p->receta_id, $p->stock_disponible)
                ->where('estado', '!=', 'vigente')
                ->map(fn ($f) => $f + ['producto' => $p]))
            ->sortBy('dias')->values();
    }

    /**
     * Retira de la tienda las unidades de lotes vencidos (merma) y avisa al administrador
     * y al jefe de producción. Lo corre el scheduler cada mañana.
     *
     * @return array<int, string> resumen por producto
     */
    public function retirarVencidos(): array
    {
        $resumen = [];
        foreach (ProductoTienda::whereNotNull('receta_id')->where('stock_disponible', '>', 0)->get() as $producto) {
            $vencidos = $this->desglose($producto->receta_id, $producto->stock_disponible)->where('estado', 'vencido');
            $unidades = $vencidos->sum('en_stock');
            if ($unidades <= 0) continue;

            DB::transaction(function () use ($producto, $vencidos, $unidades) {
                foreach ($vencidos as $f) {
                    $f['lote']->increment('merma', $f['en_stock']);
                }
                ProductoTienda::whereKey($producto->id)->decrement('stock_disponible', $unidades);
            });

            $lotes = $vencidos->map(fn ($f) => $f['lote']->codigo_lote)->implode(', ');
            $resumen[] = "{$producto->nombre}: {$unidades} und. ({$lotes})";
        }

        if ($resumen) {
            foreach (User::conRol('admin', 'jefe_produccion')->where('activo', true)->pluck('id') as $id) {
                Notificacion::enviar(
                    userId: $id,
                    titulo: '🗑️ Productos vencidos retirados de la tienda',
                    mensaje: 'Se retiró como merma: ' . implode('; ', $resumen) . '.',
                    tipo: 'merma_vencidos',
                );
            }
        }

        return $resumen;
    }

    public static function fechaVencimiento(Carbon|string $produccion, int $diasVidaUtil): string
    {
        return Carbon::parse($produccion)->addDays($diasVidaUtil)->toDateString();
    }
}
