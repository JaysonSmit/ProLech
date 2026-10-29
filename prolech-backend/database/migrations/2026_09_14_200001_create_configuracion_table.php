<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Tabla de configuración del sistema — precios y parámetros configurables.
 *
 * Permite al administrador cambiar precios por temporada sin tocar el código.
 * Se accede via Configuracion::get('clave') o Configuracion::set('clave', valor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 80)->unique();
            $table->text('valor');
            $table->string('descripcion', 255)->nullable();
            $table->string('tipo', 20)->default('decimal')
                  ->comment('decimal | entero | texto | booleano');
            $table->timestamps();
        });

        // ── Valores por defecto según el documento Ecolactus Guata ─────────────
        DB::table('configuracion')->insert([
            // Precios de leche
            ['clave' => 'precio_litro_normal',       'valor' => '1.70', 'descripcion' => 'Precio S/ por litro en temporada normal',            'tipo' => 'decimal',  'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'precio_litro_lluvias',       'valor' => '1.20', 'descripcion' => 'Precio S/ por litro en temporada de lluvias (ene-feb)', 'tipo' => 'decimal', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'precio_litro_penalidad',     'valor' => '0.65', 'descripcion' => 'Precio S/ por litro cuando adulteración >5% (rango 0.60-0.70)', 'tipo' => 'decimal', 'created_at' => now(), 'updated_at' => now()],
            // Precios de queso
            ['clave' => 'precio_queso_mayorista',     'valor' => '20.00', 'descripcion' => 'Precio S/ por molde para mayoristas',              'tipo' => 'decimal',  'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'precio_queso_proveedor',     'valor' => '18.00', 'descripcion' => 'Precio S/ por molde para proveedores de leche',    'tipo' => 'decimal',  'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'precio_queso_publico',       'valor' => '21.00', 'descripcion' => 'Precio S/ por molde para público general',         'tipo' => 'decimal',  'created_at' => now(), 'updated_at' => now()],
            // Temporada activa
            ['clave' => 'temporada_activa',           'valor' => 'normal', 'descripcion' => 'Temporada activa: normal | lluvias',             'tipo' => 'texto',    'created_at' => now(), 'updated_at' => now()],
            // Parámetros de calidad
            ['clave' => 'agua_limite_descuento',      'valor' => '5.0',  'descripcion' => '% agua máximo para descuento (no retiro)',         'tipo' => 'decimal',  'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'agua_limite_retiro',         'valor' => '5.0',  'descripcion' => '% agua mínimo para retiro definitivo',             'tipo' => 'decimal',  'created_at' => now(), 'updated_at' => now()],
            // Rendimiento producción
            ['clave' => 'rendimiento_quesos_100L',    'valor' => '11.5', 'descripcion' => 'Quesos esperados por cada 100L (rango 11-12)',     'tipo' => 'decimal',  'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'stock_minimo_venta_directa', 'valor' => '17',   'descripcion' => 'Moldes mínimos reservados para venta en planta',   'tipo' => 'entero',   'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};
