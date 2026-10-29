<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega coordenadas GPS a la tabla ganaderos.
     * Nullable para no romper registros existentes sin coordenadas.
     */
    public function up(): void
    {
        Schema::table('ganaderos', function (Blueprint $table) {
            $table->decimal('geo_lat', 10, 7)->nullable()->after('direccion')
                  ->comment('Latitud GPS del predio del ganadero');
            $table->decimal('geo_lon', 10, 7)->nullable()->after('geo_lat')
                  ->comment('Longitud GPS del predio del ganadero');
        });
    }

    public function down(): void
    {
        Schema::table('ganaderos', function (Blueprint $table) {
            $table->dropColumn(['geo_lat', 'geo_lon']);
        });
    }
};
