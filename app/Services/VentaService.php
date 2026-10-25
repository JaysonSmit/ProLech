<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Produccion;
use App\Models\ProductoTienda;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Venta con carrito: varias líneas (queso de planta por kg y/o productos publicados).
 * Todo en una transacción: si una línea no tiene stock, no se guarda nada.
 */
class VentaService
{
    public const QUESO_PLANTA = 'queso';

    /** Precio por unidad según el tipo de cliente. */
    public function precio(string $tipoCliente, ?ProductoTienda $producto): float
    {
        if ($producto === null) {
            return Venta::precioParaTipo($tipoCliente);
        }
        $precio = (float) $producto->precio_venta;
        if ($tipoCliente === 'mayorista') {
            $descuento = (float) Configuracion::get('descuento_mayorista_productos_pct', 10);
            $precio = $precio * (1 - $descuento / 100);
        }
        return round($precio, 2);
    }

    /** Catálogo para el formulario: queso de planta + productos activos con stock. */
    public function catalogo(): array
    {
        $items = [[
            'clave'     => self::QUESO_PLANTA,
            'nombre'    => 'Queso de planta (por kg / molde)',
            'unidad'    => 'kg',
            'stock'     => Produccion::stockActualMoldes(),
            'mayorista' => $this->precio('mayorista', null),
            'publico'   => $this->precio('publico', null),
            'decimales' => true,
        ]];
        foreach (ProductoTienda::where('estado', 'activo')->orderBy('nombre')->get() as $p) {
            $items[] = [
                'clave'     => (string) $p->id,
                'nombre'    => $p->nombre,
                'unidad'    => 'und',
                'stock'     => (int) $p->stock_disponible,
                'mayorista' => $this->precio('mayorista', $p),
                'publico'   => $this->precio('publico', $p),
                'decimales' => false,
            ];
        }
        return $items;
    }

    /**
     * @param  array  $datos   cliente, tipo_cliente, destino, fecha_venta, guia_remision, notas_despacho
     * @param  array  $lineas  [['producto' => 'queso'|id, 'cantidad' => n], ...]
     * @throws \RuntimeException  sin stock / producto inactivo
     */
    public function registrar(array $datos, array $lineas, User $usuario): Venta
    {
        return DB::transaction(function () use ($datos, $lineas, $usuario) {
            // Agrupa líneas repetidas del mismo producto
            $agrupadas = [];
            foreach ($lineas as $l) {
                $clave = (string) $l['producto'];
                $agrupadas[$clave] = ($agrupadas[$clave] ?? 0) + (float) $l['cantidad'];
            }

            $detalles = [];
            $kgQueso = 0.0;
            foreach ($agrupadas as $clave => $cantidad) {
                if ($clave === self::QUESO_PLANTA) {
                    $disponible = Produccion::stockActualMoldes();
                    if ($cantidad > $disponible) {
                        throw new \RuntimeException("Stock insuficiente de queso de planta: pediste {$cantidad} kg, hay {$disponible} kg.");
                    }
                    $kgQueso = $cantidad;
                    $precio = $this->precio($datos['tipo_cliente'], null);
                    $detalles[] = ['producto_tienda_id' => null, 'descripcion' => 'Queso de planta', 'unidad' => 'kg',
                        'cantidad' => $cantidad, 'precio_unitario' => $precio, 'subtotal' => round($cantidad * $precio, 2)];
                    continue;
                }

                /** @var ProductoTienda|null $producto */
                $producto = ProductoTienda::lockForUpdate()->find((int) $clave);
                if (! $producto || $producto->estado !== 'activo') {
                    throw new \RuntimeException('Uno de los productos ya no está disponible.');
                }
                if (floor($cantidad) != $cantidad) {
                    throw new \RuntimeException("\"{$producto->nombre}\" se vende por unidades enteras.");
                }
                if ($producto->stock_disponible < $cantidad) {
                    throw new \RuntimeException("Stock insuficiente de \"{$producto->nombre}\": pediste {$cantidad}, hay {$producto->stock_disponible}.");
                }
                $producto->decrement('stock_disponible', (int) $cantidad);
                $precio = $this->precio($datos['tipo_cliente'], $producto);
                $detalles[] = ['producto_tienda_id' => $producto->id, 'descripcion' => $producto->nombre, 'unidad' => 'und',
                    'cantidad' => $cantidad, 'precio_unitario' => $precio, 'subtotal' => round($cantidad * $precio, 2)];
            }

            $venta = new Venta(array_merge($datos, [
                'cantidad_kg'     => $kgQueso,   // el stock de queso de planta se calcula con esta columna
                'precio_unitario' => $this->precio($datos['tipo_cliente'], null),
                'registrado_por'  => $usuario->id,
            ]));
            $venta->totalDesdeDetalles = true;
            $venta->total = round(collect($detalles)->sum('subtotal'), 2);
            $venta->save();
            $venta->detalles()->createMany($detalles);

            return $venta->load('detalles');
        });
    }
}
