<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reuniones', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->enum('tipo', ['capacitacion', 'asamblea', 'otro'])->default('capacitacion');
            $table->dateTime('fecha_hora');
            $table->string('enlace', 500)->nullable()->comment('URL de videollamada (Meet, Zoom, etc.)');
            $table->text('descripcion')->nullable();
            $table->string('lugar', 200)->nullable()->comment('Dirección física si es presencial');
            $table->enum('estado', ['programada', 'en_curso', 'finalizada', 'cancelada'])
                  ->default('programada');
            $table->foreignId('creado_por')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->timestamps();

            $table->index('fecha_hora');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reuniones');
    }
};
