<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_web', function (Blueprint $table) {
            $table->id();

            // Código legible para el cliente (WEB-1001, WEB-1002…)
            $table->string('codigo_pedido', 20)->unique()
                  ->comment('Formato WEB-NNNN, auto-generado');

            // Datos del cliente (no requiere cuenta)
            $table->string('cliente_nombre', 150);
            $table->string('cliente_telefono', 20);
            $table->string('cliente_direccion', 300);
            $table->string('cliente_email', 150)->nullable();

            $table->decimal('total', 12, 2);

            $table->enum('metodo_pago', ['Yape', 'Efectivo', 'Tarjeta'])
                  ->default('Efectivo');

            $table->enum('estado_pedido', ['Pendiente', 'Pagado', 'Enviado', 'Cancelado'])
                  ->default('Pendiente');

            // Comprobante de pago Yape (path en disco "public")
            $table->string('comprobante_yape_url', 500)->nullable();

            // Notas del cliente
            $table->text('notas')->nullable();

            $table->timestamps();

            $table->index('estado_pedido');
            $table->index('metodo_pago');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_web');
    }
};
