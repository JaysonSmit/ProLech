<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_web', function (Blueprint $table) {
            // DNI (8) o CE (hasta 12) del cliente: obligatorio en boletas desde S/ 700
            $table->string('cliente_documento', 12)->nullable()->after('cliente_email');

            // Serie y correlativo de la boleta: B001-00000001
            $table->string('numero_comprobante', 20)->nullable()->unique()->after('codigo_pedido');

            // Referencia del pago: nº de operación Yape o "VISA •••• 4242" (nunca el PAN completo)
            $table->string('pago_referencia', 60)->nullable()->after('metodo_pago');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_web', function (Blueprint $table) {
            $table->dropUnique(['numero_comprobante']);
            $table->dropColumn(['cliente_documento', 'numero_comprobante', 'pago_referencia']);
        });
    }
};
