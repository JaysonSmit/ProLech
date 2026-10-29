<?php

namespace Tests\Feature;

use App\Models\AcopioCampo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AcopioCampoApiTest extends TestCase
{
    use RefreshDatabase;

    private function chofer(): User
    {
        return User::factory()->create(['rol' => 'chofer', 'activo' => true]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'uuid'            => (string) Str::uuid(),
            'ganadero_id'     => 1,
            'ganadero_nombre' => 'Juan Quispe',
            'litros'          => 45.5,
            'porcentaje_agua' => 1.2,
            'densidad'        => 1.031,
            'estado_calidad'  => 'APROBADO',
            'actualizado_en'  => 1_760_000_000_000,
        ], $extra);
    }

    public function test_requiere_autenticacion(): void
    {
        $this->getJson('/api/acopios-campo')->assertUnauthorized();
    }

    public function test_rol_no_permitido_recibe_403(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'ganadero']));

        $this->getJson('/api/acopios-campo')->assertForbidden();
    }

    public function test_crea_y_lista_acopios_del_chofer(): void
    {
        Sanctum::actingAs($this->chofer());
        $datos = $this->payload();

        $this->postJson('/api/acopios-campo', $datos)
             ->assertCreated()
             ->assertJsonPath('data.uuid', $datos['uuid']);

        $this->getJson('/api/acopios-campo')
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.ganadero_nombre', 'Juan Quispe');
    }

    public function test_post_repetido_es_idempotente(): void
    {
        Sanctum::actingAs($this->chofer());
        $datos = $this->payload();

        $this->postJson('/api/acopios-campo', $datos)->assertCreated();
        $this->postJson('/api/acopios-campo', $datos)->assertOk();

        $this->assertSame(1, AcopioCampo::count());
    }

    public function test_last_write_wins_conserva_version_mas_reciente(): void
    {
        Sanctum::actingAs($this->chofer());
        $datos = $this->payload(['actualizado_en' => 2000]);
        $this->postJson('/api/acopios-campo', $datos)->assertCreated();

        // Llega una edición más vieja: se ignora
        $this->putJson("/api/acopios-campo/{$datos['uuid']}", array_merge($datos, [
            'litros' => 10, 'actualizado_en' => 1000,
        ]))->assertOk()->assertJsonPath('data.litros', 45.5);

        // Llega una edición más nueva: se aplica
        $this->putJson("/api/acopios-campo/{$datos['uuid']}", array_merge($datos, [
            'litros' => 60, 'actualizado_en' => 3000,
        ]))->assertOk()->assertJsonPath('data.litros', 60);
    }

    public function test_no_puede_tocar_acopios_de_otro_chofer(): void
    {
        $otro  = $this->chofer();
        $datos = $this->payload();
        AcopioCampo::create(array_merge($datos, ['chofer_id' => $otro->id]));

        Sanctum::actingAs($this->chofer());

        $this->putJson("/api/acopios-campo/{$datos['uuid']}", $datos)->assertForbidden();
        $this->deleteJson("/api/acopios-campo/{$datos['uuid']}")->assertNotFound();
        $this->getJson('/api/acopios-campo')->assertJsonCount(0, 'data');
    }

    public function test_elimina_acopio(): void
    {
        Sanctum::actingAs($this->chofer());
        $datos = $this->payload();
        $this->postJson('/api/acopios-campo', $datos)->assertCreated();

        $this->deleteJson("/api/acopios-campo/{$datos['uuid']}")->assertOk();
        $this->deleteJson("/api/acopios-campo/{$datos['uuid']}")->assertNotFound();
    }

    public function test_valida_datos(): void
    {
        Sanctum::actingAs($this->chofer());

        $this->postJson('/api/acopios-campo', $this->payload([
            'uuid' => 'no-es-uuid', 'estado_calidad' => 'MALO',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['uuid', 'estado_calidad']);
    }
}
