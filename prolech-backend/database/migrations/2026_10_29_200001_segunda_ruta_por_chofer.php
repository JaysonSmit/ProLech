<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Segunda ruta del chofer en el mes: cuando hay más rutas que choferes, un chofer (primero
 * el volante) cubre también una ruta libre y en la app ve las paradas de las dos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignaciones_zona', function (Blueprint $table) {
            $table->foreignId('zona_extra_id')->nullable()->after('zona_id')->constrained('zonas')->nullOnDelete();
            $table->unique(['zona_extra_id', 'mes', 'anio'], 'uniq_zona_extra_mes');
        });
    }

    public function down(): void
    {
        Schema::table('asignaciones_zona', function (Blueprint $table) {
            $table->dropUnique('uniq_zona_extra_mes');
            $table->dropConstrainedForeignId('zona_extra_id');
        });
    }
};
