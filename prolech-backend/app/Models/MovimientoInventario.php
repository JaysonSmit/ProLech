<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'insumo_id',
        'tipo',
        'cantidad',
        'motivo',
        'referencia_id',
        'referencia_tipo',
        'registrado_por',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    /** Insumo afectado. */
    public function insumo()
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    /** Documento origen (morphic — ProduccionLote u otro). */
    public function referencia()
    {
        return $this->morphTo('referencia');
    }

    /** Usuario que registró el movimiento. */
    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'ingreso');
    }

    public function scopeEgresos($query)
    {
        return $query->where('tipo', 'egreso');
    }

    public function scopeDeInsumo($query, int $insumoId)
    {
        return $query->where('insumo_id', $insumoId);
    }
}
