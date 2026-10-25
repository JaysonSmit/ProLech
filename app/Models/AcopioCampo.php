<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcopioCampo extends Model
{
    protected $table = 'acopios_campo';

    protected $fillable = [
        'uuid',
        'chofer_id',
        'ganadero_id',
        'ganadero_nombre',
        'litros',
        'porcentaje_agua',
        'densidad',
        'estado_calidad',
        'actualizado_en',
    ];

    protected $casts = [
        'ganadero_id'     => 'integer',
        'litros'          => 'float',
        'porcentaje_agua' => 'float',
        'densidad'        => 'float',
        'actualizado_en'  => 'integer',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function chofer()
    {
        return $this->belongsTo(User::class, 'chofer_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /** Formato JSON que espera AcopioCampoDto de la app Kotlin. */
    public function toApi(): array
    {
        return [
            'uuid'            => $this->uuid,
            'ganadero_id'     => $this->ganadero_id,
            'ganadero_nombre' => $this->ganadero_nombre,
            'litros'          => $this->litros,
            'porcentaje_agua' => $this->porcentaje_agua,
            'densidad'        => $this->densidad,
            'estado_calidad'  => $this->estado_calidad,
            'actualizado_en'  => $this->actualizado_en,
        ];
    }
}
