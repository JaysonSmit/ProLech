<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Entregas — ProLech</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; background:#f4f6f4; color:#1a2e1a; }
        .header { background:#1a5c2a; color:#fff; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; }
        .header-logo { font-size:1.2rem; font-weight:800; }
        .nav-tabs { background:#fff; border-bottom:2px solid #e0e8e0; display:flex; }
        .nav-tab { padding:12px 20px; font-size:.88rem; font-weight:600; color:#6b8f6b; text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-2px; }
        .nav-tab.active { color:#1a5c2a; border-bottom-color:#1a5c2a; }
        .container { max-width:900px; margin:24px auto; padding:0 16px; }
        .card { background:#fff; border-radius:10px; border:1px solid #e0e8e0; margin-bottom:18px; overflow:hidden; }
        .card-header { padding:14px 18px; border-bottom:1px solid #e0e8e0; display:flex; justify-content:space-between; align-items:center; }
        .card-header h3 { font-size:.95rem; font-weight:700; color:#0f3d1a; }
        table { width:100%; border-collapse:collapse; }
        th { padding:9px 14px; font-size:.78rem; color:#6b8f6b; font-weight:600; text-align:left; background:#f4f6f4; }
        td { padding:10px 14px; font-size:.88rem; border-bottom:1px solid #e0e8e0; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:700; }
        .badge-aprobado  { background:#e8f5e9; color:#1a5c2a; }
        .badge-observado { background:#fff8e1; color:#b45309; }
        .badge-rechazado { background:#ffebee; color:#b71c1c; }
        .filtro-btn { padding:7px 16px; border-radius:20px; border:1px solid #e0e8e0; background:#fff; font-size:.82rem; color:#6b8f6b; cursor:pointer; text-decoration:none; }
        .filtro-btn.active { background:#1a5c2a; color:#fff; border-color:#1a5c2a; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-logo">🐄 ProLech · Mis Entregas</div>
    <div style="font-size:.82rem;color:#a7f3c8">{{ $ganadero->nombre }}</div>
</div>

<div class="nav-tabs">
    <a href="{{ route('proveedor.dashboard') }}" class="nav-tab">🏠 Inicio</a>
    <a href="{{ route('proveedor.historial') }}" class="nav-tab active">📋 Mis Entregas</a>
    <a href="{{ route('proveedor.pagos') }}" class="nav-tab">💰 Mis Pagos</a>
</div>

<div class="container">

    {{-- Filtro de período --}}
    <div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap">
        @foreach([7 => 'Esta semana', 30 => 'Último mes', 90 => '3 meses'] as $d => $label)
        <a href="{{ route('proveedor.historial', ['dias' => $d]) }}"
           class="filtro-btn {{ $dias == $d ? 'active' : '' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <div class="stats-row" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px">
        <div class="card" style="padding:14px;text-align:center">
            <div style="font-size:.75rem;color:#6b8f6b">Entregas</div>
            <div style="font-size:1.6rem;font-weight:900;color:#1a5c2a">{{ $acopios->count() }}</div>
        </div>
        <div class="card" style="padding:14px;text-align:center">
            <div style="font-size:.75rem;color:#6b8f6b">Incidencias calidad / sanciones</div>
            <div style="font-size:1.6rem;font-weight:900;color:#b45309">{{ $sancionesCount }}</div>
        </div>
        <div class="card" style="padding:14px;text-align:center">
            <div style="font-size:.75rem;color:#6b8f6b">Reclamos</div>
            <div style="font-size:1.6rem;font-weight:900;color:#1565c0">{{ $reclamos->count() }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>📋 Historial de entregas — últimos {{ $dias }} días</h3>
            <span style="font-size:.8rem;color:#6b8f6b">{{ $acopios->count() }} registros</span>
        </div>

        @if($acopios->isEmpty())
        <div style="padding:32px;text-align:center;color:#6b8f6b">
            <div style="font-size:2rem;margin-bottom:8px">📦</div>
            Sin entregas en este período.
        </div>
        @else
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th style="text-align:right">Litros</th>
                    <th style="text-align:center">% Agua</th>
                    <th style="text-align:center">pH</th>
                    <th style="text-align:center">Densidad</th>
                    <th style="text-align:center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($acopios as $a)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($a->fecha)->isoFormat('ddd D MMM') }}</td>
                    <td style="text-align:right;font-weight:700;color:#1a5c2a">
                        {{ number_format($a->litros, 1) }} L
                    </td>
                    <td style="text-align:center">
                        @if($a->porcentaje_agua !== null)
                            <span style="color:{{ $a->porcentaje_agua > 5 ? '#b71c1c' : ($a->porcentaje_agua > 1 ? '#b45309' : '#1a5c2a') }};font-weight:600">
                                {{ number_format($a->porcentaje_agua, 2) }}%
                            </span>
                        @else <span style="color:#aaa">—</span> @endif
                    </td>
                    <td style="text-align:center;color:#6b8f6b">
                        {{ $a->acidez_ph !== null ? number_format($a->acidez_ph, 2) : '—' }}
                    </td>
                    <td style="text-align:center;color:#6b8f6b">
                        {{ $a->densidad !== null ? number_format($a->densidad, 3) : '—' }}
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-{{ $a->estado }}">{{ ucfirst($a->estado) }}</span>
                        @if($a->tiene_descuento)
                            <span style="font-size:.7rem;color:#b45309;display:block">-descuento</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f4f6f4">
                    <td style="padding:10px 14px;font-weight:700">Total</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:900;color:#1a5c2a">
                        {{ number_format($acopios->sum('litros'), 1) }} L
                    </td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>

    @if($reclamos->isNotEmpty())
    <div class="card">
        <div class="card-header"><h3>📢 Reclamos de litraje</h3></div>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Registrado</th>
                    <th>Reclamado</th>
                    <th>Estado</th>
                    <th>Respuesta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reclamos as $rec)
                <tr>
                    <td>{{ $rec->fecha_reclamo->format('d/m/Y') }}</td>
                    <td>{{ number_format($rec->litros_registrados, 1) }} L</td>
                    <td style="font-weight:700">{{ number_format($rec->litros_correctos, 1) }} L</td>
                    <td>{{ ucfirst($rec->estado) }}</td>
                    <td style="font-size:.82rem;color:#6b8f6b">{{ $rec->respuesta_admin ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($liquidaciones->isNotEmpty())
    <div class="card">
        <div class="card-header"><h3>💰 Liquidaciones del período</h3></div>
        <table>
            <thead>
                <tr>
                    <th>Semana</th>
                    <th>Litros</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($liquidaciones as $liq)
                <tr>
                    <td>{{ $liq->semana_inicio->format('d/m') }} → {{ $liq->semana_fin->format('d/m/Y') }}</td>
                    <td>{{ number_format($liq->litros_aprobados, 1) }} L</td>
                    <td style="font-weight:700;color:#1a5c2a">S/ {{ number_format($liq->total_neto, 2) }}</td>
                    <td>{{ ucfirst($liq->estado) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
</body>
</html>
