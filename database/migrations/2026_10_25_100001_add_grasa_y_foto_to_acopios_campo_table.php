<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acopios_campo', function (Blueprint $table) {
            // % de grasa leído del Lactoscan (por OCR o a mano)
            $table->decimal('grasa', 5, 2)->nullable()->after('densidad');
            // Foto de evidencia (balde, pantalla del Lactoscan, leche rechazada)
            $table->string('foto_path', 500)->nullable()->after('estado_calidad');
        });
    }

    public function down(): void
    {
        Schema::table('acopios_campo', function (Blueprint $table) {
            $table->dropColumn(['grasa', 'foto_path']);
        });
    }
};
