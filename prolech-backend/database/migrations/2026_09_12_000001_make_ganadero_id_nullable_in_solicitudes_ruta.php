<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La migración original (2026_09_09_200006) creó ganadero_id como NOT NULL.
 * Cuando un chofer solicita cambio de ruta desde la app Android, no envía
 * ganadero_id (no aplica para solicitudes de choferes), lo que provoca un
 * error de BD y la solicitud nunca se guarda.
 *
 * Esta migración hace ganadero_id nullable para permitir solicitudes
 * generadas desde la app móvil (que tienen chofer_id en su lugar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_ruta', function (Blueprint $table) {
            // Primero eliminar la FK constraint existente para poder cambiar la columna
            $table->dropForeign(['ganadero_id']);

            // Cambiar ganadero_id a nullable y volver a agregar la FK
            $table->foreignId('ganadero_id')
                  ->nullable()
                  ->change();

            $table->foreign('ganadero_id')
                  ->references('id')
                  ->on('ganaderos')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_ruta', function (Blueprint $table) {
            $table->dropForeign(['ganadero_id']);

            $table->foreignId('ganadero_id')
                  ->nullable(false)
                  ->change();

            $table->foreign('ganadero_id')
                  ->references('id')
                  ->on('ganaderos')
                  ->cascadeOnDelete();
        });
    }
};
