<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('configuracion')->where('clave', 'stock_minimo_quesos')->exists()) {
            DB::table('configuracion')->insert([
                'clave'       => 'stock_minimo_quesos',
                'valor'       => '15',
                'descripcion' => 'Stock mínimo de reserva de quesos en planta para venta directa (moldes)',
                'tipo'        => 'entero',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('configuracion')->where('clave', 'stock_minimo_quesos')->delete();
    }
};
