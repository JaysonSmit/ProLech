@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', '📊 Dashboard — Resumen del día')

@section('content')

{{-- ── Tarjetas de estadísticas ─────────────────────────────────────────────── --}}
<div class="stats-grid">

    <div class="stat-card azul">
        <div class="label">Litros jornada reciente</div>
        <div class="value">{{ number_format($stats['total_litros_hoy'], 1) }}</div>
        <div class="unit">
            {{ $stats['fecha_litros_hoy'] === now()->toDateString() ? 'hoy' : \Carbon\Carbon::parse($stats['fecha_litros_hoy'])->translatedFormat('d/m/Y') }}
            — aprobados
        </div>
    </div>

    <div class="stat-card azul">
        <div class="label">Litros este mes</div>
        <div class="value">{{ number_format($stats['total_litros_mes'], 0) }}</div>
        <div class="unit">litros acumulados</div>
    </div>

    <div class="stat-card verde">
        <div class="label">Kg queso (mes)</div>
        <div class="value">{{ number_format($stats['kg_producidos_mes'], 1) }}</div>
        <div class="unit">kg producidos</div>
    </div>

    <div class="stat-card verde">
        <div class="label">Ventas del mes</div>
        <div class="value">S/ {{ number_format($stats['ventas_mes'], 2) }}</div>
        <div class="unit">ingresos totales</div>
    </div>

    <div class="stat-card naranja">
        <div class="label">Acopios observados</div>
        <div class="value">{{ $stats['acopios_observados'] }}</div>
        <div class="unit">este mes — requieren revisión</div>
    </div>

    <div class="stat-card rojo">
        <div class="label">Acopios rechazados</div>
        <div class="value">{{ $stats['acopios_rechazados'] }}</div>
        <div class="unit">este mes — adulterados</div>
    </div>

    <div class="stat-card azul">
        <div class="label">Ganaderos activos</div>
        <div class="value">{{ $stats['total_ganaderos'] }}</div>
        <div class="unit">en padrón</div>
    </div>

    <div class="stat-card {{ $stats['alerta_stock_quesos'] ? 'rojo' : 'verde' }}">
        <div class="label">Stock quesos planta</div>
        <div class="value">{{ number_format($stats['stock_actual_quesos'], 1) }}</div>
        <div class="unit">moldes disp. (mín: {{ $stats['stock_minimo_quesos'] }})</div>
    </div>

</div>

@if($stats['alerta_stock_quesos'])
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--red);background:#fffbfb">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:1.4rem">🧀⚠️</span>
            <div>
                <strong style="color:var(--red);font-size:0.95rem">Alerta de Stock Crítico de Quesos en Planta</strong>
                <p style="font-size:0.8rem;color:var(--text-muted);margin-top:2px">
                    El inventario disponible en planta es de <strong>{{ number_format($stats['stock_actual_quesos'], 1) }} moldes</strong>,
                    inferior o igual al mínimo de reserva de <strong>{{ $stats['stock_minimo_quesos'] }} moldes</strong> para venta en planta.
                </p>
            </div>
        </div>
        @if(auth()->user()->seccionWebPermitida('produccion'))
        <a href="{{ route('admin.produccion') }}" class="btn btn-primary btn-sm">Iniciar Lote</a>
        @endif
    </div>
</div>
@endif

@if($acopiosSinBalde->isNotEmpty())
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--amber)">
    <div class="card-header">
        <h3>🪣 RF-ACO-07 — Acopios hoy sin confirmación de balde</h3>
        <span class="tag" style="background:var(--amber-bg);color:#92400e">{{ $acopiosSinBalde->count() }}</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Chofer</th>
                    <th>Litros</th>
                    <th>Hora</th>
                </tr>
            </thead>
            <tbody>
                @foreach($acopiosSinBalde as $a)
                <tr>
                    <td>{{ $a->ganadero->nombre ?? '—' }}</td>
                    <td>{{ $a->chofer->name ?? '—' }}</td>
                    <td>{{ number_format($a->litros, 1) }} L</td>
                    <td>{{ $a->created_at->format('H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(auth()->user()->isAdmin())
<div style="margin-bottom:22px">@include('admin.costos._costo_leche')</div>

@endif
{{-- ── Tabla adulteraciones del día ────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <h3>⚠️ Adulteraciones detectadas — {{ \Carbon\Carbon::parse($stats['fecha_litros_hoy'])->translatedFormat('d/m/Y') }}</h3>
        <span style="font-size:.8rem;color:var(--texto-suave)">
            {{ $adulteracionesHoy->count() }} registro(s)
        </span>
    </div>
    <div class="card-body" style="padding:0">
        @if($adulteracionesHoy->isEmpty())
            <p style="padding:24px;color:var(--texto-suave);text-align:center">
                ✅ Sin adulteraciones registradas hoy.
            </p>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ganadero</th>
                        <th>DNI</th>
                        <th>Chofer</th>
                        <th>Litros</th>
                        <th>% Agua</th>
                        <th>pH</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($adulteracionesHoy as $a)
                    <tr>
                        <td style="color:var(--texto-suave);font-size:.8rem">{{ $a->id }}</td>
                        <td><strong>{{ $a->ganadero->nombre ?? '—' }}</strong></td>
                        <td style="font-family:monospace">{{ $a->ganadero->dni ?? '—' }}</td>
                        <td>{{ $a->chofer->name ?? '—' }}</td>
                        <td>{{ number_format($a->litros, 1) }} L</td>
                        <td>
                            @if($a->porcentaje_agua !== null)
                                <span style="color:{{ $a->porcentaje_agua > 5 ? 'var(--rojo)' : 'var(--texto)' }};font-weight:{{ $a->porcentaje_agua > 5 ? '700' : '400' }}">
                                    {{ number_format($a->porcentaje_agua, 2) }}%
                                </span>
                            @else
                                <span style="color:var(--texto-suave)">—</span>
                            @endif
                        </td>
                        <td>
                            @if($a->acidez_ph !== null)
                                <span style="color:{{ ($a->acidez_ph < 6.4 || $a->acidez_ph > 6.8) ? 'var(--rojo)' : 'var(--texto)' }};font-weight:{{ ($a->acidez_ph < 6.4 || $a->acidez_ph > 6.8) ? '700' : '400' }}">
                                    {{ number_format($a->acidez_ph, 2) }}
                                </span>
                            @else
                                <span style="color:var(--texto-suave)">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $a->estado }}">{{ ucfirst($a->estado) }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ── Últimos 10 acopios ──────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <h3>🚛 Últimos acopios registrados</h3>
    </div>
    <div class="card-body" style="padding:0">
        @if($acopiosRecientes->isEmpty())
            <p style="padding:24px;color:var(--texto-suave);text-align:center">
                Sin acopios registrados aún.
            </p>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Ganadero</th>
                        <th>Chofer</th>
                        <th>Litros</th>
                        <th>Balde</th>
                        <th>LactoScan</th>
                        <th>% Agua</th>
                        <th>pH</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($acopiosRecientes as $a)
                    <tr>
                        <td style="white-space:nowrap">{{ $a->fecha->format('d/m/Y') }}</td>
                        <td>{{ $a->ganadero->nombre ?? '—' }}</td>
                        <td>{{ $a->chofer->name ?? '—' }}</td>
                        <td>{{ number_format($a->litros, 1) }}</td>
                        <td>
                            @if($a->medicion_balde_confirmada ?? true)
                                <span style="color:var(--green)">✓</span>
                            @else
                                <span style="color:var(--amber);font-weight:700">⚠</span>
                            @endif
                        </td>
                        <td>{{ $a->equipo_lactoscan ? '🔬 '.$a->equipo_lactoscan : '—' }}</td>
                        <td>{{ $a->porcentaje_agua !== null ? number_format($a->porcentaje_agua, 2).'%' : '—' }}</td>
                        <td>{{ $a->acidez_ph !== null ? number_format($a->acidez_ph, 2) : '—' }}</td>
                        <td><span class="badge badge-{{ $a->estado }}">{{ ucfirst($a->estado) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection
