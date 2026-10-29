<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\Insumo;
use App\Models\ProductoTienda;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Costo por unidad desde la receta, precio sugerido con margen y avisos de margen bajo. */
class PreciosRecetaTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): static
    {
        $admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        return $this->actingAs($admin)->withSession(['2fa_verified' => true]);
    }

    /** Queso Fresco de la planta: 8.5 L de leche a S/ 1.80 + insumos = S/ 15.55 por unidad. */
    private function quesoFresco(int $rinde = 1): Receta
    {
        $receta = Receta::create(['nombre' => 'Queso Fresco', 'rendimiento_esperado_unidades' => $rinde, 'activa' => true]);
        foreach ([['Leche Cruda', 'L', 1.80, 8.5], ['Cuajo Líquido', 'mL', 0.085, 1.5], ['Cloruro de Calcio', 'mL', 0.04, 0.8], ['Sal Industrial', 'kg', 0.80, 0.08], ['Molde', 'unidad', 0.03, 1]] as [$n, $u, $c, $q]) {
            $i = Insumo::create(['nombre' => $n, 'unidad_medida' => $u, 'costo_unitario' => $c, 'stock_actual' => 100, 'stock_minimo' => 1, 'activo' => true]);
            RecetaDetalle::create(['receta_id' => $receta->id, 'insumo_id' => $i->id, 'cantidad_requerida' => $q * $rinde]);
        }
        return $receta->load('detalles.insumo');
    }

    public function test_costo_por_unidad_y_precio_sugerido(): void
    {
        $r = $this->quesoFresco();
        $this->assertSame(15.55, $r->costoUnitario());
        $this->assertSame(20.5, $r->precioSugerido());          // 15.55 × 1.30 = 20.22 → S/ 20.50
        $this->assertSame(17.0, $r->precioSugerido(9));          // otro margen
        $this->assertSame(2.8, $r->margenDe(16.0));              // precio actual de la tienda
        $this->assertSame(15.55, $this->quesoFresco(4)->costoUnitario(), 'la receta que rinde 4 da el mismo costo por unidad');
    }

    public function test_el_margen_objetivo_se_configura(): void
    {
        Configuracion::set('margen_ganancia_pct', 50);
        $this->assertSame(23.5, $this->quesoFresco()->precioSugerido());   // 15.55 × 1.5 = 23.33 → 23.50
    }

    public function test_recetas_muestra_costo_sugerido_y_avisa_margen_bajo(): void
    {
        $r = $this->quesoFresco();
        ProductoTienda::create(['nombre' => 'Queso Fresco', 'precio_venta' => 15.00, 'stock_disponible' => 1, 'estado' => 'activo', 'receta_id' => $r->id]);

        $this->comoAdmin()->get('/admin/recetas')->assertOk()
            ->assertSee('Costo por unidad')->assertSee('S/ 15.55')->assertSee('S/ 20.50')
            ->assertSee('Se vende por debajo del costo');
    }

    public function test_el_modal_publicar_llega_con_el_precio_sugerido(): void
    {
        $this->quesoFresco();
        $this->comoAdmin()->get('/admin/recetas')->assertOk()->assertSee('15.55, 20.5)', false);
    }

    public function test_editar_precio_muestra_la_ayuda_de_costo(): void
    {
        $r = $this->quesoFresco();
        $p = ProductoTienda::create(['nombre' => 'Queso Fresco', 'precio_venta' => 16, 'stock_disponible' => 1, 'estado' => 'activo', 'receta_id' => $r->id]);

        $this->comoAdmin()->get("/admin/tienda/productos/{$p->id}/edit")->assertOk()
            ->assertSee('const COSTOS', false)->assertSee('"sugerido":20.5', false);
    }
}
