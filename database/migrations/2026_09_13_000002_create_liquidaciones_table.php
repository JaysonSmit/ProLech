<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de liquidaciones semanales.
 *
 * Ciclo de pago ProLech Huata:
 *   - Semana de acumulación: jueves → miércoles (inclusive)
 *   - Día de pago: viernes de cada semana
 *   - Precio base leche: variable (definido en config)
 *   - Penalidad adulteración: S/ 1.50 / litro (los litros adulterados se pagan a este precio)
 *   - Precio queso a proveedor: S/ 17.00 / unidad
 *   - Máximo adulteraciones: 2 por semana (3ª afecta semana siguiente)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ganadero_id')
                  ->constrained('ganaderos')
                  ->cascadeOnDelete();

            // Período de la semana
            $table->date('semana_inicio')->comment('Jueves de inicio del período');
            $table->date('semana_fin')->comment('Miércoles de cierre del período');
            $table->date('fecha_pago')->comment('Viernes de pago');

            // Producción normal
            $table->decimal('litros_aprobados', 10, 2)->default(0);
            $table->decimal('precio_litro', 8, 4)->comment('Precio base S/ por litro');
            $table->decimal('subtotal_normal', 10, 2)->default(0);

            // Adulteraciones (se pagan a tarifa penalizada)
            $table->decimal('litros_adulterados', 10, 2)->default(0);
            $table->decimal('precio_penalidad', 8, 4)->default(1.50)
                  ->comment('S/ 1.50 por litro adulterado');
            $table->decimal('subtotal_penalidad', 10, 2)->default(0);
            $table->integer('num_adulteraciones')->default(0);

            // Descuentos por compra de queso
            $table->integer('quesos_solicitados')->default(0);
            $table->decimal('precio_queso_proveedor', 8, 2)->default(17.00)
                  ->comment('Precio preferencial S/ 17 para proveedores');
            $table->decimal('descuento_quesos', 10, 2)->default(0);

            // Otros descuentos
            $table->decimal('otros_descuentos', 10, 2)->default(0);
            $table->text('detalle_descuentos')->nullable();

            // Total final
            $table->decimal('total_bruto', 10, 2)->default(0)
                  ->comment('subtotal_normal + subtotal_penalidad');
            $table->decimal('total_descuentos', 10, 2)->default(0)
                  ->comment('descuento_quesos + otros_descuentos');
            $table->decimal('total_neto', 10, 2)->default(0)
                  ->comment('total_bruto - total_descuentos');

            // Estado
            $table->enum('estado', ['borrador', 'confirmada', 'pagada'])
                  ->default('borrador');

            $table->foreignId('generada_por')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamp('pagada_at')->nullable();
            $table->timestamps();

            // Un ganadero solo puede tener una liquidación por semana
            $table->unique(['ganadero_id', 'semana_inicio']);
            $table->index(['semana_inicio', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidaciones');
    }
};
