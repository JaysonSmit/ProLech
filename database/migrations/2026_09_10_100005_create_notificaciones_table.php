<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('titulo', 200);
            $table->text('mensaje');
            $table->boolean('leida')->default(false);
            $table->string('tipo', 50)->nullable()
                  ->comment('ruta_aprobada|ruta_rechazada|reunion|acopio|general');
            $table->unsignedBigInteger('referencia_id')->nullable()
                  ->comment('ID del recurso relacionado (solicitud_id, reunion_id, etc.)');
            $table->timestamps();

            $table->index(['user_id', 'leida']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
