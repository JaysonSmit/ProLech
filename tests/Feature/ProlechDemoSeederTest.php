<?php

namespace Tests\Feature;

use App\Models\Acopio;
use App\Models\AsignacionZona;
use App\Models\Ganadero;
use App\Models\PedidoWeb;
use App\Models\ProductoTienda;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** La base de datos de demostración se puede crear desde cero y deja el sistema "lleno". */
class ProlechDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_completo_desde_cero_y_repetible(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $this->seed(\Database\Seeders\ProlechDemoSeeder::class);   // 2.ª vez: no duplica

        $this->assertSame(4, Zona::whereNotNull('poligono')->count());
        $this->assertSame(5, User::where('rol', 'chofer')->count());
        $this->assertSame(4, AsignacionZona::where('rol_mes', 'titular')->where('mes', now()->month)->count());
        $this->assertSame(1, AsignacionZona::where('rol_mes', 'volante')->where('mes', now()->month)->count());
        $this->assertGreaterThanOrEqual(20, Ganadero::whereNotNull('zona_id')->whereNotNull('geo_lat')->count());
        $this->assertSame(1, Ganadero::where('estado', 'Suspendido')->count());
        $this->assertGreaterThan(200, Acopio::count());
        $this->assertSame(Acopio::count(), Acopio::distinct('client_uuid')->count('client_uuid') + Acopio::whereNull('client_uuid')->count());

        // Cada ganadero quedó dentro del polígono de su zona
        Ganadero::whereNotNull('zona_id')->with('zona')->get()
            ->each(fn ($g) => $this->assertTrue($g->zona->contiene((float) $g->geo_lat, (float) $g->geo_lon), $g->nombre));

        // Tienda con imágenes que existen y pedidos en varios estados
        foreach (ProductoTienda::all() as $p) {
            Storage::disk('public')->assertExists($p->imagen_url);
        }
        $this->assertEqualsCanonicalizing(['Pendiente', 'Pagado', 'Enviado'], PedidoWeb::distinct()->pluck('estado_pedido')->all());
    }

    public function test_chofer_sembrado_recibe_su_ruta_por_la_api(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $carlos = User::where('email', 'carlos@prolech.com')->first();

        $ruta = $this->actingAs($carlos, 'sanctum')->getJson('/api/mi-ruta')->assertOk()->json();

        $this->assertSame('Collana 1', $ruta['ruta_nombre']);
        $this->assertSame(5, $ruta['total']);
        $this->assertSame(1500, (int) $ruta['capacidad_litros']);
    }

    public function test_ubicar_en_el_mapa_asigna_la_zona_del_poligono(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('rol', 'admin')->first();
        $g = Ganadero::where('dni', '12345678')->first();   // Collana 1
        $collana3 = Zona::where('codigo', 'COLLANA-3')->first();

        $this->actingAs($admin)->withSession(['2fa_verified' => true])
             ->postJson("/admin/zonas/mapa/{$g->id}", ['geo_lat' => -15.6620, 'geo_lon' => -70.0400])
             ->assertOk();

        $this->assertSame($collana3->id, $g->fresh()->zona_id);
    }
}
