<?php

namespace Tests\Feature;

use App\Models\AsignacionZona;
use App\Models\Notificacion;
use App\Models\SolicitudRuta;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Solicitudes de cambio de ruta: la página no queda bloqueada y aprobar cambia la ruta. */
class SolicitudesRutaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $chofer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $this->chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true, 'name' => 'Fredy']);
    }

    private function comoAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true]);
    }

    private function solicitud(string $ruta): SolicitudRuta
    {
        return SolicitudRuta::create([
            'chofer_id' => $this->chofer->id, 'ruta_actual' => 'Sin asignar', 'ruta_solicitada' => $ruta,
            'motivo' => 'Vivo cerca', 'estado' => 'pendiente', 'fecha_solicitud' => now(),
        ]);
    }

    public function test_la_pagina_carga_con_el_modal_cerrado(): void
    {
        $this->comoAdmin()->get('/admin/rutas/solicitudes')->assertOk()
            ->assertSee('id="modalOverlay"', false)
            ->assertDontSee('modal-overlay abierto', false)
            // la animación de entrada del layout ya no fuerza opacity:1 sobre el fondo del modal
            ->assertSee('.content > :not(.modal-overlay)', false)
            ->assertSee('--blanco:', false);
    }

    public function test_aprobar_asigna_la_ruta_pedida_e_intercambia(): void
    {
        $zona = Zona::create(['codigo' => 'COLLANA-2', 'nombre' => 'Collana 2', 'orden' => 2, 'activa' => true]);
        $otro = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        AsignacionZona::asignar($otro->id, $zona->id, now()->month, now()->year);
        $s = $this->solicitud('collana 2');

        $this->comoAdmin()->post("/admin/rutas/solicitudes/{$s->id}/responder", ['accion' => 'aprobado', 'nota_respuesta' => 'Ok'])
            ->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'Ya quedó asignado a Collana 2'));

        $this->assertSame('aprobado', $s->fresh()->estado);
        $this->assertSame($zona->id, AsignacionZona::where('chofer_id', $this->chofer->id)->value('zona_id'));
        $this->assertNull(AsignacionZona::where('chofer_id', $otro->id)->value('zona_id'));
        $this->assertSame(1, Notificacion::where('user_id', $this->chofer->id)->where('tipo', 'ruta_aprobada')->count());
    }

    public function test_rechazar_no_cambia_la_ruta(): void
    {
        Zona::create(['codigo' => 'COLLANA-2', 'nombre' => 'Collana 2', 'orden' => 2, 'activa' => true]);
        $s = $this->solicitud('Collana 2');

        $this->comoAdmin()->post("/admin/rutas/solicitudes/{$s->id}/responder", ['accion' => 'rechazado'])->assertRedirect();

        $this->assertSame('rechazado', $s->fresh()->estado);
        $this->assertFalse(AsignacionZona::where('chofer_id', $this->chofer->id)->exists());
    }

    public function test_aprobar_una_ruta_inexistente_avisa_que_hay_que_asignarla_a_mano(): void
    {
        $s = $this->solicitud('Ruta Lunar');

        $this->comoAdmin()->post("/admin/rutas/solicitudes/{$s->id}/responder", ['accion' => 'aprobado'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'no existe en el sistema'));
    }
}
