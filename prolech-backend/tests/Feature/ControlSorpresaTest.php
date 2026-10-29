<?php

namespace Tests\Feature;

use App\Models\Acopio;
use App\Models\AsignacionZona;
use App\Models\ControlCalidad;
use App\Models\Ganadero;
use App\Models\Liquidacion;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Zona;
use App\Services\ControlCalidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Control de calidad sorpresa: sorteo, frasco, resultados, sanciones y bloqueo por entregas. */
class ControlSorpresaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $chofer;
    private Zona $zona;
    private Ganadero $g;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $this->chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $this->zona = Zona::create(['codigo' => 'C1', 'nombre' => 'Collana 1', 'orden' => 1, 'activa' => true]);
        AsignacionZona::asignar($this->chofer->id, $this->zona->id, now()->month, now()->year);
        $this->g = Ganadero::create(['dni' => '1', 'nombre' => 'Rosa Quispe', 'activo' => true, 'zona_id' => $this->zona->id, 'estado' => 'Activo']);
    }

    private function servicio(): ControlCalidadService { return app(ControlCalidadService::class); }

    private function comoAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true]);
    }

    private function acopioCampo(array $extra = []): \Illuminate\Testing\TestResponse
    {
        Sanctum::actingAs($this->chofer);
        return $this->postJson('/api/acopios-campo', array_merge([
            'uuid' => (string) Str::uuid(), 'ganadero_id' => $this->g->id, 'ganadero_nombre' => 'Rosa Quispe',
            'litros' => 40, 'actualizado_en' => now()->getTimestampMs(),
        ], $extra));
    }

    public function test_el_chofer_registra_solo_litros_y_observacion(): void
    {
        $this->acopioCampo(['observacion' => 'olor', 'observacion_nota' => 'huele agrio'])->assertCreated();

        $acopio = Acopio::sole();
        $this->assertSame('aprobado', $acopio->estado);
        $this->assertSame('olor', $acopio->observacion);
        $this->assertSame(1, Notificacion::where('tipo', 'observacion_chofer')->where('user_id', $this->admin->id)->count());
        $this->assertSame(2.0, $this->servicio()->riesgo($this->g)['puntaje']);   // +2 por la observación: aún verde
    }

    public function test_sorteo_programa_controles_y_la_ruta_pide_el_frasco(): void
    {
        $controles = $this->servicio()->sortear();
        $this->assertCount(2, $controles);                                  // 1 ganadero (predio) + tanque
        $this->assertCount(0, $this->servicio()->sortear(), 'no se repite el mismo día');

        ControlCalidad::query()->delete();
        $c = $this->servicio()->programar($this->g, 'muestra', 'Manual');
        Sanctum::actingAs($this->chofer);
        $this->getJson('/api/mi-ruta')->assertOk()->assertJsonPath('paradas.0.muestra', $c->frasco_codigo);

        $this->acopioCampo(['muestra_codigo' => $c->frasco_codigo])->assertCreated();
        $this->assertSame('muestra_tomada', $c->fresh()->estado);
        $this->assertSame(Acopio::sole()->id, $c->fresh()->acopio_id);
    }

    public function test_resultado_grave_bloquea_3_entregas_6_si_reincide_y_suspende_a_la_tercera(): void
    {
        foreach ([3, 6] as $i => $esperado) {
            $c = $this->servicio()->programar($this->g, 'predio', 'Manual');
            $this->comoAdmin()->post("/admin/calidad/control/{$c->id}/resultado", ['agua' => 12])->assertRedirect();
            $this->assertSame('grave', $c->fresh()->resultado);
            $this->assertSame($esperado, $this->g->fresh()->entregas_bloqueadas);
        }
        $c = $this->servicio()->programar($this->g, 'predio', 'Manual');
        $this->comoAdmin()->post("/admin/calidad/control/{$c->id}/resultado", ['resultado' => 'grave']);
        $this->assertTrue($this->g->fresh()->estaSuspendido());
        $this->assertGreaterThan(0, Notificacion::where('tipo', 'control_calidad')->where('user_id', $this->chofer->id)->count());
    }

    public function test_bloqueado_no_puede_entregar_y_se_descuenta_cuando_pasa_el_camion(): void
    {
        $this->g->update(['entregas_bloqueadas' => 1]);
        Sanctum::actingAs($this->chofer);
        $this->getJson('/api/mi-ruta')->assertJsonPath('paradas.0.bloqueado', 1);

        $otro = Ganadero::create(['dni' => '2', 'nombre' => 'Otro', 'activo' => true, 'zona_id' => $this->zona->id, 'estado' => 'Activo']);
        Acopio::create(['ganadero_id' => $otro->id, 'chofer_id' => $this->chofer->id, 'litros' => 20, 'estado' => 'aprobado', 'fecha' => today()]);
        $this->artisan('prolech:calidad bloqueos')->assertSuccessful();

        $this->assertSame(0, $this->g->fresh()->entregas_bloqueadas);
    }

    public function test_la_leche_de_una_muestra_grave_no_se_paga_y_la_leve_tiene_descuento(): void
    {
        [$ini, $fin] = Acopio::rangoSemanaLiquidacion();
        $bueno = Acopio::create(['ganadero_id' => $this->g->id, 'chofer_id' => $this->chofer->id, 'litros' => 100, 'estado' => 'aprobado', 'fecha' => $ini]);
        $malo = Acopio::create(['ganadero_id' => $this->g->id, 'chofer_id' => $this->chofer->id, 'litros' => 40, 'estado' => 'aprobado', 'fecha' => $fin]);
        $c = ControlCalidad::create(['fecha' => $fin, 'tipo' => 'muestra', 'ganadero_id' => $this->g->id, 'zona_id' => $this->zona->id, 'acopio_id' => $malo->id, 'estado' => 'muestra_tomada']);
        $this->servicio()->registrarResultado($c, ['agua' => 15], $this->admin);

        $this->comoAdmin()->post('/admin/liquidaciones/generar', ['semana_inicio' => $ini]);
        $liq = Liquidacion::where('ganadero_id', $this->g->id)->sole();
        $this->assertEquals(100, (float) $liq->litros_aprobados);
        $this->assertStringContainsString('40 L no se pagan', $liq->detalle_descuentos);
        $this->assertSame('grave', $malo->fresh()->control_resultado);

        $c2 = ControlCalidad::create(['fecha' => $ini, 'tipo' => 'muestra', 'ganadero_id' => $this->g->id, 'acopio_id' => $bueno->id, 'estado' => 'muestra_tomada']);
        $this->servicio()->registrarResultado($c2, ['agua' => 1], $this->admin);
        $this->assertSame('leve', $c2->fresh()->resultado);
        $this->assertTrue((bool) $bueno->fresh()->tiene_descuento);
    }

    public function test_tanque_malo_programa_muestras_en_la_ruta_para_manana(): void
    {
        $c = ControlCalidad::create(['fecha' => today(), 'tipo' => 'tanque', 'zona_id' => $this->zona->id]);
        $this->servicio()->registrarResultado($c, ['resultado' => 'leve'], $this->admin);

        $this->assertSame(1, ControlCalidad::where('tipo', 'muestra')->whereDate('fecha', today()->addDay())->where('ganadero_id', $this->g->id)->count());
    }

    public function test_pagina_del_control_sorpresa_solo_admin_y_calidad(): void
    {
        $this->servicio()->programar($this->g, 'predio', 'Manual');
        $this->comoAdmin()->get('/admin/calidad/control')->assertOk()
            ->assertSee('Controles de hoy')->assertSee('Rosa Quispe')->assertSee('Semáforo de ganaderos');

        $calidad = User::factory()->create(['rol' => 'calidad', 'activo' => true]);
        $this->actingAs($calidad)->withSession(['2fa_verified' => true])->get('/admin/calidad/control')->assertOk();
        $this->actingAs($this->chofer)->withSession(['2fa_verified' => true])->get('/admin/calidad/control')->assertForbidden();
    }
}
