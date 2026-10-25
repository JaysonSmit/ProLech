<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('entidad_tipo', 80);
            $table->unsignedBigInteger('entidad_id');
            $table->string('accion', 40); // creacion, edicion, anulacion, cambio_estado
            $table->string('campo_modificado', 80)->nullable();
            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo')->nullable();
            $table->text('motivo');
            $table->timestamps();

            $table->index(['entidad_tipo', 'entidad_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
