<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuracion
 *
 * Parámetros del sistema configurables por el administrador sin tocar código.
 * Uso: Configuracion::get('precio_litro_normal', 1.70)
 */
class Configuracion extends Model
{
    protected $table    = 'configuracion';
    protected $fillable = ['clave', 'valor', 'descripcion', 'tipo'];

    /** Devuelve el valor de una clave; si no existe retorna $default */
    public static function get(string $clave, mixed $default = null): mixed
    {
        $row = static::where('clave', $clave)->first();
        if (!$row) return $default;

        return match ($row->tipo) {
            'decimal' => (float)  $row->valor,
            'entero'  => (int)    $row->valor,
            'booleano'=> filter_var($row->valor, FILTER_VALIDATE_BOOLEAN),
            default   => $row->valor,
        };
    }

    /** Guarda o actualiza el valor de una clave */
    public static function set(string $clave, mixed $valor): void
    {
        static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => (string) $valor]
        );
    }

    /** Devuelve el precio por litro según la temporada activa */
    public static function precioLitroActual(): float
    {
        $temporada = static::get('temporada_activa', 'normal');
        return $temporada === 'lluvias'
            ? (float) static::get('precio_litro_lluvias', 1.20)
            : (float) static::get('precio_litro_normal',  1.70);
    }
}
