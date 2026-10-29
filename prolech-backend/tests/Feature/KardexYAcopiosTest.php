<?php

namespace Tests\Feature;

use App\Models\Acopio;
use App\Models\Ganadero;
use App\Models\Insumo;
use App\Models\MovimientoInventario;
use App\Models\ProduccionLote;
use App\Models\ProductoTienda;
use App\Models\Receta;
use App\Models\User;
use App\Services\Kardex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kárdex valorizado (promedio ponderado), listado de entregas y paginación del panel. */
class KardexYAcopiosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->actingAs(User::factory()->create(['rol' => 'admin', 'activo' => true]))->withSession(['2fa_verified' => true]);
    }

    public function test_kardex_de_insumo_valoriza_al_costo_promedio(): void
    {
        $insumo = Insumo::create(['nombre' => 'Cuajo Líquido', 'unidad_medida' => 'mL', 'costo_unitario' => 0.10, 'stock_actual' => 150, 'stock_minimo' => 10, 'activo' => true]);
        MovimientoInventario::create(['insumo_id' => $insumo->id, 'tipo' => 'ingreso', 'cantidad' => 200, 'motivo' => 'Compra']);
        MovimientoInventario::create(['insumo_id' => $insumo->id, 'tipo' => 'egreso', 'cantidad' => 50, 'motivo' => 'Lote']);

        $filas = app(Kardex::class)->insumo($insumo);
        $this->assertCount(2, $filas);
        $this->assertEquals(20.0, $filas[0]['total']);           // 200 × 0.10
        $this->assertEquals(5.0, $filas[1]['total']);            // 50 al costo promedio 0.10
        $this->assertEquals(150, $filas[1]['saldo_cant']);
        $this->assertEquals(15.0, $filas[1]['saldo_valor']);

        $this->admin()->get('/admin/kardex/insumos?id=' . $insumo->id)->assertOk()->assertSee('Kárdex valorizado de insumos')->assertSee('Mostrando');
    }

    public function test_kardex_de_productos_entra_por_lotes_y_sale_por_tienda(): void
    {
        $receta = Receta::create(['nombre' => 'Queso Fresco', 'rendimiento_esperado_unidades' => 1, 'activa' => true]);
        $p = ProductoTienda::create(['nombre' => 'Queso Fresco', 'precio_venta' => 16, 'stock_disponible' => 8, 'estado' => 'activo', 'receta_id' => $receta->id]);
        ProduccionLote::create(['codigo_lote' => 'L-1', 'receta_id' => $receta->id, 'fecha_produccion' => today()->subDay(), 'cantidad_producida' => 10, 'costo_total' => 100, 'responsable_id' => User::factory()->create()->id]);

        $filas = app(Kardex::class)->producto($p);
        $this->assertEquals(10.0, $filas[0]['costo_unit']);
        $this->assertEquals(10, $filas->last()['saldo_cant']);
        $this->admin()->get('/admin/kardex/productos?id=' . $p->id)->assertOk()->assertSee('L-1');
    }

    public function test_listado_de_entregas_filtra_y_pagina(): void
    {
        $g = Ganadero::create(['dni' => '123', 'nombre' => 'Rosa Quispe', 'activo' => true, 'estado' => 'Activo']);
        $chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        foreach (range(1, 18) as $i) {
            Acopio::create(['ganadero_id' => $g->id, 'chofer_id' => $chofer->id, 'litros' => 20, 'estado' => $i === 1 ? 'observado' : 'aprobado', 'fecha' => today()->subDays($i % 5)]);
        }
        $this->admin()->get('/admin/acopios')->assertOk()->assertSee('Mostrando')->assertSee('de <strong style="color:#0f172a">18</strong> resultados', false);
        $this->admin()->get('/admin/acopios?estado=observado')->assertOk()->assertSee('de <strong style="color:#0f172a">1</strong> resultados', false);
    }
}
