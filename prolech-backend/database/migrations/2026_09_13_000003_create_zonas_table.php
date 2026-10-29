<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de zonas de recolección.
 * Operación Huata: 4 zonas fijas (Collana 1–4) que se recorren 1 vez al día.
 * Un acopiador por zona. Los acopiadores rotan de zona cada mes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique()
                  ->comment('Ej: COLLANA-1, COLLANA-2, COLLANA-3, COLLANA-4');
            $table->string('nombre', 100)
                  ->comment('Nombre descriptivo de la zona');
            $table->string('descripcion', 255)->nullable();
            $table->integer('orden')->default(1)
                  ->comment('Orden de recorrido del día');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas');
    }
};
