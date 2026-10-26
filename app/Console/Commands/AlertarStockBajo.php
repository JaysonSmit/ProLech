<?php

namespace App\Console\Commands;

use App\Models\Insumo;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Aviso diario al administrador y al jefe de producción con los insumos activos
 * que están en su stock mínimo o por debajo. Un solo aviso por persona y día.
 */
class AlertarStockBajo extends Command
{
    protected $signature = 'prolech:alertas-stock';

    protected $description = 'Avisa en el panel qué insumos están en su stock mínimo o por debajo';

    public function handle(): int
    {
        $insumos = Insumo::activos()->conStockBajo()->orderBy('nombre')->get();

        if ($insumos->isEmpty()) {
            $this->info('Ningún insumo bajo el mínimo.');
            return self::SUCCESS;
        }

        $lista = $insumos->map(fn (Insumo $i) => sprintf(
            '%s: %s de %s %s',
            $i->nombre,
            rtrim(rtrim(number_format((float) $i->stock_actual, 3, '.', ''), '0'), '.'),
            rtrim(rtrim(number_format((float) $i->stock_minimo, 3, '.', ''), '0'), '.'),
            $i->unidad_medida,
        ))->implode('; ');

        $avisados = 0;
        User::whereIn('rol', ['admin', 'jefe_produccion'])->where('activo', true)->pluck('id')
            ->each(function (int $id) use ($insumos, $lista, &$avisados) {
                $yaAvisado = Notificacion::where('user_id', $id)->where('tipo', 'stock_bajo')
                    ->whereDate('created_at', today())->exists();
                if ($yaAvisado) {
                    return;
                }

                Notificacion::enviar(
                    userId:  $id,
                    titulo:  "📦 {$insumos->count()} insumo(s) en stock mínimo",
                    mensaje: "Reponer antes de la próxima producción. {$lista}",
                    tipo:    'stock_bajo',
                );
                $avisados++;
            });

        $this->info("Insumos bajo el mínimo: {$insumos->count()}. Usuarios avisados: {$avisados}.");

        return self::SUCCESS;
    }
}
