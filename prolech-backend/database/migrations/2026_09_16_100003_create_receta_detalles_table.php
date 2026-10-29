<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receta_id')
                  ->constrained('recetas')
                  ->cascadeOnDelete();
            $table->foreignId('insumo_id')
                  ->constrained('insumos')
                  ->restrictOnDelete();
            $table->decimal('cantidad_requerida', 12, 4)
                  ->comment('Cantidad del insumo para producir rendimiento_esperado_unidades de la receta base');
            $table->timestamps();

            // Un insumo no puede aparecer dos veces en la misma receta
            $table->unique(['receta_id', 'insumo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receta_detalles');
    }
};
