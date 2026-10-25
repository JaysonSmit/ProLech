<?php

namespace Database\Seeders;

use App\Http\Controllers\Web\LiquidacionController;
use App\Models\Acopio;
use App\Models\AcopioCampo;
use App\Models\AsignacionZona;
use App\Models\Configuracion;
use App\Models\Ganadero;
use App\Models\Insumo;
use App\Models\Liquidacion;
use App\Models\PedidoWeb;
use App\Models\ProduccionLote;
use App\Models\ProductoTienda;
use App\Models\Receta;
use App\Models\TrackingGps;
use App\Models\TrackingGpsPunto;
use App\Models\User;
use App\Models\Zona;
use App\Services\CheckoutService;
use App\Services\ProduccionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de demostración realistas de la operación en Huata (Puno).
 *
 *   php artisan db:seed --class=ProlechDemoSeeder
 *
 * Es idempotente: se puede correr varias veces. No borra datos existentes; lo que ya
 * existe (usuarios, zonas, asignaciones del mes, productos) se reutiliza tal cual.
 * Los registros generados llevan el prefijo "demo-" para reconocerlos.
 */
class ProlechDemoSeeder extends Seeder
{
    /** Planta de acopio y producción, Huata. */
    private const PLANTA = [-15.6330, -70.0380];

    private const CLAVE_DEMO = 'prolech2024';

    public function run(): void
    {
        mt_srand(2026);

        $this->planta();
        $zonas    = $this->zonas();
        $choferes = $this->choferes();
        $this->asignaciones($zonas, $choferes);
        $ganaderos = $this->ganaderos($zonas);
        $this->historialAcopios($ganaderos, $zonas, $choferes);
        $this->rutaDeHoy($ganaderos, $zonas, $choferes);
        $this->liquidaciones();
        $productos = $this->produccionYTienda();
        $this->pedidos($productos);

        $this->command?->info('✅ ProlechDemoSeeder: Huata lista para la demostración.');
        $this->command?->info('   → 4 zonas con polígono · 5 choferes (4 titulares + 1 volante) · ' . Ganadero::count() . ' ganaderos');
        $this->command?->info('   → Choferes: carlos@, miguel@, julio@, wilber@, fredy@prolech.com / ' . self::CLAVE_DEMO);
    }

    // ── Planta ────────────────────────────────────────────────────────────

    private function planta(): void
    {
        Configuracion::set('planta_nombre', 'Planta ProLech Huata');
        Configuracion::set('planta_lat', (string) self::PLANTA[0]);
        Configuracion::set('planta_lon', (string) self::PLANTA[1]);
        if (Configuracion::get('capacidad_camion_litros') === null) {
            Configuracion::set('capacidad_camion_litros', '1500');
        }
        foreach ([
            'empresa_razon_social' => 'ProLech — Planta de Lácteos Huata',
            'empresa_ruc'          => '20601234567',
            'empresa_direccion'    => 'Av. Principal s/n, Huata, Puno — Perú',
            'empresa_telefono'     => '951 234 567',
            'yape_numero'          => '951 234 567',
            'yape_titular'         => 'ProLech Huata',
        ] as $clave => $valor) {
            if (Configuracion::get($clave) === null) {
                Configuracion::set($clave, $valor);
            }
        }
    }

    // ── Zonas: cuatro sectores alrededor de la planta ─────────────────────

    /** @return array<string, Zona> */
    private function zonas(): array
    {
        $definicion = [
            'COLLANA-1' => ['Collana 1', 1, '#1f6feb', 'Sector norte: Collana Alta, Chijichaya y Yasín', [
                [-15.5980, -70.0600], [-15.5960, -70.0280], [-15.6110, -70.0180], [-15.6260, -70.0270], [-15.6250, -70.0560],
            ]],
            'COLLANA-2' => ['Collana 2', 2, '#f2a541', 'Sector este, orilla del lago: Ccapia, Ccotos y Huataquita', [
                [-15.6230, -70.0250], [-15.6120, -70.0080], [-15.6330, -69.9930], [-15.6560, -70.0020], [-15.6450, -70.0280],
            ]],
            'COLLANA-3' => ['Collana 3', 3, '#8b5cf6', 'Sector sur: Ccopamaya, Kellhuani y Totorani', [
                [-15.6460, -70.0640], [-15.6440, -70.0300], [-15.6600, -70.0200], [-15.6790, -70.0350], [-15.6720, -70.0660],
            ]],
            'COLLANA-4' => ['Collana 4', 4, '#10b981', 'Sector oeste: Jatun Pampa, Ticani y Moroccollo', [
                [-15.6150, -70.0950], [-15.6200, -70.0620], [-15.6420, -70.0600], [-15.6550, -70.0700], [-15.6400, -70.1000],
            ]],
        ];

        $zonas = [];
        foreach ($definicion as $codigo => [$nombre, $orden, $color, $descripcion, $poligono]) {
            $zona = Zona::firstOrCreate(['codigo' => $codigo], ['nombre' => $nombre, 'orden' => $orden, 'activa' => true]);
            $zona->update([
                'descripcion' => $zona->descripcion ?: $descripcion,
                'poligono'    => $poligono,
                'color'       => $color,
                'centro_lat'  => round(collect($poligono)->avg(0), 7),
                'centro_lon'  => round(collect($poligono)->avg(1), 7),
            ]);
            $zonas[$codigo] = $zona;
        }
        return $zonas;
    }

    // ── Choferes ──────────────────────────────────────────────────────────

    /** @return array<int, User> 0..3 titulares de Collana 1..4, 4 = volante */
    private function choferes(): array
    {
        $datos = [
            ['carlos@prolech.com', 'Carlos Ramos'],
            ['miguel@prolech.com', 'Miguel Torres'],
            ['julio@prolech.com',  'Julio Mamani'],
            ['wilber@prolech.com', 'Wilber Apaza'],
            ['fredy@prolech.com',  'Fredy Ccori'],
        ];
        return array_map(fn ($d) => User::firstOrCreate(['email' => $d[0]], [
            'name' => $d[1], 'password' => Hash::make(self::CLAVE_DEMO), 'rol' => 'chofer', 'activo' => true,
        ]), $datos);
    }

    private function asignaciones(array $zonas, array $choferes): void
    {
        $hoy = now();
        $base = ['mes' => $hoy->month, 'anio' => $hoy->year,
            'fecha_inicio' => $hoy->copy()->startOfMonth()->toDateString(),
            'fecha_fin' => $hoy->copy()->endOfMonth()->toDateString()];

        foreach (array_values($zonas) as $i => $zona) {
            $ocupada = AsignacionZona::where('zona_id', $zona->id)->where('mes', $hoy->month)->where('anio', $hoy->year)->exists();
            $choferOcupado = AsignacionZona::where('chofer_id', $choferes[$i]->id)->where('mes', $hoy->month)->where('anio', $hoy->year)->exists();
            if (! $ocupada && ! $choferOcupado) {
                AsignacionZona::create($base + ['chofer_id' => $choferes[$i]->id, 'zona_id' => $zona->id, 'rol_mes' => 'titular']);
            }
        }
        if (! AsignacionZona::where('chofer_id', $choferes[4]->id)->where('mes', $hoy->month)->where('anio', $hoy->year)->exists()) {
            AsignacionZona::create($base + ['chofer_id' => $choferes[4]->id, 'zona_id' => null, 'rol_mes' => 'volante',
                'notas' => 'Cubre descansos y emergencias de cualquier zona']);
        }
    }

    // ── Ganaderos: 6 por zona con su predio dentro del polígono ───────────

    /** @return array<string, Ganadero[]> por código de zona */
    private function ganaderos(array $zonas): array
    {
        $porZona = [
            'COLLANA-1' => [
                ['12345678', 'Juan Pérez',        'Collana Alta, sector Cruzpata',   -15.6050, -70.0450],
                ['23456789', 'María García',      'Chijichaya, frente a la capilla', -15.6120, -70.0360],
                ['41200311', 'Teodoro Huanca',    'Yasín alto',                      -15.6020, -70.0330],
                ['41200312', 'Felícitas Choque',  'Collana Alta, pampa',             -15.6180, -70.0500],
                ['41200313', 'Gregorio Quispe',   'Chijichaya bajo',                 -15.6200, -70.0280],
            ],
            'COLLANA-2' => [
                ['34567890', 'Pedro López',       'Ccapia, cerca al muelle',         -15.6250, -70.0100],
                ['42200321', 'Hilaria Mamani',    'Ccotos, orilla del lago',         -15.6330, -70.0020],
                ['42200322', 'Nicanor Apaza',     'Huataquita, sector Ayrihua',      -15.6420, -70.0120],
                ['42200323', 'Justina Condori',   'Ccapia, totoral',                 -15.6300, -70.0180],
                ['42200324', 'Valentín Coaquira', 'Huataquita alta',                 -15.6460, -70.0060],
            ],
            'COLLANA-3' => [
                ['45678901', 'Rosa Quispe',       'Ccopamaya, entrada',              -15.6550, -70.0450],
                ['56789012', 'Luis Mamani',       'Kellhuani, sector escuela',       -15.6640, -70.0320],
                ['67890123', 'Elena Condori',     'Totorani',                        -15.6700, -70.0500],
                ['43200331', 'Basilio Flores',    'Ccopamaya, pampa baja',           -15.6580, -70.0560],
                ['43200332', 'Martina Ticona',    'Kellhuani alto',                  -15.6720, -70.0380],
            ],
            'COLLANA-4' => [
                ['44200341', 'Eusebio Calla',     'Jatun Pampa',                     -15.6250, -70.0800],
                ['44200342', 'Dionisia Cutipa',   'Ticani, sector bofedal',          -15.6350, -70.0700],
                ['44200343', 'Faustino Larico',   'Moroccollo',                      -15.6450, -70.0880],
                ['44200344', 'Asunta Pari',       'Jatun Pampa, canal',              -15.6300, -70.0900],
                ['44200345', 'Celestino Ramos',   'Ticani bajo',                     -15.6480, -70.0720],
            ],
        ];

        $resultado = [];
        $tel = 951100200;
        foreach ($porZona as $codigo => $lista) {
            foreach ($lista as [$dni, $nombre, $direccion, $lat, $lon]) {
                $g = Ganadero::firstOrNew(['dni' => $dni]);
                $g->fill([
                    'nombre'                => $g->nombre ?: $nombre,
                    'direccion'             => $direccion,
                    'geo_lat'               => $lat,
                    'geo_lon'               => $lon,
                    'zona_id'               => $zonas[$codigo]->id,
                    'ruta_asignada'         => $zonas[$codigo]->nombre,
                    'telefono'              => $g->telefono ?: (string) $tel,
                    'metodo_pago_preferido' => $g->metodo_pago_preferido ?: ($tel % 3 === 0 ? 'yape' : 'efectivo'),
                    'activo'                => true,
                    'estado'                => $g->estado ?: Ganadero::ESTADO_ACTIVO,
                ])->save();
                $resultado[$codigo][] = $g;
                $tel += 7;
            }
        }
        return $resultado;
    }

    // ── Historial de 14 días (acopios de la app clásica) ──────────────────

    private function historialAcopios(array $ganaderos, array $zonas, array $choferes): void
    {
        $suspendido = '42200324';   // Valentín Coaquira: 2 lecturas con agua esta semana → suspendido
        if (! Acopio::where('client_uuid', 'like', 'demo-%')->exists()) {
            $this->crearHistorial($ganaderos, $zonas, $choferes, $suspendido);
        }

        $strikes = Acopio::where('ganadero_id', Ganadero::where('dni', $suspendido)->value('id'))
            ->whereIn('estado', ['rechazado', 'observado'])
            ->whereBetween('fecha', Acopio::rangoSemanaLiquidacion())->count();
        if ($strikes >= 2) {
            Ganadero::where('dni', $suspendido)->first()->suspender();
        }
    }

    private function crearHistorial(array $ganaderos, array $zonas, array $choferes, string $suspendido): void
    {
        $codigos = array_keys($zonas);
        [$inicioSemana] = Acopio::rangoSemanaLiquidacion();

        for ($d = 14; $d >= 1; $d--) {
            $fecha = now()->subDays($d);
            foreach ($codigos as $iz => $codigo) {
                foreach ($ganaderos[$codigo] as $ig => $g) {
                    $esStrike = $g->dni === $suspendido && $fecha->gte($inicioSemana) && in_array($d, [1, 2], true);
                    if (! $esStrike && mt_rand(1, 100) <= 8) continue;   // a veces no entrega

                    [$agua, $ph] = [round(mt_rand(5, 30) / 10, 1), round(mt_rand(64, 68) / 10, 1)];
                    $sorteo = mt_rand(1, 100);
                    if ($sorteo <= 7)       [$agua, $ph] = [round(mt_rand(55, 85) / 10, 1), 6.5];   // observado
                    elseif ($sorteo <= 9)   [$agua, $ph] = [round(mt_rand(105, 140) / 10, 1), 6.4]; // rechazado

                    if ($esStrike) [$agua, $ph] = [$d === 1 ? 11.5 : 7.2, 6.4];

                    $densidad = round(1.028 + mt_rand(5, 45) / 10000, 4);
                    $litros   = round(mt_rand(150, 750) / 10, 1);
                    $estado   = Acopio::calcularEstado($agua, $ph, $densidad);

                    Acopio::forceCreate([
                        'client_uuid'               => "demo-{$g->id}-{$fecha->format('Ymd')}",
                        'ganadero_id'               => $g->id,
                        'chofer_id'                 => $choferes[$iz]->id,
                        'litros'                    => $litros,
                        'medicion_balde_confirmada' => true,
                        'equipo_lactoscan'          => ($ig % 3) + 1,
                        'capacidad_tacho_litros'    => 50,
                        'porcentaje_agua'           => $agua,
                        'acidez_ph'                 => $ph,
                        'densidad'                  => $densidad,
                        'grasa'                     => round(mt_rand(32, 42) / 10, 1),
                        'proteina'                  => round(mt_rand(29, 34) / 10, 1),
                        'tiene_descuento'           => Acopio::calcularDescuento($agua),
                        'estado'                    => $estado,
                        'fecha'                     => $fecha->toDateString(),
                        'observaciones'             => $estado === 'rechazado' ? 'Leche devuelta al productor' : null,
                        'created_at'                => $fecha->copy()->setTime(6 + $ig, mt_rand(0, 59)),
                        'updated_at'                => $fecha->copy()->setTime(6 + $ig, mt_rand(0, 59)),
                    ]);
                }
            }
        }

    }

    // ── Hoy: Collana 1 y 2 en ruta (acopios de la app offline + GPS) ──────

    private function rutaDeHoy(array $ganaderos, array $zonas, array $choferes): void
    {
        foreach (['COLLANA-1' => 0, 'COLLANA-2' => 1] as $codigo => $i) {
            $chofer = $choferes[$i];
            if (TrackingGpsPunto::where('chofer_id', $chofer->id)->whereDate('registrado_en', today())->exists()) {
                continue;
            }
            $salida = now()->subMinutes(95);
            $ruta = [self::PLANTA];
            $atendidos = array_slice(
                collect($ganaderos[$codigo])->map->fresh()->filter(fn ($g) => $g->estado !== Ganadero::ESTADO_SUSPENDIDO)
                    ->sortBy(fn ($g) => hypot($g->geo_lat - self::PLANTA[0], $g->geo_lon - self::PLANTA[1]))->values()->all(),
                0, 3
            );

            foreach ($atendidos as $k => $g) {
                $ruta[] = [(float) $g->geo_lat, (float) $g->geo_lon];
                $agua = $k === 2 ? 6.1 : round(mt_rand(8, 25) / 10, 1);
                AcopioCampo::firstOrNew(['uuid' => "demo-hoy-{$g->id}-" . today()->format('Ymd')])->forceFill([
                    'chofer_id'       => $chofer->id,
                    'ganadero_id'     => $g->id,
                    'ganadero_nombre' => $g->nombre,
                    'litros'          => round(mt_rand(250, 650) / 10, 1),
                    'porcentaje_agua' => $agua,
                    'densidad'        => 1.0305,
                    'grasa'           => 3.6,
                    'estado_calidad'  => $agua > 5 ? 'OBSERVADO' : 'APROBADO',
                    'actualizado_en'  => $salida->copy()->addMinutes(25 * ($k + 1))->getTimestampMs(),
                    'created_at'      => $salida->copy()->addMinutes(25 * ($k + 1)),
                ])->save();
            }

            // Trazo GPS: puntos cada ~1 min interpolados entre paradas
            $t = $salida->copy();
            for ($s = 1; $s < count($ruta); $s++) {
                [$a, $b] = [$ruta[$s - 1], $ruta[$s]];
                for ($p = 0; $p < 20; $p++) {
                    $f = $p / 20;
                    TrackingGpsPunto::create([
                        'chofer_id'     => $chofer->id,
                        'latitud'       => round($a[0] + ($b[0] - $a[0]) * $f + mt_rand(-3, 3) / 100000, 7),
                        'longitud'      => round($a[1] + ($b[1] - $a[1]) * $f + mt_rand(-3, 3) / 100000, 7),
                        'precision_m'   => mt_rand(4, 12),
                        'velocidad_kmh' => $p < 2 ? mt_rand(3, 10) : mt_rand(20, 45),
                        'registrado_en' => $t->addSeconds(75)->copy(),
                    ]);
                }
            }
            $ultimo = end($ruta);
            TrackingGps::updateOrCreate(['chofer_id' => $chofer->id], [
                'latitud' => $ultimo[0], 'longitud' => $ultimo[1],
                'estado_ruta' => 'en_camino', 'ultima_actualizacion' => now()->subMinutes(2),
            ]);
        }
    }

    // ── Liquidación de la semana pasada (se paga el viernes) ──────────────

    private function liquidaciones(): void
    {
        $inicio = Liquidacion::inicioSemana(now()->subWeek());
        if (Liquidacion::whereDate('semana_inicio', $inicio->toDateString())->exists()) {
            return;
        }
        $fin   = Liquidacion::finSemana($inicio);
        $pago  = Liquidacion::fechaPago($fin);
        $admin = User::where('rol', 'admin')->first();
        $calcular = new \ReflectionMethod(LiquidacionController::class, 'calcularLiquidacion');
        $controlador = app(LiquidacionController::class);

        foreach (Ganadero::where('activo', true)->get() as $g) {
            $liq = $calcular->invoke($controlador, $g, $inicio, $fin, $pago, $admin->id);
            if ($liq && $pago->isPast()) {
                $liq->update(['estado' => 'pagada', 'pagada_at' => $pago, 'metodo_pago' => $g->metodo_pago_preferido ?? 'efectivo']);
            }
        }
    }

    // ── Producción y tienda ───────────────────────────────────────────────

    /** @return ProductoTienda[] */
    private function produccionYTienda(): array
    {
        // Stock amplio de insumos para poder producir en la demo
        Insumo::where('activo', true)->get()->each(function (Insumo $i) {
            $objetivo = max((float) $i->stock_minimo * 20, 100);
            if ((float) $i->stock_actual < $objetivo) {
                $i->update(['stock_actual' => $objetivo]);
            }
        });

        $catalogo = [
            // receta => [precio, unidades del lote, color, descripción, emoji]
            'Queso Tipo Paria'        => [21.00, 40, '#f2c14e', 'Queso madurado tradicional de Puno, de leche entera de Huata. Pieza de ~1 kg.'],
            'Queso Andino'            => [24.00, 25, '#e8a33d', 'Queso semi-maduro de pasta firme y sabor intenso. Pieza de ~1 kg.'],
            'Queso Fresco'            => [16.00, 30, '#f6e7b4', 'Queso fresco del día, suave y bajo en sal. Ideal para desayunos.'],
            'Yogurt Natural 1L'       => [9.50,  40, '#e9f1fb', 'Yogurt natural sin azúcar añadida, cultivos vivos. Botella de 1 L.'],
            'Yogurt Frutado Fresa 1L' => [10.50, 40, '#f7a1b5', 'Yogurt batido con mermelada de fresa. Botella de 1 L.'],
            'Yogurt con Vainilla 1L'  => [10.50, 30, '#f3e3c3', 'Yogurt cremoso con esencia natural de vainilla. Botella de 1 L.'],
            'Manjar Blanco 1kg'       => [12.00, 15, '#c98a4b', 'Dulce de leche cocido a fuego lento por 90 minutos. Pote de 1 kg.'],
        ];

        $responsable = User::whereIn('rol', ['jefe_produccion', 'admin'])->orderByRaw("rol = 'admin'")->first();
        $produccion  = app(ProduccionService::class);
        $productos   = [];

        foreach ($catalogo as $nombreReceta => [$precio, $unidades, $color, $descripcion]) {
            $receta = Receta::where('nombre', $nombreReceta)->first();
            if (! $receta) continue;

            $yaProducida = ProduccionLote::where('receta_id', $receta->id)->where('observaciones', 'like', 'demo%')->exists();
            if (! $yaProducida && $receta->detalles()->exists()) {
                try {
                    $lote = $produccion->registrarLote($receta->id, $unidades, $responsable, 'demo: lote de la semana');
                    $lote->update(['fecha_produccion' => now()->subDays(mt_rand(1, 4))->toDateString()]);
                } catch (\RuntimeException $e) {
                    $this->command?->warn("   ⚠ {$nombreReceta}: {$e->getMessage()}");
                }
            }

            $producto = ProductoTienda::where('receta_id', $receta->id)->first();
            if (! $producto) {
                $archivo = 'tienda/productos/demo-' . \Illuminate\Support\Str::slug($nombreReceta) . '.svg';
                Storage::disk('public')->put($archivo, $this->imagenProducto($nombreReceta, $color));
                $producto = ProductoTienda::create([
                    'nombre'           => $nombreReceta,
                    'slug'             => ProductoTienda::generarSlugUnico($nombreReceta),
                    'descripcion'      => $descripcion,
                    'precio_venta'     => $precio,
                    'stock_disponible' => $unidades,
                    'imagen_url'       => $archivo,
                    'estado'           => 'activo',
                    'receta_id'        => $receta->id,
                ]);
            } elseif ($producto->stock_disponible < 5) {
                $producto->update(['stock_disponible' => $unidades]);
            }
            $productos[] = $producto;
        }
        return $productos;
    }

    /** Ilustración SVG del producto (sin depender de imágenes externas). */
    private function imagenProducto(string $nombre, string $color): string
    {
        $esQueso   = str_contains($nombre, 'Queso');
        $esYogurt  = str_contains($nombre, 'Yogurt');
        $titulo    = htmlspecialchars($nombre, ENT_XML1);
        $figura = match (true) {
            $esQueso  => "<ellipse cx='300' cy='330' rx='190' ry='55' fill='#000' opacity='.08'/>
                <path d='M110 250 Q300 170 490 250 L490 320 Q300 400 110 320 Z' fill='{$color}' stroke='#b7791f' stroke-width='6'/>
                <ellipse cx='300' cy='250' rx='190' ry='62' fill='{$color}' stroke='#b7791f' stroke-width='6'/>
                <circle cx='240' cy='245' r='14' fill='#d69e2e' opacity='.55'/><circle cx='330' cy='232' r='10' fill='#d69e2e' opacity='.55'/>
                <circle cx='380' cy='262' r='16' fill='#d69e2e' opacity='.55'/><circle cx='190' cy='300' r='9' fill='#d69e2e' opacity='.5'/>",
            $esYogurt => "<ellipse cx='300' cy='385' rx='120' ry='22' fill='#000' opacity='.08'/>
                <rect x='240' y='95' width='120' height='40' rx='10' fill='#1f6feb'/>
                <path d='M230 140 H370 L395 200 V365 Q395 385 375 385 H225 Q205 385 205 365 V200 Z' fill='#fff' stroke='#cbd5e1' stroke-width='5'/>
                <rect x='205' y='225' width='190' height='110' fill='{$color}'/>
                <text x='300' y='292' text-anchor='middle' font-family='Arial' font-size='34' font-weight='700' fill='#1e3a8a'>1 L</text>",
            default   => "<ellipse cx='300' cy='370' rx='140' ry='24' fill='#000' opacity='.08'/>
                <rect x='175' y='150' width='250' height='215' rx='26' fill='{$color}' stroke='#8a5a2b' stroke-width='6'/>
                <rect x='160' y='120' width='280' height='55' rx='16' fill='#f8fafc' stroke='#cbd5e1' stroke-width='5'/>
                <rect x='205' y='230' width='190' height='70' rx='12' fill='#fff' opacity='.85'/>
                <text x='300' y='277' text-anchor='middle' font-family='Arial' font-size='30' font-weight='700' fill='#8a5a2b'>1 kg</text>",
        };

        return <<<SVG
<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 600' width='600' height='600'>
  <defs><linearGradient id='f' x1='0' y1='0' x2='0' y2='1'><stop offset='0' stop-color='#f5f9ff'/><stop offset='1' stop-color='#dbe9ff'/></linearGradient></defs>
  <rect width='600' height='600' fill='url(#f)'/>
  <circle cx='300' cy='270' r='210' fill='#fff' opacity='.6'/>
  {$figura}
  <text x='300' y='480' text-anchor='middle' font-family='Arial' font-size='34' font-weight='700' fill='#0f2a4d'>{$titulo}</text>
  <text x='300' y='522' text-anchor='middle' font-family='Arial' font-size='22' fill='#3b6db3'>ProLech · Huata, Puno</text>
</svg>
SVG;
    }

    // ── Pedidos web en distintos estados ──────────────────────────────────

    private function pedidos(array $productos): void
    {
        if (PedidoWeb::where('notas', 'like', 'demo:%')->exists() || count($productos) < 3) {
            return;
        }
        $checkout = app(CheckoutService::class);
        $clientes = [
            ['Rocío Huamán',     '951 300 101', 'Jr. Lima 245, Puno',          'Yape',     'Pendiente', [[0, 1], [3, 2]]],
            ['Edwin Paredes',    '951 300 102', 'Av. Simón Bolívar 880, Puno', 'Efectivo', 'Pagado',    [[1, 1]]],
            ['Gloria Saavedra',  '951 300 103', 'Jr. Moquegua 112, Juliaca',   'Tarjeta',  'Enviado',   [[2, 2], [4, 1], [6, 1]]],
            ['Hotel Titicaca SAC','951 300 104', 'Av. Costanera 410, Puno',    'Efectivo', 'Enviado',   [[0, 3], [1, 2]]],
            ['Norma Ccama',      '951 300 105', 'Huata, plaza principal',      'Yape',     'Pagado',    [[5, 2]]],
        ];

        foreach ($clientes as $n => [$nombre, $telefono, $direccion, $metodo, $estado, $lineas]) {
            $items = collect($lineas)
                ->filter(fn ($l) => isset($productos[$l[0]]))
                ->map(fn ($l) => ['id' => $productos[$l[0]]->id, 'cantidad' => $l[1]])->values()->all();
            try {
                $pedido = $checkout->procesarPedido([
                    'cliente_nombre'    => $nombre,
                    'cliente_telefono'  => $telefono,
                    'cliente_direccion' => $direccion,
                    'cliente_email'     => null,
                    'cliente_documento' => null,
                    'metodo_pago'       => $metodo,
                    'pago_referencia'   => $metodo === 'Tarjeta' ? 'VISA •••• 4242' : ($metodo === 'Yape' ? (string) (884200 + $n) : null),
                    'notas'             => 'demo: pedido de ejemplo',
                ], $items);
            } catch (\RuntimeException $e) {
                $this->command?->warn("   ⚠ Pedido de {$nombre}: {$e->getMessage()}");
                continue;
            }
            $fecha = now()->subDays(4 - min($n, 4))->setTime(9 + $n, 15);
            $pedido->forceFill(['estado_pedido' => $estado, 'sunat_estado' => 'simulada',
                'created_at' => $fecha, 'updated_at' => $fecha])->save();
        }
    }
}
