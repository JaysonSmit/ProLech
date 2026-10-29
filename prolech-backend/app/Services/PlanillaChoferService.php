<?php

namespace App\Services;

use App\Models\Acopio;
use App\Models\AsignacionZona;
use App\Models\Configuracion;
use App\Models\Ganadero;
use App\Models\Liquidacion;
use App\Models\PlanillaChofer;
use App\Models\TrackingGpsPunto;
use App\Models\User;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Planilla de choferes.
 *
 *   días trabajados = días con al menos un acopio no rechazado del chofer
 *   monto base      = días × jornal (tarifa_chofer_dia)
 *   bono            = litros recolectados × bono_chofer_litro
 *   total bruto     = base + bono
 *   descuentos      = pensión (pension_chofer_pct % del bruto) + adelantos
 *   total neto      = bruto − descuentos
 *
 * Los km de cada día salen del GPS; sin GPS, del largo de la ruta trazada; sin ruta, de km_ruta_default.
 * El combustible (km × costo_combustible_km) lo paga la planta: se informa pero no se suma al sueldo.
 */
class PlanillaChoferService
{
    /** Saltos de GPS más largos que esto son señal perdida, no recorrido. */
    private const SALTO_MAX_KM = 3.0;

    /** @return array{0: Carbon, 1: Carbon} */
    public static function periodo(string $tipo, ?Carbon $fecha = null): array
    {
        $fecha = ($fecha ?? now())->copy()->startOfDay();
        if ($tipo === 'mensual') {
            return [$fecha->copy()->startOfMonth(), $fecha->copy()->endOfMonth()->startOfDay()];
        }
        $ini = Liquidacion::inicioSemana($fecha);   // jueves → miércoles, igual que a los ganaderos
        return [$ini, $ini->copy()->addDays(6)];
    }

    public static function parametros(): array
    {
        return [
            'tarifa_dia'   => (float) Configuracion::get('tarifa_chofer_dia', 60),
            'bono_litro'   => (float) Configuracion::get('bono_chofer_litro', 0.02),
            'pension_pct'  => (float) Configuracion::get('pension_chofer_pct', 13),
            'combustible'  => (float) Configuracion::get('costo_combustible_km', 0.55),
            'km_default'   => (float) Configuracion::get('km_ruta_default', 45),
        ];
    }

    /** @return Collection<int, User> */
    public function choferes(): Collection
    {
        return User::conRol('chofer')->where('activo', true)->orderBy('name')->get(['id', 'name', 'email', 'rol', 'roles_extra']);
    }

    /** Cálculo de un chofer en el periodo (no guarda nada). */
    public function calcular(User $chofer, Carbon $desde, Carbon $hasta, float $adelantos = 0.0): array
    {
        $p = self::parametros();
        $porDia = Acopio::where('chofer_id', $chofer->id)
            ->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)
            ->where('estado', '!=', 'rechazado')
            ->get(['fecha', 'litros', 'ganadero_id'])
            ->groupBy(fn ($a) => Carbon::parse($a->fecha)->toDateString())
            ->sortKeys();

        $dias = [];
        foreach ($porDia as $fecha => $acopios) {
            [$km, $fuente] = $this->kmDelDia($chofer->id, Carbon::parse($fecha), $acopios, $p['km_default']);
            $dias[] = [
                'fecha'     => $fecha,
                'acopios'   => $acopios->count(),
                'litros'    => round((float) $acopios->sum('litros'), 2),
                'km'        => $km,
                'fuente_km' => $fuente,
            ];
        }

        $litros = round(array_sum(array_column($dias, 'litros')), 2);
        $km = round(array_sum(array_column($dias, 'km')), 2);
        $base = round(count($dias) * $p['tarifa_dia'], 2);
        $bono = round($litros * $p['bono_litro'], 2);
        $bruto = round($base + $bono, 2);
        $pension = round($bruto * $p['pension_pct'] / 100, 2);
        $descuentos = round($pension + $adelantos, 2);

        $detalle = [];
        if ($pension > 0) $detalle[] = "Pensión {$p['pension_pct']} %: S/ " . number_format($pension, 2);
        if ($adelantos > 0) $detalle[] = 'Adelantos: S/ ' . number_format($adelantos, 2);

        return [
            'chofer_id'           => $chofer->id,
            'ruta'                => AsignacionZona::with('zona:id,nombre', 'zonaExtra:id,nombre')->where('chofer_id', $chofer->id)
                                        ->where('mes', $desde->month)->where('anio', $desde->year)->first()?->nombreRutas() ?? 'Volante',
            'dias_trabajados'     => count($dias),
            'litros_recolectados' => $litros,
            'km_recorridos'       => $km,
            'tarifa_base_dia'     => $p['tarifa_dia'],
            'monto_base'          => $base,
            'bono_por_litro'      => $p['bono_litro'],
            'monto_bono'          => $bono,
            'costo_combustible'   => round($km * $p['combustible'], 2),
            'adelantos'           => round($adelantos, 2),
            'descuento_pension'   => $pension,
            'descuentos'          => $descuentos,
            'detalle_descuentos'  => $detalle ? implode(' · ', $detalle) : null,
            'total_bruto'         => $bruto,
            'total_neto'          => round(max(0, $bruto - $descuentos), 2),
            'detalle_dias'        => $dias,
        ];
    }

    /**
     * Km recorridos un día: GPS si hay suficiente recorrido; si no, el largo de la ruta trazada
     * de la zona donde acopió (ida y vuelta a la planta); si no, el valor por defecto.
     *
     * @return array{0: float, 1: string}
     */
    public function kmDelDia(int $choferId, Carbon $fecha, Collection $acopios, float $kmDefault): array
    {
        $puntos = TrackingGpsPunto::where('chofer_id', $choferId)->whereDate('registrado_en', $fecha)
            ->orderBy('registrado_en')->get(['latitud', 'longitud']);
        $km = 0.0;
        for ($i = 1; $i < $puntos->count(); $i++) {
            $tramo = RutaChoferService::distanciaKm($puntos[$i - 1]->latitud, $puntos[$i - 1]->longitud, $puntos[$i]->latitud, $puntos[$i]->longitud);
            if ($tramo <= self::SALTO_MAX_KM) $km += $tramo;
        }
        if ($km >= 1) return [round($km, 2), 'gps'];

        $zonaId = Ganadero::whereIn('id', $acopios->pluck('ganadero_id')->unique())->whereNotNull('zona_id')
            ->pluck('zona_id')->countBy()->sortDesc()->keys()->first();
        $zona = $zonaId ? Zona::find($zonaId) : null;
        if ($zona && $zona->tieneTrazado()) {
            $largo = (float) ($zona->trazado_km ?: Zona::largoKm($zona->trazado));
            if ($largo > 0) return [round($largo * 2, 2), 'ruta'];
        }
        return [$kmDefault, 'estimado'];
    }

    /**
     * Genera (o recalcula) la planilla de todos los choferes en el periodo.
     * Las ya pagadas no se tocan; los adelantos escritos a mano se conservan.
     *
     * @return Collection<int, PlanillaChofer>
     */
    public function generar(string $tipo, Carbon $desde, Carbon $hasta, ?int $usuarioId = null): Collection
    {
        $resultado = collect();
        foreach ($this->choferes() as $chofer) {
            $existente = PlanillaChofer::where('chofer_id', $chofer->id)
                ->whereDate('periodo_inicio', $desde)->whereDate('periodo_fin', $hasta)->first();
            if ($existente?->estaPagada()) {
                $resultado->push($existente);
                continue;
            }
            $datos = $this->calcular($chofer, $desde, $hasta, (float) ($existente->adelantos ?? 0));
            if ($datos['dias_trabajados'] === 0 && ! $existente) continue;

            $planilla = $existente ?? new PlanillaChofer(['chofer_id' => $chofer->id, 'estado' => 'borrador']);
            $planilla->fill($datos + [
                'tipo' => $tipo, 'periodo_inicio' => $desde->toDateString(), 'periodo_fin' => $hasta->toDateString(),
                'generada_por' => $usuarioId,
            ])->save();
            $resultado->push($planilla);
        }
        CostoLecheService::olvidar();
        return $resultado;
    }

    /** Cambia los adelantos de una planilla en borrador y recalcula descuentos y neto. */
    public function actualizarAdelantos(PlanillaChofer $planilla, float $adelantos): PlanillaChofer
    {
        $datos = $this->calcular($planilla->chofer, $planilla->periodo_inicio, $planilla->periodo_fin, $adelantos);
        $planilla->update($datos);
        return $planilla;
    }

    public function pagar(PlanillaChofer $planilla, string $metodo, ?string $referencia): PlanillaChofer
    {
        $planilla->update([
            'estado' => 'pagada', 'pagada_at' => now(), 'metodo_pago' => $metodo, 'referencia_pago' => $referencia,
        ]);
        CostoLecheService::olvidar();
        return $planilla;
    }
}
