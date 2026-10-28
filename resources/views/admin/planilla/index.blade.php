@extends('layouts.admin')

@section('title', 'Planilla de choferes')
@section('page-title', '🚚 Planilla de Choferes')

@section('content')
@php
    $soles = fn ($v) => 'S/ ' . number_format((float) $v, 2);
    $anterior  = $tipo === 'mensual' ? $desde->copy()->subMonthNoOverflow() : $desde->copy()->subWeek();
    $siguiente = $tipo === 'mensual' ? $desde->copy()->addMonthNoOverflow() : $desde->copy()->addWeek();
    $fuentes = ['gps' => '📡 GPS', 'ruta' => '🗺️ Ruta trazada', 'estimado' => '≈ Estimado'];
@endphp
<style>
    .pl-cab { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
    .pl-tabs { display:flex; gap:4px; background:#f1f5f9; border-radius:12px; padding:4px; }
    .pl-tabs a { padding:7px 16px; border-radius:9px; font-weight:700; font-size:.84rem; text-decoration:none; color:var(--text-muted); }
    .pl-tabs a.sel { background:#fff; color:var(--brand-light); box-shadow:0 1px 3px rgba(0,0,0,.1); }
    .pl-kpis { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
    .pl-kpi { background:#fff; border:1px solid var(--border); border-radius:18px; padding:14px 16px; position:relative; overflow:hidden; }
    .pl-kpi::before { content:""; position:absolute; inset:0 0 auto 0; height:4px; background:var(--c, var(--brand)); }
    .pl-kpi .lbl { font-size:.68rem; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--text-muted); }
    .pl-kpi .val { font-size:1.45rem; font-weight:800; margin-top:6px; }
    .pl-kpi .sub { font-size:.74rem; color:var(--text-muted); margin-top:2px; }
    .pl-formula { background:var(--blue-bg); border:1px solid #bfdbfe; border-radius:14px; padding:12px 16px; font-size:.82rem; color:#1e3a8a; margin-bottom:18px; line-height:1.6; }
    .pl-formula code { background:#fff; border-radius:6px; padding:1px 6px; font-weight:700; }
    .pl-tabla td, .pl-tabla th { padding:10px 12px; font-size:.84rem; vertical-align:middle; }
    .pl-tabla .num { text-align:right; white-space:nowrap; }
    .pl-dias { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:8px; padding:10px 0 4px; }
    .pl-dia { background:var(--bg); border-radius:10px; padding:8px 10px; font-size:.76rem; }
    .pl-acc { display:flex; gap:6px; flex-wrap:nowrap; align-items:center; }
    .pl-acciones { display:flex; flex-direction:column; gap:6px; min-width:250px; }
    .pl-acc input, .pl-acc select { padding:5px 8px; border:1px solid var(--border); border-radius:8px; font-size:.78rem; }
    @media (max-width: 1300px) { .pl-kpis { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width: 700px) { .pl-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>

@if(session('success'))<div class="flash flash-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="flash flash-error">⚠️ {{ session('error') }}</div>@endif
@if($errors->any())<div class="flash flash-error">⚠️ {{ $errors->first() }}</div>@endif

<div class="pl-cab">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <div class="pl-tabs">
            <a href="{{ route('admin.planilla.index', ['tipo' => 'semanal']) }}" class="{{ $tipo === 'semanal' ? 'sel' : '' }}">📅 Semanal</a>
            <a href="{{ route('admin.planilla.index', ['tipo' => 'mensual']) }}" class="{{ $tipo === 'mensual' ? 'sel' : '' }}">🗓️ Mensual</a>
        </div>
        <a href="{{ route('admin.planilla.index', ['tipo' => $tipo, 'fecha' => $anterior->toDateString()]) }}" class="btn btn-ghost btn-sm">←</a>
        <div class="tag" style="font-weight:700">{{ $desde->format('d/m/Y') }} → {{ $hasta->format('d/m/Y') }}</div>
        <a href="{{ route('admin.planilla.index', ['tipo' => $tipo, 'fecha' => $siguiente->toDateString()]) }}" class="btn btn-ghost btn-sm">→</a>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.planilla.exportar', ['tipo' => $tipo, 'fecha' => $desde->toDateString(), 'formato' => 'xlsx']) }}">📊 Excel</a>
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.planilla.exportar', ['tipo' => $tipo, 'fecha' => $desde->toDateString(), 'formato' => 'pdf']) }}">📄 PDF</a>
        <form method="POST" action="{{ route('admin.planilla.generar') }}">
            @csrf
            <input type="hidden" name="tipo" value="{{ $tipo }}">
            <input type="hidden" name="fecha" value="{{ $desde->toDateString() }}">
            <button class="btn btn-primary" onclick="return confirm('¿Generar/recalcular la planilla {{ $tipo }} del {{ $desde->format('d/m') }} al {{ $hasta->format('d/m/Y') }}? Las ya pagadas no cambian.')">⚙️ Generar planilla</button>
        </form>
    </div>
</div>

<div class="pl-formula">
    <strong>¿Cómo se calcula?</strong>
    <code>Base = días con ruta × S/ {{ number_format($parametros['tarifa_dia'], 2) }}</code> +
    <code>Bono = litros × S/ {{ number_format($parametros['bono_litro'], 3) }}</code> = <strong>Bruto</strong> ·
    <code>Descuentos = pensión {{ rtrim(rtrim(number_format($parametros['pension_pct'], 2), '0'), '.') }} % + adelantos</code> ·
    <strong>Neto = Bruto − Descuentos</strong>.
    Km: GPS del celular → ruta trazada (ida y vuelta) → {{ $parametros['km_default'] }} km estimados. El combustible (S/ {{ number_format($parametros['combustible'], 2) }}/km) lo paga la planta y entra al costo real por litro.
    <a href="{{ route('admin.configuracion.index') }}">Cambiar tarifas</a>
</div>

<div class="pl-kpis">
    <div class="pl-kpi" style="--c:#1f6feb"><div class="lbl">Días trabajados</div><div class="val">{{ $totales['dias'] }}</div><div class="sub">{{ $filas->filter(fn ($f) => $f->datos['dias_trabajados'] > 0)->count() }} choferes con ruta</div></div>
    <div class="pl-kpi" style="--c:#0ea5e9"><div class="lbl">Litros recolectados</div><div class="val">{{ number_format($totales['litros'], 0) }}</div><div class="sub">L en el periodo</div></div>
    <div class="pl-kpi" style="--c:#8b5cf6"><div class="lbl">Km recorridos</div><div class="val">{{ number_format($totales['km'], 0) }}</div><div class="sub">combustible {{ $soles($totales['combustible']) }}</div></div>
    <div class="pl-kpi" style="--c:#f59e0b"><div class="lbl">Total bruto</div><div class="val">{{ $soles($totales['bruto']) }}</div><div class="sub">base + bonos</div></div>
    <div class="pl-kpi" style="--c:#ef4444"><div class="lbl">Descuentos</div><div class="val">{{ $soles($totales['descuentos']) }}</div><div class="sub">pensión y adelantos</div></div>
    <div class="pl-kpi" style="--c:#16a34a"><div class="lbl">Neto a pagar</div><div class="val">{{ $soles($totales['neto']) }}</div><div class="sub">{{ $totales['pagadas'] }} pagadas · {{ $totales['borradores'] }} por pagar</div></div>
</div>

<div class="card" style="margin-bottom:18px">
    <div class="card-header"><h3>👷 Choferes del periodo</h3></div>
    <div class="table-wrap">
    <table class="pl-tabla" style="width:100%">
        <thead><tr>
            <th>Chofer</th><th>Ruta</th><th class="num">Días</th><th class="num">Litros</th><th class="num">Km</th>
            <th class="num">Base</th><th class="num">Bono</th><th class="num">Bruto</th><th class="num">Desc.</th><th class="num">Neto</th>
            <th>Estado</th><th>Acciones</th>
        </tr></thead>
        <tbody>
        @foreach($filas->filter(fn ($f) => $f->planilla || $f->datos['dias_trabajados'] > 0) as $f)
            @php $d = $f->datos; $p = $f->planilla; @endphp
            <tr>
                <td><strong>{{ $f->chofer->name }}</strong><br><small style="color:var(--text-muted)">{{ $f->chofer->email }}</small></td>
                <td>{{ $d['ruta'] ?? '—' }}</td>
                <td class="num">{{ $d['dias_trabajados'] }}</td>
                <td class="num">{{ number_format($d['litros_recolectados'], 0) }}</td>
                <td class="num">{{ number_format($d['km_recorridos'], 1) }}</td>
                <td class="num">{{ $soles($d['monto_base']) }}</td>
                <td class="num">{{ $soles($d['monto_bono']) }}</td>
                <td class="num">{{ $soles($d['total_bruto']) }}</td>
                <td class="num" title="{{ $d['detalle_descuentos'] }}">{{ $soles($d['descuentos']) }}</td>
                <td class="num"><strong>{{ $soles($d['total_neto']) }}</strong></td>
                <td>
                    @if(! $p)<span class="badge badge-borrador">Vista previa</span>
                    @elseif($p->estaPagada())<span class="badge badge-pagada">✓ Pagada</span><br><small>{{ $p->pagada_at->format('d/m H:i') }} · {{ \App\Models\PlanillaChofer::METODOS[$p->metodo_pago] ?? $p->metodo_pago }}</small>
                    @else<span class="badge badge-pendiente">Por pagar</span>@endif
                </td>
                <td>
                    @if($p)
                    <div class="pl-acciones">
                        @unless($p->estaPagada())
                        <form method="POST" action="{{ route('admin.planilla.adelantos', $p) }}" class="pl-acc">
                            @csrf
                            <a class="btn btn-ghost btn-sm" href="{{ route('admin.planilla.boleta', $p) }}">🧾 Boleta</a>
                            <input type="number" step="0.01" min="0" name="adelantos" value="{{ $p->adelantos }}" style="width:70px" placeholder="Adelanto" title="Adelantos (S/)">
                            <button class="btn btn-ghost btn-sm" title="Guardar adelanto">💾</button>
                        </form>
                        <form method="POST" action="{{ route('admin.planilla.pagar', $p) }}" class="pl-acc">
                            @csrf
                            <select name="metodo_pago" style="width:105px">@foreach(\App\Models\PlanillaChofer::METODOS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                            <input name="referencia_pago" placeholder="N.° op." style="width:64px">
                            <button class="btn btn-primary btn-sm" title="Confirmar pago" onclick="return confirm('¿Confirmar pago de {{ $soles($d['total_neto']) }} a {{ $f->chofer->name }}?')">💵</button>
                        </form>
                        @else
                        <a class="btn btn-ghost btn-sm" href="{{ route('admin.planilla.boleta', $p) }}" style="align-self:flex-start">🧾 Descargar boleta</a>
                        @endunless
                    </div>
                    @else
                    <small style="color:var(--text-muted)">Genera la planilla</small>
                    @endif
                </td>
            </tr>
            @if(! empty($d['detalle_dias']))
            <tr><td colspan="12" style="padding-top:0">
                <details><summary style="cursor:pointer;font-size:.78rem;color:var(--brand-light)">Ver {{ count($d['detalle_dias']) }} día(s)</summary>
                <div class="pl-dias">
                    @foreach($d['detalle_dias'] as $dia)
                    <div class="pl-dia"><strong>{{ \Carbon\Carbon::parse($dia['fecha'])->locale('es')->isoFormat('ddd D/MM') }}</strong><br>
                        {{ $dia['acopios'] }} entregas · {{ number_format($dia['litros'], 0) }} L<br>
                        {{ number_format($dia['km'], 1) }} km <span style="color:var(--text-muted)">{{ $fuentes[$dia['fuente_km']] ?? '' }}</span></div>
                    @endforeach
                </div></details>
            </td></tr>
            @endif
        @endforeach
        </tbody>
    </table>
    </div>
    @php $sinDias = $filas->filter(fn ($f) => ! $f->planilla && $f->datos['dias_trabajados'] === 0); @endphp
    @if($sinDias->isNotEmpty())
    <div style="padding:10px 18px;font-size:.8rem;color:var(--text-muted)">Sin días con ruta en el periodo: {{ $sinDias->map(fn ($f) => $f->chofer->name)->join(', ') }}.</div>
    @endif
</div>

<div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px">
    <div>@include('admin.costos._costo_leche', ['costoLeche' => $costoLeche])</div>
    <div class="card">
        <div class="card-header"><h3>🧾 Últimos pagos</h3></div>
        <div class="card-body" style="padding:12px 18px">
            @forelse($historial as $h)
            <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-soft);font-size:.84rem">
                <span>{{ $h->chofer->name }} <small style="color:var(--text-muted)">{{ $h->periodo_inicio->format('d/m') }}–{{ $h->periodo_fin->format('d/m') }}</small></span>
                <span><strong>{{ $soles($h->total_neto) }}</strong> <a href="{{ route('admin.planilla.boleta', $h) }}">🧾</a></span>
            </div>
            @empty
            <p style="color:var(--text-muted);font-size:.85rem">Aún no hay pagos confirmados.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
