<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acopios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ganadero_id')->constrained('ganaderos')->cascadeOnDelete();
            $table->foreignId('chofer_id')->constrained('users')->restrictOnDelete();
            $table->decimal('litros', 8, 2);
            $table->decimal('porcentaje_agua', 5, 2)->nullable()->comment('% agua detectado por Lactoscan');
            $table->decimal('acidez_ph', 4, 2)->nullable()->comment('pH del acopio');
            $table->enum('estado', ['aprobado', 'rechazado', 'observado'])->default('observado');
            $table->date('fecha');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['ganadero_id', 'fecha']);
            $table->index(['chofer_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acopios');
    }
};
