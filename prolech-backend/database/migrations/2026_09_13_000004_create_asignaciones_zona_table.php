<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de asignaciones mensuales de choferes a zonas.
 *
 * Reglas de rotación ProLech Huata:
 *   - 5 acopiadores en total
 *   - 4 trabajan en zona activa cada día
 *   - 1 es el "volante" que cubre días libres rotativos y feriados
 *   - Los choferes rotan de zona cada mes
 *   - El rol 'volante' también rota cada mes
 *
 * Un chofer no puede estar asignado a dos zonas en el mismo mes.
 * El chofer con rol 'volante' ese mes no tiene zona_id (es NULL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_zona', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chofer_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('zona_id')
                  ->nullable()
                  ->constrained('zonas')
                  ->nullOnDelete()
                  ->comment('NULL = este chofer es el volante ese mes');

            $table->smallInteger('mes')
                  ->comment('1=enero … 12=diciembre');
            $table->smallInteger('anio');

            $table->enum('rol_mes', ['titular', 'volante'])
                  ->default('titular')
                  ->comment('titular = asignado a zona; volante = cobertura libre');

            $table->date('fecha_inicio')
                  ->comment('Primer día del mes (01/mm/yyyy)');
            $table->date('fecha_fin')
                  ->comment('Último día del mes');

            $table->text('notas')->nullable();
            $table->timestamps();

            // Un chofer solo puede tener una asignación por mes/año
            $table->unique(['chofer_id', 'mes', 'anio'], 'uniq_chofer_mes');

            // Una zona solo puede tener un titular por mes/año
            $table->unique(['zona_id', 'mes', 'anio'], 'uniq_zona_mes');

            $table->index(['mes', 'anio']);
            $table->index('chofer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_zona');
    }
};
