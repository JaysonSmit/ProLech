<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía roles de usuario: calidad, asistente, jefe_produccion, despacho, proveedor.
 * MySQL ENUM original solo permitía admin | chofer | ganadero.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `rol` VARCHAR(30) NOT NULL DEFAULT 'chofer'");
        } elseif ($driver === 'sqlite') {
            // SQLite no soporta MODIFY; en dev local el rol ya suele ser string flexible.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `rol` ENUM('admin','chofer','ganadero') NOT NULL DEFAULT 'ganadero'");
        }
    }
};
