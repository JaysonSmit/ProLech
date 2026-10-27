{{-- Dashboard de ventas: acciones rápidas, indicadores, gráfico, carruseles y rankings --}}
@php
    $d = $dashboard;
    $soles = fn ($v) => 'S/ ' . number_format((float) $v, 2);
    $esAdmin = auth()->user()->isAdmin();
@endphp
<style>
    .vd-acciones { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:18px; }
    .vd-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:12px; font-weight:700; font-size:.86rem;
              text-decoration:none; border:1px solid var(--border); background:#fff; color:var(--text); cursor:pointer; transition:transform .12s, box-shadow .12s; }
    .vd-btn:hover { transform:translateY(-1px); box-shadow:var(--shadow-md); }
    .vd-btn.primario { background:var(--grad); color:#fff; border:0; }
    .vd-btn .chip { background:#fee2e2; color:#b91c1c; border-radius:10px; padding:1px 8px; font-size:.72rem; }
    .vd-kpis { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
    .vd-kpi { background:#fff; border:1px solid var(--border); border-radius:18px; padding:16px 18px; position:relative; overflow:hidden; }
    .vd-kpi::before { content:""; position:absolute; inset:0 0 auto 0; height:4px; background:var(--c, var(--brand)); }
    .vd-kpi .lbl { font-size:.7rem; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--text-muted); }
    .vd-kpi .val { font-size:1.65rem; font-weight:800; color:var(--text); margin-top:6px; line-height:1.1; }
    .vd-kpi .sub { font-size:.76rem; color:var(--text-muted); margin-top:4px; }
    .vd-kpi .ico { position:absolute; right:14px; top:14px; font-size:1.5rem; opacity:.9; }
    .vd-sube { color:#15803d; font-weight:700; } .vd-baja { color:#b91c1c; font-weight:700; }
    .vd-fila { display:grid; grid-template-columns:2fr 1fr; gap:16px; margin-bottom:18px; }
    .vd-panel { background:#fff; border:1px solid var(--border); border-radius:18px; padding:16px 18px; }
    .vd-panel h3 { font-size:.98rem; margin:0 0 12px; display:flex; justify-content:space-between; align-items:center; gap:8px; }
    .vd-panel h3 small { font-weight:500; color:var(--text-muted); font-size:.75rem; }
    .vd-carrusel-wrap { position:relative; }
    .vd-carrusel { display:flex; gap:14px; overflow-x:auto; scroll-snap-type:x mandatory; scroll-behavior:smooth; padding:4px 2px 10px; scrollbar-width:thin; }
    .vd-card { flex:0 0 210px; scroll-snap-align:start; border:1px solid var(--border); border-radius:16px; overflow:hidden; background:#fff; transition:transform .15s, box-shadow .15s; }
    .vd-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); }
    .vd-card img { width:100%; height:120px; object-fit:cover; background:var(--bg); display:block; }
    .vd-card .cuerpo { padding:10px 12px 12px; }
    .vd-card .nom { font-weight:700; font-size:.88rem; line-height:1.25; min-height:2.2em; }
    .vd-card .meta { display:flex; justify-content:space-between; font-size:.76rem; color:var(--text-muted); margin-top:6px; }
    .vd-rank { position:absolute; margin:8px; background:rgba(15,23,42,.75); color:#fff; font-weight:800; border-radius:10px; padding:2px 9px; font-size:.78rem; }
    .vd-flecha { position:absolute; top:42%; z-index:2; width:34px; height:34px; border-radius:50%; border:1px solid var(--border); background:#fff;
                 box-shadow:var(--shadow-md); cursor:pointer; font-size:1rem; display:flex; align-items:center; justify-content:center; }
    .vd-flecha.izq { left:-12px; } .vd-flecha.der { right:-12px; }
    .vd-pill { display:inline-block; font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:10px; }
    .vd-lista-item { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--border-soft); font-size:.84rem; }
    .vd-lista-item:last-child { border-bottom:0; }
    .vd-barra { height:6px; border-radius:6px; background:var(--bg); overflow:hidden; margin-top:4px; }
    .vd-barra span { display:block; height:100%; background:var(--grad); border-radius:6px; }
    .vd-avatar { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; flex-shrink:0; }
    .vd-pipeline { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
    .vd-etapa { border-radius:14px; padding:14px; text-decoration:none; color:inherit; border:1px solid var(--border); transition:transform .12s; }
    .vd-etapa:hover { transform:translateY(-2px); }
    .vd-etapa .n { font-size:1.6rem; font-weight:800; }
    .vd-tabs { display:flex; gap:6px; }
    .vd-tab { border:1px solid var(--border); background:#fff; border-radius:10px; padding:5px 12px; font-size:.78rem; font-weight:700; cursor:pointer; }
    .vd-tab.sel { background:#0f2a4d; color:#fff; border-color:#0f2a4d; }
    @media (max-width: 1400px) { .vd-kpis { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width: 1100px) { .vd-fila { grid-template-columns:1fr !important; } .vd-pipeline { grid-template-columns:1fr; } }
    @media (max-width: 700px) { .vd-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>

{{-- ── Acciones rápidas ─────────────────────────────────────────────────── --}}
<div class="vd-acciones">
    <button type="button" class="vd-btn primario" onclick="vdAbrirNuevaVenta()">➕ Nueva venta</button>
    <a class="vd-btn" href="#historial-ventas">📋 Historial</a>
    <a class="vd-btn" href="{{ route('admin.ventas', ['despacho' => 'preparacion']) }}#historial-ventas">📦 Por despachar <span class="chip">{{ $despachosPendientes }}</span></a>
    @if($esAdmin)
        <a class="vd-btn" href="{{ route('admin.tienda.pedidos.index') }}">🛒 Pedidos web @if($d['pendientesWeb']->n)<span class="chip">{{ $d['pendientesWeb']->n }}</span>@endif</a>
        <a class="vd-btn" href="{{ route('admin.tienda.productos.index') }}">🏷️ Productos y precios</a>
        <a class="vd-btn" href="{{ route('admin.operaciones.exportar') }}">⬇ Exportar</a>
    @endif
    @if(auth()->user()->seccionWebPermitida('reportes'))
        <a class="vd-btn" href="{{ route('admin.reportes') }}">📊 Reportes</a>
    @endif
    <a class="vd-btn" href="{{ route('tienda.index') }}" target="_blank">🏪 Ver tienda</a>
</div>

{{-- ── Indicadores ──────────────────────────────────────────────────────── --}}
<div class="vd-kpis">
    <div class="vd-kpi" style="--c:#1f6feb">
        <span class="ico">💵</span>
        <div class="lbl">Ventas de hoy</div>
        <div class="val">{{ $soles($d['hoy']['monto']) }}</div>
        <div class="sub">{{ $d['hoy']['num'] }} venta(s) · planta {{ $soles($d['hoy']['planta']) }} · web {{ $soles($d['hoy']['web']) }}</div>
    </div>
    <div class="vd-kpi" style="--c:#16a34a">
        <span class="ico">📈</span>
        <div class="lbl">Ventas del mes</div>
        <div class="val">{{ $soles($d['mes']['monto']) }}</div>
        <div class="sub">
            @if($d['variacion'] !== null)
                <span class="{{ $d['variacion'] >= 0 ? 'vd-sube' : 'vd-baja' }}">{{ $d['variacion'] >= 0 ? '▲' : '▼' }} {{ abs($d['variacion']) }} %</span> vs. mes anterior
            @else
                Primer mes con ventas registradas
            @endif
        </div>
    </div>
    <div class="vd-kpi" style="--c:#8b5cf6">
        <span class="ico">🧾</span>
        <div class="lbl">Ticket promedio</div>
        <div class="val">{{ $soles($d['ticket']) }}</div>
        <div class="sub">{{ $d['mes']['num'] }} venta(s) este mes</div>
    </div>
    <div class="vd-kpi" style="--c:#f2a541">
        <span class="ico">🛒</span>
        <div class="lbl">Pedidos web pendientes</div>
        <div class="val">{{ $d['pendientesWeb']->n }}</div>
        <div class="sub">{{ $soles($d['pendientesWeb']->monto) }} por confirmar</div>
    </div>
    <div class="vd-kpi" style="--c:{{ $d['porVencer']->isNotEmpty() ? '#dc2626' : '#10b981' }}">
        <span class="ico">⏰</span>
        <div class="lbl">Por vencer</div>
        <div class="val">{{ $d['porVencer']->sum('en_stock') }} und.</div>
        <div class="sub">{{ $d['porVencer']->isNotEmpty() ? 'Véndelos primero' : 'Nada por vencer en 3 días' }}</div>
    </div>
    <div class="vd-kpi" style="--c:{{ $alertaStockQuesos ? '#dc2626' : '#0ea5e9' }}">
        <span class="ico">🧀</span>
        <div class="lbl">Tarifas por kg</div>
        <div class="val" style="font-size:1.15rem">{{ $soles($precios['mayorista']) }} <small style="font-size:.7rem;color:var(--text-muted)">mayorista</small></div>
        <div class="sub">Público {{ $soles($precios['publico']) }} · planta {{ number_format($stockActualQuesos, 1) }} moldes{{ $alertaStockQuesos ? ' ⚠️' : '' }}</div>
    </div>
</div>

{{-- ── Gráfico + canales ────────────────────────────────────────────────── --}}
<div class="vd-fila">
    <div class="vd-panel">
        <h3>Ventas de los últimos 30 días
            <span class="vd-tabs">
                <button type="button" class="vd-tab sel" data-vista="barras">Por día</button>
                <button type="button" class="vd-tab" data-vista="acumulado">Acumulado</button>
            </span>
        </h3>
        <div style="height:260px"><canvas id="vd-grafico"></canvas></div>
    </div>
    <div class="vd-panel">
        <h3>Canales del mes <small>{{ $soles($d['mes']['monto']) }}</small></h3>
        <div style="height:150px"><canvas id="vd-canales"></canvas></div>
        <div style="margin-top:10px">
            @php $maxDest = max(1, (float) $resumenDestino->max('total_monto')); @endphp
            @forelse($resumenDestino->take(4) as $dest)
                <div style="font-size:.8rem;margin-bottom:8px">
                    <div style="display:flex;justify-content:space-between"><span>📍 {{ $dest->destino ?: 'Planta / local' }}</span><strong>{{ $soles($dest->total_monto) }}</strong></div>
                    <div class="vd-barra"><span style="width:{{ round($dest->total_monto / $maxDest * 100) }}%"></span></div>
                </div>
            @empty
                <p style="font-size:.8rem;color:var(--text-muted)">Aún no hay despachos por destino.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ── Carrusel: más vendidos ───────────────────────────────────────────── --}}
<div class="vd-panel" style="margin-bottom:18px">
    <h3>🏆 Más vendidos <small>últimos 30 días · planta + tienda web</small></h3>
    @if($d['topProductos']->isEmpty())
        <p style="font-size:.85rem;color:var(--text-muted)">Cuando registres ventas con productos aparecerán aquí.</p>
    @else
    <div class="vd-carrusel-wrap">
        <button type="button" class="vd-flecha izq" onclick="vdMover('vd-top', -1)" aria-label="Anterior">‹</button>
        <div class="vd-carrusel" id="vd-top">
            @foreach($d['topProductos'] as $i => $t)
            <div class="vd-card">
                <span class="vd-rank">#{{ $i + 1 }}</span>
                <img src="{{ $t['producto']->imagen_publica }}" alt="{{ $t['producto']->nombre }}" loading="lazy">
                <div class="cuerpo">
                    <div class="nom">{{ $t['producto']->nombre }}</div>
                    <div class="meta"><span>{{ rtrim(rtrim(number_format($t['unidades'], 2), '0'), '.') }} vendidas</span><strong style="color:var(--green)">{{ $soles($t['monto']) }}</strong></div>
                    <div class="meta">
                        <span>{{ $soles($t['producto']->precio_venta) }}</span>
                        <span class="vd-pill" style="background:{{ $t['producto']->stock_disponible <= 5 ? '#fee2e2' : '#dcfce7' }};color:{{ $t['producto']->stock_disponible <= 5 ? '#b91c1c' : '#15803d' }}">stock {{ $t['producto']->stock_disponible }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <button type="button" class="vd-flecha der" onclick="vdMover('vd-top', 1)" aria-label="Siguiente">›</button>
    </div>
    @endif
</div>

{{-- ── Carruseles: por vencer y stock bajo ──────────────────────────────── --}}
<div class="vd-fila" style="grid-template-columns:1fr 1fr">
    <div class="vd-panel">
        <h3>⏰ Ofrecer antes que venzan <small>lotes de la tienda</small></h3>
        @if($d['porVencer']->isEmpty())
            <p style="font-size:.85rem;color:var(--text-muted)">✅ No hay productos por vencer en los próximos 3 días.</p>
        @else
        <div class="vd-carrusel" id="vd-vencer">
            @foreach($d['porVencer'] as $f)
            <div class="vd-card" style="flex-basis:180px">
                <img src="{{ $f['producto']->imagen_publica }}" alt="{{ $f['producto']->nombre }}" loading="lazy" style="height:90px">
                <div class="cuerpo">
                    <div class="nom" style="min-height:auto">{{ $f['producto']->nombre }}</div>
                    <div class="meta"><span>{{ $f['en_stock'] }} und.</span><span>{{ $f['lote']->codigo_lote }}</span></div>
                    <span class="vd-pill" style="margin-top:6px;background:{{ $f['estado'] === 'vencido' ? '#fee2e2' : '#fef3c7' }};color:{{ $f['estado'] === 'vencido' ? '#b91c1c' : '#b45309' }}">
                        {{ $f['estado'] === 'vencido' ? 'Vencido' : ($f['dias'] === 0 ? 'Vence hoy' : 'Vence en ' . $f['dias'] . ' días') }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    <div class="vd-panel">
        <h3>📉 Poco stock en la tienda <small>5 unidades o menos</small></h3>
        @if($d['stockBajo']->isEmpty())
            <p style="font-size:.85rem;color:var(--text-muted)">✅ Todos los productos tienen stock suficiente.</p>
        @else
        <div class="vd-carrusel" id="vd-stock">
            @foreach($d['stockBajo'] as $p)
            <div class="vd-card" style="flex-basis:180px">
                <img src="{{ $p->imagen_publica }}" alt="{{ $p->nombre }}" loading="lazy" style="height:90px">
                <div class="cuerpo">
                    <div class="nom" style="min-height:auto">{{ $p->nombre }}</div>
                    <div class="meta"><span>{{ $soles($p->precio_venta) }}</span>
                        <span class="vd-pill" style="background:#fee2e2;color:#b91c1c">{{ $p->stock_disponible == 0 ? 'Agotado' : $p->stock_disponible . ' und.' }}</span></div>
                    @if(auth()->user()->seccionWebPermitida('produccion'))
                        <a href="{{ route('admin.produccion.lotes.create') }}" style="font-size:.74rem;font-weight:700;display:inline-block;margin-top:6px">🏭 Producir más →</a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ── Clientes, últimas ventas y despachos ─────────────────────────────── --}}
<div class="vd-fila" style="grid-template-columns:1fr 1fr 1fr">
    <div class="vd-panel">
        <h3>👑 Mejores clientes <small>este mes</small></h3>
        @php $maxCli = max(1, (float) $d['topClientes']->max('monto')); $colores = ['#1f6feb','#16a34a','#8b5cf6','#f2a541','#ec4899']; @endphp
        @forelse($d['topClientes'] as $i => $c)
            <div class="vd-lista-item">
                <div class="vd-avatar" style="background:{{ $colores[$i % 5] }}22;color:{{ $colores[$i % 5] }}">{{ mb_strtoupper(mb_substr($c['nombre'], 0, 1)) }}</div>
                <div style="flex:1;min-width:0">
                    <div style="display:flex;justify-content:space-between;gap:6px"><strong style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['nombre'] }}</strong><span>{{ $soles($c['monto']) }}</span></div>
                    <div class="vd-barra"><span style="width:{{ round($c['monto'] / $maxCli * 100) }}%"></span></div>
                    <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px">{{ $c['n'] }} compra(s)</div>
                </div>
            </div>
        @empty
            <p style="font-size:.85rem;color:var(--text-muted)">Sin ventas este mes todavía.</p>
        @endforelse
    </div>
    <div class="vd-panel">
        <h3>🕒 Últimas ventas</h3>
        @forelse($d['ultimas'] as $u)
            <div class="vd-lista-item">
                <div class="vd-avatar" style="background:{{ $u['canal'] === 'web' ? '#fef3c7' : '#dbeafe' }}">{{ $u['canal'] === 'web' ? '🛒' : '🏭' }}</div>
                <div style="flex:1;min-width:0">
                    <div style="display:flex;justify-content:space-between;gap:6px"><strong style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $u['cliente'] }}</strong><strong style="color:var(--green)">{{ $soles($u['monto']) }}</strong></div>
                    <div style="font-size:.72rem;color:var(--text-muted)">{{ $u['detalle'] }} · {{ $u['cuando']->locale('es')->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            <p style="font-size:.85rem;color:var(--text-muted)">Aún no hay ventas.</p>
        @endforelse
    </div>
    <div class="vd-panel">
        <h3>🚚 Despachos</h3>
        <div class="vd-pipeline" style="grid-template-columns:1fr">
            <a class="vd-etapa" style="background:#fffbeb" href="{{ route('admin.ventas', ['despacho' => 'preparacion']) }}#historial-ventas">
                <div style="font-size:.78rem;font-weight:700;color:#b45309">📦 En preparación</div><div class="n" style="color:#b45309">{{ $despachosPendientes }}</div>
            </a>
            <a class="vd-etapa" style="background:#eff6ff" href="{{ route('admin.ventas', ['despacho' => 'en_camino']) }}#historial-ventas">
                <div style="font-size:.78rem;font-weight:700;color:#1d4ed8">🚚 En camino</div><div class="n" style="color:#1d4ed8">{{ $despachosEnCamino }}</div>
            </a>
            <a class="vd-etapa" style="background:#f0fdf4" href="{{ route('admin.ventas', ['despacho' => 'entregado']) }}#historial-ventas">
                <div style="font-size:.78rem;font-weight:700;color:#15803d">✅ Entregados</div><div class="n" style="color:#15803d">{{ $despachosEntregados }}</div>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const SERIE = @json($d['serie']);
    const MES = @json($d['mes']);
    const soles = v => 'S/ ' + Number(v).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    window.vdMover = (id, dir) => {
        const c = document.getElementById(id);
        if (c) c.scrollBy({ left: dir * (c.clientWidth * 0.8), behavior: 'smooth' });
    };
    // El carrusel de más vendidos avanza solo cada 4 s (se detiene con el mouse encima)
    const top = document.getElementById('vd-top');
    if (top) {
        let pausa = false;
        top.addEventListener('mouseenter', () => pausa = true);
        top.addEventListener('mouseleave', () => pausa = false);
        setInterval(() => {
            if (pausa) return;
            const fin = top.scrollLeft + top.clientWidth >= top.scrollWidth - 5;
            top.scrollTo({ left: fin ? 0 : top.scrollLeft + 224, behavior: 'smooth' });
        }, 4000);
    }

    window.vdAbrirNuevaVenta = () => {
        const f = document.getElementById('nueva-venta');
        if (!f) return;
        f.open = true;
        f.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    if (typeof Chart === 'undefined') return;
    const acumulado = arr => arr.reduce((a, v, i) => (a.push((a[i - 1] || 0) + v), a), []);
    const grafico = new Chart(document.getElementById('vd-grafico'), {
        type: 'bar',
        data: {
            labels: SERIE.etiquetas,
            datasets: [
                { label: 'Planta', data: SERIE.planta, backgroundColor: '#1f6feb', borderRadius: 6, stack: 'v' },
                { label: 'Tienda web', data: SERIE.web, backgroundColor: '#f2a541', borderRadius: 6, stack: 'v' },
            ],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => `${c.dataset.label}: ${soles(c.parsed.y)}` } } },
            scales: { x: { stacked: true, grid: { display: false }, ticks: { maxTicksLimit: 10 } }, y: { stacked: true, ticks: { callback: v => 'S/ ' + v } } },
        },
    });
    document.querySelectorAll('.vd-tab').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('.vd-tab').forEach(x => x.classList.toggle('sel', x === b));
        const ac = b.dataset.vista === 'acumulado';
        grafico.config.type = ac ? 'line' : 'bar';
        grafico.data.datasets[0].data = ac ? acumulado(SERIE.planta) : SERIE.planta;
        grafico.data.datasets[1].data = ac ? acumulado(SERIE.web) : SERIE.web;
        grafico.data.datasets.forEach(ds => { ds.fill = ac; ds.tension = .3; ds.borderColor = ds.backgroundColor; });
        grafico.update();
    }));

    new Chart(document.getElementById('vd-canales'), {
        type: 'doughnut',
        data: { labels: ['Planta', 'Tienda web'], datasets: [{ data: [MES.planta, MES.web], backgroundColor: ['#1f6feb', '#f2a541'], borderWidth: 0 }] },
        options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'right' }, tooltip: { callbacks: { label: c => `${c.label}: ${soles(c.parsed)}` } } } },
    });
})();
</script>
