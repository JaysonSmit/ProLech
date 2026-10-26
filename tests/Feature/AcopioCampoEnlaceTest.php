<?php

namespace Tests\Feature;

use App\Models\Acopio;
use App\Models\AcopioCampo;
use App\Models\Ganadero;
use App\Models\Liquidacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Los acopios de la app de campo aparecen en la tabla acopios (dashboard, liquidaciones). */
class AcopioCampoEnlaceTest extends TestCase
{
    use RefreshDatabase;

    private User $chofer;
    private Ganadero $ganadero;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $this->ganadero = Ganadero::create(['dni' => '12345678', 'nombre' => 'Juan Pérez']);
        Sanctum::actingAs($this->chofer);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'uuid'            => (string) Str::uuid(),
            'ganadero_id'     => $this->ganadero->id,
            'ganadero_nombre' => 'Juan Pérez',
            'litros'          => 10,
            'porcentaje_agua' => 1.0,
            'densidad'        => 1.030,
            'grasa'           => 5.0,
            'estado_calidad'  => 'APROBADO',
            'actualizado_en'  => 1_760_000_000_000,
        ], $extra);
    }

    public function test_un_acopio_de_campo_crea_su_acopio_en_la_tabla_principal(): void
    {
        $this->postJson('/api/acopios-campo', $this->payload())->assertCreated();

        $acopio = Acopio::sole();
        $campo = AcopioCampo::sole();
        $this->assertSame($campo->id, $acopio->acopio_campo_id);
        $this->assertSame($this->ganadero->id, $acopio->ganadero_id);
        $this->assertSame($this->chofer->id, $acopio->chofer_id);
        $this->assertEquals(10.0, (float) $acopio->litros);
        $this->assertSame('aprobado', $acopio->estado);
        $this->assertTrue($acopio->tiene_descuento, '1 % de agua lleva descuento');
        $this->assertSame(now()->toDateString(), $acopio->fecha->toDateString());
        $this->assertEquals($campo->created_at, $acopio->created_at);
    }

    public function test_reintento_del_celular_no_duplica_y_la_edicion_se_refleja(): void
    {
        $datos = $this->payload();
        $this->postJson('/api/acopios-campo', $datos);
        $this->postJson('/api/acopios-campo', $datos);
        $this->putJson("/api/acopios-campo/{$datos['uuid']}", $this->payload([
            'litros' => 12.5, 'porcentaje_agua' => 11, 'estado_calidad' => 'RECHAZADO', 'actualizado_en' => 1_760_000_000_500,
        ]))->assertOk();

        $acopio = Acopio::sole();
        $this->assertEquals(12.5, (float) $acopio->litros);
        $this->assertSame('rechazado', $acopio->estado);
    }

    public function test_la_foto_del_campo_queda_como_evidencia(): void
    {
        Storage::fake('public');
        $datos = $this->payload();
        $this->postJson('/api/acopios-campo', $datos);

        $this->post("/api/acopios-campo/{$datos['uuid']}/foto", ['foto' => UploadedFile::fake()->image('lactoscan.jpg')])->assertOk();

        $this->assertNotNull(Acopio::sole()->foto_evidencia);
        $this->assertSame(AcopioCampo::sole()->foto_path, Acopio::sole()->foto_evidencia);
    }

    public function test_si_el_chofer_lo_elimina_se_elimina_el_acopio(): void
    {
        $datos = $this->payload();
        $this->postJson('/api/acopios-campo', $datos);

        $this->deleteJson("/api/acopios-campo/{$datos['uuid']}")->assertOk();

        $this->assertSame(0, Acopio::count());
    }

    public function test_si_ya_esta_en_una_liquidacion_confirmada_se_conserva(): void
    {
        $datos = $this->payload();
        $this->postJson('/api/acopios-campo', $datos);
        Liquidacion::create([
            'ganadero_id' => $this->ganadero->id, 'semana_inicio' => now()->subDays(3)->toDateString(),
            'semana_fin' => now()->addDays(3)->toDateString(), 'fecha_pago' => now()->addDays(5)->toDateString(),
            'precio_litro' => 1.2, 'estado' => 'confirmada',
        ]);

        $this->deleteJson("/api/acopios-campo/{$datos['uuid']}")->assertOk();

        $this->assertSame(0, AcopioCampo::count());
        $this->assertSame(1, Acopio::count(), 'no descuadra el pago ya confirmado');
        $this->assertNull(Acopio::sole()->acopio_campo_id);
    }

    public function test_ganadero_inexistente_no_rompe_la_sincronizacion(): void
    {
        $this->postJson('/api/acopios-campo', $this->payload(['ganadero_id' => 9999]))->assertCreated();

        $this->assertSame(1, AcopioCampo::count());
        $this->assertSame(0, Acopio::count());
    }

    public function test_el_panel_en_vivo_no_cuenta_dos_veces(): void
    {
        $this->postJson('/api/acopios-campo', $this->payload(['litros' => 10]));
        $admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);

        $this->actingAs($admin)->withSession(['2fa_verified' => true])
             ->getJson('/admin/en-vivo/datos')->assertOk()
             ->assertJsonPath('entregas_hoy', 1)
             ->assertJsonPath('litros_hoy', 10);
    }

    public function test_aparece_en_el_dashboard_web(): void
    {
        $this->postJson('/api/acopios-campo', $this->payload());
        $admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);

        $this->actingAs($admin)->withSession(['2fa_verified' => true])
             ->get('/admin/dashboard')->assertOk()->assertSee('Juan Pérez');
    }
}
