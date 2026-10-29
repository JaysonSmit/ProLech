<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChoferApiTest extends TestCase
{
    use RefreshDatabase;

    private function chofer(): User
    {
        return User::factory()->create(['rol' => 'chofer', 'activo' => true]);
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    public function test_login_devuelve_token(): void
    {
        $chofer = $this->chofer();

        $this->postJson('/api/login', ['email' => $chofer->email, 'password' => 'password'])
             ->assertOk()
             ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'rol']]);
    }

    public function test_login_con_clave_incorrecta_falla(): void
    {
        $chofer = $this->chofer();

        $this->postJson('/api/login', ['email' => $chofer->email, 'password' => 'mala'])
             ->assertUnprocessable();
    }

    public function test_usuario_inactivo_no_puede_entrar(): void
    {
        $chofer = User::factory()->create(['rol' => 'chofer', 'activo' => false]);

        $this->postJson('/api/login', ['email' => $chofer->email, 'password' => 'password'])
             ->assertUnprocessable();
    }

    public function test_logout_invalida_el_token(): void
    {
        $chofer = $this->chofer();
        $token  = $chofer->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertSame(0, $chofer->tokens()->count());
    }

    // ── Tracking GPS ──────────────────────────────────────────────────────────

    public function test_tracking_guarda_una_sola_ubicacion_por_chofer(): void
    {
        $chofer = $this->chofer();
        Sanctum::actingAs($chofer);

        $this->postJson('/api/tracking', ['latitud' => -15.84, 'longitud' => -70.02])->assertOk();
        $this->postJson('/api/tracking', ['latitud' => -15.85, 'longitud' => -70.03])->assertOk();

        $this->assertDatabaseCount('tracking_gps', 1);
        $this->assertDatabaseHas('tracking_gps', ['chofer_id' => $chofer->id]);
    }

    public function test_tracking_valida_coordenadas(): void
    {
        Sanctum::actingAs($this->chofer());

        $this->postJson('/api/tracking', ['latitud' => 200, 'longitud' => -70])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['latitud']);
    }

    // ── Solicitud de ruta ─────────────────────────────────────────────────────

    public function test_solicitar_ruta_y_consultar_estado(): void
    {
        Sanctum::actingAs($this->chofer());

        $this->getJson('/api/estado-solicitud-ruta')
             ->assertOk()
             ->assertJsonPath('tiene_solicitud', false);

        $this->postJson('/api/solicitar-ruta', [
            'ruta_solicitada' => 'A-2 Huata Sur',
            'motivo'          => 'Vivo más cerca',
        ])->assertCreated()->assertJsonPath('solicitud.estado', 'pendiente');

        $this->getJson('/api/estado-solicitud-ruta')
             ->assertOk()
             ->assertJsonPath('tiene_solicitud', true)
             ->assertJsonPath('solicitud.ruta_solicitada', 'A-2 Huata Sur');
    }

    public function test_no_permite_dos_solicitudes_pendientes(): void
    {
        Sanctum::actingAs($this->chofer());
        $datos = ['ruta_solicitada' => 'B-1', 'motivo' => 'Prueba'];

        $this->postJson('/api/solicitar-ruta', $datos)->assertCreated();
        $this->postJson('/api/solicitar-ruta', $datos)->assertUnprocessable();
    }

    // ── Notificaciones y reuniones ────────────────────────────────────────────

    public function test_notificaciones_y_reuniones_vacias(): void
    {
        Sanctum::actingAs($this->chofer());

        $this->getJson('/api/notificaciones')
             ->assertOk()
             ->assertJsonPath('total_no_leidas', 0);

        $this->postJson('/api/notificaciones/leer-todas')->assertOk();

        $this->getJson('/api/reuniones')
             ->assertOk()
             ->assertJsonPath('total', 0);
    }

    public function test_reunion_creada_por_el_admin_llega_al_chofer(): void
    {
        $admin  = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $chofer = $this->chofer();

        $this->actingAs($admin)->withSession(['2fa_verified' => true])
             ->post('/admin/reuniones', [
                 'titulo' => 'Capacitación de higiene', 'tipo' => 'capacitacion',
                 'fecha_hora' => now()->addDays(2)->format('Y-m-d H:i'), 'lugar' => 'Planta',
             ])->assertRedirect();

        Sanctum::actingAs($chofer);
        $this->getJson('/api/notificaciones')
             ->assertOk()
             ->assertJsonPath('total_no_leidas', 1)
             ->assertJsonPath('notificaciones.0.tipo', 'reunion')
             ->assertJsonPath('notificaciones.0.leida', false);
        $this->getJson('/api/reuniones')->assertOk()->assertJsonPath('total', 1)
             ->assertJsonPath('reuniones.0.titulo', 'Capacitación de higiene');

        // Leída: sale del contador pero sigue en la bandeja (?todas=1)
        $id = $this->getJson('/api/notificaciones')->json('notificaciones.0.id');
        $this->postJson("/api/notificaciones/{$id}/leer")->assertOk();
        $this->getJson('/api/notificaciones')->assertJsonPath('total_no_leidas', 0)->assertJsonCount(0, 'notificaciones');
        $this->getJson('/api/notificaciones?todas=1')->assertJsonCount(1, 'notificaciones')
             ->assertJsonPath('notificaciones.0.leida', true);
    }
}
