<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pago al chofer por un periodo (semanal o mensual): jornal + bono por litro − descuentos. */
class PlanillaChofer extends Model
{
    protected $table = 'planillas_choferes';

    protected $fillable = [
        'chofer_id', 'tipo', 'periodo_inicio', 'periodo_fin', 'ruta',
        'dias_trabajados', 'litros_recolectados', 'km_recorridos',
        'tarifa_base_dia', 'monto_base', 'bono_por_litro', 'monto_bono', 'costo_combustible',
        'adelantos', 'descuento_pension', 'descuentos', 'detalle_descuentos',
        'total_bruto', 'total_neto', 'detalle_dias',
        'estado', 'generada_por', 'pagada_at', 'metodo_pago', 'referencia_pago',
    ];

    protected $casts = [
        'periodo_inicio'      => 'date',
        'periodo_fin'         => 'date',
        'dias_trabajados'     => 'integer',
        'litros_recolectados' => 'float',
        'km_recorridos'       => 'float',
        'tarifa_base_dia'     => 'float',
        'monto_base'          => 'float',
        'bono_por_litro'      => 'float',
        'monto_bono'          => 'float',
        'costo_combustible'   => 'float',
        'adelantos'           => 'float',
        'descuento_pension'   => 'float',
        'descuentos'          => 'float',
        'total_bruto'         => 'float',
        'total_neto'          => 'float',
        'detalle_dias'        => 'array',
        'pagada_at'           => 'datetime',
    ];

    public const METODOS = ['efectivo' => 'Efectivo', 'yape' => 'Yape', 'transferencia' => 'Transferencia'];

    public function chofer()
    {
        return $this->belongsTo(User::class, 'chofer_id');
    }

    public function generador()
    {
        return $this->belongsTo(User::class, 'generada_por');
    }

    public function estaPagada(): bool
    {
        return $this->estado === 'pagada';
    }

    public function codigo(): string
    {
        return 'PCH-' . $this->periodo_inicio->format('Ymd') . '-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }
}
