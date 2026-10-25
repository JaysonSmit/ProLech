<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega los campos completos del analizador LACTOMAT a la tabla acopios.
 * Todos son nullable para mantener compatibilidad con acopios ya registrados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->decimal('grasa', 5, 2)->nullable()->after('densidad')
                  ->comment('% de grasa medido por LACTOMAT');
            $table->decimal('proteina', 5, 2)->nullable()->after('grasa')
                  ->comment('% de proteína medido por LACTOMAT');
            $table->decimal('lactosa', 5, 2)->nullable()->after('proteina')
                  ->comment('% de lactosa medido por LACTOMAT');
            $table->decimal('sng', 5, 2)->nullable()->after('lactosa')
                  ->comment('Sólidos No Grasos (%) medido por LACTOMAT');
            $table->decimal('sales', 5, 2)->nullable()->after('sng')
                  ->comment('% de sales medido por LACTOMAT');
            $table->decimal('total_solidos', 5, 2)->nullable()->after('sales')
                  ->comment('Total de sólidos (%) medido por LACTOMAT');
            $table->decimal('punto_congelacion', 6, 3)->nullable()->after('total_solidos')
                  ->comment('Punto de congelación en °C medido por LACTOMAT (normal: -0.520 a -0.530)');
        });
    }

    public function down(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->dropColumn([
                'grasa', 'proteina', 'lactosa', 'sng',
                'sales', 'total_solidos', 'punto_congelacion',
            ]);
        });
    }
};
