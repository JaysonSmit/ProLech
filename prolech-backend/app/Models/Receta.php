<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    use HasFactory;

    protected $table = 'recetas';

    protected $fillable = [
        'nombre',
        'rendimiento_esperado_unidades',
        'descripcion',
        'activa',
    ];

    protected $casts = [
        'rendimiento_esperado_unidades' => 'integer',
        'activa'                        => 'boolean',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    /** Líneas de ingredientes de esta receta (con el insumo cargado). */
    public function detalles()
    {
        return $this->hasMany(RecetaDetalle::class, 'receta_id')
                    ->with('insumo');
    }

    /** Lotes de producción generados con esta receta. */
    public function lotes()
    {
        return $this->hasMany(ProduccionLote::class, 'receta_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Calcula la cantidad total de insumos necesarios para producir $unidades.
     * Aplica regla de 3: cantidad_base / rendimiento_base * unidades_deseadas.
     *
     * @return array<int, array{insumo: Insumo, cantidad_necesaria: float}>
     */
    public function calcularInsumos(int $unidades): array
    {
        $resultado = [];

        foreach ($this->detalles as $detalle) {
            $cantidadNecesaria = ($detalle->cantidad_requerida / $this->rendimiento_esperado_unidades) * $unidades;

            $resultado[] = [
                'insumo'             => $detalle->insumo,
                'cantidad_necesaria' => round($cantidadNecesaria, 4),
                'costo_parcial'      => round($cantidadNecesaria * (float) $detalle->insumo->costo_unitario, 4),
            ];
        }

        return $resultado;
    }
}
