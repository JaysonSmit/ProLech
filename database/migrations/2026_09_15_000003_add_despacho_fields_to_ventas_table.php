<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('destino', 100)->default('Planta / Local')->after('tipo_cliente');
            $table->enum('estado_despacho', ['preparacion', 'en_camino', 'entregado'])->default('preparacion')->after('total');
            $table->string('guia_remision', 60)->nullable()->after('estado_despacho');
            $table->date('fecha_despacho')->nullable()->after('guia_remision');
            $table->text('notas_despacho')->nullable()->after('fecha_despacho');

            $table->index('destino');
            $table->index('estado_despacho');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['destino']);
            $table->dropIndex(['estado_despacho']);
            $table->dropColumn(['destino', 'estado_despacho', 'guia_remision', 'fecha_despacho', 'notas_despacho']);
        });
    }
};
