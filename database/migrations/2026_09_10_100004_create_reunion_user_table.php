<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reunion_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reunion_id')
                  ->constrained('reuniones')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->boolean('notificado')->default(false)
                  ->comment('Si ya se envió la notificación push al invitado');
            $table->boolean('confirmado')->default(false)
                  ->comment('Si el invitado confirmó asistencia desde la app');
            $table->timestamps();

            $table->unique(['reunion_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reunion_user');
    }
};
