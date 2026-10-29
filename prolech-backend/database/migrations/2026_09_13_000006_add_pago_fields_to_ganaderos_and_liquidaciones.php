<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campos de pago:
 *   ganaderos.telefono       → número para Yape/Plin
 *   ganaderos.metodo_pago    → efectivo | yape | plin
 *   liquidaciones.metodo_pago → efectivo | yape | plin
 *   liquidaciones.referencia_pago → número de operación Yape
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ganaderos', function (Blueprint $table) {
            $table->string('telefono', 15)->nullable()->after('direccion')
                  ->comment('Número de celular para Yape/Plin');
            $table->enum('metodo_pago_preferido', ['efectivo', 'yape', 'plin'])
                  ->default('efectivo')
                  ->after('telefono');
        });

        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->enum('metodo_pago', ['efectivo', 'yape', 'plin'])
                  ->nullable()
                  ->after('pagada_at')
                  ->comment('Método usado para pagar esta liquidación');
            $table->string('referencia_pago', 100)->nullable()
                  ->after('metodo_pago')
                  ->comment('Nro de operación Yape/Plin o recibo de efectivo');
        });
    }

    public function down(): void
    {
        Schema::table('ganaderos', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'metodo_pago_preferido']);
        });
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->dropColumn(['metodo_pago', 'referencia_pago']);
        });
    }
};
