<?php

namespace Database\Seeders;

use App\Models\Insumo;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use Illuminate\Database\Seeder;

/**
 * ProduccionSeeder — Catálogo completo de insumos y recetas lácteas ProLech
 *
 * Idempotente: usa updateOrCreate, puede correrse múltiples veces sin duplicar.
 *   php artisan db:seed --class=ProduccionSeeder
 */
class ProduccionSeeder extends Seeder
{
    public function run(): void
    {
        // ══════════════════════════════════════════════════════════════════
        // 1. INSUMOS
        // ══════════════════════════════════════════════════════════════════
        $insumosData = [
            // ── Materia prima ────────────────────────────────────────────
            ['nombre' => 'Leche Cruda',         'unidad_medida' => 'L',    'costo_unitario' => 1.8000,  'stock_actual' => 20000.000, 'stock_minimo' => 5000.000],
            // ── Coagulantes y aditivos ───────────────────────────────────
            ['nombre' => 'Cuajo Líquido',        'unidad_medida' => 'mL',   'costo_unitario' => 0.0850,  'stock_actual' =>  5000.000, 'stock_minimo' =>  500.000],
            ['nombre' => 'Cloruro de Calcio',    'unidad_medida' => 'mL',   'costo_unitario' => 0.0400,  'stock_actual' =>  3000.000, 'stock_minimo' =>  300.000],
            ['nombre' => 'Ácido Láctico',        'unidad_medida' => 'mL',   'costo_unitario' => 0.0600,  'stock_actual' =>  2000.000, 'stock_minimo' =>  200.000],
            // ── Sal y conservantes ───────────────────────────────────────
            ['nombre' => 'Sal Industrial',       'unidad_medida' => 'kg',   'costo_unitario' => 0.8000,  'stock_actual' =>   300.000, 'stock_minimo' =>   50.000],
            ['nombre' => 'Sorbato de Potasio',   'unidad_medida' => 'g',    'costo_unitario' => 0.0120,  'stock_actual' =>  1000.000, 'stock_minimo' =>  100.000],
            // ── Cultivos para yogurt ─────────────────────────────────────
            ['nombre' => 'Cultivo Láctico (Yogurt)',  'unidad_medida' => 'g', 'costo_unitario' => 0.2500, 'stock_actual' =>  500.000, 'stock_minimo' =>   50.000],
            ['nombre' => 'Cultivo Mesófilo (Manjar)', 'unidad_medida' => 'g', 'costo_unitario' => 0.3000, 'stock_actual' =>  300.000, 'stock_minimo' =>   30.000],
            // ── Azúcar y saborizantes ────────────────────────────────────
            ['nombre' => 'Azúcar Blanca',        'unidad_medida' => 'kg',   'costo_unitario' => 2.2000,  'stock_actual' =>   500.000, 'stock_minimo' =>   80.000],
            ['nombre' => 'Esencia de Vainilla',  'unidad_medida' => 'mL',   'costo_unitario' => 0.1800,  'stock_actual' =>  1000.000, 'stock_minimo' =>  100.000],
            ['nombre' => 'Mermelada de Fresa',   'unidad_medida' => 'kg',   'costo_unitario' => 7.5000,  'stock_actual' =>    50.000, 'stock_minimo' =>   10.000],
            // ── Crema / grasa ────────────────────────────────────────────
            ['nombre' => 'Crema de Leche',       'unidad_medida' => 'L',    'costo_unitario' => 4.5000,  'stock_actual' =>   200.000, 'stock_minimo' =>   30.000],
            // ── Empaque ──────────────────────────────────────────────────
            ['nombre' => 'Bolsa Sellable 1L',    'unidad_medida' => 'unidad','costo_unitario' => 0.1500,  'stock_actual' =>  5000.000, 'stock_minimo' =>  500.000],
            ['nombre' => 'Pote Yogurt 1L',       'unidad_medida' => 'unidad','costo_unitario' => 0.5000,  'stock_actual' =>  2000.000, 'stock_minimo' =>  200.000],
            ['nombre' => 'Molde Queso (plástico)','unidad_medida' => 'unidad','costo_unitario' => 0.0300,  'stock_actual' => 10000.000, 'stock_minimo' => 1000.000],
        ];

        $insumoMap = [];
        foreach ($insumosData as $data) {
            $ins = Insumo::updateOrCreate(
                ['nombre' => $data['nombre']],
                array_merge($data, ['activo' => true])
            );
            $insumoMap[$data['nombre']] = $ins;
        }

        $this->command->info('  ✅ ' . count($insumosData) . ' insumos registrados.');

        // ══════════════════════════════════════════════════════════════════
        // 2. RECETAS
        // ══════════════════════════════════════════════════════════════════
        $recetasData = [

            // ── QUESOS ───────────────────────────────────────────────────

            [
                'nombre'       => 'Queso Tipo Paria',
                'rendimiento'  => 1,
                'descripcion'  => 'Queso semiblando de pasta prensada. 10 L de leche → 1 molde (~1 kg). Salmuera 18% por 24 h.',
                'ingredientes' => [
                    ['Leche Cruda',       10.0000],  // 10 L / molde
                    ['Cuajo Líquido',      2.0000],  // 2 mL / molde
                    ['Cloruro de Calcio',  1.0000],  // 1 mL / molde
                    ['Sal Industrial',     0.1500],  // 150 g / molde
                    ['Molde Queso (plástico)', 1.0], // 1 molde / unidad
                ],
            ],

            [
                'nombre'       => 'Queso Fresco',
                'rendimiento'  => 1,
                'descripcion'  => 'Queso blanco sin maduración. Alta humedad, consumo inmediato. 8–9 L de leche → 1 kg.',
                'ingredientes' => [
                    ['Leche Cruda',       8.5000],
                    ['Cuajo Líquido',     1.5000],
                    ['Cloruro de Calcio', 0.8000],
                    ['Sal Industrial',    0.0800],
                    ['Molde Queso (plástico)', 1.0],
                ],
            ],

            [
                'nombre'       => 'Queso Andino',
                'rendimiento'  => 1,
                'descripcion'  => 'Queso de pasta semidura, maduración 15 días. Sabor suave, ideal para cortar.',
                'ingredientes' => [
                    ['Leche Cruda',       11.0000],
                    ['Cuajo Líquido',      2.2000],
                    ['Cloruro de Calcio',  1.1000],
                    ['Sal Industrial',     0.2000],
                    ['Sorbato de Potasio', 1.0000],
                    ['Molde Queso (plástico)', 1.0],
                ],
            ],

            // ── YOGURT ───────────────────────────────────────────────────

            [
                'nombre'       => 'Yogurt Natural 1L',
                'rendimiento'  => 1,   // 1 pote de 1 L
                'descripcion'  => 'Yogurt batido sin sabor. Fermentación a 42°C por 6–8 h. 1 L leche → 1 L yogurt (aprox).',
                'ingredientes' => [
                    ['Leche Cruda',              1.0500],  // ligera evaporación
                    ['Cultivo Láctico (Yogurt)', 0.5000],  // 0.5 g / litro
                    ['Azúcar Blanca',            0.0700],  // 70 g / litro
                    ['Pote Yogurt 1L',           1.0000],
                ],
            ],

            [
                'nombre'       => 'Yogurt Frutado Fresa 1L',
                'rendimiento'  => 1,
                'descripcion'  => 'Yogurt batido con mermelada de fresa. Temperatura fermentación 42°C · 6 h.',
                'ingredientes' => [
                    ['Leche Cruda',              1.0500],
                    ['Cultivo Láctico (Yogurt)', 0.5000],
                    ['Azúcar Blanca',            0.0800],
                    ['Mermelada de Fresa',       0.0800],  // 80 g / litro
                    ['Pote Yogurt 1L',           1.0000],
                ],
            ],

            [
                'nombre'       => 'Yogurt con Vainilla 1L',
                'rendimiento'  => 1,
                'descripcion'  => 'Yogurt batido sabor vainilla. Suave y cremoso.',
                'ingredientes' => [
                    ['Leche Cruda',              1.0500],
                    ['Cultivo Láctico (Yogurt)', 0.5000],
                    ['Azúcar Blanca',            0.0800],
                    ['Esencia de Vainilla',      2.0000],  // 2 mL / litro
                    ['Pote Yogurt 1L',           1.0000],
                ],
            ],

            // ── MANTEQUILLA ──────────────────────────────────────────────

            [
                'nombre'       => 'Mantequilla sin Sal 250g',
                'rendimiento'  => 1,   // 1 bloque de 250 g
                'descripcion'  => 'Mantequilla de crema batida. ~2.5 L crema → 1 kg mantequilla. Porción: 250 g.',
                'ingredientes' => [
                    ['Crema de Leche',   0.6250],  // 625 mL crema / 250 g
                    ['Bolsa Sellable 1L',1.0000],
                ],
            ],

            [
                'nombre'       => 'Mantequilla con Sal 250g',
                'rendimiento'  => 1,
                'descripcion'  => 'Mantequilla de crema con sal. Ideal para consumo directo.',
                'ingredientes' => [
                    ['Crema de Leche',   0.6250],
                    ['Sal Industrial',   0.0025],  // 2.5 g sal / 250 g
                    ['Bolsa Sellable 1L',1.0000],
                ],
            ],

            // ── MANJAR BLANCO ─────────────────────────────────────────────

            [
                'nombre'       => 'Manjar Blanco 1kg',
                'rendimiento'  => 1,   // 1 kg de manjar
                'descripcion'  => 'Dulce de leche. Cocción a fuego lento 90 min. 4 L leche + azúcar → 1 kg manjar.',
                'ingredientes' => [
                    ['Leche Cruda',              4.0000],  // 4 L / kg
                    ['Azúcar Blanca',            0.8000],  // 800 g / kg
                    ['Cultivo Mesófilo (Manjar)', 0.5000], // 0.5 g / kg
                    ['Esencia de Vainilla',       3.0000], // 3 mL / kg
                    ['Bolsa Sellable 1L',         1.0000],
                ],
            ],

        ];

        foreach ($recetasData as $rData) {
            $receta = Receta::updateOrCreate(
                ['nombre' => $rData['nombre']],
                [
                    'rendimiento_esperado_unidades' => $rData['rendimiento'],
                    'descripcion'                   => $rData['descripcion'],
                    'activa'                        => true,
                ]
            );

            // Sincronizar ingredientes (no borra si ya existen correctamente)
            foreach ($rData['ingredientes'] as [$nombreInsumo, $cantidad]) {
                $insumo = $insumoMap[$nombreInsumo]
                    ?? Insumo::where('nombre', $nombreInsumo)->first();

                if (! $insumo) {
                    $this->command->warn("    ⚠ Insumo \"{$nombreInsumo}\" no encontrado.");
                    continue;
                }

                RecetaDetalle::updateOrCreate(
                    ['receta_id' => $receta->id, 'insumo_id' => $insumo->id],
                    ['cantidad_requerida' => $cantidad]
                );
            }

            $this->command->line(
                sprintf('  🧀 %-35s → %d ingrediente(s)',
                    $receta->nombre,
                    count($rData['ingredientes'])
                )
            );
        }

        $this->command->info('');
        $this->command->info('  ✅ ' . count($recetasData) . ' recetas listas en el sistema.');
        $this->command->info('  ✅ ' . count($insumosData) . ' insumos en inventario.');
        $this->command->info('');
        $this->command->line('  Productos disponibles:');
        $this->command->line('     Quesos   → Paria, Fresco, Andino');
        $this->command->line('     Yogurt   → Natural, Fresa, Vainilla (potes 1L)');
        $this->command->line('     Mantequilla → sin sal / con sal (250g)');
        $this->command->line('     Manjar   → Blanco 1kg');
    }
}
