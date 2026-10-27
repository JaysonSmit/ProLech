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
        'dias_vida_util',
        'descripcion',
        'activa',
    ];

    protected $casts = [
        'rendimiento_esperado_unidades' => 'integer',
        'dias_vida_util'                => 'integer',
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
    /** Costo de los insumos de UNA unidad (receta ÷ rendimiento), con los costos actuales del inventario. */
    public function costoUnitario(): float
    {
        $total = $this->detalles->sum(fn ($d) => (float) $d->cantidad_requerida * (float) ($d->insumo->costo_unitario ?? 0));
        return round($total / max(1, (int) $this->rendimiento_esperado_unidades), 2);
    }

    /** Margen de ganancia objetivo (%) configurable en Configuración; 30 % por defecto. */
    public static function margenObjetivo(): float
    {
        return (float) Configuracion::get('margen_ganancia_pct', 30);
    }

    /** Precio sugerido = costo × (1 + margen), redondeado hacia arriba a S/ 0.50. */
    public function precioSugerido(?float $margen = null): float
    {
        $precio = $this->costoUnitario() * (1 + ($margen ?? self::margenObjetivo()) / 100);
        return max(0.5, ceil($precio * 2) / 2);
    }

    /** Margen (%) de un precio de venta sobre el costo; null si el precio no es válido. */
    public function margenDe(float $precio): ?float
    {
        return $precio > 0 ? round(($precio - $this->costoUnitario()) / $precio * 100, 1) : null;
    }

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
