<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_bloquea_fuerza_bruta_tras_5_intentos(): void
    {
        $u = User::factory()->create(['rol' => 'chofer', 'activo' => true]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => $u->email, 'password' => 'mala'])->assertUnprocessable();
        }
        $this->postJson('/api/login', ['email' => $u->email, 'password' => 'mala'])
             ->assertStatus(429)->assertJsonPath('message', 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.');
        // Ni siquiera con la clave correcta mientras dure el bloqueo
        $this->postJson('/api/login', ['email' => $u->email, 'password' => 'password'])->assertStatus(429);
    }

    public function test_otro_correo_no_queda_bloqueado(): void
    {
        $atacado = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $otro    = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/login', ['email' => $atacado->email, 'password' => 'mala']);
        }

        $this->postJson('/api/login', ['email' => $otro->email, 'password' => 'password'])->assertOk();
    }

    public function test_las_credenciales_demo_no_se_publican_fuera_de_local(): void
    {
        $this->assertFalse(app()->environment('local'));   // los tests corren en "testing"
        $this->get('/login')->assertOk()->assertDontSee('prolech2024');
    }
}
