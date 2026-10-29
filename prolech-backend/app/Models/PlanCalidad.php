<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PlanCalidad — plan de control de calidad inopinado.
 * ACCESO RESTRINGIDO: solo admin y calidad pueden ver/crear.
 */
class PlanCalidad extends Model
{
    protected $table    = 'plan_calidad';
    protected $fillable = [
        'fecha', 'ruta_objetivo', 'responsable',
        'num_equipos_lactoscan', 'observaciones',
        'ejecutado', 'resultado_resumen', 'creado_por',
    ];

    protected $casts = ['fecha' => 'date', 'ejecutado' => 'boolean'];

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
