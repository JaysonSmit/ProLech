<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produccion_lotes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_lote')->unique()
                  ->comment('Auto-generado: PRD-YYYYMMDD-NNN');
            $table->foreignId('receta_id')
                  ->constrained('recetas')
                  ->restrictOnDelete();
            $table->date('fecha_produccion');
            $table->integer('cantidad_producida')
                  ->comment('Unidades/moldes producidos en este lote');
            $table->decimal('costo_total', 12, 2)
                  ->default(0)
                  ->comment('Suma de (cantidad_insumo * costo_unitario) escalada');
            $table->foreignId('responsable_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion_lotes');
    }
};
