<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Centro y polígono de cada zona: se dibujan en los mapas y sirven para asignar ganaderos por ubicación. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->decimal('centro_lat', 10, 7)->nullable()->after('orden');
            $table->decimal('centro_lon', 10, 7)->nullable()->after('centro_lat');
            $table->json('poligono')->nullable()->after('centro_lon');   // [[lat, lon], ...]
            $table->string('color', 9)->nullable()->after('poligono');
        });
    }

    public function down(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->dropColumn(['centro_lat', 'centro_lon', 'poligono', 'color']);
        });
    }
};
