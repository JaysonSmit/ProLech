<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_invitado_es_redirigido_desde_la_raiz(): void
    {
        $response = $this->get('/');

        // Sin sesión, la raíz envía al login
        $response->assertRedirect();
    }
}
