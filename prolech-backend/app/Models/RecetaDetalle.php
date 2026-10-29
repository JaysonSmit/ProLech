<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecetaDetalle extends Model
{
    use HasFactory;

    protected $table = 'receta_detalles';

    protected $fillable = [
        'receta_id',
        'insumo_id',
        'cantidad_requerida',
    ];

    protected $casts = [
        'cantidad_requerida' => 'decimal:4',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    /** Receta a la que pertenece esta línea. */
    public function receta()
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }

    /** Insumo que se requiere. */
    public function insumo()
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Cantidad necesaria para producir $unidades (regla de 3).
     */
    public function cantidadParaUnidades(int $unidades): float
    {
        $rendimientoBase = $this->receta->rendimiento_esperado_unidades;
        return round(((float) $this->cantidad_requerida / $rendimientoBase) * $unidades, 4);
    }
}
