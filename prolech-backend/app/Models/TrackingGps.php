<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingGps extends Model
{
    protected $table = 'tracking_gps';

    protected $fillable = [
        'chofer_id',
        'latitud',
        'longitud',
        'estado_ruta',
        'ultima_actualizacion',
    ];

    protected $casts = [
        'latitud'              => 'float',
        'longitud'             => 'float',
        'ultima_actualizacion' => 'datetime',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function chofer()
    {
        return $this->belongsTo(User::class, 'chofer_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Crea o actualiza el registro de ubicación de un chofer (UPSERT).
     * Llamado cada vez que el Background Service de Kotlin envía coordenadas.
     */
    public static function actualizarUbicacion(
        int    $choferId,
        float  $latitud,
        float  $longitud,
        string $estadoRuta = 'en_camino'
    ): self {
        return static::updateOrCreate(
            ['chofer_id' => $choferId],
            [
                'latitud'              => $latitud,
                'longitud'             => $longitud,
                'estado_ruta'          => $estadoRuta,
                'ultima_actualizacion' => now(),
            ]
        );
    }

    /**
     * Choferes cuya última actualización fue hace menos de N minutos.
     * Filtra ubicaciones "viejas" o de choferes desconectados.
     */
    public function scopeActivos($query, int $minutos = 30)
    {
        return $query->where('ultima_actualizacion', '>=', now()->subMinutes($minutos))
                     ->where('estado_ruta', 'en_camino');
    }

    /**
     * Todos los choferes con tracking registrado (para el panel GPS,
     * incluyendo los que enviaron ubicación hace más de 30 min).
     * Ordenados por la más reciente actualización primero.
     */
    public function scopeTodosConTracking($query)
    {
        return $query->orderByDesc('ultima_actualizacion');
    }
}
