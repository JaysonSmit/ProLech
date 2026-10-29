<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produccion', function (Blueprint $table) {
            $table->id();
            $table->decimal('litros_usados', 10, 2);
            $table->decimal('precio_litro', 6, 2)->default(0)->comment('Precio por litro al momento del lote');
            $table->decimal('costo_cuajo', 8, 2)->default(0);
            $table->decimal('costo_sal', 8, 2)->default(0);
            $table->decimal('costo_otros', 8, 2)->default(0);
            $table->decimal('kg_queso_estimado', 8, 3);
            // costo_total calculado a nivel PHP para compatibilidad con SQLite/MySQL
            $table->decimal('costo_total', 10, 2)->default(0);
            $table->date('fecha_lote');
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion');
    }
};
