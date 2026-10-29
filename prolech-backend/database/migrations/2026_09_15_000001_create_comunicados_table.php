<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comunicados', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 150);
            $table->text('mensaje');
            $table->enum('tipo', ['general', 'urgente', 'calidad', 'precio', 'capacitacion'])->default('general');
            $table->boolean('enviar_whatsapp')->default(false);
            $table->unsignedInteger('destinatarios_count')->default(0);
            $table->foreignId('publicado_por')->constrained('users')->cascadeOnDelete();
            $table->date('fecha_publicacion');
            $table->timestamps();

            $table->index('fecha_publicacion');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicados');
    }
};
