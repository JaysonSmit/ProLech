@extends('layouts.admin')

@section('title', $tipo === 'insumos' ? 'Kárdex de insumos' : 'Kárdex de productos')
@section('page-title', $tipo === 'insumos' ? '📒 Kárdex valorizado de insumos' : '📗 Kárdex valorizado de productos terminados')

@section('content')
@php
    $s = fn ($v, $d = 2) => 'S/ ' . number_format((float) $v, $d);
    $q = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
@endphp
<style>
    .kx-cab { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
    .kx-tabs { display:flex; gap:4px; background:#f1f5f9; border-radius:12px; padding:4px; }
    .kx-tabs a { padding:7px 16px; border-radius:9px; font-weight:700; font-size:.84rem; text-decoration:none; color:var(--text-muted); }
    .kx-tabs a.sel { background:#fff; color:var(--brand-light); box-shadow:0 1px 3px rgba(0,0,0,.1); }
    .kx-filtros { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .kx-filtros select, .kx-filtros input { padding:8px 10px; border:1px solid var(--border); border-radius:8px; font-size:.84rem; }
    .kx-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
    .kx-kpi { background:#fff; border:1px solid var(--border); border-radius:16px; padding:14px 16px; border-top:4px solid var(--c); }
    .kx-kpi .l { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--text-muted); }
    .kx-kpi .v { font-size:1.35rem; font-weight:800; margin-top:4px; }
    .kx-kpi .s { font-size:.75rem; color:var(--text-muted); }
    .kx-tabla th, .kx-tabla td { padding:9px 10px; font-size:.82rem; white-space:nowrap; }
    .kx-tabla .n { text-align:right; }
    .kx-tabla thead tr:first-child th { text-align:center; background:#0f2a4d; color:#fff; }
    .kx-ent { background:#f0fdf4; } .kx-sal { background:#fef2f2; } .kx-sdo { background:#eff6ff; }
    @media (max-width:1100px) { .kx-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>

<div class="kx-cab">
    <div class="kx-tabs">
        <a href="{{ route('admin.kardex.insumos') }}" class="{{ $tipo === 'insumos' ? 'sel' : '' }}">📒 Insumos</a>
        <a href="{{ route('admin.kardex.productos') }}" class="{{ $tipo === 'productos' ? 'sel' : '' }}">📗 Productos terminados</a>
    </div>
    <form method="GET" class="kx-filtros">
        <select name="id" onchange="this.form.submit()">
            @foreach($items as $it)<option value="{{ $it->id }}" {{ $item?->id === $it->id ? 'selected' : '' }}>{{ $it->nombre }}</option>@endforeach
        </select>
        <input type="date" name="desde" value="{{ $desde?->toDateString() }}" title="Desde">
        <input type="date" name="hasta" value="{{ $hasta?->toDateString() }}" title="Hasta">
        <button class="btn btn-ghost btn-sm">Filtrar</button>
    </form>
</div>

<div class="kx-kpis">
    <div class="kx-kpi" style="--c:#16a34a"><div class="l">Entradas</div><div class="v">{{ $q($resumen['entradas']) }} {{ $unidad }}</div><div class="s">{{ $s($resumen['valor_entradas']) }}</div></div>
    <div class="kx-kpi" style="--c:#dc2626"><div class="l">Salidas</div><div class="v">{{ $q($resumen['salidas']) }} {{ $unidad }}</div><div class="s">{{ $s($resumen['valor_salidas']) }} al costo promedio</div></div>
    <div class="kx-kpi" style="--c:#1f6feb"><div class="l">Saldo final</div><div class="v">{{ $q($resumen['saldo']) }} {{ $unidad }}</div><div class="s">valorizado en {{ $s($resumen['saldo_valor']) }}</div></div>
    <div class="kx-kpi" style="--c:#f59e0b"><div class="l">Costo promedio</div><div class="v">{{ $s($resumen['costo_prom'], 4) }}</div><div class="s">por {{ $unidad }} · método promedio ponderado</div></div>
</div>

<div class="card">
    <div class="card-header"><h3>{{ $item?->nombre ?? '—' }}</h3>
        <span class="card-header-meta">
            @if($tipo === 'insumos')
                {{ $item && \App\Services\CostoLecheService::esLecheCruda($item) ? 'Entradas: acopios del día al costo real por litro · Salidas: consumo de los lotes de producción' : 'Entradas: compras e ingresos · Salidas: consumo de los lotes de producción' }}
            @else
                Entradas: lotes producidos (costo del lote ÷ unidades) · Salidas: ventas en planta y pedidos de la tienda web
            @endif
        </span>
    </div>
    <div class="table-wrap">
        <table class="kx-tabla" style="width:100%">
            <thead>
                <tr><th colspan="3"></th><th colspan="3" style="background:#15803d">ENTRADAS</th><th colspan="3" style="background:#b91c1c">SALIDAS</th><th colspan="3" style="background:#1d4ed8">SALDO</th></tr>
                <tr>
                    <th>Fecha</th><th>Documento</th><th>Concepto</th>
                    <th class="n">Cant.</th><th class="n">C. unit.</th><th class="n">Total</th>
                    <th class="n">Cant.</th><th class="n">C. unit.</th><th class="n">Total</th>
                    <th class="n">Cant.</th><th class="n">C. prom.</th><th class="n">Valor</th>
                </tr>
            </thead>
            <tbody>
            @forelse($filas as $f)
                @php $e = $f['tipo'] === 'entrada'; @endphp
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($f['fecha'])->format('d/m/Y') }}</td>
                    <td><code>{{ $f['documento'] }}</code></td>
                    <td style="white-space:normal;max-width:260px">{{ $f['concepto'] }}</td>
                    <td class="n kx-ent">{{ $e ? $q($f['cantidad']) : '' }}</td>
                    <td class="n kx-ent">{{ $e ? $s($f['costo_unit'], 4) : '' }}</td>
                    <td class="n kx-ent">{{ $e ? $s($f['total']) : '' }}</td>
                    <td class="n kx-sal">{{ $e ? '' : $q($f['cantidad']) }}</td>
                    <td class="n kx-sal">{{ $e ? '' : $s($f['costo_unit'], 4) }}</td>
                    <td class="n kx-sal">{{ $e ? '' : $s($f['total']) }}</td>
                    <td class="n kx-sdo"><strong>{{ $q($f['saldo_cant']) }}</strong></td>
                    <td class="n kx-sdo">{{ $s($f['costo_prom'], 4) }}</td>
                    <td class="n kx-sdo"><strong>{{ $s($f['saldo_valor']) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="12" style="text-align:center;padding:24px;color:var(--text-muted)">Sin movimientos en el periodo.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 18px">{{ $filas->links() }}</div>
</div>
@endsection
