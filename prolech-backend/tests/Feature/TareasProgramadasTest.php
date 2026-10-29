<?php

namespace Tests\Feature;

use App\Models\Insumo;
use App\Models\LoginOtp;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Respaldo de la base, limpieza diaria y aviso de stock mínimo. */
class TareasProgramadasTest extends TestCase
{
    private string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();
        // Sin RefreshDatabase: su transacción por prueba impide VACUUM INTO en SQLite.
        // La base es :memory: y nace vacía con cada prueba, así que basta con migrar.
        $this->artisan('migrate');
        $this->carpeta = storage_path('framework/testing/respaldos-' . uniqid());
        config(['respaldo.ruta' => $this->carpeta, 'respaldo.conservar' => 14]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpeta);
        parent::tearDown();
    }

    private function respaldos(): array
    {
        return glob($this->carpeta . DIRECTORY_SEPARATOR . 'prolech-*') ?: [];
    }

    public function test_respaldo_crea_un_archivo_comprimido_con_la_base(): void
    {
        $this->artisan('prolech:respaldo')->assertSuccessful();

        $archivos = $this->respaldos();
        $this->assertCount(1, $archivos);
        $this->assertStringEndsWith('.sqlite.gz', $archivos[0]);
        $this->assertStringStartsWith('SQLite format 3', gzdecode(file_get_contents($archivos[0])));
    }

    public function test_respaldo_conserva_solo_los_mas_recientes(): void
    {
        File::ensureDirectoryExists($this->carpeta);
        foreach (['2026-01-01_020000', '2026-01-02_020000', '2026-01-03_020000'] as $fecha) {
            file_put_contents("{$this->carpeta}/prolech-{$fecha}.sql.gz", 'viejo');
        }

        $this->artisan('prolech:respaldo', ['--conservar' => 2])->assertSuccessful();

        $nombres = array_map('basename', $this->respaldos());
        $this->assertCount(2, $nombres);
        $this->assertContains('prolech-2026-01-03_020000.sql.gz', $nombres);
        $this->assertNotContains('prolech-2026-01-01_020000.sql.gz', $nombres);
    }

    public function test_respaldo_fallido_avisa_al_admin_y_no_borra_los_anteriores(): void
    {
        $admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        File::ensureDirectoryExists($this->carpeta);
        file_put_contents("{$this->carpeta}/prolech-2026-01-01_020000.sql.gz", 'viejo');

        // VACUUM INTO falla si el archivo de destino ya existe
        $this->travelTo(now()->setTime(2, 0, 0));
        $bloqueo = "{$this->carpeta}/prolech-" . now()->format('Y-m-d_His') . '.sqlite.gz.parcial.db';
        file_put_contents($bloqueo, 'ocupado');

        $this->artisan('prolech:respaldo', ['--conservar' => 1])->assertFailed();

        $this->assertSame(1, Notificacion::where('user_id', $admin->id)->where('tipo', 'respaldo_fallido')->count());
        $this->assertFileExists("{$this->carpeta}/prolech-2026-01-01_020000.sql.gz");
        $this->assertFileDoesNotExist(substr($bloqueo, 0, -3), 'no queda un respaldo a medias');
    }

    public function test_limpieza_borra_otps_vencidos_y_notificaciones_leidas_antiguas(): void
    {
        $user = User::factory()->create();
        $vencido = LoginOtp::create(['user_id' => $user->id, 'code' => '111111', 'expires_at' => now()->subDays(2)]);
        $vigente = LoginOtp::create(['user_id' => $user->id, 'code' => '222222', 'expires_at' => now()->addMinutes(5)]);

        $vieja = Notificacion::enviar($user->id, 'Vieja', 'leída');
        $vieja->forceFill(['leida' => true, 'created_at' => now()->subDays(100)])->save();
        $sinLeer = Notificacion::enviar($user->id, 'Vieja', 'sin leer');
        $sinLeer->forceFill(['created_at' => now()->subDays(100)])->save();

        $this->artisan('model:prune', ['--model' => [LoginOtp::class, Notificacion::class]])->assertSuccessful();

        $this->assertModelMissing($vencido);
        $this->assertModelExists($vigente);
        $this->assertModelMissing($vieja);
        $this->assertModelExists($sinLeer);
    }

    public function test_alerta_de_stock_avisa_una_vez_al_dia_a_admin_y_jefe(): void
    {
        $admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $jefe = User::factory()->create(['rol' => 'jefe_produccion', 'activo' => true]);
        $chofer = User::factory()->create(['rol' => 'chofer', 'activo' => true]);
        Insumo::create(['nombre' => 'Cuajo', 'unidad_medida' => 'L', 'costo_unitario' => 10,
            'stock_actual' => 0.5, 'stock_minimo' => 2, 'activo' => true]);
        Insumo::create(['nombre' => 'Sal', 'unidad_medida' => 'kg', 'costo_unitario' => 1,
            'stock_actual' => 50, 'stock_minimo' => 10, 'activo' => true]);

        $this->artisan('prolech:alertas-stock')->assertSuccessful();
        $this->artisan('prolech:alertas-stock')->assertSuccessful();

        $aviso = Notificacion::where('user_id', $admin->id)->where('tipo', 'stock_bajo')->sole();
        $this->assertStringContainsString('Cuajo: 0.5 de 2 L', $aviso->mensaje);
        $this->assertStringNotContainsString('Sal', $aviso->mensaje);
        $this->assertSame(1, Notificacion::where('user_id', $jefe->id)->count());
        $this->assertSame(0, Notificacion::where('user_id', $chofer->id)->count());
    }

    public function test_las_tareas_estan_programadas(): void
    {
        $tareas = collect(app(Schedule::class)->events())
            ->mapWithKeys(fn ($e) => [$e->command => $e->expression]);

        $buscar = fn (string $comando) => $tareas->first(fn ($cron, $cmd) => str_contains($cmd, $comando));

        $this->assertSame('0 2 * * *', $buscar('prolech:respaldo'));
        $this->assertSame('30 6 * * *', $buscar('prolech:alertas-stock'));
        $this->assertNotNull($buscar('model:prune'));
    }
}
