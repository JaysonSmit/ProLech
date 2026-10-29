<?php

namespace Tests\Feature;

use App\Models\PedidoWeb;
use App\Models\ProductoTienda;
use App\Models\User;
use App\Services\FacturacionElectronicaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Seguimiento del pedido, devolución de stock al cancelar y boleta SUNAT (Nubefact). */
class TiendaSeguimientoYSunatTest extends TestCase
{
    use RefreshDatabase;

    private function comprar(int $cantidad = 2, array $extra = []): PedidoWeb
    {
        $p = ProductoTienda::firstOrCreate(['nombre' => 'Queso Paria'], ['precio_venta' => 18, 'stock_disponible' => 10, 'estado' => 'activo']);
        $this->post('/tienda/checkout', array_merge([
            'cliente_nombre' => 'María Quispe', 'cliente_telefono' => '987 654 321',
            'cliente_direccion' => 'Huata', 'metodo_pago' => 'Efectivo',
            'carrito' => json_encode([['id' => $p->id, 'cantidad' => $cantidad]]),
        ], $extra))->assertRedirect();
        return PedidoWeb::latest('id')->first();
    }

    private function admin(): static
    {
        return $this->actingAs(User::factory()->create(['rol' => 'admin', 'activo' => true]))
                    ->withSession(['2fa_verified' => true]);
    }

    public function test_sin_nubefact_la_boleta_queda_como_interna(): void
    {
        $pedido = $this->comprar();

        $this->assertSame('simulada', $pedido->fresh()->sunat_estado);
    }

    public function test_seguimiento_exige_codigo_y_celular(): void
    {
        $pedido = $this->comprar();

        $this->get('/tienda/seguimiento?codigo=' . $pedido->codigo_pedido . '&telefono=321')
             ->assertOk()->assertSee('Recibido')->assertSee('Queso Paria');
        $this->get('/tienda/seguimiento?codigo=' . $pedido->codigo_pedido . '&telefono=000')
             ->assertOk()->assertSee('No encontramos')->assertDontSee('Queso Paria');
        $this->get('/tienda/seguimiento')->assertOk()->assertSee('Seguir mi pedido');
    }

    public function test_cancelar_devuelve_el_stock_y_reactivar_lo_descuenta(): void
    {
        $pedido = $this->comprar(3);
        $producto = ProductoTienda::first();
        $this->assertSame(7, $producto->fresh()->stock_disponible);

        $this->admin()->post("/admin/tienda/pedidos/{$pedido->id}/estado", ['estado_pedido' => 'Cancelado'])->assertRedirect();
        $this->assertSame(10, $producto->fresh()->stock_disponible);

        $this->admin()->post("/admin/tienda/pedidos/{$pedido->id}/estado", ['estado_pedido' => 'Pagado'])->assertRedirect();
        $this->assertSame(7, $producto->fresh()->stock_disponible);
    }

    public function test_no_reactiva_si_ya_no_hay_stock(): void
    {
        $pedido = $this->comprar(3);
        $this->admin()->post("/admin/tienda/pedidos/{$pedido->id}/estado", ['estado_pedido' => 'Cancelado']);
        ProductoTienda::first()->update(['stock_disponible' => 1]);

        $this->admin()->post("/admin/tienda/pedidos/{$pedido->id}/estado", ['estado_pedido' => 'Pagado'])
             ->assertSessionHasErrors('estado_pedido');
        $this->assertSame('Cancelado', $pedido->fresh()->estado_pedido);
    }

    public function test_mensajes_de_whatsapp_por_estado(): void
    {
        $pedido = $this->comprar();
        $this->assertStringContainsString('Recibimos tu pedido', $pedido->mensajeEstado());
        $this->assertStringStartsWith('https://wa.me/51987654321?text=', $pedido->whatsapp_url);

        $pedido->update(['estado_pedido' => 'Enviado']);
        $this->assertStringContainsString('va en camino', $pedido->mensajeEstado());
    }

    public function test_payload_de_nubefact_cuadra_con_la_boleta(): void
    {
        $pedido = $this->comprar(2, ['cliente_documento' => '12345678']);

        $json = app(FacturacionElectronicaService::class)->payloadBoleta($pedido);

        $this->assertSame(2, $json['tipo_de_comprobante']);
        $this->assertSame('B001', $json['serie']);
        $this->assertSame(1, $json['numero']);
        $this->assertSame(1, $json['cliente_tipo_de_documento']);
        $this->assertEquals(36.00, $json['total']);
        $this->assertEquals(30.51, $json['total_gravada']);
        $this->assertEquals(5.49, $json['total_igv']);
        $this->assertEqualsWithDelta($json['total'], collect($json['items'])->sum('total'), 0.001);
    }

    public function test_envio_real_a_nubefact_aceptado_y_rechazado(): void
    {
        config(['services.nubefact.url' => 'https://api.nubefact.test/abc', 'services.nubefact.token' => 'token-prueba']);
        Http::fake([
            'api.nubefact.test/*' => Http::sequence()
                ->push(['aceptada_por_sunat' => true, 'enlace_del_pdf' => 'https://nubefact.test/b001-1.pdf', 'sunat_description' => 'La Boleta numero B001-1, ha sido aceptada'])
                ->push(['errors' => 'El número de documento ya existe'], 422),
        ]);

        $ok = $this->comprar();
        $this->assertSame('aceptada', $ok->fresh()->sunat_estado);
        $this->assertSame('https://nubefact.test/b001-1.pdf', $ok->fresh()->sunat_pdf_url);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer token-prueba') && $r['serie'] === 'B001');

        $mal = $this->comprar(1);
        $this->assertSame('rechazada', $mal->fresh()->sunat_estado, (string) $mal->fresh()->sunat_mensaje);
        $this->assertStringContainsString('ya existe', $mal->fresh()->sunat_mensaje);
    }
}
