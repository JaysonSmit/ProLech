<?php

namespace Tests\Feature;

use App\Http\Controllers\Web\UsuarioController;
use App\Models\AsignacionZona;
use App\Models\Ganadero;
use App\Models\Notificacion;
use App\Models\SolicitudRuta;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Detalles: usuarios editables, ruta actual de las solicitudes, caminos en el mapa GPS y avisos al chofer. */
class DetallesSistemaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
    }

    private function comoAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true]);
    }

    public function test_nombres_con_mayusculas_iniciales(): void
    {
        $this->assertSame('Jhon Fredy Quilla Larico', UsuarioController::nombrePropio('  jhon fredy   quilla LARICO '));
        $this->assertSame('María de la Cruz y Ramos', UsuarioController::nombrePropio('maría DE LA cruz Y ramos'));
    }

    public function test_editar_nombre_y_correo_de_un_usuario(): void
    {
        $u = User::factory()->create(['rol' => 'chofer', 'name' => 'Jhon fredy quilla larico', 'email' => 'fredy1@prolech.com']);

        $this->comoAdmin()->post("/admin/usuarios/{$u->id}/accesos", [
            'name' => 'jhon fredy quilla larico', 'email' => 'Jhon.Quilla@ProLech.com', 'rol' => 'chofer',
        ])->assertSessionHas('success');

        $u->refresh();
        $this->assertSame('Jhon Fredy Quilla Larico', $u->name);
        $this->assertSame('jhon.quilla@prolech.com', $u->email);
        // El correo no puede repetirse con otro usuario
        $this->comoAdmin()->post("/admin/usuarios/{$u->id}/accesos", ['name' => 'X', 'email' => $this->admin->email, 'rol' => 'chofer'])
            ->assertSessionHasErrors('email');
    }

    public function test_formulario_de_usuarios_sin_autocompletar(): void
    {
        $this->comoAdmin()->get('/admin/usuarios')->assertOk()
            ->assertSee('autocomplete="off"', false)->assertSee('autocomplete="new-password"', false)
            ->assertSee('✏️ Editar usuario');
    }

    public function test_la_solicitud_guarda_y_muestra_la_ruta_del_mes(): void
    {
        $zona = Zona::create(['codigo' => 'HUATA1', 'nombre' => 'HUATA1', 'orden' => 5, 'activa' => true]);
        $chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        AsignacionZona::asignar($chofer->id, $zona->id, now()->month, now()->year);
        Sanctum::actingAs($chofer);

        $this->postJson('/api/solicitar-ruta', ['ruta_solicitada' => 'Collana 2', 'motivo' => 'Más cerca de mi casa'])->assertSuccessful();
        $this->assertSame('HUATA1', SolicitudRuta::sole()->ruta_actual);

        // Si la ruta cambia antes de responder, la pendiente muestra la de hoy
        SolicitudRuta::query()->update(['ruta_actual' => 'Sin asignar']);
        $this->comoAdmin()->get('/admin/rutas/solicitudes')->assertOk()->assertSee('HUATA1');
    }

    public function test_el_mapa_gps_dibuja_los_caminos_trazados(): void
    {
        Zona::create(['codigo' => 'HUATA1', 'nombre' => 'HUATA1', 'orden' => 5, 'activa' => true,
            'trazado' => [[-15.61, -69.97], [-15.65, -70.0]], 'trazado_km' => 5.2]);

        $this->comoAdmin()->get('/admin/mapa')->assertOk()->assertSee('var RUTAS', false)->assertSee('HUATA1');
    }

    public function test_el_chofer_recibe_aviso_cuando_cambian_su_ruta(): void
    {
        $zona = Zona::create(['codigo' => 'HUATA1', 'nombre' => 'HUATA1', 'orden' => 5, 'activa' => true]);
        $chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        $g = Ganadero::create(['dni' => '1', 'nombre' => 'Rosa Quispe', 'activo' => true]);

        $this->comoAdmin()->postJson("/admin/rutas/{$zona->id}/chofer", ['chofer_id' => $chofer->id])->assertOk();
        $this->comoAdmin()->postJson("/admin/rutas/{$zona->id}/proveedores", ['ganadero_ids' => [$g->id], 'accion' => 'agregar'])->assertOk();
        $this->comoAdmin()->postJson("/admin/rutas/trazar/{$zona->id}", ['trazado' => [[-15.61, -69.97], [-15.65, -70.0]]])->assertOk();

        $avisos = Notificacion::where('user_id', $chofer->id)->where('tipo', 'ruta_cambiada')->pluck('mensaje');
        $this->assertCount(3, $avisos);
        $this->assertStringContainsString('Rosa Quispe', $avisos[1]);
    }
}
