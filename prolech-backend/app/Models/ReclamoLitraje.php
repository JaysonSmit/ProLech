<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReclamoLitraje extends Model
{
    protected $table = 'reclamos_litraje';

    protected $fillable = [
        'ganadero_id',
        'acopio_id',
        'fecha_reclamo',
        'litros_registrados',
        'litros_correctos',
        'motivo',
        'estado',
        'respuesta_admin',
        'atendido_por',
        'atendido_at',
    ];

    protected $casts = [
        'fecha_reclamo'      => 'date',
        'litros_registrados' => 'float',
        'litros_correctos'   => 'float',
        'atendido_at'        => 'datetime',
    ];

    public function ganadero(): BelongsTo
    {
        return $this->belongsTo(Ganadero::class);
    }

    public function acopio(): BelongsTo
    {
        return $this->belongsTo(Acopio::class);
    }

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }
}
