@extends('layouts.admin')

@section('title', 'Reportes')
@section('page-title', '📈 Reportes y Estadísticas')

@section('content')
<style>
    /* ── Selector de año ───────────────────────────────────────────────────── */
    .year-selector {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .year-selector label {
        font-size: .85rem;
        font-weight: 600;
        color: var(--texto-suave);
        text-transform: none;
        letter-spacing: 0;
    }

    .year-selector select {
        width: auto;
        padding: 7px 14px;
    }

    /* ── KPI cards ─────────────────────────────────────────────────────────── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 14px;
        margin-bottom: 28px;
    }

    .kpi-card {
        background: var(--blanco);
        border: 1px solid var(--gris-borde);
        border-radius: 12px;
        padding: 18px 20px;
        position: relative;
        overflow: hidden;
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
    }
    .kpi-card.azul::before   { background: var(--azul); }
    .kpi-card.verde::before  { background: var(--verde); }
    .kpi-card.naranja::before{ background: var(--naranja); }
    .kpi-card.rojo::before   { background: var(--rojo); }
    .kpi-card.purpura::before{ background: #7c3aed; }
    .kpi-card.indigo::before { background: #4338ca; }

    .kpi-icon  { font-size: 1.6rem; margin-bottom: 8px; }
    .kpi-label { font-size: .72rem; color: var(--texto-suave); text-transform: uppercase; letter-spacing: .4px; }
    .kpi-value { font-size: 1.5rem; font-weight: 800; margin-top: 4px; line-height: 1.1; }
    .kpi-sub   { font-size: .75rem; color: var(--texto-suave); margin-top: 3px; }

    /* ── Grid de gráficos ──────────────────────────────────────────────────── */
    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }

    .chart-container {
        background: var(--blanco);
        border: 1px solid var(--gris-borde);
        border-radius: 12px;
        padding: 20px;
    }

    .chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .chart-header h3 { font-size: .95rem; font-weight: 700; }

    canvas { max-height: 280px; }

    /* ── Botones de exportación ────────────────────────────────────────────── */
    .export-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .btn-export {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: .875rem;
        font-weight: 600;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all .15s;
        text-decoration: none;
    }

    .btn-export-pdf {
        background: #fff;
        border-color: #dc2626;
        color: #dc2626;
    }
    .btn-export-pdf:hover { background: #fee2e2; }

    .btn-export-excel {
        background: #fff;
        border-color: #16a34a;
        color: #16a34a;
    }
    .btn-export-excel:hover { background: #dcfce7; }

    .btn-export-print {
        background: #fff;
        border-color: var(--azul);
        color: var(--azul);
    }
    .btn-export-print:hover { background: #eff6ff; }

    /* ── Tabla de rutas ────────────────────────────────────────────────────── */
    .ruta-bar {
        height: 8px;
        border-radius: 4px;
        background: linear-gradient(90deg, var(--azul), var(--azul-claro));
        min-width: 4px;
    }
</style>

{{-- ── Selector de año ─────────────────────────────────────────────────────── --}}
<form method="GET" class="year-selector">
    <label for="anio">Año de análisis:</label>
    <select name="anio" id="anio" onchange="this.form.submit()">
        @foreach($aniosDisponibles as $a)
            <option value="{{ $a }}" {{ $a == $anio ? 'selected' : '' }}>{{ $a }}</option>
        @endforeach
    </select>
    <span style="font-size:.82rem;color:var(--texto-suave)">
        Datos del año {{ $anio }}
    </span>
</form>

{{-- Campo vs planta — últimos 14 días --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3>⚖️ Campo vs planta (litros registrados vs recibidos)</h3>
        <span style="font-size:.78rem;color:var(--texto-suave)">Últimos 14 días</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th style="text-align:right">Campo (acopios)</th>
                    <th style="text-align:right">Planta (control calidad)</th>
                    <th style="text-align:right">Diferencia</th>
                    <th style="text-align:center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campoVsPlanta as $fila)
                <tr style="{{ $fila['alerta'] ? 'background:#fff8e1' : '' }}">
                    <td>{{ $fila['fecha_label'] }}</td>
                    <td style="text-align:right;font-weight:600">{{ number_format($fila['litros_campo'], 0) }} L</td>
                    <td style="text-align:right">{{ number_format($fila['litros_planta'], 0) }} L</td>
                    <td style="text-align:right;color:{{ $fila['diferencia'] >= 0 ? 'var(--verde)' : 'var(--rojo)' }}">
                        {{ $fila['diferencia'] >= 0 ? '+' : '' }}{{ number_format($fila['diferencia'], 0) }} L
                        @if($fila['litros_campo'] > 0)
                            <span style="font-size:.72rem;color:var(--texto-suave)">({{ $fila['pct_diff'] }}%)</span>
                        @endif
                    </td>
                    <td style="text-align:center">
                        @if($fila['litros_campo'] == 0 && $fila['litros_planta'] == 0)
                            <span style="color:var(--texto-suave)">—</span>
                        @elseif($fila['alerta'])
                            <span class="badge badge-observado">⚠ Revisar</span>
                        @else
                            <span class="badge badge-aprobado">OK</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ── Botones de exportación ──────────────────────────────────────────────── --}}
<div class="export-bar">
    <button class="btn-export btn-export-pdf" onclick="exportarPDF()">
        📄 Exportar PDF
    </button>
    <button class="btn-export btn-export-excel" onclick="exportarExcel()">
        📊 Exportar Excel
    </button>
    <button class="btn-export btn-export-print" onclick="window.print()">
        🖨️ Imprimir
    </button>
</div>

{{-- ── KPI Cards ───────────────────────────────────────────────────────────── --}}
<div class="kpi-grid">
    <div class="kpi-card azul">
        <div class="kpi-icon">🥛</div>
        <div class="kpi-label">Total litros {{ $anio }}</div>
        <div class="kpi-value" style="color:var(--azul)">
            {{ number_format($resumenAnio['total_litros'], 0, '.', ',') }}
        </div>
        <div class="kpi-sub">litros acopiados</div>
    </div>

    <div class="kpi-card verde">
        <div class="kpi-icon">🧀</div>
        <div class="kpi-label">Kg queso producido</div>
        <div class="kpi-value" style="color:var(--verde)">
            {{ number_format($resumenAnio['total_kg_queso'], 1, '.', ',') }}
        </div>
        <div class="kpi-sub">kilogramos</div>
    </div>

    <div class="kpi-card indigo">
        <div class="kpi-icon">📦</div>
        <div class="kpi-label">Total acopios</div>
        <div class="kpi-value" style="color:#4338ca">
            {{ number_format($resumenAnio['total_acopios'], 0) }}
        </div>
        <div class="kpi-sub">registros aprobados</div>
    </div>

    <div class="kpi-card naranja">
        <div class="kpi-icon">⚠️</div>
        <div class="kpi-label">Adulterados</div>
        <div class="kpi-value" style="color:var(--naranja)">
            {{ number_format($resumenAnio['adulterados'], 0) }}
        </div>
        <div class="kpi-sub">rechazados</div>
    </div>

    <div class="kpi-card verde">
        <div class="kpi-icon">💰</div>
        <div class="kpi-label">Ventas totales</div>
        <div class="kpi-value" style="color:var(--verde)">
            S/ {{ number_format($resumenAnio['total_ventas'], 2, '.', ',') }}
        </div>
        <div class="kpi-sub">ingresos brutos</div>
    </div>

    <div class="kpi-card purpura">
        <div class="kpi-icon">💹</div>
        <div class="kpi-label">Margen estimado</div>
        <div class="kpi-value" style="color:{{ $resumenAnio['margen'] >= 0 ? '#7c3aed' : 'var(--rojo)' }}">
            S/ {{ number_format($resumenAnio['margen'], 2, '.', ',') }}
        </div>
        <div class="kpi-sub">ventas − costos producción</div>
    </div>
</div>

{{-- ── Gráficos ────────────────────────────────────────────────────────────── --}}
<div class="charts-grid">

    {{-- Gráfico principal: Litros mensuales + Kg queso --}}
    <div class="chart-container">
        <div class="chart-header">
            <h3>📅 Rendimiento mensual {{ $anio }}</h3>
            <div style="display:flex;gap:14px;font-size:.75rem">
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:12px;height:12px;background:#3b82f6;border-radius:3px;display:inline-block"></span>
                    Litros acopiados
                </span>
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:12px;height:12px;background:#16a34a;border-radius:3px;display:inline-block"></span>
                    Kg queso
                </span>
            </div>
        </div>
        <canvas id="chartRendimiento"></canvas>
    </div>

    {{-- Gráfico de ventas por tipo --}}
    <div class="chart-container">
        <div class="chart-header">
            <h3>💰 Ventas por mes</h3>
        </div>
        <canvas id="chartVentas"></canvas>
    </div>

</div>

{{-- ── RF-RENT — Análisis de Rentabilidad Leche ↔ Queso y Sensibilidad ──────── --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header" style="flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center">
        <div>
            <h3>🧀 Rentabilidad Leche ↔ Queso y Sensibilidad de Temporada</h3>
            <span class="card-header-meta">
                Relación de transformación: <strong>{{ number_format($analisisRentabilidad['rendimiento_litros_kg'], 2) }} L / molde</strong> ·
                Temporada activa: <strong style="text-transform:uppercase;color:{{ $analisisRentabilidad['temporada_activa'] === 'lluvias' ? '#2563eb' : '#16a34a' }}">{{ $analisisRentabilidad['temporada_activa'] }}</strong>
            </span>
        </div>
    </div>
    <div class="card-body">
        {{-- Comparación Temporada Normal vs Lluvias --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:18px;margin-bottom:20px">
            
            {{-- Tarjeta Temporada Normal --}}
            <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:18px;position:relative">
                @if($analisisRentabilidad['temporada_activa'] === 'normal')
                    <span style="position:absolute;top:12px;right:12px;background:#dcfce7;color:#166534;padding:2px 8px;border-radius:12px;font-size:.7rem;font-weight:700">ACTIVA</span>
                @endif
                <h4 style="font-size:.9rem;font-weight:700;margin-bottom:6px;display:flex;align-items:center;gap:6px">
                    ☀️ Temporada Seca / Normal
                </h4>
                <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:12px">
                    Precio compra leche: <strong>S/ {{ number_format($analisisRentabilidad['precio_leche_normal'], 2) }} / L</strong>
                </div>
                <div style="border-top:1px solid var(--border);padding-top:10px;display:grid;gap:8px;font-size:.83rem">
                    <div style="display:flex;justify-content:space-between">
                        <span>Costo materia prima leche:</span>
                        <strong>S/ {{ number_format($analisisRentabilidad['normal']['costo_leche'], 2) }} / molde</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span>Margen Canal Mayorista (PVP S/ {{ number_format($analisisRentabilidad['precio_mayorista'], 2) }}):</span>
                        <strong style="color:var(--green)">
                            S/ {{ number_format($analisisRentabilidad['normal']['margen_mayorista'], 2) }}
                            <span style="font-size:.72rem;font-weight:400">({{ $analisisRentabilidad['normal']['pct_mayorista'] }}%)</span>
                        </strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span>Margen Público / Planta (PVP S/ {{ number_format($analisisRentabilidad['precio_publico'], 2) }}):</span>
                        <strong style="color:var(--green)">
                            S/ {{ number_format($analisisRentabilidad['normal']['margen_publico'], 2) }}
                            <span style="font-size:.72rem;font-weight:400">({{ $analisisRentabilidad['normal']['pct_publico'] }}%)</span>
                        </strong>
                    </div>
                </div>
            </div>

            {{-- Tarjeta Temporada de Lluvias --}}
            <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:18px;position:relative">
                @if($analisisRentabilidad['temporada_activa'] === 'lluvias')
                    <span style="position:absolute;top:12px;right:12px;background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:12px;font-size:.7rem;font-weight:700">ACTIVA</span>
                @endif
                <h4 style="font-size:.9rem;font-weight:700;margin-bottom:6px;display:flex;align-items:center;gap:6px">
                    🌧️ Temporada de Lluvias (Alta Producción)
                </h4>
                <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:12px">
                    Precio compra leche: <strong>S/ {{ number_format($analisisRentabilidad['precio_leche_lluvias'], 2) }} / L</strong>
                </div>
                <div style="border-top:1px solid var(--border);padding-top:10px;display:grid;gap:8px;font-size:.83rem">
                    <div style="display:flex;justify-content:space-between">
                        <span>Costo materia prima leche:</span>
                        <strong style="color:#2563eb">S/ {{ number_format($analisisRentabilidad['lluvias']['costo_leche'], 2) }} / molde</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span>Margen Canal Mayorista (PVP S/ {{ number_format($analisisRentabilidad['precio_mayorista'], 2) }}):</span>
                        <strong style="color:var(--green)">
                            S/ {{ number_format($analisisRentabilidad['lluvias']['margen_mayorista'], 2) }}
                            <span style="font-size:.72rem;font-weight:400">({{ $analisisRentabilidad['lluvias']['pct_mayorista'] }}%)</span>
                        </strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span>Margen Público / Planta (PVP S/ {{ number_format($analisisRentabilidad['precio_publico'], 2) }}):</span>
                        <strong style="color:var(--green)">
                            S/ {{ number_format($analisisRentabilidad['lluvias']['margen_publico'], 2) }}
                            <span style="font-size:.72rem;font-weight:400">({{ $analisisRentabilidad['lluvias']['pct_publico'] }}%)</span>
                        </strong>
                    </div>
                </div>
            </div>

        </div>

        {{-- Análisis de sensibilidad --}}
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;font-size:.82rem;color:#1e40af;margin-bottom:18px">
            💡 <strong>Sensibilidad de margen por temporada:</strong> En temporada de lluvias el costo unitario de materia prima por molde disminuye en <strong>S/ {{ number_format($analisisRentabilidad['normal']['costo_leche'] - $analisisRentabilidad['lluvias']['costo_leche'], 2) }}</strong>, incrementando el margen bruto unitario del canal mayorista de <strong>{{ $analisisRentabilidad['normal']['pct_mayorista'] }}%</strong> al <strong>{{ $analisisRentabilidad['lluvias']['pct_mayorista'] }}%</strong>.
        </div>

        {{-- Desglose de rentabilidad por destino --}}
        <h4 style="font-size:.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:10px">
            📍 Rentabilidad y Despachos por Destino (Año {{ $anio }})
        </h4>
        @if($rentabilidadDestinos->isEmpty())
            <p style="color:var(--text-muted);font-size:.85rem">Sin ventas registradas en el año {{ $anio }}.</p>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Mercado Destino</th>
                        <th>Despachos</th>
                        <th>Volumen (kg/moldes)</th>
                        <th>Ingresos Totales</th>
                        <th>Costo Est. Leche</th>
                        <th>Margen Bruto Est.</th>
                        <th>% Margen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rentabilidadDestinos as $rd)
                    <tr>
                        <td><strong>📍 {{ $rd->destino ?: 'Planta / Local' }}</strong></td>
                        <td>{{ $rd->num_despachos }}</td>
                        <td>{{ number_format($rd->total_kg, 1) }} kg</td>
                        <td style="font-weight:600">S/ {{ number_format($rd->total_ingreso, 2) }}</td>
                        <td style="color:var(--text-muted)">S/ {{ number_format($rd->costo_estimado, 2) }}</td>
                        <td style="color:var(--green);font-weight:700">S/ {{ number_format($rd->margen_estimado, 2) }}</td>
                        <td>
                            <span class="badge" style="background:#dcfce7;color:#166534">
                                {{ $rd->pct_margen }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f8fafc;font-weight:700">
                        <td>TOTALES</td>
                        <td>{{ $rentabilidadDestinos->sum('num_despachos') }}</td>
                        <td>{{ number_format($rentabilidadDestinos->sum('total_kg'), 1) }} kg</td>
                        <td>S/ {{ number_format($rentabilidadDestinos->sum('total_ingreso'), 2) }}</td>
                        <td style="color:var(--text-muted)">S/ {{ number_format($rentabilidadDestinos->sum('costo_estimado'), 2) }}</td>
                        <td style="color:var(--green)">S/ {{ number_format($rentabilidadDestinos->sum('margen_estimado'), 2) }}</td>
                        <td>
                            @php
                                $totIng = $rentabilidadDestinos->sum('total_ingreso');
                                $totMarg = $rentabilidadDestinos->sum('margen_estimado');
                                $pctTot = $totIng > 0 ? round(($totMarg / $totIng) * 100, 1) : 0;
                            @endphp
                            <span class="badge" style="background:#dcfce7;color:#166534">{{ $pctTot }}%</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ── Tabla de litros por ruta (mes actual) ──────────────────────────────── --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h3>🛣️ Litros por ruta — {{ now()->isoFormat('MMMM YYYY') }}</h3>
    </div>
    <div class="card-body" style="padding:0">
        @if($litrosPorRutaHoy->isEmpty())
            <p style="padding:24px;text-align:center;color:var(--texto-suave)">
                Sin datos de acopios para el mes actual.
            </p>
        @else
        @php $maxLitros = $litrosPorRutaHoy->max('total_litros') ?: 1; @endphp
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ruta</th>
                        <th>Ganaderos</th>
                        <th>Acopios</th>
                        <th>Total litros</th>
                        <th style="width:200px">Proporción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($litrosPorRutaHoy as $r)
                    <tr>
                        <td><strong>{{ $r->ruta }}</strong></td>
                        <td>{{ $r->num_ganaderos }}</td>
                        <td>{{ $r->num_acopios }}</td>
                        <td><strong>{{ number_format($r->total_litros, 1) }} L</strong></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div class="ruta-bar"
                                     style="width:{{ round(($r->total_litros / $maxLitros) * 160) }}px">
                                </div>
                                <span style="font-size:.78rem;color:var(--texto-suave)">
                                    {{ round(($r->total_litros / $litrosPorRutaHoy->sum('total_litros')) * 100) }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f8fafc">
                        <td colspan="3" style="font-weight:700;padding:11px 14px">TOTAL</td>
                        <td style="font-weight:700;padding:11px 14px">
                            {{ number_format($litrosPorRutaHoy->sum('total_litros'), 1) }} L
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ── Tabla mensual detallada ─────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <h3>📋 Detalle mensual {{ $anio }}</h3>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th>Litros acopiados</th>
                        <th>Acopios</th>
                        <th>Kg queso</th>
                        <th>Rendimiento</th>
                        <th>Costo producción</th>
                        <th>Ventas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($litrosMensuales as $m)
                    @php
                        $prod = $produccionMensualFull->firstWhere('mes', $m['mes']);
                        $venta = collect(range(1,12))->map(fn($x) => $x)->first() !== null
                            ? $ventasMensual->where('mes', $m['mes'])->sum('total_ventas')
                            : 0;
                        $rendimiento = ($prod && $prod['total_kg'] > 0 && $prod['total_litros'] > 0)
                            ? $prod['total_litros'] / $prod['total_kg']
                            : null;
                    @endphp
                    <tr style="{{ $m['total_litros'] == 0 ? 'opacity:.45' : '' }}">
                        <td style="font-weight:600">{{ $m['mes_nombre'] }}</td>
                        <td>{{ $m['total_litros'] > 0 ? number_format($m['total_litros'], 1).' L' : '—' }}</td>
                        <td>{{ $m['num_acopios'] > 0 ? $m['num_acopios'] : '—' }}</td>
                        <td>{{ $prod && $prod['total_kg'] > 0 ? number_format($prod['total_kg'], 2).' kg' : '—' }}</td>
                        <td>{{ $rendimiento ? number_format($rendimiento, 2).' L/kg' : '—' }}</td>
                        <td>{{ $prod && $prod['total_costo'] > 0 ? 'S/ '.number_format($prod['total_costo'], 2) : '—' }}</td>
                        <td>{{ $venta > 0 ? 'S/ '.number_format($venta, 2) : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Chart.js ────────────────────────────────────────────────────────────── --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    // ── Datos del servidor ──────────────────────────────────────────────────
    var labels  = {!! $chartLabels->toJson() !!};
    var litros  = {!! $chartLitros->toJson() !!};
    var kgQueso = {!! $chartKgQueso->toJson() !!};
    var ventas  = {!! $chartVentas->toJson() !!};

    // ── Opciones comunes ────────────────────────────────────────────────────
    var opcionesBase = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: { mode: 'index', intersect: false },
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 } } },
        },
    };

    // ── Gráfico 1: Litros + Kg queso ────────────────────────────────────────
    var ctx1 = document.getElementById('chartRendimiento').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label:           'Litros acopiados',
                    data:            litros,
                    backgroundColor: 'rgba(59,130,246,.7)',
                    borderColor:     '#3b82f6',
                    borderWidth:     1,
                    borderRadius:    4,
                    yAxisID:         'yLitros',
                },
                {
                    label:       'Kg queso producido',
                    data:        kgQueso,
                    type:        'line',
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,.1)',
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#16a34a',
                    fill:        true,
                    tension:     0.4,
                    yAxisID:     'yKg',
                },
            ],
        },
        options: Object.assign({}, opcionesBase, {
            scales: {
                x:       { grid: { display: false }, ticks: { font: { size: 11 } } },
                yLitros: {
                    type:     'linear', position: 'left',
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    title: { display: true, text: 'Litros', font: { size: 10 } },
                },
                yKg: {
                    type:     'linear', position: 'right',
                    beginAtZero: true,
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Kg queso', font: { size: 10 } },
                },
            },
        }),
    });

    // ── Gráfico 2: Ventas mensuales ─────────────────────────────────────────
    var ctx2 = document.getElementById('chartVentas').getContext('2d');
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label:           'Ventas (S/)',
                data:            ventas,
                borderColor:     '#7c3aed',
                backgroundColor: 'rgba(124,58,237,.12)',
                borderWidth:     2.5,
                pointRadius:     4,
                pointBackgroundColor: '#7c3aed',
                fill:            true,
                tension:         0.4,
            }],
        },
        options: opcionesBase,
    });

    // ── Exportaciones (ficticias con alerta informativa) ────────────────────
    window.exportarPDF = function () {
        var anio = document.getElementById('anio').value;
        alert('📄 Exportando reporte PDF del año ' + anio + '...\n\n' +
              'Integra esta función conectando laravel-dompdf o barryvdh/laravel-dompdf:\n' +
              'composer require barryvdh/laravel-dompdf\n\n' +
              'Ruta sugerida: GET /admin/reportes/exportar-pdf?anio=' + anio);
    };

    window.exportarExcel = function () {
        var anio = document.getElementById('anio').value;
        alert('📊 Exportando reporte Excel del año ' + anio + '...\n\n' +
              'Integra esta función con maatwebsite/excel:\n' +
              'composer require maatwebsite/excel\n\n' +
              'Ruta sugerida: GET /admin/reportes/exportar-excel?anio=' + anio);
    };
}());
</script>

@endsection
