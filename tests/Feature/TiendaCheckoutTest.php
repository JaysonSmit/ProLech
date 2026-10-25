<?php

namespace Tests\Feature;

use App\Models\PedidoWeb;
use App\Models\ProductoTienda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Tienda pública: catálogo, checkout con métodos de pago y boleta. */
class TiendaCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function producto(float $precio = 12.0, int $stock = 10): ProductoTienda
    {
        return ProductoTienda::create([
            'nombre' => 'Manjar Blanco 1kg', 'precio_venta' => $precio,
            'stock_disponible' => $stock, 'estado' => 'activo',
        ]);
    }

    private function datos(ProductoTienda $p, int $cantidad, array $extra = []): array
    {
        return array_merge([
            'cliente_nombre'    => 'María Quispe',
            'cliente_telefono'  => '999888777',
            'cliente_direccion' => 'Jr. Lima 123, Huata',
            'metodo_pago'       => 'Efectivo',
            'carrito'           => json_encode([['id' => $p->id, 'cantidad' => $cantidad]]),
        ], $extra);
    }

    public function test_catalogo_y_detalle_cargan(): void
    {
        $p = $this->producto();

        $this->get('/tienda')->assertOk()->assertSee('Manjar Blanco 1kg');
        $this->get('/tienda/show/' . $p->slug)->assertOk()->assertSee('Añadir al carrito');
        $this->get('/tienda/checkout')->assertOk()->assertSee('Finalizar compra');
    }

    public function test_pedido_en_efectivo_genera_boleta_correlativa_y_descuenta_stock(): void
    {
        $p = $this->producto();

        $this->post('/tienda/checkout', $this->datos($p, 2))->assertRedirect();
        $this->post('/tienda/checkout', $this->datos($p, 1))->assertRedirect();

        $pedidos = PedidoWeb::orderBy('id')->get();
        $this->assertSame(['B001-00000001', 'B001-00000002'], $pedidos->pluck('numero_comprobante')->all());
        $this->assertSame('Pendiente', $pedidos[0]->estado_pedido);
        $this->assertSame(7, $p->fresh()->stock_disponible);
        $this->assertEquals(24.00, (float) $pedidos[0]->total);
    }

    public function test_tarjeta_queda_pagada_y_solo_guarda_ultimos_4_digitos(): void
    {
        $p = $this->producto();

        $this->post('/tienda/checkout', $this->datos($p, 1, [
            'metodo_pago' => 'Tarjeta', 'pago_referencia' => 'VISA •••• 4242',
        ]))->assertRedirect();
        $this->post('/tienda/checkout', $this->datos($p, 1, [
            'metodo_pago' => 'Tarjeta', 'pago_referencia' => '4242424242424242',
        ]))->assertRedirect();

        [$ok, $sospechoso] = PedidoWeb::orderBy('id')->get()->all();
        $this->assertSame('Pagado', $ok->estado_pedido);
        $this->assertSame('VISA •••• 4242', $ok->pago_referencia);
        $this->assertNull($sospechoso->pago_referencia, 'nunca se guarda el número completo');
    }

    public function test_yape_exige_captura(): void
    {
        $p = $this->producto();

        $this->post('/tienda/checkout', $this->datos($p, 1, ['metodo_pago' => 'Yape']))
             ->assertSessionHasErrors('comprobante_yape');

        Storage::fake('public');
        $this->post('/tienda/checkout', $this->datos($p, 1, [
            'metodo_pago' => 'Yape',
            'comprobante_yape' => UploadedFile::fake()->image('yape.jpg'),
        ]))->assertRedirect();
        $this->assertNotNull(PedidoWeb::first()->comprobante_yape_url);
    }

    public function test_desde_700_soles_exige_dni(): void
    {
        $p = $this->producto(100, 20);

        $this->post('/tienda/checkout', $this->datos($p, 7))->assertSessionHasErrors('cliente_documento');
        $this->post('/tienda/checkout', $this->datos($p, 7, ['cliente_documento' => '12345678']))->assertRedirect();
        $this->assertSame('12345678', PedidoWeb::first()->cliente_documento);
    }

    public function test_stock_insuficiente_no_crea_pedido(): void
    {
        $p = $this->producto(12, 1);

        $this->post('/tienda/checkout', $this->datos($p, 5))->assertSessionHasErrors('checkout');
        $this->assertSame(0, PedidoWeb::count());
        $this->assertSame(1, $p->fresh()->stock_disponible);
    }

    public function test_boleta_requiere_firma_y_muestra_igv_e_importe_en_letras(): void
    {
        $p = $this->producto(12);
        $this->post('/tienda/checkout', $this->datos($p, 1));
        $pedido = PedidoWeb::first();

        $this->get('/tienda/boleta/' . $pedido->codigo_pedido)->assertForbidden();

        $this->get(URL::signedRoute('tienda.boleta', ['codigo' => $pedido->codigo_pedido]))
             ->assertOk()
             ->assertSee('B001-00000001')
             ->assertSee('10.17')   // op. gravada = 12 / 1.18
             ->assertSee('1.83')    // IGV
             ->assertSee('DOCE Y 00/100 SOLES');
    }

    public function test_confirmacion_muestra_enlace_a_la_boleta(): void
    {
        $p = $this->producto();
        $respuesta = $this->post('/tienda/checkout', $this->datos($p, 1));

        $this->get($respuesta->headers->get('Location'))
             ->assertOk()
             ->assertSee('Ver / descargar boleta')
             ->assertSee('B001-00000001');
    }
}
