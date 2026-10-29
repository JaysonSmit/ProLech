<?php

namespace Database\Seeders;

use App\Models\Acopio;
use App\Models\Ganadero;
use App\Models\Produccion;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ─────────────────────────────────────────────────────────────
        $admin = User::create([
            'name'     => 'Administrador ProLech',
            'email'    => 'admin@prolech.com',
            'password' => Hash::make('prolech2024'),
            'rol'      => 'admin',
            'activo'   => true,
        ]);

        // ── Choferes ──────────────────────────────────────────────────────────
        $chofer1 = User::create([
            'name'     => 'Carlos Ramos',
            'email'    => 'carlos@prolech.com',
            'password' => Hash::make('prolech2024'),
            'rol'      => 'chofer',
            'activo'   => true,
        ]);

        $chofer2 = User::create([
            'name'     => 'Miguel Torres',
            'email'    => 'miguel@prolech.com',
            'password' => Hash::make('prolech2024'),
            'rol'      => 'chofer',
            'activo'   => true,
        ]);

        // ── Personal de planta (roles web RF-USU) ─────────────────────────────
        User::create([
            'name'     => 'Lucía Vargas',
            'email'    => 'calidad@prolech.com',
            'password' => Hash::make('prolech2024'),
            'rol'      => 'calidad',
            'activo'   => true,
        ]);

        User::create([
            'name'     => 'Ana Quispe',
            'email'    => 'asistente@prolech.com',
            'password' => Hash::make('prolech2024'),
            'rol'      => 'asistente',
            'activo'   => true,
        ]);

        User::create([
            'name'     => 'Roberto Flores',
            'email'    => 'produccion@prolech.com',
            'password' => Hash::make('prolech2024'),
            'rol'      => 'jefe_produccion',
            'activo'   => true,
        ]);

        User::create([
            'name'     => 'Sandra Ríos',
            'email'    => 'despacho@prolech.com',
            'password' => Hash::make('prolech2024'),
            'rol'      => 'despacho',
            'activo'   => true,
        ]);

        // ── Ganaderos ─────────────────────────────────────────────────────────
        // Coordenadas GPS reales del distrito de Huata, Puno (orillas del Titicaca)
        $ganaderos = [
            // Ruta Norte — comunidades al norte de Huata
            ['dni' => '12345678', 'nombre' => 'Juan Pérez',    'ruta_asignada' => 'Ruta Norte', 'direccion' => 'Comunidad Huata Centro',   'geo_lat' => -15.8180, 'geo_lon' => -70.0210],
            ['dni' => '23456789', 'nombre' => 'María García',  'ruta_asignada' => 'Ruta Norte', 'direccion' => 'Comunidad Ccapia',         'geo_lat' => -15.8050, 'geo_lon' => -70.0180],
            ['dni' => '34567890', 'nombre' => 'Pedro López',   'ruta_asignada' => 'Ruta Norte', 'direccion' => 'Comunidad Chijichaya',     'geo_lat' => -15.7920, 'geo_lon' => -70.0090],
            // Ruta Sur — comunidades al sur de Huata
            ['dni' => '45678901', 'nombre' => 'Rosa Quispe',   'ruta_asignada' => 'Ruta Sur',   'direccion' => 'Comunidad Ccopamaya',      'geo_lat' => -15.8350, 'geo_lon' => -70.0320],
            ['dni' => '56789012', 'nombre' => 'Luis Mamani',   'ruta_asignada' => 'Ruta Sur',   'direccion' => 'Comunidad Huataquita',     'geo_lat' => -15.8480, 'geo_lon' => -70.0410],
            ['dni' => '67890123', 'nombre' => 'Elena Condori', 'ruta_asignada' => 'Ruta Sur',   'direccion' => 'Comunidad Kellhuani',      'geo_lat' => -15.8600, 'geo_lon' => -70.0500],
        ];

        $ganaderosCreados = [];
        foreach ($ganaderos as $data) {
            $ganaderosCreados[] = Ganadero::create(array_merge($data, ['activo' => true]));
        }

        // ── Acopios (hoy y ayer para que el dashboard muestre datos reales) ─────
        $acopiosDatos = [
            ['ganadero_id' => $ganaderosCreados[0]->id, 'chofer_id' => $chofer1->id, 'litros' => 45.50, 'porcentaje_agua' => 2.1,  'acidez_ph' => 6.6, 'fecha' => now()->toDateString()],
            ['ganadero_id' => $ganaderosCreados[1]->id, 'chofer_id' => $chofer1->id, 'litros' => 30.00, 'porcentaje_agua' => 3.5,  'acidez_ph' => 6.5, 'fecha' => now()->toDateString()],
            ['ganadero_id' => $ganaderosCreados[2]->id, 'chofer_id' => $chofer1->id, 'litros' => 15.75, 'porcentaje_agua' => 12.0, 'acidez_ph' => 6.3, 'fecha' => now()->subDays(1)->toDateString()],  // rechazado
            ['ganadero_id' => $ganaderosCreados[3]->id, 'chofer_id' => $chofer2->id, 'litros' => 60.00, 'porcentaje_agua' => 1.8,  'acidez_ph' => 6.7, 'fecha' => now()->toDateString()],
            ['ganadero_id' => $ganaderosCreados[4]->id, 'chofer_id' => $chofer2->id, 'litros' => 22.50, 'porcentaje_agua' => 6.0,  'acidez_ph' => 6.5, 'fecha' => now()->toDateString()],  // observado
            ['ganadero_id' => $ganaderosCreados[5]->id, 'chofer_id' => $chofer2->id, 'litros' => 38.00, 'porcentaje_agua' => 2.0,  'acidez_ph' => 6.6, 'fecha' => now()->subDays(1)->toDateString()],
        ];

        $idx = 0;
        foreach ($acopiosDatos as $data) {
            $estado = Acopio::calcularEstado($data['porcentaje_agua'], $data['acidez_ph']);
            Acopio::create(array_merge($data, [
                'estado'                    => $estado,
                'medicion_balde_confirmada' => $idx !== 1, // uno sin balde para demo RF-ACO-07
                'equipo_lactoscan'          => ($idx % 3) + 1,
                'capacidad_tacho_litros'    => 50.0,
            ]));
            $idx++;
        }

        // ── Lotes de producción ───────────────────────────────────────────────
        Produccion::create([
            'litros_usados'     => 200.00,
            'costo_cuajo'       => 15.00,
            'costo_sal'         => 8.00,
            'costo_otros'       => 5.00,
            'kg_queso_estimado' => 20.000,
            'fecha_lote'        => now()->subDays(2)->toDateString(),
            'registrado_por'    => $admin->id,
            'notas'             => 'Producción normal, buen rendimiento.',
        ]);

        Produccion::create([
            'litros_usados'     => 150.00,
            'costo_cuajo'       => 12.00,
            'costo_sal'         => 6.00,
            'costo_otros'       => 3.00,
            'kg_queso_estimado' => 15.500,
            'fecha_lote'        => now()->subDays(5)->toDateString(),
            'registrado_por'    => $admin->id,
            'notas'             => null,
        ]);

        // ── Ventas ────────────────────────────────────────────────────────────
        Venta::create([
            'cliente'        => 'Supermercado La Estrella',
            'tipo_cliente'   => 'mayorista',
            'cantidad_kg'    => 15.000,
            'precio_unitario' => Venta::PRECIO_MAYORISTA,
            'fecha_venta'    => now()->subDays(1)->toDateString(),
            'registrado_por' => $admin->id,
        ]);

        Venta::create([
            'cliente'        => 'Ana Soto',
            'tipo_cliente'   => 'publico',
            'cantidad_kg'    => 2.500,
            'precio_unitario' => Venta::PRECIO_PUBLICO,
            'fecha_venta'    => now()->toDateString(),
            'registrado_por' => $admin->id,
        ]);

        $this->command->info('✅ Seeder ProLech completado:');
        $this->command->info('   → Admin:      admin@prolech.com / prolech2024');
        $this->command->info('   → Chofer1:    carlos@prolech.com / prolech2024');
        $this->command->info('   → Chofer2:    miguel@prolech.com / prolech2024');
        $this->command->info('   → Calidad:    calidad@prolech.com / prolech2024');
        $this->command->info('   → Asistente:  asistente@prolech.com / prolech2024');
        $this->command->info('   → Jefe prod.: produccion@prolech.com / prolech2024');
        $this->command->info('   → Despacho:   despacho@prolech.com / prolech2024');
        $this->command->info('   → 6 ganaderos, 6 acopios, 2 lotes de producción, 2 ventas');

        // ── Módulo de Producción por Receta ───────────────────────────────────
        $this->call(ProduccionSeeder::class);

        // ── Operación completa de Huata: zonas, rutas del mes, historial, tienda ──
        $this->call(ProlechDemoSeeder::class);
    }
}
