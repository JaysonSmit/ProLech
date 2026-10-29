<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reunion extends Model
{
    use HasFactory;

    protected $table = 'reuniones';

    protected $fillable = [
        'titulo',
        'tipo',
        'fecha_hora',
        'enlace',
        'descripcion',
        'lugar',
        'estado',
        'creado_por',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeProximas($query)
    {
        return $query->where('fecha_hora', '>=', now())
                     ->whereIn('estado', ['programada', 'en_curso'])
                     ->orderBy('fecha_hora');
    }

    public function scopePasadas($query)
    {
        return $query->where('fecha_hora', '<', now())
                     ->orderByDesc('fecha_hora');
    }

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Usuarios invitados a esta reunión (pivot: reunion_user).
     */
    public function invitados()
    {
        return $this->belongsToMany(User::class, 'reunion_user')
                    ->withPivot(['notificado', 'confirmado'])
                    ->withTimestamps();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function esProxima(): bool
    {
        return $this->fecha_hora->isFuture();
    }

    public function tieneEnlaceVideo(): bool
    {
        return ! empty($this->enlace);
    }

    public function etiquetaTipo(): string
    {
        return match($this->tipo) {
            'capacitacion' => '📚 Capacitación',
            'asamblea'     => '🏛️ Asamblea',
            default        => '📅 Evento',
        };
    }
}
