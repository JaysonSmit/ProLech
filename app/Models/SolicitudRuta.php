<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudRuta extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_ruta';

    protected $fillable = [
        'chofer_id',
        'ganadero_id',
        'ruta_actual',
        'ruta_solicitada',
        'motivo',
        'estado',
        'revisado_por',
        'revisado_at',
        'fecha_solicitud',
    ];

    protected $casts = [
        'revisado_at'     => 'datetime',
        'fecha_solicitud' => 'date',
    ];

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente')->orderBy('created_at');
    }

    // ── Relaciones ─────────────────────────────────────────────────────────────

    /**
     * Chofer que solicita el cambio de ruta (vía app móvil).
     */
    public function chofer()
    {
        return $this->belongsTo(User::class, 'chofer_id');
    }

    /**
     * Ganadero relacionado (solicitudes antiguas del sistema de campo).
     */
    public function ganadero()
    {
        return $this->belongsTo(Ganadero::class);
    }

    /**
     * Administrador que revisó la solicitud.
     */
    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function esPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function etiquetaEstado(): string
    {
        return match($this->estado) {
            'pendiente'  => '⏳ Pendiente',
            'aprobado'   => '✅ Aprobada',
            'rechazado'  => '❌ Rechazada',
            default      => $this->estado,
        };
    }
}
