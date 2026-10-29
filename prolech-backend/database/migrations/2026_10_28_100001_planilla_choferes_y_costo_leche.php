<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Planilla de pago a choferes (semanal o mensual) y parámetros del costo real por litro
 * de leche puesto en planta.
 */
return new class extends Migration
{
    private const PARAMETROS = [
        ['tarifa_chofer_dia',     '60',   'decimal', 'Jornal del chofer por día con ruta (S/)'],
        ['bono_chofer_litro',     '0.02', 'decimal', 'Bono del chofer por litro acopiado (S/)'],
        ['pension_chofer_pct',    '13',   'decimal', 'Aporte de pensión retenido al chofer (%) — ONP'],
        ['costo_combustible_km',  '0.55', 'decimal', 'Costo de combustible por km recorrido (S/)'],
        ['km_ruta_default',       '45',   'decimal', 'Km por día cuando no hay GPS ni ruta trazada'],
        ['gastos_generales_pct',  '4',    'decimal', 'Gastos generales de planta sobre el costo del litro (%)'],
    ];

    public function up(): void
    {
        Schema::create('planillas_choferes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chofer_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 10)->default('semanal');                // semanal | mensual
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->string('ruta', 80)->nullable();
            $table->unsignedSmallInteger('dias_trabajados')->default(0);
            $table->decimal('litros_recolectados', 12, 2)->default(0);
            $table->decimal('km_recorridos', 10, 2)->default(0);
            $table->decimal('tarifa_base_dia', 8, 2)->default(0);
            $table->decimal('monto_base', 10, 2)->default(0);
            $table->decimal('bono_por_litro', 8, 4)->default(0);
            $table->decimal('monto_bono', 10, 2)->default(0);
            $table->decimal('costo_combustible', 10, 2)->default(0);        // lo paga la planta, no es sueldo
            $table->decimal('adelantos', 10, 2)->default(0);
            $table->decimal('descuento_pension', 10, 2)->default(0);
            $table->decimal('descuentos', 10, 2)->default(0);
            $table->string('detalle_descuentos', 500)->nullable();
            $table->decimal('total_bruto', 10, 2)->default(0);
            $table->decimal('total_neto', 10, 2)->default(0);
            $table->json('detalle_dias')->nullable();
            $table->string('estado', 12)->default('borrador');             // borrador | pagada
            $table->foreignId('generada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('pagada_at')->nullable();
            $table->string('metodo_pago', 20)->nullable();
            $table->string('referencia_pago', 60)->nullable();
            $table->timestamps();

            $table->unique(['chofer_id', 'periodo_inicio', 'periodo_fin'], 'planilla_chofer_periodo_unico');
            $table->index(['periodo_inicio', 'periodo_fin']);
        });

        $ahora = now();
        foreach (self::PARAMETROS as [$clave, $valor, $tipo, $desc]) {
            DB::table('configuracion')->insertOrIgnore([
                'clave' => $clave, 'valor' => $valor, 'tipo' => $tipo, 'descripcion' => $desc,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('planillas_choferes');
        DB::table('configuracion')->whereIn('clave', array_column(self::PARAMETROS, 0))->delete();
    }
};
