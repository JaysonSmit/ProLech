<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade los campos de análisis Lactoscan completo:
 *   densidad      — g/mL, rango normal 1.028-1.033
 *   tiene_descuento — true cuando % agua está ~1% (penalización menor en liquidación)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->decimal('densidad', 6, 4)
                  ->nullable()
                  ->after('acidez_ph')
                  ->comment('Densidad en g/mL medida por Lactoscan (normal: 1.028-1.033)');

            $table->boolean('tiene_descuento')
                  ->default(false)
                  ->after('densidad')
                  ->comment('true cuando % agua ~1%: aplica descuento en liquidación semanal');
        });
    }

    public function down(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->dropColumn(['densidad', 'tiene_descuento']);
        });
    }
};
