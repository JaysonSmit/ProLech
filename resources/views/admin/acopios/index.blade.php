@extends('layouts.admin')

@section('title', 'Entregas de leche')
@section('page-title', '🥛 Entregas de leche (acopios)')

@section('content')
@php $obsTxt = \App\Services\ControlCalidadService::OBSERVACIONES; @endphp
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:14px 18px">
        <form method="GET" style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr)) auto;gap:10px;align-items:end">
            <div class="form-group" style="margin:0"><label>Desde</label><input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"></div>
            <div class="form-group" style="margin:0"><label>Hasta</label><input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"></div>
            <div class="form-group" style="margin:0"><label>Ruta</label>
                <select name="zona"><option value="">Todas</option>@foreach($zonas as $z)<option value="{{ $z->id }}" {{ ($filtros['zona'] ?? '') == $z->id ? 'selected' : '' }}>{{ $z->nombre }}</option>@endforeach</select></div>
            <div class="form-group" style="margin:0"><label>Chofer</label>
                <select name="chofer"><option value="">Todos</option>@foreach($choferes as $c)<option value="{{ $c->id }}" {{ ($filtros['chofer'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
            <div class="form-group" style="margin:0"><label>Estado</label>
                <select name="estado"><option value="">Todos</option>@foreach(['aprobado','observado','rechazado'] as $e)<option value="{{ $e }}" {{ ($filtros['estado'] ?? '') === $e ? 'selected' : '' }}>{{ ucfirst($e) }}</option>@endforeach</select></div>
            <div class="form-group" style="margin:0"><label>Ganadero o DNI</label><input name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Buscar…"></div>
            <div style="display:flex;gap:6px"><button class="btn btn-primary btn-sm">Filtrar</button><a href="{{ route('admin.acopios') }}" class="btn btn-ghost btn-sm">Limpiar</a></div>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:16px">
    @foreach([['Entregas', $totales->n, '#1f6feb'], ['Litros', number_format($totales->litros, 1) . ' L', '#0ea5e9'], ['Aprobados', $totales->aprobados, '#16a34a'], ['Observados / rechazados', $totales->observados . ' / ' . $totales->rechazados, '#dc2626']] as [$l, $v, $c])
    <div class="card" style="border-top:4px solid {{ $c }};padding:14px 16px"><div style="font-size:.68rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.6px">{{ $l }}</div><div style="font-size:1.4rem;font-weight:800;margin-top:4px">{{ $v }}</div></div>
    @endforeach
</div>

<div class="card">
    <div class="table-wrap">
        <table style="width:100%">
            <thead><tr><th>Fecha</th><th>Ganadero</th><th>Ruta</th><th>Chofer</th><th>Litros</th><th>Observación</th><th>Calidad (planta)</th><th>Muestra</th><th>Estado</th></tr></thead>
            <tbody>
            @forelse($acopios as $a)
                @php $sinMedir = (float) $a->porcentaje_agua == 0.0 && ! $a->densidad && ! $a->acidez_ph; @endphp
                <tr>
                    <td style="white-space:nowrap">{{ $a->fecha->format('d/m/Y') }}<br><small style="color:var(--text-muted)">{{ $a->acopio_campo_id ? '📱 App' : '🏭 Planta' }}</small></td>
                    <td>{{ $a->ganadero->nombre ?? '—' }}<br><small style="color:var(--text-muted)">DNI {{ $a->ganadero->dni ?? '—' }}</small></td>
                    <td>{{ $a->ganadero?->zona?->nombre ?? '—' }}</td>
                    <td>{{ $a->chofer->name ?? '—' }}</td>
                    <td><strong>{{ number_format($a->litros, 1) }}</strong></td>
                    <td style="font-size:.8rem">{{ $a->observacion ? ($obsTxt[$a->observacion] ?? $a->observacion) : '—' }}</td>
                    <td style="font-size:.8rem;white-space:nowrap">{{ $sinMedir ? 'Sin medir' : number_format($a->porcentaje_agua, 1) . ' % agua' . ($a->acidez_ph ? ' · pH ' . number_format($a->acidez_ph, 2) : '') }}</td>
                    <td style="font-size:.8rem">{{ $a->muestra_codigo ? '🧪 ' . $a->muestra_codigo : '—' }}</td>
                    <td><span class="badge badge-{{ $a->estado }}">{{ ucfirst($a->estado) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;padding:24px;color:var(--text-muted)">Sin entregas con esos filtros.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 18px">{{ $acopios->links() }}</div>
</div>
@endsection
