<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - Órdenes de producción: varios lotes (uno por producto) registrados juntos comparten un código ORD-.
 * - Ventas con carrito: cada venta puede llevar varios productos (detalle).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produccion_lotes', function (Blueprint $table) {
            $table->string('orden_codigo', 30)->nullable()->after('codigo_lote')->index();
        });

        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            // null = queso de planta vendido por kg/molde (el stock que ya usaba el módulo de ventas)
            $table->foreignId('producto_tienda_id')->nullable()->constrained('productos_tienda')->nullOnDelete();
            $table->string('descripcion', 150);
            $table->string('unidad', 20)->default('und');
            $table->decimal('cantidad', 10, 3);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
        Schema::table('produccion_lotes', function (Blueprint $table) {
            $table->dropIndex(['orden_codigo']);
            $table->dropColumn('orden_codigo');
        });
    }
};
