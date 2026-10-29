<?php

namespace App\Console\Commands;

use App\Services\StockLotes;
use Illuminate\Console\Command;

/** Retira de la tienda las unidades de lotes vencidos (merma) y avisa al admin y al jefe de producción. */
class RetirarVencidos extends Command
{
    protected $signature = 'prolech:retirar-vencidos';

    protected $description = 'Quita del stock de la tienda los productos de lotes vencidos';

    public function handle(StockLotes $stock): int
    {
        $resumen = $stock->retirarVencidos();
        $this->info($resumen ? 'Retirado: ' . implode('; ', $resumen) : 'No hay productos vencidos en la tienda.');

        return self::SUCCESS;
    }
}
