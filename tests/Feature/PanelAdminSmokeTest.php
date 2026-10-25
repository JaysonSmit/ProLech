<?php

namespace Tests\Feature;

use App\Models\AsignacionZona;
use App\Models\Ganadero;
use App\Models\PedidoWeb;
use App\Models\ProductoTienda;
use App\Models\TrackingGps;
use App\Models\TrackingGpsPunto;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Barrido del panel web: abre TODAS las páginas GET de /admin (y las que reciben
 * un id, con datos de ejemplo) como administrador y exige que ninguna falle.
 */
class PanelAdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true, 'name' => 'Chofer Mapa']);
        $zona = Zona::create(['codigo' => 'COLLANA-1', 'nombre' => 'Collana 1', 'orden' => 1, 'activa' => true]);
        AsignacionZona::create(['chofer_id' => $chofer->id, 'zona_id' => $zona->id, 'mes' => now()->month,
            'anio' => now()->year, 'rol_mes' => 'titular', 'fecha_inicio' => now()->startOfMonth(), 'fecha_fin' => now()->endOfMonth()]);
        $g = Ganadero::create(['dni' => '12345678', 'nombre' => 'Juan', 'ruta_asignada' => 'Norte', 'zona_id' => $zona->id,
            'activo' => true, 'estado' => 'Activo', 'geo_lat' => -15.7, 'geo_lon' => -69.99]);
        TrackingGps::actualizarUbicacion($chofer->id, -15.7, -69.99);
        TrackingGpsPunto::create(['chofer_id' => $chofer->id, 'latitud' => -15.61, 'longitud' => -69.97, 'registrado_en' => now()->subMinutes(5)]);
        TrackingGpsPunto::create(['chofer_id' => $chofer->id, 'latitud' => -15.70, 'longitud' => -69.99, 'registrado_en' => now()]);
        $p = ProductoTienda::create(['nombre' => 'Queso Paria', 'precio_venta' => 18, 'stock_disponible' => 5, 'estado' => 'activo']);
        $pedido = PedidoWeb::create(['codigo_pedido' => 'WEB-1001', 'numero_comprobante' => 'B001-00000001',
            'cliente_nombre' => 'Cliente', 'cliente_telefono' => '999', 'cliente_direccion' => 'Huata',
            'total' => 18, 'metodo_pago' => 'Efectivo', 'estado_pedido' => 'Pendiente']);
        $this->ids = ['ganadero' => $g->id, 'producto' => $p->id, 'pedido' => $pedido->id, 'zona' => $zona->id,
            'chofer' => $chofer->id];
    }

    private function comoAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true]);
    }

    public function test_ninguna_pagina_del_panel_da_error(): void
    {
        $fallas = [];
        $revisadas = 0;

        foreach (Route::getRoutes() as $ruta) {
            if (! in_array('GET', $ruta->methods(), true) || ! str_starts_with($ruta->uri(), 'admin')) {
                continue;
            }
            // Sustituye {producto}, {pedido}, {ganadero}... por ids reales; omite el resto
            $uri = preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => $this->ids[$m[1]] ?? '__omitir__', $ruta->uri());
            if (str_contains($uri, '__omitir__')) {
                continue;
            }
            $respuesta = $this->comoAdmin()->get('/' . $uri);
            $revisadas++;
            if ($respuesta->getStatusCode() >= 500) {
                $fallas[] = $uri . ' → ' . $respuesta->getStatusCode() . ' ' .
                    mb_substr(strip_tags((string) ($respuesta->exception?->getMessage() ?? '')), 0, 200);
            }
        }

        $this->assertGreaterThan(10, $revisadas);
        $this->assertSame([], $fallas, "Páginas con error:\n" . implode("\n", $fallas));
    }

    public function test_mapa_gps_entrega_recorrido_y_zona_del_chofer(): void
    {
        $json = $this->comoAdmin()->getJson('/admin/mapa/ubicaciones')->assertOk()->json('choferes.0');

        $this->assertSame('Collana 1', $json['zona']);
        $this->assertCount(2, $json['recorrido']);
    }

    public function test_detalle_de_pedido_tiene_boton_de_boleta(): void
    {
        $this->comoAdmin()->get('/admin/tienda/pedidos/' . $this->ids['pedido'])
             ->assertOk()->assertSee('Boleta B001-00000001');
    }

    public function test_alta_de_ganadero_con_ubicacion_gps(): void
    {
        $this->comoAdmin()->post('/admin/ganaderos', [
            'dni' => '87654321', 'nombre' => 'Rosa', 'ruta_asignada' => 'Norte',
            'zona_id' => $this->ids['zona'], 'geo_lat' => -15.72, 'geo_lon' => -69.98,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(-15.72, Ganadero::where('dni', '87654321')->value('geo_lat'), 0.0001);
    }

    public function test_ubicacion_incompleta_es_rechazada(): void
    {
        $this->comoAdmin()->post('/admin/ganaderos', [
            'dni' => '87654322', 'nombre' => 'Ana', 'ruta_asignada' => 'Norte', 'geo_lat' => -15.72,
        ])->assertSessionHasErrors('geo_lon');
    }
}
