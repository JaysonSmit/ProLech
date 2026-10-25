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

    /**
     * Guarda o actualiza el valor de una clave. Una clave nueva toma el tipo del valor:
     * antes la columna "tipo" quedaba en "decimal" y un texto como el nombre de la planta
     * se leía como 0 (y un RUC como número con decimales).
     */
    public static function set(string $clave, mixed $valor): void
    {
        $fila = static::firstOrNew(['clave' => $clave]);
        $fila->valor = is_bool($valor) ? ($valor ? '1' : '0') : (string) $valor;
        if (! $fila->exists) {
            $fila->tipo = self::tipoDe($valor);
        }
        $fila->save();
    }

    public static function tipoDe(mixed $valor): string
    {
        return match (true) {
            is_bool($valor)  => 'booleano',
            is_int($valor)   => 'entero',
            is_float($valor) => 'decimal',
            // Números escritos como texto ("1500", "-15.633") sí son números; un RUC o un celular no
            is_string($valor) && preg_match('/^-?\d{1,7}(\.\d+)?$/', $valor) === 1 => 'decimal',
            default          => 'texto',
        };
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
