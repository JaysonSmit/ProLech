<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de puntos GPS del recorrido de cada chofer.
 * `tracking_gps` guarda solo la última posición; esta tabla guarda el trazo
 * completo para dibujar el recorrido real en el mapa del panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_gps_puntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chofer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->float('precision_m')->nullable();
            $table->float('velocidad_kmh')->nullable();
            // Hora en que el celular tomó el punto (puede llegar tarde si estaba sin señal)
            $table->dateTime('registrado_en', 3);
            $table->timestamps();

            // Reintentos del celular no duplican puntos
            $table->unique(['chofer_id', 'registrado_en']);
            $table->index(['chofer_id', 'registrado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_gps_puntos');
    }
};
