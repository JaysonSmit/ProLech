<?php

namespace Database\Seeders;

use App\Models\Zona;
use Illuminate\Database\Seeder;

class ZonasSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Zona::zonasDefault() as $datos) {
            Zona::firstOrCreate(['codigo' => $datos['codigo']], array_merge($datos, ['activa' => true]));
        }

        $this->command->info('✅ Zonas creadas: Collana 1, 2, 3 y 4.');
    }
}
