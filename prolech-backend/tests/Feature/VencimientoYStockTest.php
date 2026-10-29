<?php

namespace Tests\Feature;

use App\Models\Insumo;
use App\Models\Notificacion;
use App\Models\ProduccionLote;
use App\Models\ProductoTienda;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\User;
use App\Services\ProduccionService;
use App\Services\StockLotes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Vencimiento de lotes y stock de la tienda que sale de la producción. */
class VencimientoYStockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Receta $receta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $this->receta = Receta::create(['nombre' => 'Queso Fresco', 'rendimiento_esperado_unidades' => 1, 'dias_vida_util' => 7, 'activa' => true]);
        $leche = Insumo::create(['nombre' => 'Leche Cruda', 'unidad_medida' => 'L', 'costo_unitario' => 1.8, 'stock_actual' => 10000, 'stock_minimo' => 1, 'activo' => true]);
        RecetaDetalle::create(['receta_id' => $this->receta->id, 'insumo_id' => $leche->id, 'cantidad_requerida' => 8.5]);
    }

    private function comoAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true]);
    }

    private function lote(int $unidades, int $haceDias): ProduccionLote
    {
        $lote = app(ProduccionService::class)->registrarLote($this->receta->id, $unidades, $this->admin);
        $fecha = now()->subDays($haceDias)->toDateString();
        $lote->update(['fecha_produccion' => $fecha, 'fecha_vencimiento' => StockLotes::fechaVencimiento($fecha, 7)]);
        return $lote->fresh();
    }

    public function test_cada_lote_vence_segun_la_vida_util_de_la_receta(): void
    {
        $lote = app(ProduccionService::class)->registrarLote($this->receta->id, 5, $this->admin);

        $this->assertSame(now()->addDays(7)->toDateString(), $lote->fecha_vencimiento->toDateString());
        $this->assertSame('vigente', $lote->estadoVencimiento());
    }

    public function test_publicar_la_receta_trae_el_stock_de_lo_producido_vigente(): void
    {
        $this->lote(10, 10);   // ya vencido: no cuenta
        $this->lote(20, 1);

        $this->comoAdmin()->post('/admin/recetas/publicar', ['receta_id' => $this->receta->id, 'precio_venta' => 20])->assertRedirect();

        $this->assertSame(20, ProductoTienda::where('receta_id', $this->receta->id)->value('stock_disponible'));
    }

    public function test_crear_producto_con_receta_y_stock_vacio_usa_lo_producido(): void
    {
        $this->lote(12, 0);

        $this->comoAdmin()->post('/admin/tienda/productos', [
            'nombre' => 'Queso Fresco', 'precio_venta' => 20, 'estado' => 'activo', 'receta_id' => $this->receta->id,
        ])->assertRedirect();

        $this->assertSame(12, ProductoTienda::sole()->stock_disponible);
    }

    public function test_desglose_vende_primero_lo_que_vence_antes_y_retira_lo_vencido(): void
    {
        $viejo = $this->lote(10, 8);    // venció ayer
        $nuevo = $this->lote(10, 0);
        $p = ProductoTienda::create(['nombre' => 'Queso Fresco', 'precio_venta' => 20, 'stock_disponible' => 14, 'estado' => 'activo', 'receta_id' => $this->receta->id]);

        $desglose = app(StockLotes::class)->desglose($this->receta->id, 14);
        $this->assertSame([4, 10], $desglose->pluck('en_stock')->all());           // 10 del nuevo + 4 que quedan del viejo
        $this->assertSame(['vencido', 'vigente'], $desglose->pluck('estado')->all());

        $this->artisan('prolech:retirar-vencidos')->assertSuccessful();

        $this->assertSame(10, $p->fresh()->stock_disponible);
        $this->assertSame(4, $viejo->fresh()->merma);
        $this->assertSame(1, Notificacion::where('tipo', 'merma_vencidos')->count());
        $this->artisan('prolech:retirar-vencidos')->assertSuccessful();             // no retira dos veces
        $this->assertSame(10, $p->fresh()->stock_disponible);
    }

    public function test_produccion_muestra_vencimiento_y_aviso_de_lo_que_vence(): void
    {
        $this->lote(10, 6);   // vence mañana
        ProductoTienda::create(['nombre' => 'Queso Fresco', 'precio_venta' => 20, 'stock_disponible' => 6, 'estado' => 'activo', 'receta_id' => $this->receta->id]);

        $this->comoAdmin()->get('/admin/produccion/lotes/create')->assertOk()
            ->assertSee('Productos por vencer en la tienda')->assertSee('Faltan 1 días')->assertSee('En tienda');
    }

    public function test_la_receta_guarda_su_vida_util(): void
    {
        $this->comoAdmin()->get("/admin/recetas/{$this->receta->id}/edit")->assertOk()->assertSee('Vida útil');
    }
}
