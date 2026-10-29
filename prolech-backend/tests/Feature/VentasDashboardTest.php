<?php

namespace Tests\Feature;

use App\Models\PedidoWeb;
use App\Models\ProductoTienda;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentasDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Dashboard de ventas: suma planta + tienda web y no cuenta anuladas ni canceladas. */
class VentasDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_indicadores_suman_planta_y_web_sin_anuladas_ni_canceladas(): void
    {
        $u = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $base = ['tipo_cliente' => 'publico', 'destino' => 'Planta', 'cantidad_kg' => 1, 'precio_unitario' => 20,
                 'estado_despacho' => 'preparacion', 'fecha_venta' => today(), 'registrado_por' => $u->id];
        $v = new Venta($base + ['cliente' => 'Ana', 'total' => 100]); $v->totalDesdeDetalles = true; $v->save();
        $a = new Venta($base + ['cliente' => 'Anulada', 'total' => 999]); $a->totalDesdeDetalles = true; $a->save();
        $a->update(['anulada_at' => now(), 'anulada_por' => $u->id, 'motivo_anulacion' => 'error de prueba']);
        foreach ([['WEB-1', 50, 'Pagado'], ['WEB-2', 70, 'Cancelado']] as [$c, $t, $e]) {
            PedidoWeb::create(['codigo_pedido' => $c, 'cliente_nombre' => 'Rosa', 'cliente_telefono' => '999', 'cliente_direccion' => 'Huata',
                'total' => $t, 'metodo_pago' => 'Efectivo', 'estado_pedido' => $e]);
        }

        $d = app(VentasDashboard::class)->datos();

        $this->assertSame(150.0, $d['hoy']['monto']);
        $this->assertSame(2, $d['hoy']['num']);
        $this->assertSame(['Ana', 'Rosa'], $d['topClientes']->pluck('nombre')->sort()->values()->all());
        $this->assertSame(150.0, array_sum($d['serie']['planta']) + array_sum($d['serie']['web']));
    }

    public function test_la_pagina_de_ventas_muestra_el_dashboard(): void
    {
        $u = User::factory()->create(['rol' => 'despacho', 'activo' => true]);
        ProductoTienda::create(['nombre' => 'Queso Fresco', 'precio_venta' => 16, 'stock_disponible' => 2, 'estado' => 'activo']);

        $this->actingAs($u)->withSession(['2fa_verified' => true])->get('/admin/ventas')->assertOk()
            ->assertSee('Ventas de hoy')->assertSee('Más vendidos')->assertSee('Poco stock en la tienda')
            ->assertSee('id="nueva-venta"', false)->assertDontSee('Pedidos web <');   // despacho no ve botones de admin
    }
}
