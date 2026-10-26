<?php

namespace Tests\Feature;

use App\Models\Produccion;
use App\Models\ProductoTienda;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Anular una venta (devuelve stock) y comprobante imprimible. */
class AnulacionYComprobanteVentaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ProductoTienda $yogurt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $this->yogurt = ProductoTienda::create(['nombre' => 'Yogurt Fresa 1L', 'precio_venta' => 10.50, 'stock_disponible' => 20, 'estado' => 'activo']);
        Produccion::create(['litros_usados' => 300, 'costo_cuajo' => 1, 'costo_sal' => 1, 'costo_otros' => 1,
            'kg_queso_estimado' => 30, 'fecha_lote' => now()->toDateString(), 'registrado_por' => $this->admin->id]);
    }

    private function como(User $u): static
    {
        return $this->actingAs($u)->withSession(['2fa_verified' => true]);
    }

    private function vender(): Venta
    {
        $this->como($this->admin)->post('/admin/ventas', [
            'cliente' => 'Bodega Don Lucho', 'tipo_cliente' => 'publico', 'destino' => 'Juliaca',
            'fecha_venta' => now()->toDateString(), 'lineas' => [
                ['producto' => 'queso', 'cantidad' => 4],
                ['producto' => (string) $this->yogurt->id, 'cantidad' => 5],
            ],
        ])->assertSessionHas('success');

        return Venta::latest('id')->first();
    }

    public function test_anular_devuelve_el_stock_y_saca_la_venta_de_los_totales(): void
    {
        $venta = $this->vender();
        $this->assertSame(15, $this->yogurt->fresh()->stock_disponible);
        $this->assertEqualsWithDelta(26.0, Produccion::stockActualMoldes(), 0.05);

        $this->como($this->admin)->post("/admin/ventas/{$venta->id}/anular", ['motivo_anulacion' => 'El cliente canceló el pedido'])
             ->assertSessionHas('success');

        $venta->refresh();
        $this->assertTrue($venta->anulada);
        $this->assertSame($this->admin->id, $venta->anulada_por);
        $this->assertSame(20, $this->yogurt->fresh()->stock_disponible);
        $this->assertEqualsWithDelta(30.0, Produccion::stockActualMoldes(), 0.05);
        $this->assertGreaterThan(0, $venta->total, 'el total original se conserva');
        $this->assertSame(0.0, (float) Venta::vigentes()->sum('total'));
        $this->assertDatabaseHas('auditorias', ['entidad_tipo' => 'Venta', 'entidad_id' => $venta->id, 'accion' => 'anulacion']);
    }

    public function test_no_se_anula_dos_veces_ni_devuelve_stock_doble(): void
    {
        $venta = $this->vender();
        $url = "/admin/ventas/{$venta->id}/anular";
        $this->como($this->admin)->post($url, ['motivo_anulacion' => 'Pedido duplicado']);
        $this->como($this->admin)->post($url, ['motivo_anulacion' => 'Pedido duplicado'])->assertSessionHasErrors('anulacion');

        $this->assertSame(20, $this->yogurt->fresh()->stock_disponible);
    }

    public function test_venta_entregada_no_se_puede_anular(): void
    {
        $venta = $this->vender();
        $venta->update(['estado_despacho' => 'entregado']);

        $this->como($this->admin)->post("/admin/ventas/{$venta->id}/anular", ['motivo_anulacion' => 'Error de registro'])
             ->assertSessionHasErrors('anulacion');
        $this->assertFalse($venta->fresh()->anulada);
        $this->assertSame(15, $this->yogurt->fresh()->stock_disponible);
    }

    public function test_motivo_obligatorio(): void
    {
        $venta = $this->vender();
        $this->como($this->admin)->post("/admin/ventas/{$venta->id}/anular", ['motivo_anulacion' => ''])
             ->assertSessionHasErrors('motivo_anulacion');
        $this->assertFalse($venta->fresh()->anulada);
    }

    public function test_personal_de_despacho_no_puede_anular(): void
    {
        $venta = $this->vender();
        $despacho = User::factory()->create(['rol' => 'despacho', 'activo' => true]);

        $this->como($despacho)->post("/admin/ventas/{$venta->id}/anular", ['motivo_anulacion' => 'Quiero anularla'])
             ->assertForbidden();
        $this->assertFalse($venta->fresh()->anulada);
    }

    public function test_venta_anulada_no_se_puede_despachar(): void
    {
        $venta = $this->vender();
        $this->como($this->admin)->post("/admin/ventas/{$venta->id}/anular", ['motivo_anulacion' => 'Cliente canceló']);

        $this->como($this->admin)->post("/admin/ventas/{$venta->id}/despacho", ['estado_despacho' => 'en_camino'])
             ->assertSessionHasErrors('despacho');
        $this->assertSame('preparacion', $venta->fresh()->estado_despacho);
    }

    public function test_comprobante_muestra_lineas_total_y_sello_de_anulada(): void
    {
        $venta = $this->vender();

        $this->como($this->admin)->get("/admin/ventas/{$venta->id}/comprobante")->assertOk()
             ->assertSee('V-' . str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT))
             ->assertSee('Bodega Don Lucho')->assertSee('Queso de planta')->assertSee('Yogurt Fresa 1L')
             ->assertSee(number_format($venta->total, 2))->assertSee('Imprimir')->assertDontSee('ANULADA');

        $this->como($this->admin)->post("/admin/ventas/{$venta->id}/anular", ['motivo_anulacion' => 'Cliente canceló']);
        $this->como($this->admin)->get("/admin/ventas/{$venta->id}/comprobante")->assertOk()
             ->assertSee('ANULADA')->assertSee('Cliente canceló');
    }

    public function test_la_lista_marca_la_anulada_y_ofrece_los_botones(): void
    {
        $vigente = $this->vender();
        $anulada = $this->vender();
        $this->como($this->admin)->post("/admin/ventas/{$anulada->id}/anular", ['motivo_anulacion' => 'Precio mal cobrado']);

        $this->como($this->admin)->get('/admin/ventas')->assertOk()
             ->assertSee('Comprobante')->assertSee('Anular')->assertSee('Anulada')->assertSee('Precio mal cobrado')
             ->assertSee(route('admin.ventas.comprobante', $vigente));
    }
}
