<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    use HasFactory;

    protected $table = 'insumos';

    protected $fillable = [
        'nombre',
        'unidad_medida',
        'costo_unitario',
        'stock_actual',
        'stock_minimo',
        'activo',
    ];

    protected $casts = [
        'costo_unitario' => 'decimal:4',
        'stock_actual'   => 'decimal:3',
        'stock_minimo'   => 'decimal:3',
        'activo'         => 'boolean',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    /** Líneas de receta donde aparece este insumo. */
    public function recetaDetalles()
    {
        return $this->hasMany(RecetaDetalle::class, 'insumo_id');
    }

    /** Todos los movimientos de inventario de este insumo. */
    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'insumo_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeConStockBajo($query)
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /** Verifica si hay stock suficiente para una cantidad dada. */
    public function tieneStockSuficiente(float $cantidad): bool
    {
        return (float) $this->stock_actual >= $cantidad;
    }

    /** Devuelve el stock formateado con unidad. */
    public function getStockFormateadoAttribute(): string
    {
        return number_format((float) $this->stock_actual, 2) . ' ' . $this->unidad_medida;
    }
}
