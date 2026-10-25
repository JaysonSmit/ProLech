<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vincula cada ganadero a una zona específica.
 * Mantiene ruta_asignada como texto para compatibilidad con el código existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ganaderos', function (Blueprint $table) {
            $table->foreignId('zona_id')
                  ->nullable()
                  ->after('ruta_asignada')
                  ->constrained('zonas')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ganaderos', function (Blueprint $table) {
            $table->dropForeign(['zona_id']);
            $table->dropColumn('zona_id');
        });
    }
};
