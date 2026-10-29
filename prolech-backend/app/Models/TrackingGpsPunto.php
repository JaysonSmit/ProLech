<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Un punto del recorrido GPS de un chofer. */
class TrackingGpsPunto extends Model
{
    protected $table = 'tracking_gps_puntos';

    protected $fillable = [
        'chofer_id',
        'latitud',
        'longitud',
        'precision_m',
        'velocidad_kmh',
        'registrado_en',
    ];

    protected $casts = [
        'latitud'       => 'float',
        'longitud'      => 'float',
        'precision_m'   => 'float',
        'velocidad_kmh' => 'float',
        'registrado_en' => 'datetime',
    ];

    public function chofer()
    {
        return $this->belongsTo(User::class, 'chofer_id');
    }

    /** Recorrido de hoy de un chofer como [[lat, lon], ...] para dibujar la polilínea. */
    public static function recorridoDeHoy(int $choferId): array
    {
        return static::where('chofer_id', $choferId)
            ->where('registrado_en', '>=', now()->startOfDay())
            ->orderBy('registrado_en')
            ->get(['latitud', 'longitud'])
            ->map(fn ($p) => [$p->latitud, $p->longitud])
            ->all();
    }
}
