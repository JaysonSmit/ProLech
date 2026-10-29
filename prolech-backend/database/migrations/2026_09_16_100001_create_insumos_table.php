<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('unidad_medida', ['L', 'kg', 'g', 'mL', 'unidad'])
                  ->default('L')
                  ->comment('Unidad de medida del insumo');
            $table->decimal('costo_unitario', 10, 4)
                  ->default(0)
                  ->comment('Costo por unidad (S/)');
            $table->decimal('stock_actual', 12, 3)
                  ->default(0)
                  ->comment('Stock disponible en almacén');
            $table->decimal('stock_minimo', 12, 3)
                  ->default(0)
                  ->comment('Umbral mínimo para alerta de reabastecimiento');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
