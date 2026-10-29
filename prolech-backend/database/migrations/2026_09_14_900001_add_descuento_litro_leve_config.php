<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('configuracion')->where('clave', 'descuento_litro_leve')->exists()) {
            DB::table('configuracion')->insert([
                'clave'       => 'descuento_litro_leve',
                'valor'       => '0.10',
                'descripcion' => 'Descuento S/ por litro en adulteración leve (<5%) — 1ra vez toda la semana',
                'tipo'        => 'decimal',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('configuracion')->where('clave', 'descuento_litro_leve')->delete();
    }
};
