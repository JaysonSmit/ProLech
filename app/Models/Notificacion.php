<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use MassPrunable;

    protected $table = 'notificaciones';

    protected $fillable = [
        'user_id',
        'titulo',
        'mensaje',
        'leida',
        'tipo',
        'referencia_id',
    ];

    protected $casts = [
        'leida' => 'boolean',
    ];

    /** Limpieza diaria (model:prune): las ya leídas de hace más de 90 días. Las no leídas se quedan. */
    public function prunable()
    {
        return static::where('leida', true)->where('created_at', '<', now()->subDays(90));
    }

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopeParaUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Factory method ─────────────────────────────────────────────────────────

    /**
     * Crea una notificación para un usuario con tipo y referencia opcionales.
     */
    public static function enviar(
        int    $userId,
        string $titulo,
        string $mensaje,
        string $tipo = 'general',
        ?int   $referenciaId = null
    ): self {
        return static::create([
            'user_id'       => $userId,
            'titulo'        => $titulo,
            'mensaje'       => $mensaje,
            'tipo'          => $tipo,
            'referencia_id' => $referenciaId,
            'leida'         => false,
        ]);
    }
}
