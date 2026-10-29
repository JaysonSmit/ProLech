<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Camino que el administrador traza en el mapa para cada zona; ordena las paradas del chofer. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->json('trazado')->nullable()->after('poligono');   // [[lat, lon], ...] desde la planta
            $table->decimal('trazado_km', 7, 2)->nullable()->after('trazado');
        });
    }

    public function down(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->dropColumn(['trazado', 'trazado_km']);
        });
    }
};
