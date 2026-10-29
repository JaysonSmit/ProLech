<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlCalidadPlanta extends Model
{
    protected $table = 'control_calidad_planta';

    protected $fillable = [
        'fecha',
        'codigo_lote',
        'cantidad_tachos',
        'litros_total',
        'equipo_lactoscan',
        'agua_promedio',
        'ph_promedio',
        'resultado',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'fecha'           => 'date',
        'litros_total'    => 'float',
        'agua_promedio'   => 'float',
        'ph_promedio'     => 'float',
        'equipo_lactoscan'=> 'integer',
        'cantidad_tachos' => 'integer',
    ];

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
