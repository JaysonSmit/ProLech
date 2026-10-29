<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Despacho de los pedidos de la tienda web: nuevo estado «Entregado» (el seguimiento del
 * cliente se quedaba en «En camino») y datos de la salida (fecha, repartidor/notas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_web', function (Blueprint $table) {
            // enum → texto para poder agregar «Entregado» (también en SQLite)
            $table->string('estado_pedido', 20)->default('Pendiente')->change();
            $table->timestamp('despachado_at')->nullable()->after('estado_pedido');
            $table->timestamp('entregado_at')->nullable()->after('despachado_at');
            $table->string('notas_despacho', 250)->nullable()->after('entregado_at');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_web', function (Blueprint $table) {
            $table->dropColumn(['despachado_at', 'entregado_at', 'notas_despacho']);
        });
    }
};
