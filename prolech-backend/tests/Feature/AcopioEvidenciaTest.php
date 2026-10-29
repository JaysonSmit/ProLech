<?php

namespace Tests\Feature;

use App\Models\Acopio;
use App\Models\Ganadero;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AcopioEvidenciaTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        $ganadero = Ganadero::create(['dni' => '12345678', 'nombre' => 'Rosa Mamani']);

        return array_merge([
            'ganadero_id'               => $ganadero->id,
            'litros'                    => 40,
            'medicion_balde_confirmada' => true,
            'equipo_lactoscan'          => 1,
            'porcentaje_agua'           => 7,
            'fecha'                     => now()->toDateString(),
            'observaciones'             => 'Adulteración detectada',
        ], $extra);
    }

    public function test_guarda_foto_de_evidencia(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create(['rol' => 'chofer']));

        $jpeg = base64_encode("\xFF\xD8\xFF\xE0" . str_repeat('x', 100));

        $this->postJson('/api/acopios', $this->payload(['foto_evidencia' => $jpeg]))
             ->assertCreated();

        $acopio = Acopio::first();
        $this->assertSame("evidencias/acopio_{$acopio->id}.jpg", $acopio->foto_evidencia);
        Storage::disk('public')->assertExists($acopio->foto_evidencia);
    }

    public function test_rechaza_foto_que_no_es_jpeg(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create(['rol' => 'chofer']));

        $this->postJson('/api/acopios', $this->payload(['foto_evidencia' => base64_encode('no es imagen')]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['foto_evidencia']);

        $this->assertSame(0, Acopio::count());
    }

    public function test_reenvio_con_mismo_client_uuid_no_duplica(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'chofer']));
        $datos = $this->payload(['client_uuid' => (string) \Illuminate\Support\Str::uuid()]);

        $primero = $this->postJson('/api/acopios', $datos)->assertCreated();

        // Reintento offline: mismo uuid → 200 con el mismo acopio, sin error de duplicado del día
        $this->postJson('/api/acopios', $datos)
             ->assertOk()
             ->assertJsonPath('duplicado', true)
             ->assertJsonPath('acopio.id', $primero->json('acopio.id'));

        $this->assertSame(1, Acopio::count());
    }

    public function test_client_uuid_de_otro_chofer_es_rechazado(): void
    {
        $uuid = (string) \Illuminate\Support\Str::uuid();
        Sanctum::actingAs(User::factory()->create(['rol' => 'chofer']));
        $datos = $this->payload(['client_uuid' => $uuid]);
        $this->postJson('/api/acopios', $datos)->assertCreated();

        Sanctum::actingAs(User::factory()->create(['rol' => 'chofer']));
        $this->postJson('/api/acopios', $datos)->assertForbidden();
    }

    public function test_acopio_sin_foto_sigue_funcionando(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'chofer']));

        $this->postJson('/api/acopios', $this->payload())->assertCreated();
        $this->assertNull(Acopio::first()->foto_evidencia);
    }
}
