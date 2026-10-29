<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_gps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chofer_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->enum('estado_ruta', ['en_camino', 'finalizado'])->default('en_camino');
            $table->timestamp('ultima_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();

            // Un solo registro activo por chofer; se hace UPSERT desde el servicio Android
            $table->unique('chofer_id');
            $table->index(['estado_ruta', 'ultima_actualizacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_gps');
    }
};
