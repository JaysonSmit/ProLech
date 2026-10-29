<?php

namespace Tests\Feature;

use App\Models\Acopio;
use App\Models\AsignacionZona;
use App\Models\Configuracion;
use App\Models\Ganadero;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Más rutas que choferes: segunda ruta del mes, volante que cubre huecos y mi-ruta con las dos. */
class DosRutasPorChoferTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
    }

    private function zonas(int $n): array
    {
        return array_map(fn ($i) => Zona::create(['codigo' => "R{$i}", 'nombre' => "Ruta {$i}", 'orden' => $i, 'activa' => true]), range(1, $n));
    }

    private function choferes(int $n): array
    {
        return array_map(fn () => User::factory()->create(['rol' => 'chofer', 'activo' => true]), range(1, $n));
    }

    private function comoAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true]);
    }

    public function test_rotacion_con_mas_rutas_que_choferes_no_falla_y_el_volante_cubre_la_libre(): void
    {
        $z = $this->zonas(4);
        $ch = $this->choferes(4);                       // 3 titulares + 1 volante para 4 rutas

        $this->comoAdmin()->post('/admin/rotacion/generar-automatico', ['mes' => now()->month, 'anio' => now()->year])
             ->assertRedirect()->assertSessionHas('success');

        $volante = AsignacionZona::where('rol_mes', 'volante')->sole();
        $this->assertSame($z[3]->id, $volante->zona_extra_id);   // la 4.ª ruta la cubre el volante
        $this->assertCount(0, AsignacionZona::zonasSinChofer(now()->month, now()->year));
        $this->assertSame($volante->chofer_id, AsignacionZona::choferDeZona($z[3]->id));
    }

    public function test_mi_ruta_junta_las_paradas_de_las_dos_rutas_y_avisa_la_capacidad(): void
    {
        [$a, $b] = $this->zonas(2);
        [$chofer] = $this->choferes(1);
        AsignacionZona::asignar($chofer->id, $a->id, now()->month, now()->year);
        AsignacionZona::asignarSegundaRuta($chofer->id, $b->id, now()->month, now()->year);
        $g1 = Ganadero::create(['dni' => '1', 'nombre' => 'Ana', 'activo' => true, 'zona_id' => $a->id, 'estado' => 'Activo']);
        $g2 = Ganadero::create(['dni' => '2', 'nombre' => 'Beto', 'activo' => true, 'zona_id' => $b->id, 'estado' => 'Activo']);
        Configuracion::set('capacidad_camion_litros', 100.0);
        foreach ([$g1, $g2] as $g) {
            Acopio::create(['ganadero_id' => $g->id, 'chofer_id' => $chofer->id, 'litros' => 80, 'estado' => 'aprobado', 'fecha' => today()->subDay()]);
        }

        Sanctum::actingAs($chofer);
        $r = $this->getJson('/api/mi-ruta')->assertOk();
        $r->assertJsonPath('ruta_nombre', 'Ruta 1 + Ruta 2')
          ->assertJsonPath('total', 2)
          ->assertJsonPath('paradas.0.ruta', 'Ruta 1')      // primero la ruta principal
          ->assertJsonPath('paradas.1.ruta', 'Ruta 2')
          ->assertJsonCount(2, 'rutas');
        $this->assertStringContainsString('descarga en la planta', $r->json('aviso_capacidad'));
    }

    public function test_panel_segunda_ruta_valida_titular_y_avisa_al_chofer(): void
    {
        [$a, $b] = $this->zonas(2);
        [$c1, $c2] = $this->choferes(2);
        AsignacionZona::asignar($c1->id, $a->id, now()->month, now()->year);
        AsignacionZona::asignar($c2->id, null, now()->month, now()->year);

        // No se puede tomar como segunda ruta una que ya tiene titular
        $this->comoAdmin()->post('/admin/rotacion/segunda-ruta', ['chofer_id' => $c2->id, 'zona_extra_id' => $a->id, 'mes' => now()->month, 'anio' => now()->year])
             ->assertSessionHas('error');

        $this->comoAdmin()->post('/admin/rotacion/segunda-ruta', ['chofer_id' => $c2->id, 'zona_extra_id' => $b->id, 'mes' => now()->month, 'anio' => now()->year])
             ->assertSessionHas('success');
        $this->assertSame($b->id, AsignacionZona::where('chofer_id', $c2->id)->value('zona_extra_id'));
        $this->assertSame(1, Notificacion::where('user_id', $c2->id)->where('tipo', 'ruta_cambiada')->count());

        // Si luego la ruta recibe titular, deja de ser segunda ruta del otro
        AsignacionZona::asignar($c1->id, $b->id, now()->month, now()->year);
        $this->assertNull(AsignacionZona::where('chofer_id', $c2->id)->value('zona_extra_id'));

        $this->comoAdmin()->get('/admin/rotacion')->assertOk()->assertSee('Segunda ruta del mes');
    }
}
