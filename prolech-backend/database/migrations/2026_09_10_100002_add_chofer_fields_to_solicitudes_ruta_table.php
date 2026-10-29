<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla solicitudes_ruta ya existe (creada en 2026_09_09_200006).
 * La migración original la vincula a ganadero_id.
 * Aquí añadimos los campos necesarios para que los CHOFERES puedan
 * solicitar cambios de ruta desde la app móvil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_ruta', function (Blueprint $table) {
            // FK al chofer que solicita el cambio (nullable para no romper registros
            // existentes asociados a ganaderos)
            $table->foreignId('chofer_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('users')
                  ->nullOnDelete();

            // Ruta que tiene asignada actualmente el chofer
            $table->string('ruta_actual', 100)
                  ->nullable()
                  ->after('chofer_id');

            // Normaliza el campo estado para incluir 'pendiente' (ya debería existir)
            // y agrega fecha_solicitud explícita
            $table->date('fecha_solicitud')
                  ->nullable()
                  ->after('revisado_at');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_ruta', function (Blueprint $table) {
            $table->dropForeign(['chofer_id']);
            $table->dropColumn(['chofer_id', 'ruta_actual', 'fecha_solicitud']);
        });
    }
};
