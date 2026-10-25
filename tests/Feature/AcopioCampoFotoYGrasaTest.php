<?php

namespace Tests\Feature;

use App\Models\AcopioCampo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Grasa del Lactoscan y foto de evidencia en los acopios offline-first. */
class AcopioCampoFotoYGrasaTest extends TestCase
{
    use RefreshDatabase;

    private const UUID = '0f8fad5b-d9cb-469f-a165-70867728950e';

    private function acopio(array $extra = []): array
    {
        return array_merge([
            'uuid' => self::UUID, 'ganadero_id' => 7, 'ganadero_nombre' => 'Juan',
            'litros' => 40, 'porcentaje_agua' => 0, 'densidad' => 1.03,
            'estado_calidad' => 'APROBADO', 'actualizado_en' => 1000,
        ], $extra);
    }

    public function test_guarda_y_devuelve_la_grasa(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'chofer', 'activo' => true]));

        $this->postJson('/api/acopios-campo', $this->acopio(['grasa' => 3.6]))->assertCreated()
             ->assertJsonPath('data.grasa', 3.6);
        $this->getJson('/api/acopios-campo')->assertJsonPath('data.0.grasa', 3.6);
    }

    public function test_rechaza_grasa_fuera_de_rango_con_mensaje(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'chofer', 'activo' => true]));

        $this->postJson('/api/acopios-campo', $this->acopio(['grasa' => 40]))
             ->assertUnprocessable()->assertJsonValidationErrors('grasa');
    }

    public function test_sube_la_foto_de_evidencia(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create(['rol' => 'chofer', 'activo' => true]));
        $this->postJson('/api/acopios-campo', $this->acopio())->assertCreated();

        $r = $this->post('/api/acopios-campo/' . self::UUID . '/foto', [
            'foto' => UploadedFile::fake()->image('balde.jpg', 800, 600),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertNotNull($r->json('data.foto_url'));
        Storage::disk('public')->assertExists(AcopioCampo::first()->foto_path);
    }

    public function test_no_puede_subir_foto_a_un_acopio_ajeno(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create(['rol' => 'chofer', 'activo' => true]));
        $this->postJson('/api/acopios-campo', $this->acopio())->assertCreated();

        Sanctum::actingAs(User::factory()->create(['rol' => 'chofer', 'activo' => true]));
        $this->post('/api/acopios-campo/' . self::UUID . '/foto', [
            'foto' => UploadedFile::fake()->image('x.jpg'),
        ], ['Accept' => 'application/json'])->assertNotFound();
    }
}
