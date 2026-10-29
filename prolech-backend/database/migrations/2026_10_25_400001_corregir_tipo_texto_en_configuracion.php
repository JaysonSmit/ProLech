<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Claves de texto que quedaron marcadas como "decimal" (el valor por defecto de la columna)
 * y se leían como 0: nombre de la planta, razón social, dirección, RUC, Yape, teléfonos.
 */
return new class extends Migration
{
    private const SIEMPRE_TEXTO = ['empresa_ruc', 'empresa_telefono', 'yape_numero', 'yape_titular',
        'planta_nombre', 'empresa_razon_social', 'empresa_direccion', 'temporada_activa'];

    public function up(): void
    {
        DB::table('configuracion')->whereIn('clave', self::SIEMPRE_TEXTO)->update(['tipo' => 'texto']);

        DB::table('configuracion')->where('tipo', 'decimal')->get(['id', 'valor'])
            ->reject(fn ($fila) => is_numeric($fila->valor))
            ->each(fn ($fila) => DB::table('configuracion')->where('id', $fila->id)->update(['tipo' => 'texto']));
    }

    public function down(): void
    {
        // Sin vuelta atrás: volver a "decimal" rompería la lectura de estos textos.
    }
};
