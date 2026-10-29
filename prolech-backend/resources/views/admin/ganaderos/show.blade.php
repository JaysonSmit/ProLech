@extends('layouts.admin')

@section('title', $ganadero->nombre)
@section('page-title', '🐄 Ficha del ganadero')

@section('content')
@php
    $col = ['verde' => ['#dcfce7', '#15803d'], 'amarillo' => ['#fef3c7', '#92400e'], 'rojo' => ['#fee2e2', '#b91c1c']][$riesgo['nivel']];
    $dias = collect(range(29, 0))->map(fn ($d) => today()->subDays($d)->toDateString());
    $max = max(1, (float) $serie->max());
    $obsTxt = \App\Services\ControlCalidadService::OBSERVACIONES;
@endphp
<div style="display:grid;grid-template-columns:1.1fr 1.9fr;gap:18px;margin-bottom:18px">
    <div class="card" style="padding:20px">
        <div style="display:flex;gap:14px;align-items:center">
            <div style="width:56px;height:56px;border-radius:50%;background:var(--grad);color:#fff;font-weight:800;font-size:1.4rem;display:flex;align-items:center;justify-content:center">{{ mb_substr($ganadero->nombre, 0, 1) }}</div>
            <div><div style="font-size:1.25rem;font-weight:800">{{ $ganadero->nombre }}</div>
                <div style="color:var(--text-muted);font-size:.85rem">DNI {{ $ganadero->dni }} · {{ $ganadero->zona?->nombre ?? 'Sin ruta' }}</div></div>
        </div>
        <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
            <span class="badge" style="background:{{ $col[0] }};color:{{ $col[1] }}">● Semáforo {{ $riesgo['nivel'] }} · {{ $riesgo['puntaje'] }} pts</span>
            <span class="badge badge-{{ $ganadero->estado === 'Suspendido' ? 'rechazado' : 'aprobado' }}">{{ $ganadero->estado }}</span>
            @if($ganadero->entregas_bloqueadas)<span class="badge badge-rechazado">Bloqueado {{ $ganadero->entregas_bloqueadas }} entrega(s)</span>@endif
        </div>
        <div style="margin-top:12px;font-size:.82rem;color:var(--text-muted)">{{ implode(' · ', $riesgo['motivos']) ?: 'Sin señales de riesgo en 21 días.' }}</div>
        <div style="margin-top:14px;font-size:.84rem">📍 {{ $ganadero->direccion ?: '—' }}
            @if($ganadero->geo_lat)<br><small style="color:var(--text-muted)">GPS {{ number_format($ganadero->geo_lat, 5) }}, {{ number_format($ganadero->geo_lon, 5) }}</small>@endif</div>
    </div>
    <div class="card" style="padding:18px 20px">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px">
            @foreach([['Entregas (30 d)', $resumen->n], ['Litros (30 d)', number_format((float) $resumen->litros, 1)], ['Agua promedio', number_format((float) $resumen->agua, 1) . ' %'], ['Observadas / rechazadas', (int) $resumen->malas]] as [$l, $v])
                <div><div style="font-size:.68rem;font-weight:700;text-transform:uppercase;color:var(--text-muted)">{{ $l }}</div><div style="font-size:1.3rem;font-weight:800">{{ $v }}</div></div>
            @endforeach
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:6px">Litros entregados por día (últimos 30 días)</div>
        <div style="display:flex;align-items:flex-end;gap:3px;height:110px;border-bottom:1px solid var(--border)">
            @foreach($dias as $d)
                @php $v = (float) ($serie[$d] ?? 0); @endphp
                <div title="{{ \Carbon\Carbon::parse($d)->format('d/m') }}: {{ $v }} L" style="flex:1;height:{{ max(2, $v / $max * 100) }}%;background:{{ $v ? 'var(--grad)' : '#e2e8f0' }};border-radius:3px 3px 0 0"></div>
            @endforeach
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:18px">
    <div class="card">
        <div class="card-header"><h3>🥛 Entregas</h3></div>
        <div class="table-wrap"><table style="width:100%">
            <thead><tr><th>Fecha</th><th>Chofer</th><th>Litros</th><th>Agua</th><th>Observación</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach($acopios as $a)
                <tr>
                    <td>{{ $a->fecha->format('d/m/Y') }}</td>
                    <td>{{ $a->chofer->name ?? '—' }}</td>
                    <td><strong>{{ number_format($a->litros, 1) }}</strong></td>
                    <td>{{ ((float) $a->porcentaje_agua || $a->acidez_ph) ? number_format($a->porcentaje_agua, 1) . ' %' : 'Sin medir' }}</td>
                    <td>{{ $a->observacion ? ($obsTxt[$a->observacion] ?? $a->observacion) : '—' }}</td>
                    <td><span class="badge badge-{{ $a->estado }}">{{ ucfirst($a->estado) }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <div style="padding:12px 18px">{{ $acopios->links() }}</div>
    </div>
    <div style="display:flex;flex-direction:column;gap:18px">
        <div class="card"><div class="card-header"><h3>📑 Liquidaciones</h3></div>
            <div class="card-body" style="padding:10px 18px">
            @forelse($liquidaciones as $l)
                <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-soft);font-size:.84rem">
                    <span>{{ \Carbon\Carbon::parse($l->semana_inicio)->format('d/m') }}–{{ \Carbon\Carbon::parse($l->semana_fin)->format('d/m') }} · {{ number_format($l->litros_aprobados, 1) }} L</span>
                    <span><strong>S/ {{ number_format($l->total_neto, 2) }}</strong> <span class="badge badge-{{ $l->estado }}" style="font-size:.62rem">{{ $l->estado }}</span></span>
                </div>
            @empty
                <p style="color:var(--text-muted);font-size:.84rem">Sin liquidaciones.</p>
            @endforelse
            </div></div>
        <div class="card"><div class="card-header"><h3>🧪 Controles de calidad</h3></div>
            <div class="card-body" style="padding:10px 18px">
            @forelse($controles as $c)
                <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-soft);font-size:.84rem">
                    <span>{{ \Carbon\Carbon::parse($c->fecha)->format('d/m/Y') }} · {{ ucfirst($c->tipo) }}</span>
                    <span>{{ $c->resultado ? ucfirst($c->resultado) : ucfirst(str_replace('_', ' ', $c->estado)) }}</span>
                </div>
            @empty
                <p style="color:var(--text-muted);font-size:.84rem">Aún no tuvo controles sorpresa.</p>
            @endforelse
            </div></div>
    </div>
</div>
@endsection
