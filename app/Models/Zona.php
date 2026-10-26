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
        'trazado',
        'trazado_km',
        'color',
    ];

    protected $casts = [
        'activa'     => 'boolean',
        'centro_lat' => 'float',
        'centro_lon' => 'float',
        'poligono'   => 'array',
        'trazado'    => 'array',
        'trazado_km' => 'float',
    ];

    public function tieneTrazado(): bool
    {
        return is_array($this->trazado) && count($this->trazado) >= 2;
    }

    /**
     * Proyecta un punto sobre el trazado de la ruta.
     * Devuelve [km recorridos por el trazado hasta ese punto, distancia en metros del punto al trazado],
     * o null si la zona no tiene trazado. Sirve para ordenar las paradas en el sentido del camino.
     */
    public function posicionEnTrazado(float $lat, float $lon): ?array
    {
        if (! $this->tieneTrazado()) {
            return null;
        }

        // Plano local en km (equirectangular): suficiente para distancias de una ruta rural
        $kx = 111.32 * cos(deg2rad($lat));
        $ky = 110.574;
        $px = $lon * $kx;
        $py = $lat * $ky;

        $mejor = null;
        $acumulado = 0.0;
        $puntos = $this->trazado;
        for ($i = 0; $i < count($puntos) - 1; $i++) {
            [$aLat, $aLon] = $puntos[$i];
            [$bLat, $bLon] = $puntos[$i + 1];
            $ax = $aLon * $kx; $ay = $aLat * $ky;
            $bx = $bLon * $kx; $by = $bLat * $ky;
            $dx = $bx - $ax; $dy = $by - $ay;
            $largo2 = $dx * $dx + $dy * $dy;
            $t = $largo2 > 0 ? max(0, min(1, (($px - $ax) * $dx + ($py - $ay) * $dy) / $largo2)) : 0;
            $distancia = hypot($px - ($ax + $t * $dx), $py - ($ay + $t * $dy));
            $largo = sqrt($largo2);

            if ($mejor === null || $distancia < $mejor[1]) {
                $mejor = [$acumulado + $t * $largo, $distancia];
            }
            $acumulado += $largo;
        }

        return [round($mejor[0], 3), round($mejor[1] * 1000)];
    }

    /** Largo del trazado en km. */
    public static function largoKm(array $puntos): float
    {
        $km = 0.0;
        for ($i = 0; $i < count($puntos) - 1; $i++) {
            $km += \App\Services\RutaChoferService::distanciaKm(
                (float) $puntos[$i][0], (float) $puntos[$i][1], (float) $puntos[$i + 1][0], (float) $puntos[$i + 1][1]);
        }
        return round($km, 2);
    }

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
