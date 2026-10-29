<?php

namespace Database\Seeders;

use App\Models\Ganadero;
use Illuminate\Database\Seeder;

/**
 * Asigna coordenadas GPS reales a los ganaderos existentes.
 * Corre esto si ya tienes datos en BD y no quieres hacer fresh migrate:
 *   php artisan db:seed --class=GanaderoCoordsSeeder
 *
 * Coordenadas del distrito de Huata, Puno (orillas del Titicaca).
 */
class GanaderoCoordsSeeder extends Seeder
{
    /** [dni => [geo_lat, geo_lon]] */
    private array $coordenadas = [
        '12345678' => [-15.8180, -70.0210],   // Juan Pérez    — Huata Centro
        '23456789' => [-15.8050, -70.0180],   // María García  — Ccapia
        '34567890' => [-15.7920, -70.0090],   // Pedro López   — Chijichaya
        '45678901' => [-15.8350, -70.0320],   // Rosa Quispe   — Ccopamaya
        '56789012' => [-15.8480, -70.0410],   // Luis Mamani   — Huataquita
        '67890123' => [-15.8600, -70.0500],   // Elena Condori — Kellhuani
    ];

    public function run(): void
    {
        $actualizados = 0;

        foreach ($this->coordenadas as $dni => [$lat, $lon]) {
            $filas = Ganadero::where('dni', $dni)
                ->update(['geo_lat' => $lat, 'geo_lon' => $lon]);
            $actualizados += $filas;
        }

        $this->command->info("✅ GanaderoCoordsSeeder: $actualizados ganaderos actualizados con coordenadas GPS.");
    }
}
