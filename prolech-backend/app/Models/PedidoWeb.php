<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoWeb extends Model
{
    use HasFactory;

    protected $table = 'pedidos_web';

    protected $fillable = [
        'codigo_pedido',
        'numero_comprobante',
        'cliente_nombre',
        'cliente_telefono',
        'cliente_direccion',
        'cliente_email',
        'cliente_documento',
        'total',
        'metodo_pago',
        'pago_referencia',
        'estado_pedido',
        'despachado_at',
        'entregado_at',
        'notas_despacho',
        'comprobante_yape_url',
        'notas',
        'sunat_estado',
        'sunat_pdf_url',
        'sunat_mensaje',
    ];

    protected $casts = [
        'despachado_at' => 'datetime',
        'entregado_at'  => 'datetime',
        'total' => 'decimal:2',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Líneas de detalle de este pedido. */
    public function detalles()
    {
        return $this->hasMany(PedidoDetalle::class, 'pedido_web_id');
    }

    /** Acceso directo a los productos de este pedido a través de los detalles. */
    public function productos()
    {
        return $this->hasManyThrough(
            ProductoTienda::class,
            PedidoDetalle::class,
            'pedido_web_id',
            'id',
            'id',
            'producto_tienda_id'
        );
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePendientes($query)
    {
        return $query->where('estado_pedido', 'Pendiente');
    }

    public function scopePorEnviar($query)
    {
        return $query->whereIn('estado_pedido', ['Pendiente', 'Pagado']);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /** Badge visual para el estado del pedido. */
    public function getEstadoBadgeAttribute(): array
    {
        return match ($this->estado_pedido) {
            'Pendiente'  => ['label' => 'Pendiente',  'color' => '#92400e', 'bg' => '#fef3c7'],
            'Pagado'     => ['label' => 'Pagado',     'color' => '#1d4ed8', 'bg' => '#dbeafe'],
            'Enviado'    => ['label' => 'En camino',  'color' => '#7c2d12', 'bg' => '#ffedd5'],
            'Entregado'  => ['label' => 'Entregado',  'color' => '#166534', 'bg' => '#dcfce7'],
            'Cancelado'  => ['label' => 'Cancelado',  'color' => '#991b1b', 'bg' => '#fee2e2'],
            default      => ['label' => $this->estado_pedido, 'color' => '#64748b', 'bg' => '#f1f5f9'],
        };
    }

    /** Badge visual para el método de pago. */
    public function getMetodoBadgeAttribute(): array
    {
        return match ($this->metodo_pago) {
            'Yape'     => ['label' => 'Yape',     'icon' => '💜', 'color' => '#6d28d9', 'bg' => '#ede9fe'],
            'Tarjeta'  => ['label' => 'Tarjeta',  'icon' => '💳', 'color' => '#1d4ed8', 'bg' => '#dbeafe'],
            default    => ['label' => 'Efectivo', 'icon' => '💵', 'color' => '#166534', 'bg' => '#dcfce7'],
        };
    }

    /** URL pública del comprobante Yape. */
    public function getComprobanteUrlAttribute(): ?string
    {
        return $this->comprobante_yape_url
            ? asset('storage/' . $this->comprobante_yape_url)
            : null;
    }

    /** Total formateado. */
    public function getTotalFormateadoAttribute(): string
    {
        return 'S/ ' . number_format((float) $this->total, 2);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Genera el siguiente código correlativo global.
     * Formato: WEB-NNNN (relleno a 4 dígitos mínimo).
     */
    public static function generarCodigo(): string
    {
        $ultimo = static::orderByDesc('id')->value('codigo_pedido');

        if ($ultimo) {
            // Extrae el número del último código: "WEB-1042" → 1042
            $numero = (int) substr($ultimo, 4);
            $siguiente = $numero + 1;
        } else {
            $siguiente = 1001; // Empieza desde WEB-1001
        }

        return 'WEB-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Siguiente número de boleta: serie B001 + correlativo de 8 dígitos.
     * Se llama dentro de la transacción del checkout.
     */
    public static function generarNumeroBoleta(): string
    {
        $ultimo = static::whereNotNull('numero_comprobante')
            ->lockForUpdate()
            ->orderByDesc('numero_comprobante')
            ->value('numero_comprobante');

        $siguiente = $ultimo ? ((int) substr($ultimo, 5)) + 1 : 1;

        return 'B001-' . str_pad((string) $siguiente, 8, '0', STR_PAD_LEFT);
    }

    /** Desglose tributario: los precios de la tienda ya incluyen IGV (18 %). */
    public function getDesgloseIgvAttribute(): array
    {
        $total    = (float) $this->total;
        $gravada  = round($total / 1.18, 2);

        return [
            'gravada' => $gravada,
            'igv'     => round($total - $gravada, 2),
            'total'   => $total,
        ];
    }

    /** Enlace "click to chat" para escribirle al cliente desde el panel. */
    public function getWhatsappUrlAttribute(): string
    {
        $numero = preg_replace('/\D/', '', (string) $this->cliente_telefono);
        if (strlen($numero) === 9) $numero = '51' . $numero;
        return 'https://wa.me/' . $numero . '?text=' . rawurlencode($this->mensajeEstado());
    }

    /** Texto para avisar al cliente del estado actual de su pedido. */
    public function mensajeEstado(): string
    {
        $nombre = explode(' ', trim($this->cliente_nombre))[0];
        $seguimiento = route('tienda.seguimiento', ['codigo' => $this->codigo_pedido]);
        return match ($this->estado_pedido) {
            'Pagado'    => "Hola {$nombre} 👋 Confirmamos el pago de tu pedido {$this->codigo_pedido} (S/ " . number_format((float) $this->total, 2) . "). Lo estamos preparando 🧀",
            'Enviado'   => "Hola {$nombre} 🚚 Tu pedido {$this->codigo_pedido} ya va en camino. ¡Gracias por elegir ProLech!",
            'Entregado' => "Hola {$nombre} 🏠 Tu pedido {$this->codigo_pedido} fue entregado. ¡Que lo disfrutes! Gracias por comprar en ProLech.",
            'Cancelado' => "Hola {$nombre}. Tu pedido {$this->codigo_pedido} fue cancelado. Si tienes dudas, responde este mensaje.",
            default     => "Hola {$nombre} 🥛 Recibimos tu pedido {$this->codigo_pedido} por S/ " . number_format((float) $this->total, 2) . ". Síguelo aquí: {$seguimiento}",
        };
    }

    /** Devuelve el stock al cancelar (o lo vuelve a descontar si se reactiva). */
    public function moverStock(int $signo): void
    {
        foreach ($this->detalles as $d) {
            ProductoTienda::where('id', $d->producto_tienda_id)
                ->{$signo > 0 ? 'increment' : 'decrement'}('stock_disponible', $d->cantidad);
        }
    }

    /** Estados en el orden del despacho y el siguiente paso de cada uno. */
    public const FLUJO = ['Pendiente' => 'Pagado', 'Pagado' => 'Enviado', 'Enviado' => 'Entregado'];

    public const ACCION_SIGUIENTE = ['Pagado' => '💳 Confirmar pago', 'Enviado' => '🚚 Despachar', 'Entregado' => '🏠 Marcar entregado'];

    public function siguienteEstado(): ?string
    {
        return self::FLUJO[$this->estado_pedido] ?? null;
    }

    /**
     * Marca el pedido como Enviado.
     */
    public function marcarEnviado(): bool
    {
        return $this->update(['estado_pedido' => 'Enviado']);
    }

    /**
     * Marca el pedido como Pagado.
     */
    public function marcarPagado(): bool
    {
        return $this->update(['estado_pedido' => 'Pagado']);
    }
}
