<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Una línea del carrito de una venta: queso de planta (por kg) o un producto publicado. */
class VentaDetalle extends Model
{
    protected $table = 'venta_detalles';

    protected $fillable = [
        'venta_id',
        'producto_tienda_id',
        'descripcion',
        'unidad',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'cantidad'        => 'float',
        'precio_unitario' => 'float',
        'subtotal'        => 'float',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto()
    {
        return $this->belongsTo(ProductoTienda::class, 'producto_tienda_id');
    }
}
