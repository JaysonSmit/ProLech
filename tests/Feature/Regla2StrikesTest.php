<?php

namespace Tests\Feature;

use App\Models\Acopio;
use App\Models\Ganadero;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Regla de 2 adulteraciones por semana: app clásica y app de campo. */
class Regla2StrikesTest extends TestCase
{
    use RefreshDatabase;

    private User $chofer;
    private User $admin;
    private Ganadero $ganadero;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $this->chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $this->ganadero = Ganadero::create(['dni' => '12345678', 'nombre' => 'Juan Pérez']);
        Sanctum::actingAs($this->chofer);
    }

    private function campo(string $estado, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/acopios-campo', array_merge([
            'uuid'            => (string) Str::uuid(),
            'ganadero_id'     => $this->ganadero->id,
            'ganadero_nombre' => 'Juan Pérez',
            'litros'          => 20,
            'porcentaje_agua' => $estado === 'APROBADO' ? 0 : 7,
            'densidad'        => 1.030,
            'estado_calidad'  => $estado,
            'actualizado_en'  => 1_760_000_000_000,
        ], $extra));
    }

    /** Acopio de la app clásica en la misma semana, para combinar ambas apps. */
    private function clasicoAdulterado(): void
    {
        Acopio::create(['ganadero_id' => $this->ganadero->id, 'chofer_id' => $this->chofer->id, 'litros' => 15,
            'porcentaje_agua' => 6, 'estado' => 'observado', 'fecha' => now()->toDateString()]);
    }

    private function notificaciones(string $tipo): int
    {
        return Notificacion::where('user_id', $this->admin->id)->where('tipo', $tipo)->count();
    }

    public function test_primer_adulterado_desde_el_campo_avisa_al_admin(): void
    {
        $this->campo('OBSERVADO')->assertCreated();

        $this->assertSame(1, $this->notificaciones('adulteracion_exceso'));
        $this->assertFalse($this->ganadero->fresh()->estaSuspendido());
    }

    public function test_segundo_adulterado_desde_el_campo_suspende_al_ganadero(): void
    {
        $this->campo('OBSERVADO');
        $this->campo('RECHAZADO', ['porcentaje_agua' => 12])->assertCreated();

        $this->assertTrue($this->ganadero->fresh()->estaSuspendido());
        $this->assertSame(1, $this->notificaciones('suspension'));
    }

    public function test_cuenta_juntas_las_adulteraciones_de_ambas_apps(): void
    {
        $this->clasicoAdulterado();
        $this->campo('OBSERVADO');

        $this->assertTrue($this->ganadero->fresh()->estaSuspendido());
    }

    public function test_aprobados_no_cuentan(): void
    {
        $this->campo('APROBADO');
        $this->campo('APROBADO');

        $this->assertSame(0, Notificacion::count());
        $this->assertFalse($this->ganadero->fresh()->estaSuspendido());
    }

    public function test_reintento_del_celular_no_cuenta_doble(): void
    {
        $uuid = (string) Str::uuid();
        $this->campo('OBSERVADO', ['uuid' => $uuid]);
        $this->campo('OBSERVADO', ['uuid' => $uuid]);

        $this->assertFalse($this->ganadero->fresh()->estaSuspendido());
        $this->assertSame(1, $this->notificaciones('adulteracion_exceso'));
    }

    public function test_editar_de_aprobado_a_adulterado_cuenta_como_strike(): void
    {
        $this->clasicoAdulterado();
        $uuid = (string) Str::uuid();
        $this->campo('APROBADO', ['uuid' => $uuid]);
        $this->assertFalse($this->ganadero->fresh()->estaSuspendido());

        $this->putJson("/api/acopios-campo/{$uuid}", [
            'ganadero_id' => $this->ganadero->id, 'ganadero_nombre' => 'Juan Pérez', 'litros' => 20,
            'porcentaje_agua' => 7, 'densidad' => 1.030, 'estado_calidad' => 'OBSERVADO', 'actualizado_en' => 1_760_000_000_900,
        ])->assertOk();

        $this->assertTrue($this->ganadero->fresh()->estaSuspendido());
    }

    public function test_ganadero_ya_suspendido_no_genera_avisos_repetidos(): void
    {
        $this->ganadero->suspender();
        $this->campo('RECHAZADO', ['porcentaje_agua' => 12])->assertCreated();

        $this->assertSame(0, Notificacion::count());
        $this->assertSame(1, Acopio::count(), 'la leche ya se recogió: igual queda registrada');
    }

    public function test_app_clasica_sigue_suspendiendo_al_segundo_strike(): void
    {
        $this->clasicoAdulterado();

        $this->postJson('/api/acopios', [
            'ganadero_id' => $this->ganadero->id, 'litros' => 20, 'medicion_balde_confirmada' => true,
            'equipo_lactoscan' => 1, 'porcentaje_agua' => 7, 'acidez_ph' => 6.6,
            'fecha' => now()->subDay()->max(now()->startOfWeek(\Carbon\Carbon::THURSDAY))->toDateString(),
        ])->assertCreated()->assertJsonPath('ganadero_suspendido', true);
    }
}
