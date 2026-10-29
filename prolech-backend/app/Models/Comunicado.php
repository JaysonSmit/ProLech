<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comunicado extends Model
{
    use HasFactory;

    protected $table = 'comunicados';

    protected $fillable = [
        'titulo',
        'mensaje',
        'tipo',
        'enviar_whatsapp',
        'destinatarios_count',
        'publicado_por',
        'fecha_publicacion',
    ];

    protected $casts = [
        'enviar_whatsapp'   => 'boolean',
        'fecha_publicacion' => 'date',
        'destinatarios_count' => 'integer',
    ];

    public function autor()
    {
        return $this->belongsTo(User::class, 'publicado_por');
    }

    public function scopeRecientes($query)
    {
        return $query->orderByDesc('fecha_publicacion')->orderByDesc('created_at');
    }

    public function getTipoBadgeAttribute(): array
    {
        return match ($this->tipo) {
            'urgente'      => ['label' => '🚨 Urgente',     'class' => 'badge-rechazado'],
            'calidad'      => ['label' => '🔬 Calidad',     'class' => 'badge-observado'],
            'precio'       => ['label' => '💵 Precios',     'class' => 'badge-aprobado'],
            'capacitacion' => ['label' => '📚 Capacitación', 'class' => 'badge-mayorista'],
            default        => ['label' => '📢 General',     'class' => 'badge-borrador'],
        };
    }
}
