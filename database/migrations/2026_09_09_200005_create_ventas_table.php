<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('cliente', 150);
            $table->enum('tipo_cliente', ['mayorista', 'publico'])->default('publico');
            $table->decimal('cantidad_kg', 8, 3);
            $table->decimal('precio_unitario', 6, 2)->comment('S/18 mayorista | S/21 público');
            // total calculado a nivel PHP para compatibilidad universal
            $table->decimal('total', 10, 2)->default(0);
            $table->date('fecha_venta');
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('fecha_venta');
            $table->index('tipo_cliente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
