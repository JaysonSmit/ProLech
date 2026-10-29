<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_calidad_planta', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('codigo_lote', 40);
            $table->unsignedSmallInteger('cantidad_tachos');
            $table->decimal('litros_total', 10, 2);
            $table->unsignedTinyInteger('equipo_lactoscan')->nullable()->comment('1-3');
            $table->decimal('agua_promedio', 5, 2)->nullable();
            $table->decimal('ph_promedio', 4, 2)->nullable();
            $table->enum('resultado', ['aprobado', 'observado', 'rechazado'])->default('aprobado');
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['fecha', 'codigo_lote']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_calidad_planta');
    }
};
