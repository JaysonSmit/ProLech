<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vida útil de cada receta y vencimiento de cada lote. El stock de la tienda se explica
 * por lotes (sale primero lo que vence antes) y lo vencido se retira como merma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recetas', function (Blueprint $table) {
            $table->unsignedSmallInteger('dias_vida_util')->default(15)->after('rendimiento_esperado_unidades');
        });
        Schema::table('produccion_lotes', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->after('fecha_produccion');
            $table->unsignedInteger('merma')->default(0)->after('cantidad_producida');   // unidades retiradas por vencidas
        });

        // Vida útil inicial según el tipo de producto (se puede cambiar en cada receta)
        foreach (DB::table('recetas')->get(['id', 'nombre']) as $r) {
            $n = mb_strtolower($r->nombre);
            $dias = match (true) {
                str_contains($n, 'fresco')      => 7,
                str_contains($n, 'yogur')       => 21,
                str_contains($n, 'paria')       => 30,
                str_contains($n, 'mantequilla') => 45,
                str_contains($n, 'andino')      => 60,
                str_contains($n, 'manjar')      => 90,
                default                         => 15,
            };
            DB::table('recetas')->where('id', $r->id)->update(['dias_vida_util' => $dias]);
        }

        foreach (DB::table('produccion_lotes')->get(['id', 'receta_id', 'fecha_produccion']) as $l) {
            $dias = (int) DB::table('recetas')->where('id', $l->receta_id)->value('dias_vida_util');
            DB::table('produccion_lotes')->where('id', $l->id)->update([
                'fecha_vencimiento' => \Illuminate\Support\Carbon::parse($l->fecha_produccion)->addDays($dias)->toDateString(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('produccion_lotes', function (Blueprint $table) {
            $table->dropColumn(['fecha_vencimiento', 'merma']);
        });
        Schema::table('recetas', function (Blueprint $table) {
            $table->dropColumn('dias_vida_util');
        });
    }
};
