<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Control de calidad sorpresa: el chofer solo registra litros y una observación; la
 * calidad la mide el inspector con muestras sorpresa (en el predio, en frasco que trae el
 * chofer o del tanque del camión). Las sanciones bloquean entregas.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Lo que el chofer ve a simple vista y el frasco de muestra si la app se lo pidió
        foreach (['acopios_campo', 'acopios'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('observacion', 20)->nullable();          // normal, olor, color, grumos, caliente, otro
                $table->string('observacion_nota', 200)->nullable();
                $table->string('muestra_codigo', 20)->nullable();
            });
        }
        // Resultado del control sobre esa entrega: leve → descuento; grave → no se paga
        Schema::table('acopios', function (Blueprint $table) {
            $table->string('control_resultado', 10)->nullable();
        });

        // Sanción vigente: entregas que aún no puede hacer el ganadero
        Schema::table('ganaderos', function (Blueprint $table) {
            $table->unsignedSmallInteger('entregas_bloqueadas')->default(0);
            $table->string('bloqueo_motivo', 200)->nullable();
        });

        Schema::create('controles_calidad', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('tipo', 10);                                 // predio, muestra, tanque
            $table->string('estado', 15)->default('programado');        // programado, muestra_tomada, analizado, cancelado
            $table->string('motivo', 150)->nullable();                  // sorteo, alerta del chofer, riesgo, seguimiento, manual…
            $table->foreignId('ganadero_id')->nullable()->constrained('ganaderos')->nullOnDelete();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->nullOnDelete();
            $table->foreignId('acopio_id')->nullable()->constrained('acopios')->nullOnDelete();
            $table->string('frasco_codigo', 20)->nullable()->unique();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('agua', 5, 2)->nullable();
            $table->decimal('densidad', 6, 4)->nullable();
            $table->decimal('grasa', 5, 2)->nullable();
            $table->decimal('ph', 4, 2)->nullable();
            $table->decimal('temperatura', 4, 1)->nullable();
            $table->string('resultado', 10)->nullable();                // normal, leve, grave
            $table->string('sancion', 150)->nullable();                 // texto de lo que se aplicó
            $table->text('notas')->nullable();
            $table->string('foto_path', 255)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lon', 10, 7)->nullable();
            $table->uuid('uuid')->nullable()->unique();                 // registro offline desde la app del inspector
            $table->timestamp('analizado_at')->nullable();
            $table->timestamps();

            $table->index(['fecha', 'estado']);
            $table->index(['ganadero_id', 'resultado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controles_calidad');
        Schema::table('ganaderos', function (Blueprint $table) {
            $table->dropColumn(['entregas_bloqueadas', 'bloqueo_motivo']);
        });
        Schema::table('acopios', function (Blueprint $table) {
            $table->dropColumn('control_resultado');
        });
        foreach (['acopios_campo', 'acopios'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn(['observacion', 'observacion_nota', 'muestra_codigo']);
            });
        }
    }
};
