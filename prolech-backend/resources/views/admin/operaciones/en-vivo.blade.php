@extends('layouts.admin')

@section('title', 'Panel en vivo')
@section('page-title', '📡 Panel en vivo')

@section('content')
<style>
    .vivo-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 22px; }
    .kpi { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 20px; box-shadow: var(--shadow); }
    .kpi .lbl { font-size: .72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .6px; font-weight: 700; }
    .kpi .val { font-size: 2rem; font-weight: 800; letter-spacing: -1px; margin-top: 6px; color: var(--brand-dark, #0b1f44); }
    .kpi.destacado { background: linear-gradient(135deg, #0b1f44, #163a7a); color: #fff; border: none; }
    .kpi.destacado .lbl { color: #9ec5ff; } .kpi.destacado .val { color: #fff; }
    .cols { display: grid; grid-template-columns: 1.3fr 1fr; gap: 18px; }
    .barra { display: flex; align-items: center; gap: 10px; margin: 10px 0; font-size: .85rem; }
    .barra .nom { width: 130px; font-weight: 700; }
    .barra .pista { flex: 1; height: 14px; background: #eef2f8; border-radius: 99px; overflow: hidden; }
    .barra .relleno { height: 100%; background: var(--grad); border-radius: 99px; transition: width .6s; }
    .barra .num { width: 110px; text-align: right; color: var(--text-2); font-weight: 600; }
    .alerta { display: flex; justify-content: space-between; gap: 10px; padding: 10px 12px; border-radius: 12px; margin-bottom: 8px; font-size: .84rem; }
    .alerta.RECHAZADO { background: #fee2e2; color: #991b1b; } .alerta.OBSERVADO { background: #fef3c7; color: #92400e; }
    .vivo-pulso { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #22c55e; margin-right: 6px; animation: pulse-dot 2s infinite; }
    @media (max-width: 1100px) { .vivo-grid { grid-template-columns: repeat(2, 1fr); } .cols { grid-template-columns: 1fr; } }
</style>

<p style="color:var(--text-muted); font-size:.85rem; margin-bottom:16px;">
    <span class="vivo-pulso"></span>Se actualiza solo cada 15 s · última lectura <b id="hora">{{ $datos['actualizado'] }}</b>
</p>

<div class="vivo-grid">
    <div class="kpi destacado"><div class="lbl">Litros hoy</div><div class="val" id="k-litros">{{ number_format($datos['litros_hoy'], 1) }}</div></div>
    <div class="kpi"><div class="lbl">Entregas</div><div class="val" id="k-entregas">{{ $datos['entregas_hoy'] }}</div></div>
    <div class="kpi"><div class="lbl">Calidad aprobada</div><div class="val" id="k-aprob">{{ $datos['pct_aprobado'] !== null ? $datos['pct_aprobado'].' %' : '—' }}</div></div>
    <div class="kpi"><div class="lbl">Choferes en ruta</div><div class="val" id="k-ruta">{{ count($datos['en_ruta']) }}</div></div>
</div>

<div class="cols">
    <div class="card">
        <div class="card-header"><h3>🗺️ Litros por zona</h3></div>
        <div class="card-body" id="zonas"></div>
    </div>
    <div>
        <div class="card">
            <div class="card-header"><h3>🚛 En ruta ahora</h3></div>
            <div class="card-body" id="ruta"></div>
        </div>
        <div class="card">
            <div class="card-header"><h3>⚠️ Alertas de calidad de hoy</h3></div>
            <div class="card-body" id="alertas"></div>
        </div>
    </div>
</div>

<script>
(function () {
    const URL_DATOS = @json(route('admin.operaciones.en-vivo.datos'));
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const num = (n, d = 1) => Number(n).toLocaleString('es-PE', { minimumFractionDigits: d, maximumFractionDigits: d });

    function pintar(d) {
        document.getElementById('hora').textContent = d.actualizado;
        document.getElementById('k-litros').textContent = num(d.litros_hoy);
        document.getElementById('k-entregas').textContent = d.entregas_hoy;
        document.getElementById('k-aprob').textContent = d.pct_aprobado === null ? '—' : d.pct_aprobado + ' %';
        document.getElementById('k-ruta').textContent = d.en_ruta.length;

        const max = Math.max(1, ...d.por_zona.map(z => z.litros));
        document.getElementById('zonas').innerHTML = d.por_zona.length ? d.por_zona.map(z => `
            <div class="barra">
                <span class="nom">${esc(z.zona)}<br><small style="color:var(--text-muted);font-weight:500">${esc(z.chofer || 'sin chofer')}</small></span>
                <span class="pista"><span class="relleno" style="width:${(z.litros / max * 100).toFixed(1)}%"></span></span>
                <span class="num">${num(z.litros)} L · ${z.entregas}</span>
            </div>`).join('') : '<p style="color:var(--text-muted)">Sin zonas configuradas.</p>';

        document.getElementById('ruta').innerHTML = d.en_ruta.length ? d.en_ruta.map(c => `
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-soft);font-size:.86rem">
                <span>🚛 <b>${esc(c.chofer)}</b></span>
                <span style="color:var(--text-muted)">${num(c.km_hoy)} km · hace ${c.minutos} min</span>
            </div>`).join('') : '<p style="color:var(--text-muted);font-size:.86rem">Ningún chofer reportando en los últimos 30 min.</p>';

        document.getElementById('alertas').innerHTML = d.alertas.length ? d.alertas.map(a => `
            <div class="alerta ${a.calidad}">
                <span><b>${esc(a.ganadero)}</b> · ${num(a.litros)} L · agua ${num(a.agua)} %</span>
                <span>${a.calidad === 'RECHAZADO' ? '❌' : '⚠️'} ${esc(a.hora || '')}</span>
            </div>`).join('') : '<p style="color:var(--text-muted);font-size:.86rem">✅ Sin observaciones hoy.</p>';
    }

    pintar(@json($datos));
    setInterval(() => fetch(URL_DATOS, { headers: { Accept: 'application/json' } })
        .then(r => r.json()).then(pintar).catch(() => {}), 15000);
})();
</script>
@endsection
