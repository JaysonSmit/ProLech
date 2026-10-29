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
            // SQLite no tiene MODIFY: Laravel reconstruye la tabla y se va el CHECK del enum
            // (antes un "migrate:fresh --seed" en SQLite fallaba al crear el usuario de calidad).
            Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('rol', 30)->default('chofer')->change();
            });
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
