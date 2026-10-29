<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapacitacionAtos extends Model
{
    protected $table = 'capacitaciones_atos';

    protected $fillable = [
        'fecha',
        'tipo',
        'ganadero_id',
        'tema',
        'asistentes',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'fecha'      => 'date',
        'asistentes' => 'integer',
    ];

    public function ganadero(): BelongsTo
    {
        return $this->belongsTo(Ganadero::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
