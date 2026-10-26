<?php

namespace Tests\Feature;

use App\Models\AsignacionZona;
use App\Models\Ganadero;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Rutas trazadas por el administrador y asignación manual de zonas sin choques. */
class TrazadoYRotacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
    }

    private function comoAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true]);
    }

    private function zona(string $codigo, int $orden): Zona
    {
        return Zona::create(['codigo' => $codigo, 'nombre' => "Collana {$orden}", 'orden' => $orden, 'activa' => true]);
    }

    private function asignar(User $chofer, ?Zona $zona): AsignacionZona
    {
        return AsignacionZona::create([
            'chofer_id' => $chofer->id, 'zona_id' => $zona?->id, 'mes' => now()->month, 'anio' => now()->year,
            'rol_mes' => $zona ? 'titular' : 'volante',
            'fecha_inicio' => now()->startOfMonth(), 'fecha_fin' => now()->endOfMonth(),
        ]);
    }

    private function asignarManual(User $chofer, ?Zona $zona): \Illuminate\Testing\TestResponse
    {
        return $this->comoAdmin()->post('/admin/rotacion/asignar-manual', [
            'chofer_id' => $chofer->id, 'zona_id' => $zona?->id, 'mes' => now()->month, 'anio' => now()->year,
            'rol_mes' => $zona ? 'titular' : 'volante',
        ]);
    }

    private function zonaDe(User $chofer): ?int
    {
        return AsignacionZona::where('chofer_id', $chofer->id)->where('mes', now()->month)->value('zona_id');
    }

    // ── Rotación manual ─────────────────────────────────────────────────────

    public function test_asignar_una_zona_ocupada_intercambia_a_los_choferes(): void
    {
        [$z1, $z2] = [$this->zona('C1', 1), $this->zona('C2', 2)];
        $carlos = User::factory()->create(['rol' => 'chofer', 'name' => 'Carlos']);
        $miguel = User::factory()->create(['rol' => 'chofer', 'name' => 'Miguel']);
        $this->asignar($carlos, $z1);
        $this->asignar($miguel, $z2);

        $this->asignarManual($carlos, $z2)->assertRedirect()->assertSessionHas('success');

        $this->assertSame($z2->id, $this->zonaDe($carlos));
        $this->assertSame($z1->id, $this->zonaDe($miguel));
    }

    public function test_si_el_chofer_no_tenia_zona_el_que_la_ocupaba_queda_de_volante(): void
    {
        $z1 = $this->zona('C1', 1);
        $carlos = User::factory()->create(['rol' => 'chofer']);
        $fredy  = User::factory()->create(['rol' => 'chofer']);
        $this->asignar($carlos, $z1);

        $this->asignarManual($fredy, $z1)->assertRedirect()->assertSessionHas('success');

        $this->assertSame($z1->id, $this->zonaDe($fredy));
        $this->assertNull($this->zonaDe($carlos));
        $this->assertSame('volante', AsignacionZona::where('chofer_id', $carlos->id)->value('rol_mes'));
    }

    // ── Trazado de rutas ────────────────────────────────────────────────────

    /** Planta (-15.6167, -69.9708) → oeste → sur → Lejos → Medio → Cerca → planta. */
    private const TRAZO = [
        [-15.6167, -69.9708], [-15.62, -70.10], [-15.818, -70.10], [-15.818, -70.021],
        [-15.760, -70.000], [-15.700, -69.990], [-15.6167, -69.9708],
    ];

    public function test_guardar_trazado_calcula_km_y_ordena_las_paradas_en_su_sentido(): void
    {
        $zona  = $this->zona('C2', 2);
        $cerca = Ganadero::create(['dni' => '1', 'nombre' => 'Cerca', 'activo' => true, 'zona_id' => $zona->id, 'geo_lat' => -15.700, 'geo_lon' => -69.990]);
        $medio = Ganadero::create(['dni' => '2', 'nombre' => 'Medio', 'activo' => true, 'zona_id' => $zona->id, 'geo_lat' => -15.760, 'geo_lon' => -70.000]);
        $lejos = Ganadero::create(['dni' => '3', 'nombre' => 'Lejos', 'activo' => true, 'zona_id' => $zona->id, 'geo_lat' => -15.818, 'geo_lon' => -70.021]);

        $r = $this->comoAdmin()->postJson("/admin/rutas/trazar/{$zona->id}", ['trazado' => self::TRAZO])->assertOk();

        $this->assertGreaterThan(50, $r->json('trazado_km'));
        $this->assertSame(['Lejos', 'Medio', 'Cerca'], array_column($r->json('paradas'), 'nombre'));

        // La app del chofer recibe el trazado y las paradas en ese orden (no por cercanía)
        $chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $this->asignar($chofer, $zona);
        Sanctum::actingAs($chofer);
        $ruta = $this->getJson('/api/mi-ruta')->assertOk();
        $this->assertSame([$lejos->id, $medio->id, $cerca->id], array_column($ruta->json('paradas'), 'ganadero_id'));
        $this->assertCount(count(self::TRAZO), $ruta->json('trazado'));
    }

    public function test_sin_trazado_la_api_manda_lista_vacia_y_ordena_por_cercania(): void
    {
        $zona = $this->zona('C2', 2);
        $chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $this->asignar($chofer, $zona);
        Sanctum::actingAs($chofer);

        $this->getJson('/api/mi-ruta')->assertOk()->assertJsonPath('trazado', [])->assertJsonPath('trazado_km', null);
    }

    public function test_borrar_el_trazado_y_validaciones(): void
    {
        $zona = $this->zona('C2', 2);
        $this->comoAdmin()->postJson("/admin/rutas/trazar/{$zona->id}", ['trazado' => self::TRAZO])->assertOk();

        $this->comoAdmin()->postJson("/admin/rutas/trazar/{$zona->id}", ['trazado' => [[-15.6, -70.0]]])->assertStatus(422);
        $this->comoAdmin()->postJson("/admin/rutas/trazar/{$zona->id}", ['trazado' => [[-200, 0], [0, 0]]])->assertStatus(422);

        $this->comoAdmin()->postJson("/admin/rutas/trazar/{$zona->id}", ['trazado' => []])->assertOk();
        $this->assertNull($zona->fresh()->trazado);
        $this->assertNull($zona->fresh()->trazado_km);
    }

    public function test_pagina_de_trazar_rutas_solo_para_administrador(): void
    {
        $this->zona('C1', 1);
        $this->comoAdmin()->get('/admin/rutas/trazar')->assertOk()->assertSee('Rutas: proveedores, chofer y camino')->assertSee('Collana 1');

        $chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $this->actingAs($chofer)->withSession(['2fa_verified' => true])->get('/admin/rutas/trazar')->assertForbidden();
    }

    // ── Gestión de rutas: crear, proveedores, chofer, eliminar ─────────────

    public function test_crear_ruta_nueva_con_codigo_unico(): void
    {
        $r = $this->comoAdmin()->postJson('/admin/rutas', ['nombre' => 'Collana 5', 'color' => '#0ea5e9'])->assertCreated();

        $this->assertSame('COLLANA-5', $r->json('zona.codigo'));
        $this->assertTrue(Zona::where('nombre', 'Collana 5')->value('activa'));
        $this->comoAdmin()->postJson('/admin/rutas', ['nombre' => 'Collana 5'])->assertStatus(422);
    }

    public function test_agregar_y_quitar_proveedores_cambia_la_ruta_del_chofer(): void
    {
        $zona = $this->zona('C5', 5);
        $g = Ganadero::create(['dni' => '7', 'nombre' => 'Nuevo', 'activo' => true, 'geo_lat' => -15.7, 'geo_lon' => -70.0]);
        $chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $this->asignar($chofer, $zona);

        $this->comoAdmin()->postJson("/admin/rutas/{$zona->id}/proveedores", ['ganadero_ids' => [$g->id], 'accion' => 'agregar'])
            ->assertOk()->assertJsonPath('paradas.0.nombre', 'Nuevo');
        Sanctum::actingAs($chofer);
        $this->getJson('/api/mi-ruta')->assertOk()->assertJsonPath('total', 1);

        $this->comoAdmin()->postJson("/admin/rutas/{$zona->id}/proveedores", ['ganadero_ids' => [$g->id], 'accion' => 'quitar'])
            ->assertOk()->assertJsonPath('paradas', []);
        $this->assertNull($g->fresh()->zona_id);
    }

    public function test_elegir_chofer_de_la_ruta_intercambia_y_se_puede_quitar(): void
    {
        [$z1, $z2] = [$this->zona('C1', 1), $this->zona('C2', 2)];
        $carlos = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $miguel = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $this->asignar($carlos, $z1);
        $this->asignar($miguel, $z2);

        $r = $this->comoAdmin()->postJson("/admin/rutas/{$z2->id}/chofer", ['chofer_id' => $carlos->id])->assertOk();
        $this->assertSame($carlos->id, $r->json("titulares.{$z2->id}.id"));
        $this->assertSame($z1->id, $this->zonaDe($miguel));

        $this->comoAdmin()->postJson("/admin/rutas/{$z2->id}/chofer", ['chofer_id' => null])->assertOk();
        $this->assertNull($this->zonaDe($carlos));
        $this->comoAdmin()->postJson("/admin/rutas/{$z2->id}/chofer", ['chofer_id' => $this->admin->id])->assertStatus(422);
    }

    public function test_eliminar_ruta_exige_que_no_tenga_proveedores(): void
    {
        $zona = $this->zona('C5', 5);
        $g = Ganadero::create(['dni' => '8', 'nombre' => 'Uno', 'activo' => true, 'zona_id' => $zona->id]);
        $chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $this->asignar($chofer, $zona);

        $this->comoAdmin()->putJson("/admin/rutas/{$zona->id}", ['activa' => false])->assertStatus(422);

        $g->update(['zona_id' => null]);
        $this->comoAdmin()->putJson("/admin/rutas/{$zona->id}", ['activa' => false])->assertOk();
        $this->assertFalse($zona->fresh()->activa);
        $this->assertSame('volante', AsignacionZona::where('chofer_id', $chofer->id)->value('rol_mes'));
    }

    public function test_rotacion_automatica_funciona_con_mas_de_cuatro_rutas(): void
    {
        foreach (range(1, 5) as $i) $this->zona("C{$i}", $i);
        $ids = User::factory()->count(6)->create(['rol' => 'chofer', 'activo' => true])->pluck('id')->all();

        $creadas = AsignacionZona::generarRotacion($ids, now()->month, now()->year);

        $this->assertCount(6, $creadas);
        $this->assertSame(1, collect($creadas)->where('rol_mes', 'volante')->count());
        $this->assertSame(6, collect($creadas)->pluck('chofer_id')->unique()->count());
    }
}
