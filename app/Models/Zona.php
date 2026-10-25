<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    use HasFactory;

    protected $table = 'zonas';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'orden',
        'activa',
        'centro_lat',
        'centro_lon',
        'poligono',
        'color',
    ];

    protected $casts = [
        'activa'     => 'boolean',
        'centro_lat' => 'float',
        'centro_lon' => 'float',
        'poligono'   => 'array',
    ];

    /** ¿El punto cae dentro del polígono de la zona? (ray casting) */
    public function contiene(float $lat, float $lon): bool
    {
        $vertices = $this->poligono ?? [];
        $dentro = false;
        for ($i = 0, $j = count($vertices) - 1; $i < count($vertices); $j = $i++) {
            [$latI, $lonI] = $vertices[$i];
            [$latJ, $lonJ] = $vertices[$j];
            if ((($lonI > $lon) !== ($lonJ > $lon))
                && ($lat < ($latJ - $latI) * ($lon - $lonI) / ($lonJ - $lonI) + $latI)) {
                $dentro = ! $dentro;
            }
        }
        return $dentro;
    }

    /** Zona activa cuyo polígono contiene el punto, o null. */
    public static function deUbicacion(float $lat, float $lon): ?self
    {
        return static::where('activa', true)->whereNotNull('poligono')->orderBy('orden')->get()
            ->first(fn (self $z) => $z->contiene($lat, $lon));
    }

    public function ganaderos()
    {
        return $this->hasMany(Ganadero::class);
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionZona::class);
    }

    /**
     * El chofer asignado a esta zona en el mes/año indicado.
     */
    public function choferActual(?int $mes = null, ?int $anio = null)
    {
        $mes  = $mes  ?? now()->month;
        $anio = $anio ?? now()->year;

        return $this->asignaciones()
                    ->where('mes', $mes)
                    ->where('anio', $anio)
                    ->where('rol_mes', 'titular')
                    ->with('chofer:id,name,email')
                    ->first()
                    ?->chofer;
    }

    // Las 4 zonas fijas de la operación Huata
    public static function zonasDefault(): array
    {
        return [
            ['codigo' => 'COLLANA-1', 'nombre' => 'Collana 1', 'orden' => 1],
            ['codigo' => 'COLLANA-2', 'nombre' => 'Collana 2', 'orden' => 2],
            ['codigo' => 'COLLANA-3', 'nombre' => 'Collana 3', 'orden' => 3],
            ['codigo' => 'COLLANA-4', 'nombre' => 'Collana 4', 'orden' => 4],
        ];
    }
}
