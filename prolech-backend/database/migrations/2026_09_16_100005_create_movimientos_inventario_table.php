<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')
                  ->constrained('insumos')
                  ->restrictOnDelete();
            $table->enum('tipo', ['ingreso', 'egreso'])
                  ->comment('ingreso = entrada al almacén, egreso = consumo en producción');
            $table->decimal('cantidad', 12, 4);
            $table->string('motivo')->nullable()
                  ->comment('Ej: Compra proveedor, Consumo lote PRD-20260916-001');
            // Polimórfico simple: referencia al ID del lote o documento de compra
            $table->unsignedBigInteger('referencia_id')->nullable()
                  ->comment('ID del produccion_lote u otro documento origen');
            $table->string('referencia_tipo')->nullable()
                  ->comment('Clase del modelo origen, ej: App\Models\ProduccionLote');
            $table->foreignId('registrado_por')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            $table->index(['insumo_id', 'tipo']);
            $table->index(['referencia_tipo', 'referencia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
