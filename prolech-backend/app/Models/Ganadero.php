<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ganadero extends Model
{
    use HasFactory;

    protected $table = 'ganaderos';

    protected $fillable = [
        'dni',
        'nombre',
        'direccion',
        'geo_lat',         // Latitud GPS del predio
        'geo_lon',         // Longitud GPS del predio
        'telefono',
        'metodo_pago_preferido',
        'ruta_asignada',
        'zona_id',
        'user_id',
        'activo',
        'estado',          // 'Activo' | 'Suspendido'
        'entregas_bloqueadas',   // sanción de calidad: entregas que aún no puede hacer
        'bloqueo_motivo',
    ];

    protected $casts = [
        'activo'  => 'boolean',
        'geo_lat' => 'float',
        'geo_lon' => 'float',
    ];

    // ── Constantes de estado ──────────────────────────────────────────────────

    const ESTADO_ACTIVO     = 'Activo';
    const ESTADO_SUSPENDIDO = 'Suspendido';

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function acopios()
    {
        return $this->hasMany(Acopio::class);
    }

    public function solicitudesRuta()
    {
        return $this->hasMany(SolicitudRuta::class);
    }

    // ── Helpers de estado ─────────────────────────────────────────────────────

    public function estaActivo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO && $this->activo;
    }

    public function estaSuspendido(): bool
    {
        return $this->estado === self::ESTADO_SUSPENDIDO;
    }

    public function suspender(): void
    {
        $this->update(['estado' => self::ESTADO_SUSPENDIDO]);
    }

    public function rehabilitar(): void
    {
        $this->update(['estado' => self::ESTADO_ACTIVO]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeSuspendidos($query)
    {
        return $query->where('estado', self::ESTADO_SUSPENDIDO);
    }
}
