<?php

namespace Tests\Feature;

use App\Models\AcopioCampo;
use App\Models\AsignacionZona;
use App\Models\Ganadero;
use App\Models\TrackingGps;
use App\Models\TrackingGpsPunto;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Panel en vivo, repetición de recorrido, zonas en el mapa y exportación. */
class OperacionesPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $chofer;
    private Zona $zona;
    private Ganadero $ganadero;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin  = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $this->chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true, 'name' => 'Carlos Ramos']);
        $this->zona   = Zona::create(['codigo' => 'COLLANA-1', 'nombre' => 'Collana 1', 'orden' => 1, 'activa' => true]);
        AsignacionZona::create(['chofer_id' => $this->chofer->id, 'zona_id' => $this->zona->id, 'mes' => now()->month,
            'anio' => now()->year, 'rol_mes' => 'titular', 'fecha_inicio' => now()->startOfMonth(), 'fecha_fin' => now()->endOfMonth()]);
        $this->ganadero = Ganadero::create(['dni' => '12345678', 'nombre' => 'Juan Pérez', 'zona_id' => $this->zona->id,
            'activo' => true, 'estado' => 'Activo', 'geo_lat' => -15.70, 'geo_lon' => -69.99]);

        foreach ([[40, 'APROBADO', 0], [12.5, 'RECHAZADO', 6.0]] as $i => [$litros, $calidad, $agua]) {
            AcopioCampo::create([
                'uuid' => "0f8fad5b-d9cb-469f-a165-7086772895{$i}0", 'chofer_id' => $this->chofer->id,
                'ganadero_id' => $this->ganadero->id, 'ganadero_nombre' => 'Juan Pérez', 'litros' => $litros,
                'porcentaje_agua' => $agua, 'densidad' => 1.03, 'estado_calidad' => $calidad,
                'actualizado_en' => now()->getTimestampMs(), 'grasa' => 3.5,
            ]);
        }
        TrackingGps::actualizarUbicacion($this->chofer->id, -15.70, -69.99);
        TrackingGpsPunto::create(['chofer_id' => $this->chofer->id, 'latitud' => -15.6167, 'longitud' => -69.9708, 'registrado_en' => now()->subMinutes(30)]);
        TrackingGpsPunto::create(['chofer_id' => $this->chofer->id, 'latitud' => -15.7000, 'longitud' => -69.9900, 'registrado_en' => now()->subMinutes(5)]);
    }

    private function comoAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true]);
    }

    public function test_panel_en_vivo_resume_el_dia(): void
    {
        $d = $this->comoAdmin()->getJson('/admin/en-vivo/datos')->assertOk()->json();

        $this->assertEquals(52.5, $d['litros_hoy']);
        $this->assertSame(2, $d['entregas_hoy']);
        $this->assertSame(50, $d['pct_aprobado']);
        $this->assertSame('Collana 1', $d['por_zona'][0]['zona']);
        $this->assertEquals(52.5, $d['por_zona'][0]['litros']);
        $this->assertSame('Carlos Ramos', $d['por_zona'][0]['chofer']);
        $this->assertSame('RECHAZADO', $d['alertas'][0]['calidad']);
        $this->assertCount(1, $d['en_ruta']);
        $this->assertGreaterThan(9, $d['en_ruta'][0]['km_hoy']);

        $this->comoAdmin()->get('/admin/en-vivo')->assertOk()->assertSee('Litros hoy');
    }

    public function test_recorrido_entrega_puntos_km_y_acopios_para_la_repeticion(): void
    {
        $d = $this->comoAdmin()->getJson("/admin/operaciones/recorrido/{$this->chofer->id}/datos")->assertOk()->json();

        $this->assertCount(2, $d['puntos']);
        $this->assertGreaterThan(9, $d['km']);   // planta → predio ≈ 9,4 km en línea recta
        $this->assertCount(2, $d['acopios']);
        $this->assertEquals(-15.70, $d['acopios'][0]['lat']);

        $this->comoAdmin()->get('/admin/operaciones/recorrido')->assertOk()->assertSee('Carlos Ramos')->assertSee('km');
    }

    public function test_zonas_en_el_mapa_cambia_zona_y_ubicacion(): void
    {
        $otra = Zona::create(['codigo' => 'COLLANA-2', 'nombre' => 'Collana 2', 'orden' => 2, 'activa' => true]);

        $this->comoAdmin()->postJson("/admin/zonas/mapa/{$this->ganadero->id}", ['zona_id' => $otra->id])->assertOk();
        $this->comoAdmin()->postJson("/admin/zonas/mapa/{$this->ganadero->id}", ['geo_lat' => -15.75, 'geo_lon' => -70.0])->assertOk();

        $g = $this->ganadero->fresh();
        $this->assertSame($otra->id, $g->zona_id);
        $this->assertEqualsWithDelta(-15.75, $g->geo_lat, 0.00001);
        // Los ganaderos se inyectan como JSON para Leaflet (las tildes van escapadas): se busca el DNI
        $this->comoAdmin()->get('/admin/zonas/mapa')->assertOk()->assertSee('12345678')->assertSee('Collana 2');
    }

    public function test_mover_un_ganadero_con_ruta_no_le_cambia_la_ruta_y_uno_sin_ruta_toma_la_zona(): void
    {
        $poligono = [[-15.70, -70.05], [-15.70, -69.95], [-15.80, -69.95], [-15.80, -70.05]];
        $conPoligono = Zona::create(['codigo' => 'C3', 'nombre' => 'Collana 3', 'orden' => 3, 'activa' => true, 'poligono' => $poligono]);
        $sinPoligono = Zona::create(['codigo' => 'HUATA1', 'nombre' => 'HUATA1', 'orden' => 5, 'activa' => true]);
        $this->ganadero->update(['zona_id' => $sinPoligono->id]);
        $sinRuta = \App\Models\Ganadero::create(['dni' => '99999999', 'nombre' => 'Nuevo', 'activo' => true]);

        $this->comoAdmin()->postJson("/admin/zonas/mapa/{$this->ganadero->id}", ['geo_lat' => -15.75, 'geo_lon' => -70.0])->assertOk();
        $this->comoAdmin()->postJson("/admin/zonas/mapa/{$sinRuta->id}", ['geo_lat' => -15.75, 'geo_lon' => -70.0])->assertOk();

        $this->assertSame($sinPoligono->id, $this->ganadero->fresh()->zona_id);
        $this->assertSame($conPoligono->id, $sinRuta->fresh()->zona_id);
    }

    public function test_exportar_acopios_genera_csv_para_excel(): void
    {
        $r = $this->comoAdmin()->get('/admin/exportar/acopios.csv')->assertOk();
        $csv = $r->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'BOM para que Excel lea las tildes');
        $this->assertStringContainsString('Fecha;Hora;Ganadero;DNI;Zona;Chofer;Litros', $csv);
        $this->assertStringContainsString('Juan Pérez', $csv);
        $this->assertStringContainsString('12,5', $csv, 'decimales con coma');
        $this->assertStringContainsString('Collana 1', $csv);
    }

    public function test_exportar_rutas_y_liquidaciones_y_pdf(): void
    {
        $this->assertStringContainsString('Carlos Ramos', $this->comoAdmin()->get('/admin/exportar/rutas.csv')->streamedContent());
        $this->assertStringContainsString('Sin datos', $this->comoAdmin()->get('/admin/exportar/liquidaciones.csv')->streamedContent());
        $this->comoAdmin()->get('/admin/exportar/acopios/imprimir')->assertOk()->assertSee('Reporte de acopios')->assertSee('52.5');
        $this->comoAdmin()->get('/admin/exportar')->assertOk()->assertSee('Liquidaciones');
    }

    public function test_exportar_en_excel_xlsx(): void
    {
        $r = $this->comoAdmin()->get('/admin/exportar/acopios.csv?formato=xlsx')->assertOk()
                  ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $xlsx = $r->getContent();

        $this->assertStringStartsWith("PK\x03\x04", $xlsx, 'es un ZIP');
        $this->assertStringContainsString('xl/worksheets/sheet1.xml', $xlsx);
        // Entradas sin comprimir: el XML de la hoja se lee tal cual
        $this->assertStringContainsString('<t xml:space="preserve">Juan Pérez</t>', $xlsx);
        $this->assertStringContainsString('<v>12.5</v>', $xlsx, 'litros como número, no texto');
        $this->assertStringContainsString('state="frozen"', $xlsx);

        // El CRC de cada entrada coincide con su contenido (Excel rechaza ZIPs corruptos)
        preg_match_all('/PK\x03\x04.{10}(.{4})(.{4}).{4}(..)(..)/s', $xlsx, $m, PREG_OFFSET_CAPTURE);
        foreach ($m[0] as $i => [$cab, $pos]) {
            $crc = unpack('V', $m[1][$i][0])[1];
            $tam = unpack('V', $m[2][$i][0])[1];
            $largoNombre = unpack('v', $m[3][$i][0])[1];
            $this->assertSame($crc, crc32(substr($xlsx, $pos + 30 + $largoNombre, $tam)));
        }
        $this->assertCount(6, $m[0]);
    }

    public function test_exportar_en_pdf(): void
    {
        foreach (['/admin/exportar/acopios.csv', '/admin/exportar/liquidaciones.csv', '/admin/exportar/rutas.csv'] as $url) {
            $r = $this->comoAdmin()->get("$url?formato=pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $r->getContent(), $url);
        }
    }

    public function test_un_chofer_no_entra_al_panel(): void
    {
        $this->actingAs($this->chofer)->withSession(['2fa_verified' => true])->get('/admin/en-vivo')->assertForbidden();
    }
}
