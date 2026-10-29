<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamos_litraje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ganadero_id')->constrained('ganaderos')->cascadeOnDelete();
            $table->foreignId('acopio_id')->nullable()->constrained('acopios')->nullOnDelete();
            $table->date('fecha_reclamo');
            $table->decimal('litros_registrados', 10, 2);
            $table->decimal('litros_correctos', 10, 2);
            $table->text('motivo');
            $table->string('estado', 20)->default('pendiente'); // pendiente | atendido | rechazado
            $table->text('respuesta_admin')->nullable();
            $table->foreignId('atendido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('atendido_at')->nullable();
            $table->timestamps();

            $table->index(['ganadero_id', 'fecha_reclamo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamos_litraje');
    }
};
