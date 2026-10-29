<?php

namespace App\Console\Commands;

use App\Services\ControlCalidadService;
use Illuminate\Console\Command;

/**
 * prolech:calidad sortear  → sorteo de controles sorpresa del día (madrugada)
 * prolech:calidad bloqueos → descuenta una entrega a los bloqueados cuyo camión pasó hoy (noche)
 */
class ControlCalidadDiario extends Command
{
    protected $signature = 'prolech:calidad {accion : sortear|bloqueos}';

    protected $description = 'Control de calidad sorpresa: sorteo diario y bloqueos por entregas';

    public function handle(ControlCalidadService $calidad): int
    {
        return match ($this->argument('accion')) {
            'sortear' => tap(self::SUCCESS, function () use ($calidad) {
                $controles = $calidad->sortear();
                $this->info($controles->isEmpty() ? 'Ya había sorteo hoy (o no hay rutas con chofer).' : "Programados {$controles->count()} controles.");
            }),
            'bloqueos' => tap(self::SUCCESS, fn () => $this->info('Entregas descontadas: ' . $calidad->consumirBloqueos())),
            default => tap(self::INVALID, fn () => $this->error('Usa: sortear o bloqueos')),
        };
    }
}
