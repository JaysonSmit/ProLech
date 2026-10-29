<?php

namespace Tests\Feature;

use App\Models\AcopioCampo;
use App\Models\AsignacionZona;
use App\Models\Ganadero;
use App\Models\TrackingGps;
use App\Models\TrackingGpsPunto;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Contrato que consume la app Android: /api/mi-ruta y /api/tracking/lote. */
class RutaYTrackingApiTest extends TestCase
{
    use RefreshDatabase;

    private function chofer(): User
    {
        return User::factory()->create(['rol' => 'chofer', 'activo' => true]);
    }

    private function ganadero(string $dni, string $nombre, ?float $lat, ?float $lon, array $extra = []): Ganadero
    {
        return Ganadero::create(array_merge([
            'dni' => $dni, 'nombre' => $nombre, 'activo' => true,
            'geo_lat' => $lat, 'geo_lon' => $lon, 'estado' => 'Activo',
        ], $extra));
    }

    private function zonaAsignada(User $chofer, string $codigo = 'COLLANA-2'): Zona
    {
        $zona = Zona::create(['codigo' => $codigo, 'nombre' => 'Collana 2', 'orden' => 2, 'activa' => true]);
        AsignacionZona::create([
            'chofer_id' => $chofer->id, 'zona_id' => $zona->id,
            'mes' => now()->month, 'anio' => now()->year, 'rol_mes' => 'titular',
            'fecha_inicio' => now()->startOfMonth(), 'fecha_fin' => now()->endOfMonth(),
        ]);
        return $zona;
    }

    // ── /api/mi-ruta ────────────────────────────────────────────────────────

    public function test_mi_ruta_usa_la_zona_asignada_del_mes_y_ordena_por_cercania(): void
    {
        $chofer = $this->chofer();
        $zona   = $this->zonaAsignada($chofer);
        // La planta está en (-15.6167, -69.9708): el orden esperado es cerca → medio → lejos
        $lejos = $this->ganadero('11111111', 'Lejos', -15.818, -70.021, ['zona_id' => $zona->id]);
        $cerca = $this->ganadero('22222222', 'Cerca', -15.700, -69.990, ['zona_id' => $zona->id]);
        $medio = $this->ganadero('33333333', 'Medio', -15.760, -70.000, ['zona_id' => $zona->id]);
        $this->ganadero('44444444', 'Otra zona', -15.70, -69.99); // no debe aparecer
        Sanctum::actingAs($chofer);

        $r = $this->getJson('/api/mi-ruta')->assertOk();

        $r->assertJsonPath('origen_ruta', 'zona')
          ->assertJsonPath('ruta_nombre', 'Collana 2')
          ->assertJsonPath('rol_mes', 'titular')
          ->assertJsonPath('total', 3)
          ->assertJsonStructure(['planta' => ['lat', 'lon'], 'paradas' => [['ganadero_id', 'orden', 'geo_lat', 'atendido', 'suspendido']]]);
        $this->assertSame([$cerca->id, $medio->id, $lejos->id], array_column($r->json('paradas'), 'ganadero_id'));
    }

    public function test_mi_ruta_marca_atendidos_y_suspendidos_y_deja_sin_gps_al_final(): void
    {
        $chofer = $this->chofer();
        $zona   = $this->zonaAsignada($chofer);
        $sinGps     = $this->ganadero('11111111', 'Sin GPS', null, null, ['zona_id' => $zona->id]);
        $atendido   = $this->ganadero('22222222', 'Atendido', -15.70, -69.99, ['zona_id' => $zona->id]);
        $suspendido = $this->ganadero('33333333', 'Suspendido', -15.75, -70.0, ['zona_id' => $zona->id, 'estado' => 'Suspendido']);
        AcopioCampo::create([
            'uuid' => '0f8fad5b-d9cb-469f-a165-70867728950e', 'chofer_id' => $chofer->id,
            'ganadero_id' => $atendido->id, 'ganadero_nombre' => 'Atendido', 'litros' => 40,
            'porcentaje_agua' => 0, 'densidad' => 1.03, 'estado_calidad' => 'APROBADO',
            'actualizado_en' => now()->getTimestampMs(),
        ]);
        Sanctum::actingAs($chofer);

        $paradas = collect($this->getJson('/api/mi-ruta')->assertOk()->json('paradas'))->keyBy('ganadero_id');

        $this->assertTrue($paradas[$atendido->id]['atendido']);
        $this->assertTrue($paradas[$suspendido->id]['suspendido']);
        $this->assertSame($sinGps->id, $paradas->last()['ganadero_id']);
    }

    public function test_mi_ruta_sin_zona_usa_la_ruta_por_defecto(): void
    {
        $this->ganadero('11111111', 'Juan', -15.8, -70.0, ['ruta_asignada' => 'Ruta Norte']);
        Sanctum::actingAs($this->chofer());

        $this->getJson('/api/mi-ruta')
             ->assertOk()
             ->assertJsonPath('origen_ruta', 'historial')
             ->assertJsonPath('rol_mes', 'sin_asignar')
             ->assertJsonPath('ruta_nombre', 'Ruta Norte');
    }

    public function test_mi_ruta_sin_ganaderos_ni_zona_devuelve_422_con_mensaje(): void
    {
        Sanctum::actingAs($this->chofer());

        $this->getJson('/api/mi-ruta')->assertUnprocessable()->assertJsonStructure(['message']);
    }

    public function test_mi_ruta_exige_token(): void
    {
        $this->getJson('/api/mi-ruta')->assertUnauthorized();
    }

    // ── /api/tracking/lote ──────────────────────────────────────────────────

    public function test_lote_guarda_el_recorrido_y_la_ultima_posicion(): void
    {
        $chofer = $this->chofer();
        Sanctum::actingAs($chofer);
        $t0 = now()->getTimestampMs();

        $this->postJson('/api/tracking/lote', ['puntos' => [
            ['latitud' => -15.6167, 'longitud' => -69.9708, 'registrado_en' => $t0, 'precision_m' => 5],
            ['latitud' => -15.6300, 'longitud' => -69.9800, 'registrado_en' => $t0 + 60_000, 'velocidad_kmh' => 22.5],
        ]])->assertOk()->assertJson(['recibidos' => 2, 'nuevos' => 2]);

        $this->assertSame(2, TrackingGpsPunto::where('chofer_id', $chofer->id)->count());
        $vivo = TrackingGps::where('chofer_id', $chofer->id)->first();
        $this->assertEqualsWithDelta(-15.63, $vivo->latitud, 0.0001);
        $this->assertCount(2, TrackingGpsPunto::recorridoDeHoy($chofer->id));
    }

    public function test_reenviar_el_mismo_lote_no_duplica_puntos(): void
    {
        $chofer = $this->chofer();
        Sanctum::actingAs($chofer);
        $lote = ['puntos' => [['latitud' => -15.6, 'longitud' => -69.9, 'registrado_en' => now()->getTimestampMs()]]];

        $this->postJson('/api/tracking/lote', $lote)->assertOk()->assertJson(['nuevos' => 1]);
        $this->postJson('/api/tracking/lote', $lote)->assertOk()->assertJson(['nuevos' => 0]);

        $this->assertSame(1, TrackingGpsPunto::count());
    }

    public function test_lote_finalizado_marca_la_ruta_como_terminada(): void
    {
        $chofer = $this->chofer();
        Sanctum::actingAs($chofer);

        $this->postJson('/api/tracking/lote', [
            'estado_ruta' => 'finalizado',
            'puntos' => [['latitud' => -15.6, 'longitud' => -69.9, 'registrado_en' => now()->getTimestampMs()]],
        ])->assertOk();

        $this->assertSame('finalizado', TrackingGps::where('chofer_id', $chofer->id)->value('estado_ruta'));
    }

    public function test_lote_valida_coordenadas_y_tamano(): void
    {
        Sanctum::actingAs($this->chofer());

        $this->postJson('/api/tracking/lote', ['puntos' => []])->assertUnprocessable();
        $this->postJson('/api/tracking/lote', ['puntos' => [['latitud' => 95, 'longitud' => 0, 'registrado_en' => 1]]])
             ->assertUnprocessable();
    }

    // ── /api/ganaderos/{id}/ubicacion ───────────────────────────────────────

    public function test_chofer_fija_la_ubicacion_de_un_ganadero_de_su_ruta(): void
    {
        $chofer = $this->chofer();
        $zona   = $this->zonaAsignada($chofer);
        $g = $this->ganadero('11111111', 'Sin GPS', null, null, ['zona_id' => $zona->id]);
        Sanctum::actingAs($chofer);

        $this->postJson("/api/ganaderos/{$g->id}/ubicacion", ['latitud' => -15.71, 'longitud' => -69.98, 'precision_m' => 8])
             ->assertOk();

        $this->assertEqualsWithDelta(-15.71, $g->fresh()->geo_lat, 0.00001);
        $this->assertSame($g->id, $this->getJson('/api/mi-ruta')->json('paradas.0.ganadero_id'));
    }

    public function test_chofer_no_puede_mover_ganaderos_de_otra_zona(): void
    {
        $chofer = $this->chofer();
        $this->zonaAsignada($chofer);
        $ajeno = $this->ganadero('22222222', 'Otra zona', -15.8, -70.0);
        Sanctum::actingAs($chofer);

        $this->postJson("/api/ganaderos/{$ajeno->id}/ubicacion", ['latitud' => -15.0, 'longitud' => -69.0])
             ->assertForbidden();
        $this->assertEqualsWithDelta(-15.8, $ajeno->fresh()->geo_lat, 0.00001);
    }

    public function test_rechaza_ubicacion_con_gps_impreciso(): void
    {
        $chofer = $this->chofer();
        $zona = $this->zonaAsignada($chofer);
        $g = $this->ganadero('33333333', 'X', null, null, ['zona_id' => $zona->id]);
        Sanctum::actingAs($chofer);

        $this->postJson("/api/ganaderos/{$g->id}/ubicacion", ['latitud' => -15.7, 'longitud' => -69.9, 'precision_m' => 500])
             ->assertUnprocessable();
    }

    public function test_un_ganadero_no_puede_enviar_tracking(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'ganadero', 'activo' => true]));

        $this->postJson('/api/tracking/lote', ['puntos' => [['latitud' => -15.6, 'longitud' => -69.9, 'registrado_en' => 1]]])
             ->assertForbidden();
    }
}
