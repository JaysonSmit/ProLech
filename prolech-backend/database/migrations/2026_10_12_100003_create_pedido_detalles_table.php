<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_web_id')
                  ->constrained('pedidos_web')
                  ->cascadeOnDelete();

            $table->foreignId('producto_tienda_id')
                  ->constrained('productos_tienda')
                  ->restrictOnDelete();

            $table->unsignedInteger('cantidad');

            // Precio capturado al momento del pedido (inmutable ante cambios futuros)
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();

            $table->index('pedido_web_id');
            $table->index('producto_tienda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_detalles');
    }
};
