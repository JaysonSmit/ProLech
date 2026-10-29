<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que los proveedores (ganaderos) tengan acceso al portal web.
 * Se usa el modelo User existente con rol 'proveedor'.
 * El campo user_id en ganaderos ya vincula ambas tablas.
 */
return new class extends Migration
{
    public function up(): void
    {
        // El campo 'rol' en users ya existe — solo documentamos el nuevo valor permitido.
        // No se necesita cambio de esquema, el middleware CheckRol ya acepta cualquier string.
        // Esta migración es un marcador semántico.
    }

    public function down(): void {}
};
