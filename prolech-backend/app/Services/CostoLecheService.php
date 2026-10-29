<?php

namespace App\Services;

use App\Models\Acopio;
use App\Models\Configuracion;
use App\Models\Insumo;
use App\Models\Liquidacion;
use App\Models\PlanillaChofer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Costo real por litro de leche puesto en planta (ventana móvil de 30 días):
 *
 *   base       = Σ pagado a ganaderos ÷ Σ litros liquidados          (precio promedio pagado)
 *   logística  = (combustible de rutas + pago a choferes) ÷ litros acopiados
 *   gastos     = (base + logística) × gastos_generales_pct %
 *   costo real = base + logística + gastos
 *
 * El insumo «Leche Cruda» de las recetas y de los lotes usa este costo: así el costo unitario
 * y el precio sugerido de quesos y yogures siguen al costo real de la leche.
 */
class CostoLecheService
{
    public const DIAS_VENTANA = 30;
    private const CACHE = 'prolech.costo_leche_real';

    public static function olvidar(): void
    {
        Cache::forget(self::CACHE);
    }

    /** Costo vigente (cacheado 1 h); null si en la ventana no hubo acopios. */
    public static function actual(): ?array
    {
        $r = Cache::remember(self::CACHE, 3600, fn () => app(self::class)->calcular() ?? false);
        return $r ?: null;
    }

    public static function esLecheCruda(Insumo $insumo): bool
    {
        return mb_strtolower(trim($insumo->nombre)) === 'leche cruda';
    }

    public function calcular(?Carbon $desde = null, ?Carbon $hasta = null): ?array
    {
        $hasta = ($hasta ?? today())->copy()->startOfDay();
        $desde = ($desde ?? $hasta->copy()->subDays(self::DIAS_VENTANA - 1))->copy()->startOfDay();

        $litros = (float) Acopio::whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)
            ->where('estado', '!=', 'rechazado')->sum('litros');
        if ($litros <= 0) return null;

        // 1. Precio promedio pagado al ganadero (liquidaciones de semanas dentro de la ventana)
        //    = Σ total pagado ÷ Σ litros recibidos (no rechazados) en esas mismas semanas
        $liq = Liquidacion::whereDate('semana_inicio', '>=', $desde->copy()->subDays(6))->whereDate('semana_inicio', '<=', $hasta)
            ->selectRaw('COALESCE(SUM(total_bruto),0) pagado, MIN(semana_inicio) ini, MAX(semana_fin) fin')->first();
        $litrosLiquidados = $liq->ini ? (float) Acopio::whereDate('fecha', '>=', $liq->ini)->whereDate('fecha', '<=', $liq->fin)
            ->where('estado', '!=', 'rechazado')->sum('litros') : 0.0;
        $base = $litrosLiquidados > 0 && (float) $liq->pagado > 0
            ? (float) $liq->pagado / $litrosLiquidados
            : Configuracion::precioLitroActual();
        $fuenteBase = $litrosLiquidados > 0 && (float) $liq->pagado > 0 ? 'liquidaciones' : 'precio configurado';

        // 2. Logística: combustible + pago a choferes del mismo periodo
        $planillas = app(PlanillaChoferService::class);
        $p = PlanillaChoferService::parametros();
        $km = 0.0;
        $pagoChoferes = 0.0;
        foreach ($planillas->choferes() as $chofer) {
            $c = $planillas->calcular($chofer, $desde, $hasta);
            $km += $c['km_recorridos'];
            $pagoChoferes += $c['total_bruto'];
        }
        $combustible = round($km * $p['combustible'], 2);
        $logistica = ($combustible + $pagoChoferes) / $litros;

        // 3. Gastos generales de planta
        $pct = (float) Configuracion::get('gastos_generales_pct', 4);
        $gastos = ($base + $logistica) * $pct / 100;

        return [
            'desde'          => $desde->toDateString(),
            'hasta'          => $hasta->toDateString(),
            'litros'         => round($litros, 2),
            'base'           => round($base, 4),
            'fuente_base'    => $fuenteBase,
            'km'             => round($km, 2),
            'combustible'    => $combustible,
            'pago_choferes'  => round($pagoChoferes, 2),
            'logistica'      => round($logistica, 4),
            'gastos_pct'     => $pct,
            'gastos'         => round($gastos, 4),
            'costo_real'     => round($base + $logistica + $gastos, 4),
            'planillas_pagadas' => PlanillaChofer::where('estado', 'pagada')->whereDate('periodo_fin', '>=', $desde)->count(),
        ];
    }

    /** Guarda el costo real en el insumo «Leche Cruda» (lo usan los lotes de producción). */
    public function aplicarAInsumo(): ?float
    {
        self::olvidar();
        $costo = self::actual();
        if (! $costo) return null;
        $leche = Insumo::all()->first(fn ($i) => self::esLecheCruda($i));
        $leche?->update(['costo_unitario' => round($costo['costo_real'], 4)]);
        return $costo['costo_real'];
    }
}
