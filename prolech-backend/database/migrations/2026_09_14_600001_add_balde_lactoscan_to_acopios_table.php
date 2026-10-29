<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->boolean('medicion_balde_confirmada')->default(false)->after('litros')
                ->comment('RF-ACO-07: conductor confirmó medición con balde');
            $table->unsignedTinyInteger('equipo_lactoscan')->nullable()->after('medicion_balde_confirmada')
                ->comment('RF-CAL-01: equipo LactoScan 1, 2 o 3');
            $table->decimal('capacidad_tacho_litros', 8, 2)->nullable()->after('equipo_lactoscan');
        });
    }

    public function down(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->dropColumn(['medicion_balde_confirmada', 'equipo_lactoscan', 'capacidad_tacho_litros']);
        });
    }
};
