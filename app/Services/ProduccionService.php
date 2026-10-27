<?php

namespace App\Services;

use App\Models\Insumo;
use App\Models\MovimientoInventario;
use App\Models\ProduccionLote;
use App\Models\ProductoTienda;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProduccionService
{
    /**
     * Ejecuta un lote de producción completo dentro de una transacción:
     *
     *  1. Carga la receta con sus detalles e insumos.
     *  2. Aplica regla de 3 para escalar las cantidades al número de unidades deseadas.
     *  3. Verifica stock suficiente en TODOS los insumos antes de tocar nada.
     *  4. Descuenta stock, registra egresos en movimientos_inventario y acumula costo.
     *  5. Crea el ProduccionLote con código auto-generado y costo total.
     *
     * @param  int    $recetaId          ID de la receta a ejecutar.
     * @param  int    $cantidadProducida Unidades/moldes a producir.
     * @param  User   $responsable       Usuario que registra el lote.
     * @param  string|null $observaciones Notas opcionales.
     * @return ProduccionLote            El lote recién creado.
     *
     * @throws \RuntimeException  Si algún insumo no tiene stock suficiente (provoca rollback).
     */
    /**
     * Orden de producción con VARIOS productos: crea un lote por producto, todos con el
     * mismo código ORD-AAAAMMDD-NNN. Es todo o nada: si los insumos compartidos (p. ej. la
     * leche cruda) no alcanzan para la suma de las líneas, no se registra ninguna.
     *
     * @param  array<int, array{receta_id:int, cantidad:int}>  $lineas
     * @return \Illuminate\Support\Collection<int, ProduccionLote>
     */
    public function registrarOrden(array $lineas, User $responsable, ?string $observaciones = null): \Illuminate\Support\Collection
    {
        // Misma receta dos veces → se suma en una sola línea
        $porReceta = [];
        foreach ($lineas as $l) {
            $porReceta[(int) $l['receta_id']] = ($porReceta[(int) $l['receta_id']] ?? 0) + (int) $l['cantidad'];
        }
        if ($porReceta === []) {
            throw new \RuntimeException('Agrega al menos un producto a la orden.');
        }

        return DB::transaction(function () use ($porReceta, $responsable, $observaciones) {
            $orden = count($porReceta) > 1 ? ProduccionLote::generarCodigoOrden() : null;
            $lotes = collect();
            foreach ($porReceta as $recetaId => $cantidad) {
                // Cada lote descuenta su stock; el siguiente ya ve el stock reducido
                $lote = $this->registrarLote($recetaId, $cantidad, $responsable, $observaciones);
                if ($orden) {
                    $lote->update(['orden_codigo' => $orden]);
                }
                $lotes->push($lote);
            }
            return $lotes;
        });
    }

    public function registrarLote(
        int    $recetaId,
        int    $cantidadProducida,
        User   $responsable,
        ?string $observaciones = null
    ): ProduccionLote {

        return DB::transaction(function () use (
            $recetaId,
            $cantidadProducida,
            $responsable,
            $observaciones
        ) {
            // ── 1. Cargar receta con detalles e insumos (lock optimista) ──────
            /** @var Receta $receta */
            $receta = Receta::with('detalles.insumo')->findOrFail($recetaId);

            if ($receta->detalles->isEmpty()) {
                throw new \RuntimeException(
                    "La receta \"{$receta->nombre}\" no tiene ingredientes configurados."
                );
            }

            // ── 2. Escalar ingredientes (regla de 3) ──────────────────────────
            //   cantidad_necesaria = (cantidad_base / rendimiento_base) * unidades_deseadas
            $insumosProcesados = [];

            foreach ($receta->detalles as $detalle) {
                $cantidadNecesaria = round(
                    ((float) $detalle->cantidad_requerida / $receta->rendimiento_esperado_unidades)
                    * $cantidadProducida,
                    4
                );

                $insumosProcesados[] = [
                    'insumo'     => $detalle->insumo,
                    'cantidad'   => $cantidadNecesaria,
                    'costo_parcial' => round(
                        $cantidadNecesaria * (float) $detalle->insumo->costo_unitario,
                        4
                    ),
                ];
            }

            // ── 3. Verificar stock ANTES de modificar nada ───────────────────
            //   Si cualquier insumo falla → Exception → rollback automático
            foreach ($insumosProcesados as $item) {
                /** @var Insumo $insumo */
                $insumo = $item['insumo'];

                // Re-leer con bloqueo pesimista para evitar race conditions
                $stockActual = (float) Insumo::lockForUpdate()->find($insumo->id)->stock_actual;

                if ($stockActual < $item['cantidad']) {
                    $faltante = round($item['cantidad'] - $stockActual, 4);
                    throw new \RuntimeException(
                        "Stock insuficiente para \"{$insumo->nombre}\": " .
                        "se necesitan {$item['cantidad']} {$insumo->unidad_medida}, " .
                        "hay {$stockActual} {$insumo->unidad_medida} " .
                        "(faltan {$faltante} {$insumo->unidad_medida})."
                    );
                }
            }

            // ── 4. Descontar stock + registrar egresos + acumular costo ──────
            $costoTotal = 0.0;
            $fechaHoy   = now()->toDateString();

            // Código del lote generado antes de crear el registro
            $codigoLote = ProduccionLote::generarCodigo($fechaHoy);

            foreach ($insumosProcesados as $item) {
                /** @var Insumo $insumo */
                $insumo = $item['insumo'];

                // Descontar del stock (UPDATE atómico)
                Insumo::where('id', $insumo->id)
                      ->decrement('stock_actual', $item['cantidad']);

                // Registrar egreso en trazabilidad
                MovimientoInventario::create([
                    'insumo_id'       => $insumo->id,
                    'tipo'            => 'egreso',
                    'cantidad'        => $item['cantidad'],
                    'motivo'          => "Consumo lote {$codigoLote}",
                    'referencia_id'   => null,   // se actualiza abajo tras crear el lote
                    'referencia_tipo' => ProduccionLote::class,
                    'registrado_por'  => $responsable->id,
                ]);

                $costoTotal += $item['costo_parcial'];
            }

            // ── 5. Crear el ProduccionLote ────────────────────────────────────
            $lote = ProduccionLote::create([
                'codigo_lote'        => $codigoLote,
                'receta_id'          => $receta->id,
                'fecha_produccion'   => $fechaHoy,
                // Vence según la vida útil de la receta (Queso Fresco 7 días, Paria 30…)
                'fecha_vencimiento'  => StockLotes::fechaVencimiento($fechaHoy, (int) ($receta->dias_vida_util ?: 15)),
                'cantidad_producida' => $cantidadProducida,
                'costo_total'        => round($costoTotal, 2),
                'responsable_id'     => $responsable->id,
                'observaciones'      => $observaciones,
            ]);

            // Actualizar referencia_id en los movimientos recién creados
            MovimientoInventario::where('referencia_tipo', ProduccionLote::class)
                ->where('motivo', "Consumo lote {$codigoLote}")
                ->update(['referencia_id' => $lote->id]);

            // ── 6. Incrementar stock en la Tienda si hay producto vinculado ──
            // Si la receta tiene un ProductoTienda asociado, se suma el stock
            // automáticamente al terminar el lote (sin intervención manual).
            $productoTienda = ProductoTienda::where('receta_id', $receta->id)->first();
            if ($productoTienda) {
                ProductoTienda::where('id', $productoTienda->id)
                              ->increment('stock_disponible', $cantidadProducida);
            }

            return $lote->load('receta', 'responsable');
        });
    }

    /**
     * Registra un ingreso de insumos al almacén (compra / ajuste).
     *
     * @param  int    $insumoId
     * @param  float  $cantidad
     * @param  string $motivo
     * @param  User   $responsable
     * @return MovimientoInventario
     */
    public function registrarIngreso(
        int    $insumoId,
        float  $cantidad,
        string $motivo,
        User   $responsable
    ): MovimientoInventario {

        return DB::transaction(function () use ($insumoId, $cantidad, $motivo, $responsable) {
            Insumo::where('id', $insumoId)->increment('stock_actual', $cantidad);

            return MovimientoInventario::create([
                'insumo_id'       => $insumoId,
                'tipo'            => 'ingreso',
                'cantidad'        => $cantidad,
                'motivo'          => $motivo,
                'referencia_id'   => null,
                'referencia_tipo' => null,
                'registrado_por'  => $responsable->id,
            ]);
        });
    }

    /**
     * Devuelve una proyección de cuántos insumos se consumirían si se producen
     * $cantidadProducida unidades con la receta dada. No modifica nada.
     *
     * @return array<int, array{insumo_id, nombre, unidad_medida, cantidad_necesaria, stock_actual, alcanza, faltante}>
     */
    public function proyectarConsumo(int $recetaId, int $cantidadProducida): array
    {
        $receta = Receta::with('detalles.insumo')->findOrFail($recetaId);

        $proyeccion = [];

        foreach ($receta->detalles as $detalle) {
            $cantidadNecesaria = round(
                ((float) $detalle->cantidad_requerida / $receta->rendimiento_esperado_unidades)
                * $cantidadProducida,
                4
            );

            $stockActual = (float) $detalle->insumo->stock_actual;
            $alcanza     = $stockActual >= $cantidadNecesaria;

            $proyeccion[] = [
                'insumo_id'         => $detalle->insumo->id,
                'nombre'            => $detalle->insumo->nombre,
                'unidad_medida'     => $detalle->insumo->unidad_medida,
                'cantidad_necesaria' => $cantidadNecesaria,
                'costo_unitario'    => (float) $detalle->insumo->costo_unitario,
                'costo_parcial'     => round($cantidadNecesaria * (float) $detalle->insumo->costo_unitario, 2),
                'stock_actual'      => $stockActual,
                'alcanza'           => $alcanza,
                'faltante'          => $alcanza ? 0 : round($cantidadNecesaria - $stockActual, 4),
            ];
        }

        return $proyeccion;
    }
}
