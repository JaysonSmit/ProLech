<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan de control de calidad inopinado (RF-CAL-08).
 *
 * CONFIDENCIAL — solo visible para admin y personal de calidad.
 * Los acopiadores/conductores NO pueden ver esta tabla ni las rutas asociadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_calidad', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->comment('Día del operativo inopinado');
            $table->string('ruta_objetivo', 100)->comment('Ruta/zona a inspeccionar');
            $table->string('responsable', 150)->nullable()->comment('Personal de calidad asignado');
            $table->integer('num_equipos_lactoscan')->default(1)->comment('Equipos LACTOMAT a llevar (1-3)');
            $table->text('observaciones')->nullable();
            $table->boolean('ejecutado')->default(false);
            $table->text('resultado_resumen')->nullable()->comment('Resumen post-operativo');
            $table->foreignId('creado_por')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['fecha', 'ejecutado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_calidad');
    }
};
