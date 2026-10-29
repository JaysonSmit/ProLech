<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductoTienda extends Model
{
    use HasFactory;

    protected $table = 'productos_tienda';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'precio_venta',
        'stock_disponible',
        'imagen_url',
        'estado',
        'receta_id',
    ];

    protected $casts = [
        'precio_venta'     => 'decimal:2',
        'stock_disponible' => 'integer',
    ];

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        // Auto-genera el slug a partir del nombre si no se provee
        static::saving(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = static::generarSlugUnico($model->nombre, $model->id);
            }
        });
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Receta de producción que genera este producto (opcional). */
    public function receta()
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }

    /** Líneas de detalle en pedidos que incluyen este producto. */
    public function pedidoDetalles()
    {
        return $this->hasMany(PedidoDetalle::class, 'producto_tienda_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Solo productos activos con stock mayor a cero. */
    public function scopeDisponibles($query)
    {
        return $query->where('estado', 'activo')->where('stock_disponible', '>', 0);
    }

    /** Solo productos activos (pueden estar sin stock). */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /** URL pública de la imagen (devuelve placeholder si no hay imagen). */
    public function getImagenPublicaAttribute(): string
    {
        if ($this->imagen_url) {
            return asset('storage/' . $this->imagen_url);
        }
        return asset('images/producto-placeholder.png');
    }

    /** Precio formateado en soles. */
    public function getPrecioFormateadoAttribute(): string
    {
        return 'S/ ' . number_format((float) $this->precio_venta, 2);
    }

    /** ¿Hay stock suficiente para al menos 1 unidad? */
    public function getTieneStockAttribute(): bool
    {
        return $this->stock_disponible > 0;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Genera un slug único para el nombre dado.
     * Si ya existe uno igual, agrega sufijo numérico (-2, -3…).
     */
    public static function generarSlugUnico(string $nombre, ?int $exceptId = null): string
    {
        $base  = Str::slug($nombre);
        $slug  = $base;
        $count = 2;

        while (
            static::where('slug', $slug)
                  ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
                  ->exists()
        ) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Incrementa el stock disponible.
     * Se llama desde ProduccionLoteController al finalizar un lote.
     */
    public function incrementarStock(int $cantidad): void
    {
        $this->increment('stock_disponible', $cantidad);
    }

    /**
     * Decrementa el stock. Lanza excepción si no hay suficiente.
     * Debe llamarse dentro de DB::transaction con lockForUpdate().
     *
     * @throws \RuntimeException
     */
    public function decrementarStock(int $cantidad): void
    {
        $stockActual = (int) static::lockForUpdate()->find($this->id)->stock_disponible;

        if ($stockActual < $cantidad) {
            throw new \RuntimeException(
                "Stock insuficiente para \"{$this->nombre}\": " .
                "solicitado {$cantidad}, disponible {$stockActual}."
            );
        }

        $this->decrement('stock_disponible', $cantidad);
    }

    /**
     * Genera el siguiente código de pedido web.
     * Formato: WEB-NNNN (correlativo global, relleno a 4 dígitos).
     */
    public static function siguienteCodigoPedido(): string
    {
        // Delegado al modelo PedidoWeb para mantener cohesión
        return PedidoWeb::generarCodigo();
    }
}
