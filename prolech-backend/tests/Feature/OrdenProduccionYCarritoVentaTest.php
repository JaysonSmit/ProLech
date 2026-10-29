<?php

namespace Tests\Feature;

use App\Models\Insumo;
use App\Models\Produccion;
use App\Models\ProduccionLote;
use App\Models\ProductoTienda;
use App\Models\Receta;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\ProduccionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Producción de varios productos en una orden y ventas con carrito. */
class OrdenProduccionYCarritoVentaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProduccionSeeder::class);
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
    }

    private function comoAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true]);
    }

    private function receta(string $nombre): Receta
    {
        return Receta::where('nombre', $nombre)->firstOrFail();
    }

    private function leche(): float
    {
        return (float) Insumo::where('nombre', 'Leche Cruda')->value('stock_actual');
    }

    /** Litros de leche que pide una receta para N unidades. */
    private function lecheDe(Receta $r, int $n): float
    {
        $d = $r->detalles()->whereHas('insumo', fn ($q) => $q->where('nombre', 'Leche Cruda'))->first();
        return $d ? (float) $d->cantidad_requerida / $r->rendimiento_esperado_unidades * $n : 0.0;
    }

    // ── Producción ─────────────────────────────────────────────────────────

    public function test_una_orden_con_dos_productos_crea_dos_lotes_y_suma_los_insumos(): void
    {
        $paria = $this->receta('Queso Tipo Paria');
        $yogurt = $this->receta('Yogurt Natural 1L');
        $antes = $this->leche();

        $this->comoAdmin()->post('/admin/produccion/lotes', ['lineas' => [
            ['receta_id' => $paria->id, 'cantidad' => 10],
            ['receta_id' => $yogurt->id, 'cantidad' => 20],
        ], 'observaciones' => 'Turno mañana'])->assertRedirect()->assertSessionHas('success');

        $lotes = ProduccionLote::orderBy('id')->get();
        $this->assertCount(2, $lotes);
        $this->assertNotNull($lotes[0]->orden_codigo);
        $this->assertSame($lotes[0]->orden_codigo, $lotes[1]->orden_codigo);
        $this->assertStringStartsWith('ORD-', $lotes[0]->orden_codigo);
        $this->assertEqualsWithDelta($antes - $this->lecheDe($paria, 10) - $this->lecheDe($yogurt, 20), $this->leche(), 0.01);
    }

    public function test_si_la_leche_no_alcanza_para_la_suma_no_se_registra_nada(): void
    {
        $paria = $this->receta('Queso Tipo Paria');
        $fresco = $this->receta('Queso Fresco');
        // Leche justa para cada producto por separado, pero no para los dos juntos
        $necesita = max($this->lecheDe($paria, 10), $this->lecheDe($fresco, 10));
        Insumo::where('nombre', 'Leche Cruda')->update(['stock_actual' => $necesita + 1]);

        $this->comoAdmin()->post('/admin/produccion/lotes', ['lineas' => [
            ['receta_id' => $paria->id, 'cantidad' => 10],
            ['receta_id' => $fresco->id, 'cantidad' => 10],
        ]])->assertSessionHasErrors('stock');

        $this->assertSame(0, ProduccionLote::count(), 'todo o nada');
        $this->assertEqualsWithDelta($necesita + 1, $this->leche(), 0.001);
    }

    public function test_el_mismo_producto_dos_veces_se_suma_en_un_lote(): void
    {
        $paria = $this->receta('Queso Tipo Paria');

        $this->comoAdmin()->post('/admin/produccion/lotes', ['lineas' => [
            ['receta_id' => $paria->id, 'cantidad' => 4],
            ['receta_id' => $paria->id, 'cantidad' => 6],
        ]])->assertSessionHas('success');

        $this->assertSame(1, ProduccionLote::count());
        $this->assertSame(10, ProduccionLote::first()->cantidad_producida);
        $this->assertNull(ProduccionLote::first()->orden_codigo, 'un solo lote no necesita orden');
    }

    public function test_sigue_aceptando_el_formulario_de_un_solo_producto(): void
    {
        $this->comoAdmin()->post('/admin/produccion/lotes', [
            'receta_id' => $this->receta('Queso Andino')->id, 'cantidad_producida' => 5,
        ])->assertSessionHas('success');

        $this->assertSame(5, ProduccionLote::first()->cantidad_producida);
    }

    public function test_la_pantalla_de_produccion_muestra_el_formulario_de_varias_lineas(): void
    {
        $this->comoAdmin()->get('/admin/produccion/lotes/create')
             ->assertOk()->assertSee('Nueva orden de producción')->assertSee('Agregar otro producto');
    }

    // ── Ventas con carrito ─────────────────────────────────────────────────

    private function productos(): array
    {
        return [
            ProductoTienda::create(['nombre' => 'Yogurt Fresa 1L', 'precio_venta' => 10.50, 'stock_disponible' => 20, 'estado' => 'activo']),
            ProductoTienda::create(['nombre' => 'Manjar 1kg', 'precio_venta' => 12.00, 'stock_disponible' => 5, 'estado' => 'activo']),
        ];
    }

    private function quesoEnPlanta(float $kg): void
    {
        Produccion::create(['litros_usados' => $kg * 10, 'costo_cuajo' => 1, 'costo_sal' => 1, 'costo_otros' => 1,
            'kg_queso_estimado' => $kg, 'fecha_lote' => now()->toDateString(), 'registrado_por' => $this->admin->id]);
    }

    private function venta(array $lineas, string $tipo = 'publico')
    {
        return $this->comoAdmin()->post('/admin/ventas', [
            'cliente' => 'Bodega Don Lucho', 'tipo_cliente' => $tipo, 'destino' => 'Juliaca',
            'fecha_venta' => now()->toDateString(), 'lineas' => $lineas,
        ]);
    }

    public function test_venta_con_varios_productos_descuenta_stock_y_suma_el_total(): void
    {
        [$yogurt, $manjar] = $this->productos();
        $this->quesoEnPlanta(30);

        $this->venta([
            ['producto' => 'queso', 'cantidad' => 2.5],
            ['producto' => (string) $yogurt->id, 'cantidad' => 3],
            ['producto' => (string) $manjar->id, 'cantidad' => 2],
        ])->assertRedirect()->assertSessionHas('success');

        $venta = Venta::with('detalles')->first();
        $this->assertCount(3, $venta->detalles);
        $precioQueso = Venta::precioParaTipo('publico');
        $this->assertEqualsWithDelta(2.5 * $precioQueso + 3 * 10.50 + 2 * 12.00, $venta->total, 0.001);
        $this->assertEqualsWithDelta(2.5, $venta->cantidad_kg, 0.001, 'el stock de queso de planta usa cantidad_kg');
        $this->assertSame(17, $yogurt->fresh()->stock_disponible);
        $this->assertSame(3, $manjar->fresh()->stock_disponible);
        $this->assertEqualsWithDelta(27.5, Produccion::stockActualMoldes(), 0.05);
    }

    public function test_si_un_producto_no_tiene_stock_no_se_guarda_la_venta(): void
    {
        [$yogurt, $manjar] = $this->productos();

        $this->venta([
            ['producto' => (string) $yogurt->id, 'cantidad' => 3],
            ['producto' => (string) $manjar->id, 'cantidad' => 9],   // solo hay 5
        ])->assertSessionHasErrors('lineas');

        $this->assertSame(0, Venta::count());
        $this->assertSame(20, $yogurt->fresh()->stock_disponible, 'no se descontó nada');
    }

    public function test_no_vende_mas_queso_de_planta_del_que_hay(): void
    {
        $this->quesoEnPlanta(4);
        $this->venta([['producto' => 'queso', 'cantidad' => 10]])->assertSessionHasErrors('lineas');
        $this->assertSame(0, Venta::count());
    }

    public function test_mayorista_paga_menos_por_los_productos(): void
    {
        [$yogurt] = $this->productos();

        $this->venta([['producto' => (string) $yogurt->id, 'cantidad' => 10]], 'mayorista')->assertSessionHas('success');

        // 10 % de descuento mayorista por defecto
        $this->assertEqualsWithDelta(94.50, Venta::first()->total, 0.001);
    }

    public function test_carrito_vacio_o_unidades_partidas_se_rechazan(): void
    {
        [$yogurt] = $this->productos();
        $this->venta([])->assertSessionHasErrors('lineas');
        $this->venta([['producto' => (string) $yogurt->id, 'cantidad' => 1.5]])->assertSessionHasErrors('lineas');
        $this->assertSame(0, Venta::count());
    }

    public function test_la_pantalla_de_ventas_muestra_el_carrito_y_las_ventas_antiguas(): void
    {
        $this->productos();
        $this->quesoEnPlanta(20);
        // Venta registrada antes del carrito (sin detalle)
        Venta::create(['cliente' => 'Antiguo', 'tipo_cliente' => 'publico', 'destino' => 'Puno', 'cantidad_kg' => 3,
            'precio_unitario' => 21, 'fecha_venta' => now()->toDateString(), 'registrado_por' => $this->admin->id]);

        $this->comoAdmin()->get('/admin/ventas')->assertOk()
             ->assertSee('Productos de la venta')->assertSee('Agregar producto')
             ->assertSee('Yogurt Fresa 1L')->assertSee('Queso de planta');
        $this->assertEqualsWithDelta(63.0, Venta::first()->total, 0.001, 'las ventas sin carrito siguen calculando kg × precio');
    }
}
