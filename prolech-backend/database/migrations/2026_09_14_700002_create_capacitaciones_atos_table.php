<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacitaciones_atos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->enum('tipo', ['capacitacion', 'visita'])->default('capacitacion');
            $table->foreignId('ganadero_id')->nullable()->constrained('ganaderos')->nullOnDelete();
            $table->string('tema', 200);
            $table->unsignedSmallInteger('asistentes')->default(1);
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacitaciones_atos');
    }
};
