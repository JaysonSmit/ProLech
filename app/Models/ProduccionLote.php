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
        'orden_codigo',
        'receta_id',
        'fecha_produccion',
        'fecha_vencimiento',
        'cantidad_producida',
        'merma',
        'costo_total',
        'responsable_id',
        'observaciones',
    ];

    protected $casts = [
        'fecha_produccion'  => 'date',
        'fecha_vencimiento' => 'date',
        'merma'             => 'integer',
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

    /** Días que faltan para el vencimiento (negativo si ya venció). */
    public function diasParaVencer(): ?int
    {
        return $this->fecha_vencimiento ? (int) now()->startOfDay()->diffInDays($this->fecha_vencimiento, false) : null;
    }

    /** vigente · por_vencer (≤ 3 días) · vencido */
    public function estadoVencimiento(): string
    {
        $dias = $this->diasParaVencer();
        return $dias === null ? 'vigente' : ($dias < 0 ? 'vencido' : ($dias <= \App\Services\StockLotes::DIAS_AVISO ? 'por_vencer' : 'vigente'));
    }

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
    /** ORD-AAAAMMDD-NNN: agrupa los lotes registrados juntos en una orden. */
    public static function generarCodigoOrden(): string
    {
        $prefijo = 'ORD-' . now()->format('Ymd') . '-';
        $ultimo = static::where('orden_codigo', 'like', $prefijo . '%')->orderByDesc('orden_codigo')->value('orden_codigo');
        $n = $ultimo ? ((int) substr($ultimo, -3)) + 1 : 1;
        return $prefijo . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    public static function generarCodigo(\DateTimeInterface|string|null $fecha = null): string
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
