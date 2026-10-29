<?php

namespace App\Console\Commands;

use App\Services\CostoLecheService;
use Illuminate\Console\Command;

/** Recalcula el costo real por litro (últimos 30 días) y lo aplica al insumo «Leche Cruda». */
class ActualizarCostoLeche extends Command
{
    protected $signature = 'prolech:costo-leche';

    protected $description = 'Actualiza el costo real por litro de leche puesto en planta';

    public function handle(CostoLecheService $costos): int
    {
        $costo = $costos->aplicarAInsumo();
        $this->info($costo ? 'Costo real por litro: S/ ' . number_format($costo, 4) : 'Sin acopios en los últimos 30 días.');
        return self::SUCCESS;
    }
}
