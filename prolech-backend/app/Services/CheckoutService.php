<?php

namespace App\Services;

use App\Models\PedidoDetalle;
use App\Models\PedidoWeb;
use App\Models\ProductoTienda;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * Procesa un pedido web completo dentro de una transacción:
     *
     *  1. Valida que cada producto exista, esté activo y tenga stock.
     *  2. Descuenta el stock con bloqueo pesimista (lockForUpdate).
     *  3. Crea el PedidoWeb con código correlativo WEB-NNNN.
     *  4. Inserta todas las líneas de PedidoDetalle.
     *  5. Guarda el comprobante Yape si viene adjunto.
     *
     * @param  array  $datosCliente  Campos validados del formulario de checkout.
     * @param  array  $items         [{id, nombre, precio, cantidad}, …] del carrito.
     * @param  string|null $comprobanteYapePath  Path ya almacenado en disco "public".
     * @return PedidoWeb             El pedido recién creado con sus detalles cargados.
     *
     * @throws \RuntimeException  Si algún producto no existe, está inactivo o sin stock.
     * @throws \Throwable         Cualquier error de BD (dispara rollback automático).
     */
    public function procesarPedido(
        array   $datosCliente,
        array   $items,
        ?string $comprobanteYapePath = null
    ): PedidoWeb {

        return DB::transaction(function () use ($datosCliente, $items, $comprobanteYapePath) {

            // ── 1. Cargar y validar todos los productos ANTES de tocar nada ───
            $lineas = [];
            $total  = 0.0;

            foreach ($items as $item) {
                $id       = (int) $item['id'];
                $cantidad = (int) $item['cantidad'];

                /** @var ProductoTienda $producto */
                $producto = ProductoTienda::lockForUpdate()->find($id);

                if (! $producto) {
                    throw new \RuntimeException(
                        "El producto con ID {$id} ya no existe en el catálogo."
                    );
                }

                if ($producto->estado !== 'activo') {
                    throw new \RuntimeException(
                        "El producto \"{$producto->nombre}\" ya no está disponible."
                    );
                }

                if ($producto->stock_disponible < $cantidad) {
                    throw new \RuntimeException(
                        "Stock insuficiente para \"{$producto->nombre}\": " .
                        "pediste {$cantidad}, hay {$producto->stock_disponible} disponibles."
                    );
                }

                $precioCapturado = (float) $producto->precio_venta;
                $subtotal        = round($precioCapturado * $cantidad, 2);

                $lineas[] = [
                    'producto'        => $producto,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioCapturado,
                    'subtotal'        => $subtotal,
                ];

                $total += $subtotal;
            }

            // ── 2. Crear el PedidoWeb ─────────────────────────────────────────
            $pedido = PedidoWeb::create([
                'codigo_pedido'        => PedidoWeb::generarCodigo(),
                'numero_comprobante'   => PedidoWeb::generarNumeroBoleta(),
                'cliente_documento'    => $datosCliente['cliente_documento'] ?? null,
                'pago_referencia'      => $datosCliente['pago_referencia'] ?? null,
                'cliente_nombre'       => $datosCliente['cliente_nombre'],
                'cliente_telefono'     => $datosCliente['cliente_telefono'],
                'cliente_direccion'    => $datosCliente['cliente_direccion'],
                'cliente_email'        => $datosCliente['cliente_email'] ?? null,
                'total'                => round($total, 2),
                'metodo_pago'          => $datosCliente['metodo_pago'],
                // La tarjeta se aprueba en la pasarela (demo); Yape y efectivo esperan verificación
                'estado_pedido'        => $datosCliente['metodo_pago'] === 'Tarjeta' ? 'Pagado' : 'Pendiente',
                'comprobante_yape_url' => $comprobanteYapePath,
                'notas'                => $datosCliente['notas'] ?? null,
            ]);

            // ── 3. Insertar detalles y descontar stock ────────────────────────
            foreach ($lineas as $linea) {
                /** @var ProductoTienda $producto */
                $producto = $linea['producto'];

                PedidoDetalle::create([
                    'pedido_web_id'      => $pedido->id,
                    'producto_tienda_id' => $producto->id,
                    'cantidad'           => $linea['cantidad'],
                    'precio_unitario'    => $linea['precio_unitario'],
                    'subtotal'           => $linea['subtotal'],
                ]);

                // Decremento atómico (UPDATE ... SET stock = stock - N WHERE id = X)
                ProductoTienda::where('id', $producto->id)
                               ->decrement('stock_disponible', $linea['cantidad']);
            }

            return $pedido->load('detalles.producto');
        });
    }
}
