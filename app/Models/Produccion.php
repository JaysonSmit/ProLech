<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produccion extends Model
{
    use HasFactory;

    protected $table = 'produccion';

    protected $fillable = [
        'litros_usados',
        'precio_litro',
        'costo_cuajo',
        'costo_sal',
        'costo_otros',
        'costo_total',
        'kg_queso_estimado',
        'fecha_lote',
        'registrado_por',
        'notas',
    ];

    protected $casts = [
        'fecha_lote' => 'date',
    ];

    /**
     * Antes de guardar, calcula el costo_total automáticamente.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $model) {
            $costoMateria  = ($model->litros_usados ?? 0) * ($model->precio_litro ?? 0);
            $model->costo_total = $costoMateria + $model->costo_cuajo + $model->costo_sal + $model->costo_otros;
        });
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * Litros usados por cada kg de queso producido (rendimiento).
     */
    public function getRendimientoAttribute(): float
    {
        return $this->kg_queso_estimado > 0
            ? round($this->litros_usados / $this->kg_queso_estimado, 2)
            : 0;
    }

    /**
     * Stock disponible en planta (moldes / kg) = producido acumulado - vendido acumulado.
     */
    public static function stockActualMoldes(): float
    {
        $producido = (float) static::sum('kg_queso_estimado');
        $vendido   = (float) Venta::vigentes()->sum('cantidad_kg');
        return max(0.0, round($producido - $vendido, 1));
    }

    /**
     * Retorna si el stock actual está en o por debajo del mínimo de reserva (ej. 15 moldes).
     */
    public static function alertaStockMinimo(): bool
    {
        $stockActual = self::stockActualMoldes();
        $stockMinimo = (float) (Configuracion::get('stock_minimo_venta_directa') ?? Configuracion::get('stock_minimo_quesos', 15));
        return $stockActual <= $stockMinimo;
    }
}
