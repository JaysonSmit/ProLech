{{-- Tarjeta del costo real por litro de leche puesto en planta --}}
@php
    $c = $costoLeche ?? \App\Services\CostoLecheService::actual();
    $s = fn ($v, $dec = 2) => 'S/ ' . number_format((float) $v, $dec);
@endphp
<div class="card" style="overflow:hidden">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:10px">
        <h3>🥛 Costo real por litro puesto en planta</h3>
        @if(auth()->user()->isAdmin() && Route::has('admin.costo-leche.recalcular'))
        <form method="POST" action="{{ route('admin.costo-leche.recalcular') }}">@csrf
            <button class="btn btn-ghost btn-sm" title="Recalcular y aplicar al insumo Leche Cruda">↻ Recalcular</button>
        </form>
        @endif
    </div>
    <div class="card-body" style="padding:16px 20px">
    @if($c)
        @php
            $partes = [
                ['Precio al ganadero', $c['base'], '#1f6feb', 'promedio de ' . $c['fuente_base']],
                ['Logística', $c['logistica'], '#f59e0b', $s($c['combustible']) . ' combustible + ' . $s($c['pago_choferes']) . ' choferes'],
                ['Gastos generales', $c['gastos'], '#8b5cf6', rtrim(rtrim(number_format($c['gastos_pct'], 2), '0'), '.') . ' % de planta'],
            ];
        @endphp
        <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-bottom:12px">
            <span style="font-size:2rem;font-weight:800;color:var(--text)">{{ $s($c['costo_real']) }}</span>
            <span style="color:var(--text-muted);font-size:.85rem">por litro · {{ number_format($c['litros'], 0) }} L del {{ \Carbon\Carbon::parse($c['desde'])->format('d/m') }} al {{ \Carbon\Carbon::parse($c['hasta'])->format('d/m') }}</span>
        </div>
        <div style="display:flex;height:12px;border-radius:8px;overflow:hidden;margin-bottom:12px">
            @foreach($partes as [$n, $v, $col])
                <span style="width:{{ $c['costo_real'] > 0 ? round($v / $c['costo_real'] * 100, 1) : 0 }}%;background:{{ $col }}"></span>
            @endforeach
        </div>
        @foreach($partes as [$n, $v, $col, $det])
        <div style="display:flex;justify-content:space-between;gap:10px;font-size:.85rem;padding:5px 0">
            <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:{{ $col }};margin-right:6px"></span>{{ $n }} <small style="color:var(--text-muted)">· {{ $det }}</small></span>
            <strong>{{ $s($v) }}</strong>
        </div>
        @endforeach
        <div style="margin-top:10px;padding:9px 12px;background:var(--bg);border-radius:10px;font-size:.78rem;color:var(--text-muted)">
            {{ $s($c['base']) }} base + {{ $s($c['logistica']) }} logística + {{ $s($c['gastos']) }} gastos = <strong style="color:var(--text)">{{ $s($c['costo_real']) }}/L real</strong>.
            Las recetas usan este costo para la «Leche Cruda» y el precio sugerido.
        </div>
    @else
        <p style="color:var(--text-muted);font-size:.86rem;margin:0">Sin acopios en los últimos 30 días: las recetas usan el costo guardado del insumo «Leche Cruda».</p>
    @endif
    </div>
</div>
