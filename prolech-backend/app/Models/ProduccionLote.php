<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduccionLote extends Model
{
    use HasFactory;

    protected $table = 'produccion_lotes';

    protected $fillable = [
        'codigo_lote',
        'receta_id',
        'fecha_produccion',
        'cantidad_producida',
        'costo_total',
        'responsable_id',
        'observaciones',
    ];

    protected $casts = [
        'fecha_produccion'  => 'date',
        'cantidad_producida' => 'integer',
        'costo_total'       => 'decimal:2',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    /** Receta usada para este lote. */
    public function receta()
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }

    /** Usuario responsable del lote. */
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /** Movimientos de inventario (egresos) generados por este lote. */
    public function movimientos()
    {
        return $this->morphMany(MovimientoInventario::class, 'referencia');
    }

    // ── Helpers / Accessors ────────────────────────────────────────────────────

    /** Costo por unidad producida. */
    public function getCostoPorUnidadAttribute(): float
    {
        if ((int) $this->cantidad_producida === 0) {
            return 0.0;
        }

        return round((float) $this->costo_total / $this->cantidad_producida, 4);
    }

    /**
     * Genera el siguiente código de lote del día.
     * Formato: PRD-YYYYMMDD-NNN (NNN = correlativo diario con cero-relleno)
     */
    public static function generarCodigo(\DateTimeInterface|string $fecha = null): string
    {
        $fecha = $fecha
            ? \Carbon\Carbon::parse($fecha)
            : now();

        $prefijo = 'PRD-' . $fecha->format('Ymd') . '-';

        $ultimo = static::where('codigo_lote', 'like', $prefijo . '%')
                        ->orderByDesc('codigo_lote')
                        ->value('codigo_lote');

        $siguiente = $ultimo
            ? (int) substr($ultimo, -3) + 1
            : 1;

        return $prefijo . str_pad($siguiente, 3, '0', STR_PAD_LEFT);
    }
}
