<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Un usuario con rol principal y accesos adicionales. */
class RolesMultiplesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
    }

    private function como(User $u): static
    {
        return $this->actingAs($u)->withSession(['2fa_verified' => true]);
    }

    public function test_crear_usuario_con_accesos_adicionales(): void
    {
        $this->como($this->admin)->post('/admin/usuarios', [
            'name' => 'Jhon Fredy Quilla', 'email' => 'jhon@prolech.com', 'rol' => 'despacho',
            'roles_extra' => ['chofer', 'despacho'],   // el principal repetido se ignora
            'password' => 'prolech2026', 'password_confirmation' => 'prolech2026',
        ])->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'Personal de despacho + Chofer'));

        $u = User::where('email', 'jhon@prolech.com')->sole();
        $this->assertSame(['despacho', 'chofer'], $u->roles());
        $this->assertSame(['chofer'], $u->roles_extra);
    }

    public function test_el_chofer_adicional_cuenta_como_chofer_en_listas_y_en_la_app(): void
    {
        $u = User::factory()->create(['rol' => 'despacho', 'roles_extra' => ['chofer'], 'activo' => true,
            'email' => 'mixto@prolech.com', 'password' => 'secreto123']);
        $soloDespacho = User::factory()->create(['rol' => 'despacho', 'activo' => true]);

        $ids = User::conRol('chofer')->pluck('id');
        $this->assertTrue($ids->contains($u->id));
        $this->assertFalse($ids->contains($soloDespacho->id));

        // Se le puede asignar una ruta y entra a la app como chofer
        $zona = Zona::create(['codigo' => 'C1', 'nombre' => 'Collana 1', 'orden' => 1, 'activa' => true]);
        $this->como($this->admin)->postJson("/admin/rutas/{$zona->id}/chofer", ['chofer_id' => $u->id])->assertOk();
        $this->postJson('/api/login', ['email' => 'mixto@prolech.com', 'password' => 'secreto123'])
            ->assertOk()->assertJsonPath('user.rol', 'chofer')->assertJsonPath('user.roles', ['despacho', 'chofer']);
    }

    public function test_ve_las_secciones_de_todos_sus_roles_y_nada_mas(): void
    {
        $u = User::factory()->create(['rol' => 'despacho', 'roles_extra' => ['calidad'], 'activo' => true]);

        $this->como($u)->get('/admin/ventas')->assertOk();                  // despacho
        $this->como($u)->get('/admin/calidad/plan')->assertOk();            // calidad (acceso adicional)
        $this->como($u)->get('/admin/usuarios')->assertForbidden();         // solo admin
        $this->assertTrue($u->seccionWebPermitida('calidad_plan'));
        $this->assertFalse($u->seccionWebPermitida('liquidaciones'));
    }

    public function test_editar_accesos_y_no_quitarse_el_admin_propio(): void
    {
        $u = User::factory()->create(['rol' => 'chofer', 'activo' => true]);

        $this->como($this->admin)->post("/admin/usuarios/{$u->id}/accesos", ['rol' => 'chofer', 'roles_extra' => ['despacho']])
            ->assertSessionHas('success');
        $this->assertSame(['chofer', 'despacho'], $u->fresh()->roles());

        $this->como($this->admin)->post("/admin/usuarios/{$u->id}/accesos", ['rol' => 'chofer'])->assertSessionHas('success');
        $this->assertNull($u->fresh()->roles_extra);

        $this->como($this->admin)->post("/admin/usuarios/{$this->admin->id}/accesos", ['rol' => 'despacho'])
            ->assertSessionHas('error');
        $this->assertSame('admin', $this->admin->fresh()->rol);
    }

    public function test_la_lista_de_usuarios_muestra_los_accesos(): void
    {
        User::factory()->create(['rol' => 'despacho', 'roles_extra' => ['chofer'], 'name' => 'Mixto']);

        $this->como($this->admin)->get('/admin/usuarios')->assertOk()
            ->assertSee('+ Chofer / Acopiador')->assertSee('Accesos adicionales')->assertSee('abrirAccesos(', false);
    }
}
