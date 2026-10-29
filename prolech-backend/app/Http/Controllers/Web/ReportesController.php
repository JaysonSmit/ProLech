<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Acopio;
use App\Models\ControlCalidadPlanta;
use App\Models\Produccion;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportesController extends Controller
{
    /** Mes de una fecha en SQL portable (MySQL en producción, SQLite en los tests). */
    private function mes(string $columna): string
    {
        return \DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', {$columna}) AS INTEGER)"
            : "MONTH({$columna})";
    }

    /**
     * GET /admin/reportes
     * Genera la data consolidada para mostrar gráficos y estadísticas.
     */
    public function index(Request $request): View
    {
        $anio = (int) $request->query('anio', now()->year);

        // ── Litros acopiados por ruta y mes ────────────────────────────────────
        $litrosPorRutaMes = Acopio::join('ganaderos', 'ganaderos.id', '=', 'acopios.ganadero_id')
            ->selectRaw(
                'ganaderos.ruta_asignada as ruta,
                 ' . $this->mes('acopios.fecha') . ' as mes,
                 SUM(acopios.litros) as total_litros,
                 COUNT(*) as num_acopios'
            )
            ->whereYear('acopios.fecha', $anio)
            ->where('acopios.estado', 'aprobado')
            ->whereNotNull('ganaderos.ruta_asignada')
            ->groupBy('ganaderos.ruta_asignada', 'mes')
            ->orderBy('mes')
            ->orderBy('ruta')
            ->get();

        // ── Litros por mes (todos los meses del año seleccionado) ─────────────
        $litrosPorMes = Acopio::selectRaw(
                $this->mes('fecha') . ' as mes, SUM(litros) as total_litros, COUNT(*) as num_acopios'
            )
            ->whereYear('fecha', $anio)
            ->where('estado', 'aprobado')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        // Rellenar los 12 meses (meses sin datos = 0)
        $litrosMensuales = collect(range(1, 12))->map(fn ($m) => [
            'mes'          => $m,
            'mes_nombre'   => now()->setMonth($m)->isoFormat('MMM'),
            'total_litros' => $litrosPorMes->get($m)?->total_litros ?? 0,
            'num_acopios'  => $litrosPorMes->get($m)?->num_acopios  ?? 0,
        ]);

        // ── Producción mensual ─────────────────────────────────────────────────
        $produccionMensual = Produccion::selectRaw(
                $this->mes('fecha_lote') . ' as mes,
                 SUM(litros_usados) as total_litros,
                 SUM(kg_queso_estimado) as total_kg,
                 SUM(costo_total) as total_costo'
            )
            ->whereYear('fecha_lote', $anio)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        $produccionMensualFull = collect(range(1, 12))->map(fn ($m) => [
            'mes'          => $m,
            'mes_nombre'   => now()->setMonth($m)->isoFormat('MMM'),
            'total_litros' => $produccionMensual->get($m)?->total_litros ?? 0,
            'total_kg'     => $produccionMensual->get($m)?->total_kg     ?? 0,
            'total_costo'  => $produccionMensual->get($m)?->total_costo  ?? 0,
        ]);

        // ── Ventas mensuales ───────────────────────────────────────────────────
        $ventasMensual = Venta::vigentes()->selectRaw(
                $this->mes('fecha_venta') . ' as mes,
                 tipo_cliente,
                 SUM(total) as total_ventas,
                 SUM(cantidad_kg) as total_kg,
                 COUNT(*) as num_ventas'
            )
            ->whereYear('fecha_venta', $anio)
            ->groupBy('mes', 'tipo_cliente')
            ->orderBy('mes')
            ->get();

        // ── Resumen del año ────────────────────────────────────────────────────
        $resumenAnio = [
            'total_litros'     => Acopio::whereYear('fecha', $anio)->where('estado', 'aprobado')->sum('litros'),
            'total_acopios'    => Acopio::whereYear('fecha', $anio)->where('estado', 'aprobado')->count(),
            'adulterados'      => Acopio::whereYear('fecha', $anio)->where('estado', 'rechazado')->count(),
            'total_kg_queso'   => Produccion::whereYear('fecha_lote', $anio)->sum('kg_queso_estimado'),
            'total_ventas'     => Venta::vigentes()->whereYear('fecha_venta', $anio)->sum('total'),
            'costo_produccion' => Produccion::whereYear('fecha_lote', $anio)->sum('costo_total'),
        ];
        $resumenAnio['margen'] = $resumenAnio['total_ventas'] - $resumenAnio['costo_produccion'];

        // ── Rutas activas (para filtro y tabla) ───────────────────────────────
        $rutasActivas = \App\Models\Ganadero::whereNotNull('ruta_asignada')
            ->where('activo', true)
            ->distinct()
            ->pluck('ruta_asignada')
            ->sort()
            ->values();

        // ── Detalle litros por ruta (mes actual) ──────────────────────────────
        $mesActual = now()->month;
        $litrosPorRutaHoy = Acopio::join('ganaderos', 'ganaderos.id', '=', 'acopios.ganadero_id')
            ->selectRaw(
                'ganaderos.ruta_asignada as ruta,
                 SUM(acopios.litros) as total_litros,
                 COUNT(DISTINCT acopios.ganadero_id) as num_ganaderos,
                 COUNT(*) as num_acopios'
            )
            ->whereYear('acopios.fecha', $anio)
            ->whereMonth('acopios.fecha', $mesActual)
            ->where('acopios.estado', 'aprobado')
            ->whereNotNull('ganaderos.ruta_asignada')
            ->groupBy('ganaderos.ruta_asignada')
            ->orderByDesc('total_litros')
            ->get();

        // Arrays para Chart.js (serialización segura)
        $chartLabels  = $litrosMensuales->pluck('mes_nombre');
        $chartLitros  = $litrosMensuales->pluck('total_litros');
        $chartKgQueso = $produccionMensualFull->pluck('total_kg');
        $chartVentas  = collect(range(1, 12))->map(function ($m) use ($ventasMensual) {
            return $ventasMensual->where('mes', $m)->sum('total_ventas');
        });

        $aniosDisponibles = range(now()->year - 2, now()->year + 1);

        // RF — Reporte comparativo campo vs planta (últimos 14 días)
        $campoVsPlanta = collect(range(0, 13))->map(function ($diasAtras) {
            $fecha = now()->subDays($diasAtras)->toDateString();
            $litrosCampo  = (float) Acopio::whereDate('fecha', $fecha)->sum('litros');
            $litrosPlanta = (float) ControlCalidadPlanta::whereDate('fecha', $fecha)->sum('litros_total');
            $diferencia   = $litrosCampo - $litrosPlanta;
            $pctDiff      = $litrosCampo > 0 ? round(abs($diferencia) / $litrosCampo * 100, 1) : 0;

            return [
                'fecha'         => $fecha,
                'fecha_label'   => now()->subDays($diasAtras)->isoFormat('ddd D/M'),
                'litros_campo'  => $litrosCampo,
                'litros_planta' => $litrosPlanta,
                'diferencia'    => $diferencia,
                'alerta'        => $litrosCampo > 0 && abs($diferencia) > ($litrosCampo * 0.05),
                'pct_diff'      => $pctDiff,
            ];
        })->reverse()->values();

        // ── RF-RENT — Análisis de Rentabilidad Leche ↔ Queso y Sensibilidad ──
        $precioLitroNormal   = (float) \App\Models\Configuracion::get('precio_litro_normal', 1.70);
        $precioLitroLluvias  = (float) \App\Models\Configuracion::get('precio_litro_lluvias', 1.20);
        $precioMayorista     = (float) \App\Models\Configuracion::get('precio_queso_mayorista', 20.00);
        $precioPublico       = (float) \App\Models\Configuracion::get('precio_queso_publico', 21.00);
        $temporadaActiva     = (string) \App\Models\Configuracion::get('temporada_activa', 'normal');

        $totalLitrosProd = Produccion::whereYear('fecha_lote', $anio)->sum('litros_usados');
        $totalKgProd     = Produccion::whereYear('fecha_lote', $anio)->sum('kg_queso_estimado');
        $rendimientoReal = ($totalKgProd > 0 && $totalLitrosProd > 0)
            ? round($totalLitrosProd / $totalKgProd, 2)
            : 9.5;

        $costoLecheNormal  = round($rendimientoReal * $precioLitroNormal, 2);
        $costoLecheLluvias = round($rendimientoReal * $precioLitroLluvias, 2);

        $analisisRentabilidad = [
            'rendimiento_litros_kg' => $rendimientoReal,
            'temporada_activa'      => $temporadaActiva,
            'precio_leche_normal'   => $precioLitroNormal,
            'precio_leche_lluvias'  => $precioLitroLluvias,
            'precio_mayorista'      => $precioMayorista,
            'precio_publico'        => $precioPublico,
            'normal' => [
                'costo_leche'      => $costoLecheNormal,
                'margen_mayorista' => round($precioMayorista - $costoLecheNormal, 2),
                'pct_mayorista'    => $precioMayorista > 0 ? round((($precioMayorista - $costoLecheNormal) / $precioMayorista) * 100, 1) : 0,
                'margen_publico'   => round($precioPublico - $costoLecheNormal, 2),
                'pct_publico'      => $precioPublico > 0 ? round((($precioPublico - $costoLecheNormal) / $precioPublico) * 100, 1) : 0,
            ],
            'lluvias' => [
                'costo_leche'      => $costoLecheLluvias,
                'margen_mayorista' => round($precioMayorista - $costoLecheLluvias, 2),
                'pct_mayorista'    => $precioMayorista > 0 ? round((($precioMayorista - $costoLecheLluvias) / $precioMayorista) * 100, 1) : 0,
                'margen_publico'   => round($precioPublico - $costoLecheLluvias, 2),
                'pct_publico'      => $precioPublico > 0 ? round((($precioPublico - $costoLecheLluvias) / $precioPublico) * 100, 1) : 0,
            ],
        ];

        // Rentabilidad por destino en el año
        $rentabilidadDestinos = Venta::vigentes()->whereYear('fecha_venta', $anio)
            ->selectRaw('destino, SUM(cantidad_kg) as total_kg, SUM(total) as total_ingreso, COUNT(*) as num_despachos')
            ->groupBy('destino')
            ->orderByDesc('total_ingreso')
            ->get()
            ->map(function ($item) use ($rendimientoReal, $precioLitroNormal, $precioLitroLluvias, $temporadaActiva) {
                $precioLeche = $temporadaActiva === 'lluvias' ? $precioLitroLluvias : $precioLitroNormal;
                $costoEstimado = $item->total_kg * ($rendimientoReal * $precioLeche);
                $margenEstimado = $item->total_ingreso - $costoEstimado;
                $item->costo_estimado = round($costoEstimado, 2);
                $item->margen_estimado = round($margenEstimado, 2);
                $item->pct_margen = $item->total_ingreso > 0 ? round(($margenEstimado / $item->total_ingreso) * 100, 1) : 0;
                return $item;
            });

        return view('admin.reportes', compact(
            'resumenAnio',
            'litrosMensuales',
            'litrosPorRutaMes',
            'litrosPorRutaHoy',
            'produccionMensualFull',
            'ventasMensual',
            'rutasActivas',
            'chartLabels',
            'chartLitros',
            'chartKgQueso',
            'chartVentas',
            'anio',
            'aniosDisponibles',
            'campoVsPlanta',
            'analisisRentabilidad',
            'rentabilidadDestinos'
        ));
    }
}
