<?php

namespace Tests\Feature;

use App\Models\Acopio;
use App\Models\Configuracion;
use App\Models\Ganadero;
use App\Models\Insumo;
use App\Models\PlanillaChofer;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\User;
use App\Services\CostoLecheService;
use App\Services\PlanillaChoferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Planilla de choferes y costo real por litro puesto en planta. */
class PlanillaYCostoLecheTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $chofer;
    private Ganadero $g;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $this->chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true, 'name' => 'Carlos Ramos']);
        $this->g = Ganadero::create(['dni' => '1', 'nombre' => 'Rosa Quispe', 'activo' => true, 'estado' => 'Activo']);
        foreach ([['tarifa_chofer_dia', 60], ['bono_chofer_litro', 0.02], ['pension_chofer_pct', 10],
                  ['costo_combustible_km', 0.5], ['km_ruta_default', 40], ['gastos_generales_pct', 4],
                  ['precio_litro_normal', 1.80]] as [$k, $v]) {
            Configuracion::set($k, (float) $v);
        }
        CostoLecheService::olvidar();
    }

    private function comoAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true]);
    }

    /** 2 días con 500 L cada uno dentro de la semana actual. */
    private function acopios(): array
    {
        [$desde, $hasta] = PlanillaChoferService::periodo('semanal');
        foreach ([0, 1] as $d) {
            Acopio::create(['ganadero_id' => $this->g->id, 'chofer_id' => $this->chofer->id, 'litros' => 500, 'estado' => 'aprobado', 'fecha' => $desde->copy()->addDays($d)]);
        }
        Acopio::create(['ganadero_id' => $this->g->id, 'chofer_id' => $this->chofer->id, 'litros' => 80, 'estado' => 'rechazado', 'fecha' => $desde]);
        return [$desde, $hasta];
    }

    public function test_calcula_dias_litros_km_bono_descuentos_y_neto(): void
    {
        [$desde, $hasta] = $this->acopios();
        $c = app(PlanillaChoferService::class)->calcular($this->chofer, $desde, $hasta, 20);

        $this->assertSame(2, $c['dias_trabajados']);
        $this->assertEquals(1000, $c['litros_recolectados']);          // el rechazado no cuenta
        $this->assertEquals(80, $c['km_recorridos']);                  // 2 días × 40 km estimados
        $this->assertEquals(120, $c['monto_base']);
        $this->assertEquals(20, $c['monto_bono']);
        $this->assertEquals(140, $c['total_bruto']);
        $this->assertEquals(14 + 20, $c['descuentos']);                // pensión 10 % + adelanto
        $this->assertEquals(106, $c['total_neto']);
        $this->assertEquals(40, $c['costo_combustible']);
    }

    public function test_generar_pagar_boleta_y_exportar_desde_el_panel(): void
    {
        [$desde] = $this->acopios();
        $this->comoAdmin()->get('/admin/planilla-choferes')->assertOk()->assertSee('Carlos Ramos')->assertSee('Vista previa');

        $this->comoAdmin()->post('/admin/planilla-choferes/generar', ['tipo' => 'semanal', 'fecha' => $desde->toDateString()])->assertRedirect();
        $p = PlanillaChofer::sole();
        $this->assertSame('borrador', $p->estado);

        $this->comoAdmin()->post("/admin/planilla-choferes/{$p->id}/adelantos", ['adelantos' => 30])->assertRedirect();
        $this->assertEquals(140 - 14 - 30, $p->fresh()->total_neto);

        $this->comoAdmin()->post("/admin/planilla-choferes/{$p->id}/pagar", ['metodo_pago' => 'yape', 'referencia_pago' => 'OP-1'])->assertRedirect();
        $this->assertTrue($p->fresh()->estaPagada());

        // Regenerar no toca una planilla pagada
        $this->comoAdmin()->post('/admin/planilla-choferes/generar', ['tipo' => 'semanal', 'fecha' => $desde->toDateString()]);
        $this->assertEquals(96, $p->fresh()->total_neto);

        $this->comoAdmin()->get("/admin/planilla-choferes/{$p->id}/boleta")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->comoAdmin()->get('/admin/planilla-choferes/exportar?formato=xlsx&fecha=' . $desde->toDateString())->assertOk();

        $this->actingAs($this->chofer)->withSession(['2fa_verified' => true])->get('/admin/planilla-choferes')->assertForbidden();
    }

    public function test_costo_real_por_litro_y_receta_dinamica(): void
    {
        $this->acopios();
        $c = app(CostoLecheService::class)->calcular();

        // base 1.80 (sin liquidaciones) + (40 combustible + 140 choferes) / 1000 L = 0.18 ; gastos 4 % de 1.98
        $this->assertEquals(1.80, $c['base']);
        $this->assertEquals(0.18, $c['logistica']);
        $this->assertEqualsWithDelta(0.0792, $c['gastos'], 0.0001);
        $this->assertEqualsWithDelta(2.0592, $c['costo_real'], 0.0001);

        $leche = Insumo::create(['nombre' => 'Leche Cruda', 'unidad_medida' => 'L', 'costo_unitario' => 1.00, 'stock_actual' => 100, 'stock_minimo' => 1, 'activo' => true]);
        $receta = Receta::create(['nombre' => 'Queso Fresco', 'rendimiento_esperado_unidades' => 1, 'activa' => true]);
        RecetaDetalle::create(['receta_id' => $receta->id, 'insumo_id' => $leche->id, 'cantidad_requerida' => 10]);

        $this->assertEquals(20.59, $receta->fresh()->costoUnitario());             // 10 L × costo real, no × 1.00

        app(CostoLecheService::class)->aplicarAInsumo();
        $this->assertEqualsWithDelta(2.0592, (float) $leche->fresh()->costo_unitario, 0.0001);

        $this->comoAdmin()->get('/admin/recetas')->assertOk()->assertSee('Costo real por litro');
    }
}
