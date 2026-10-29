@extends('layouts.admin')

@section('title', 'Recorridos')
@section('page-title', '🛰️ Repetición de recorridos')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    .rec-grid { display: grid; grid-template-columns: 300px 1fr; gap: 18px; }
    #mapa-rec { height: 520px; border-radius: var(--radius); border: 1px solid var(--border); }
    .chofer-km { display: flex; justify-content: space-between; padding: 10px 12px; border-radius: 12px; cursor: pointer; font-size: .86rem; text-decoration: none; color: inherit; }
    .chofer-km:hover { background: #f3f7ff; }
    .chofer-km.activo { background: var(--grad); color: #fff; }
    .controles { display: flex; align-items: center; gap: 12px; margin-top: 12px; }
    .controles input[type=range] { flex: 1; accent-color: #1f6feb; }
    .reloj { font-weight: 800; font-size: 1.1rem; min-width: 70px; }
    @media (max-width: 1000px) { .rec-grid { grid-template-columns: 1fr; } }
</style>

<form method="GET" style="display:flex; gap:10px; align-items:end; margin-bottom:16px; flex-wrap:wrap;">
    <div class="form-group"><label>Fecha</label><input type="date" name="fecha" value="{{ $fecha }}" max="{{ now()->toDateString() }}"></div>
    <button class="btn btn-primary">Ver día</button>
</form>

<div class="rec-grid">
    <div class="card" style="margin:0">
        <div class="card-header"><h3>📏 Km por chofer</h3></div>
        <div class="card-body" style="padding:10px">
            @forelse($kmPorChofer as $k)
                <a class="chofer-km {{ $k['chofer_id'] == $choferId ? 'activo' : '' }}"
                   href="?fecha={{ $fecha }}&chofer={{ $k['chofer_id'] }}">
                    <span><b>{{ $k['chofer'] }}</b><br><small>{{ $k['inicio'] }}–{{ $k['fin'] }} · {{ $k['puntos'] }} puntos</small></span>
                    <b>{{ number_format($k['km'], 1) }} km</b>
                </a>
            @empty
                <p style="color:var(--text-muted); font-size:.86rem; padding:8px">Ningún recorrido registrado este día.</p>
            @endforelse
            @if($kmPorChofer->isNotEmpty())
                <div style="border-top:1px solid var(--border); margin-top:8px; padding:10px 12px; font-size:.86rem; display:flex; justify-content:space-between">
                    <span>Total flota</span><b>{{ number_format($kmPorChofer->sum('km'), 1) }} km</b>
                </div>
            @endif
        </div>
    </div>

    <div class="card" style="margin:0">
        <div class="card-body">
            <div id="mapa-rec"></div>
            <div class="controles">
                <button type="button" class="btn btn-primary btn-sm" id="play">▶ Reproducir</button>
                <span class="reloj" id="reloj">--:--</span>
                <input type="range" id="linea" min="0" max="0" value="0">
                <select id="velocidad" class="btn-sm" style="width:auto">
                    <option value="20">x20</option><option value="60" selected>x60</option><option value="240">x240</option>
                </select>
            </div>
            <p id="info" style="color:var(--text-muted); font-size:.82rem; margin-top:8px"></p>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const CHOFER = {{ $choferId }};
    const FECHA = @json($fecha);
    const PLANTA = [{{ $planta['lat'] }}, {{ $planta['lon'] }}];
    const mapa = L.map('mapa-rec').setView(PLANTA, 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(mapa);
    L.marker(PLANTA, { title: 'Planta' }).addTo(mapa).bindTooltip('🏭 Planta');
    if (!CHOFER) return;

    const icono = L.divIcon({ className: '', html: '<div style="font-size:26px;filter:drop-shadow(0 2px 3px rgba(0,0,0,.4))">🚛</div>', iconSize: [30, 30], iconAnchor: [15, 15] });
    let puntos = [], camion, recorridoHecho, timer = null;
    const linea = document.getElementById('linea'), reloj = document.getElementById('reloj');
    const hora = t => new Date(t).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });

    fetch(`{{ url('admin/operaciones/recorrido') }}/${CHOFER}/datos?fecha=${FECHA}`, { headers: { Accept: 'application/json' } })
        .then(r => r.json())
        .then(d => {
            puntos = d.puntos;
            document.getElementById('info').textContent = `${d.chofer}: ${d.km} km · ${puntos.length} puntos · ${d.acopios.length} acopios`;
            if (!puntos.length) return;
            const todo = puntos.map(p => [p.lat, p.lon]);
            L.polyline(todo, { color: '#94a3b8', weight: 4, dashArray: '6 8' }).addTo(mapa);
            recorridoHecho = L.polyline([], { color: '#1f6feb', weight: 6 }).addTo(mapa);
            camion = L.marker(todo[0], { icon: icono }).addTo(mapa);
            mapa.fitBounds(L.latLngBounds(todo).pad(0.15));
            d.acopios.filter(a => a.lat).forEach(a => {
                const color = a.calidad === 'RECHAZADO' ? '#dc2626' : a.calidad === 'OBSERVADO' ? '#f2a541' : '#16a34a';
                L.circleMarker([a.lat, a.lon], { radius: 8, color: '#fff', weight: 2, fillColor: color, fillOpacity: 1 })
                 .addTo(mapa).bindTooltip(`${a.ganadero} · ${a.litros} L · ${hora(a.t)}`);
            });
            linea.max = puntos.length - 1;
            mover(0);
        });

    function mover(i) {
        if (!puntos.length) return;
        linea.value = i;
        camion.setLatLng([puntos[i].lat, puntos[i].lon]);
        recorridoHecho.setLatLngs(puntos.slice(0, i + 1).map(p => [p.lat, p.lon]));
        reloj.textContent = hora(puntos[i].t);
    }
    linea.addEventListener('input', () => mover(+linea.value));

    document.getElementById('play').addEventListener('click', function () {
        if (timer) { clearInterval(timer); timer = null; this.textContent = '▶ Reproducir'; return; }
        if (+linea.value >= puntos.length - 1) mover(0);
        this.textContent = '⏸ Pausa';
        timer = setInterval(() => {
            const i = +linea.value;
            if (i >= puntos.length - 1) { clearInterval(timer); timer = null; document.getElementById('play').textContent = '▶ Reproducir'; return; }
            // Avanza en "tiempo real acelerado": salta los puntos que caen dentro del intervalo
            const objetivo = puntos[i].t + 200 * +document.getElementById('velocidad').value;
            let j = i + 1;
            while (j < puntos.length - 1 && puntos[j].t < objetivo) j++;
            mover(j);
        }, 200);
    });
})();
</script>
@endsection
