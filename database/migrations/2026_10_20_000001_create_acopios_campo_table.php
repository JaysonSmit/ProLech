<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acopios_campo', function (Blueprint $table) {
            $table->id();
            // UUID generado en el celular: permite crear registros sin red (offline-first)
            $table->uuid('uuid')->unique();
            $table->foreignId('chofer_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->unsignedBigInteger('ganadero_id');
            $table->string('ganadero_nombre');
            $table->decimal('litros', 10, 2);
            $table->decimal('porcentaje_agua', 5, 2);
            $table->decimal('densidad', 6, 4);
            $table->enum('estado_calidad', ['APROBADO', 'OBSERVADO', 'RECHAZADO']);
            // Timestamp en ms del celular: resolución de conflictos Last-Write-Wins
            $table->unsignedBigInteger('actualizado_en');
            $table->timestamps();

            $table->index(['chofer_id', 'actualizado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acopios_campo');
    }
};
