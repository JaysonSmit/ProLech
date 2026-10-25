<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega los campos de estado y teléfono a la tabla ganaderos.
 *
 * estado:    Activo (por defecto) | Suspendido (por 2 strikes de adulteración en la semana)
 * telefono:  ya existe en la migración anterior (2026_09_13_000006) — no se duplica aquí.
 *            Si no existe aún la columna, este campo se agrega con verificación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ganaderos', function (Blueprint $table) {
            // Agregar estado de proveedor — Activo | Suspendido
            $table->enum('estado', ['Activo', 'Suspendido'])
                  ->default('Activo')
                  ->after('activo')
                  ->comment('Activo = puede entregar leche. Suspendido = bloqueado por 2 adulteraciones en la semana.');

            // Agregar telefono solo si no existe aún
            if (! Schema::hasColumn('ganaderos', 'telefono')) {
                $table->string('telefono', 15)
                      ->nullable()
                      ->after('direccion')
                      ->comment('Número de celular para Yape / WhatsApp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ganaderos', function (Blueprint $table) {
            $table->dropColumn('estado');
            if (Schema::hasColumn('ganaderos', 'telefono')) {
                $table->dropColumn('telefono');
            }
        });
    }
};
