<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoDetalle extends Model
{
    use HasFactory;

    protected $table = 'pedido_detalles';

    protected $fillable = [
        'pedido_web_id',
        'producto_tienda_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'cantidad'        => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal'        => 'decimal:2',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Pedido al que pertenece esta línea. */
    public function pedido()
    {
        return $this->belongsTo(PedidoWeb::class, 'pedido_web_id');
    }

    /** Producto de esta línea. */
    public function producto()
    {
        return $this->belongsTo(ProductoTienda::class, 'producto_tienda_id');
    }

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        // Auto-calcula subtotal si no se provee explícitamente
        static::saving(function (self $model) {
            $model->subtotal = round(
                (float) $model->precio_unitario * (int) $model->cantidad,
                2
            );
        });
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getSubtotalFormateadoAttribute(): string
    {
        return 'S/ ' . number_format((float) $this->subtotal, 2);
    }
}
