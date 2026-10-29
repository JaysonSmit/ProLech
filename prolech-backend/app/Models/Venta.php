<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente',
        'tipo_cliente',
        'destino',
        'cantidad_kg',
        'precio_unitario',
        'total',
        'estado_despacho',
        'guia_remision',
        'fecha_despacho',
        'notas_despacho',
        'fecha_venta',
        'registrado_por',
        'anulada_at',
        'anulada_por',
        'motivo_anulacion',
    ];

    protected $casts = [
        'fecha_venta'     => 'date',
        'fecha_despacho'  => 'date',
        'cantidad_kg'     => 'float',
        'precio_unitario' => 'float',
        'total'           => 'float',
        'anulada_at'      => 'datetime',
    ];

    /** La venta con carrito ya trae su total (suma de las líneas); no se recalcula con kg × precio. */
    public bool $totalDesdeDetalles = false;

    // Precios de referencia del negocio (como fallback si no hay configuración)
    const PRECIO_MAYORISTA = 20.00;
    const PRECIO_PUBLICO   = 21.00;

    /**
     * Obtiene el precio por kg según tipo de cliente desde la configuración o fallback.
     */
    public static function precioParaTipo(string $tipo): float
    {
        if ($tipo === 'mayorista') {
            return (float) Configuracion::get('precio_queso_mayorista', self::PRECIO_MAYORISTA);
        }
        return (float) Configuracion::get('precio_queso_publico', self::PRECIO_PUBLICO);
    }

    /**
     * Calcula el total automáticamente antes de guardar.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $model) {
            if (! $model->totalDesdeDetalles) {
                $model->total = $model->cantidad_kg * $model->precio_unitario;
            }
            if (empty($model->destino)) {
                $model->destino = 'Planta / Local';
            }
        });
    }

    /** Ventas que cuentan para totales, reportes y stock (las anuladas no). */
    public function scopeVigentes($query)
    {
        return $query->whereNull('anulada_at');
    }

    public function getAnuladaAttribute(): bool
    {
        return $this->anulada_at !== null;
    }

    /** Número que se imprime en el comprobante: V-000123. */
    public function getNumeroComprobanteAttribute(): string
    {
        return 'V-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function anulador()
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function getEstadoDespachoBadgeAttribute(): array
    {
        return match ($this->estado_despacho) {
            'en_camino'  => [
                'label'  => 'En camino',
                'icon'   => '🚚',
                'class'  => 'badge-mayorista',
                'bg'     => '#dbeafe',
                'color'  => '#1d4ed8',
                'border' => '#bfdbfe',
            ],
            'entregado'  => [
                'label'  => 'Entregado',
                'icon'   => '✅',
                'class'  => 'badge-pagada',
                'bg'     => '#dcfce7',
                'color'  => '#166534',
                'border' => '#bbf7d0',
            ],
            default      => [
                'label'  => 'En preparación',
                'icon'   => '📦',
                'class'  => 'badge-pendiente',
                'bg'     => '#fef3c7',
                'color'  => '#92400e',
                'border' => '#fde68a',
            ],
        };
    }

    public function scopePorDestino($query, string $destino)
    {
        return $query->where('destino', $destino);
    }

    public function scopePendientesDespacho($query)
    {
        return $query->whereIn('estado_despacho', ['preparacion', 'en_camino']);
    }
}
