@extends('layouts.admin')

@section('title', 'Trazar rutas')
@section('page-title', '✏️ Trazar la ruta de cada zona')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    .tr-zonas { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .tr-zona { border: 1px solid var(--border); background: #fff; border-radius: 999px; padding: 7px 14px; font-size: .85rem; cursor: pointer; display: inline-flex; gap: 8px; align-items: center; }
    .tr-zona.sel { background: #0f2a4d; color: #fff; border-color: #0f2a4d; }
    .tr-zona small { opacity: .75; }
    .tr-grid { display: grid; grid-template-columns: 330px 1fr; gap: 18px; }
    #mapa-trazo { height: 620px; border-radius: var(--radius); border: 1px solid var(--border); cursor: crosshair; }
    .punto { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
    .tr-botones { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 10px 0; }
    .tr-botones button { padding: 9px 10px; border-radius: 10px; border: 1px solid var(--border); background: #fff; cursor: pointer; font-size: .84rem; }
    .tr-botones button:disabled { opacity: .45; cursor: default; }
    .tr-guardar { grid-column: 1 / -1; background: #1f6feb !important; color: #fff; border: 0 !important; font-weight: 600; font-size: .9rem !important; }
    .tr-km { font-size: 1.6rem; font-weight: 700; color: #0f2a4d; }
    .tr-estado { font-size: .82rem; padding: 8px 10px; border-radius: 10px; margin-top: 8px; display: none; }
    .tr-parada { display: flex; gap: 10px; align-items: center; padding: 7px 4px; border-bottom: 1px solid #eef2f7; font-size: .84rem; }
    .tr-num { width: 24px; height: 24px; border-radius: 50%; background: #1f6feb; color: #fff; font-size: .75rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .tr-aviso { color: #b45309; font-size: .76rem; }
    @media (max-width: 1000px) { .tr-grid { grid-template-columns: 1fr; } }
</style>

<div class="tr-zonas" id="zonas"></div>

<div class="tr-grid">
    <div class="card" style="margin:0; display:flex; flex-direction:column; max-height:660px">
        <div class="card-body" style="overflow:auto">
            <div style="font-size:.8rem; color:var(--text-muted)">Largo de la ruta</div>
            <div class="tr-km"><span id="km">0.0</span> km <small id="sin-guardar" style="font-size:.75rem; color:#b45309; display:none">· sin guardar</small></div>

            <label style="display:flex; gap:8px; align-items:center; font-size:.84rem; margin-top:10px">
                <input type="checkbox" id="carretera" checked> Seguir la carretera al hacer clic
            </label>

            <div class="tr-botones">
                <button type="button" id="btn-deshacer">↶ Deshacer tramo</button>
                <button type="button" id="btn-borrar">🗑 Borrar trazo</button>
                <button type="button" id="btn-planta" style="grid-column:1 / -1">🏭 Cerrar el recorrido en la planta</button>
                <button type="button" id="btn-guardar" class="tr-guardar">💾 Guardar ruta</button>
            </div>
            <div class="tr-estado" id="estado"></div>

            <p style="font-size:.8rem; color:var(--text-muted); margin:12px 0 6px">
                Haz clic en el mapa para avanzar la ruta desde la planta, pasando por los predios en el orden que quieras.
                Con «Seguir la carretera» cada tramo va por los caminos del mapa; sin señal de internet se une en línea recta.
                El chofer visitará a los ganaderos en el sentido del trazo.
            </p>

            <h4 style="margin:14px 0 4px; font-size:.9rem">Orden de visita</h4>
            <div id="paradas"></div>
        </div>
    </div>
    <div class="card" style="margin:0">
        <div class="card-body">
            <div id="mapa-trazo"></div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const ZONAS = @json($zonas);
    const GANADEROS = @json($ganaderos);
    const PARADAS = @json($paradas);
    const PLANTA = [{{ $planta['lat'] }}, {{ $planta['lon'] }}];
    const URL_BASE = @json(url('admin/rutas/trazar'));
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const COLORES = ['#1f6feb', '#f2a541', '#8b5cf6', '#10b981', '#ef4444', '#0ea5e9', '#ec4899'];
    const colorZona = z => z.color || COLORES[ZONAS.indexOf(z) % COLORES.length];
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

    let zona = null;          // zona seleccionada
    let tramos = [];          // cada clic agrega un tramo: [[lat, lon], ...]
    let sinGuardar = false;
    let ocupado = false;

    const mapa = L.map('mapa-trazo').setView(PLANTA, 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(mapa);
    L.marker(PLANTA).addTo(mapa).bindTooltip('🏭 Planta', { permanent: true, direction: 'top', offset: [-14, -10] });

    const capaZona = L.layerGroup().addTo(mapa);
    const capaOtras = L.layerGroup().addTo(mapa);
    const linea = L.polyline([], { color: '#1f6feb', weight: 5, opacity: .9 }).addTo(mapa);
    const fin = L.circleMarker(PLANTA, { radius: 7, color: '#fff', weight: 3, fillColor: '#1f6feb', fillOpacity: 1 });

    const puntos = () => tramos.reduce((todos, t) => todos.concat(todos.length ? t.slice(1) : t), []);
    const distanciaKm = (a, b) => mapa.distance(a, b) / 1000;
    const largoKm = pts => pts.slice(1).reduce((s, p, i) => s + distanciaKm(pts[i], p), 0);

    function aviso(texto, tipo = 'ok') {
        const el = document.getElementById('estado');
        el.style.display = texto ? 'block' : 'none';
        el.style.background = tipo === 'error' ? '#fdecec' : tipo === 'info' ? '#fff6e5' : '#e7f6ee';
        el.style.color = tipo === 'error' ? '#b42318' : tipo === 'info' ? '#8a5a12' : '#16683a';
        el.textContent = texto;
    }

    function pintarZonas() {
        document.getElementById('zonas').innerHTML = ZONAS.map(z => `
            <button type="button" class="tr-zona ${zona && z.id === zona.id ? 'sel' : ''}" onclick="window.trElegir(${z.id})">
                <span class="punto" style="background:${colorZona(z)}"></span>${esc(z.nombre)}
                <small>${z.trazado_km ? '✓ ' + Number(z.trazado_km).toFixed(1) + ' km' : 'sin trazar'}</small>
            </button>`).join('');
    }

    function pintarTrazo() {
        const pts = puntos();
        linea.setLatLngs(pts);
        if (pts.length) fin.setLatLng(pts[pts.length - 1]).addTo(mapa); else mapa.removeLayer(fin);
        document.getElementById('km').textContent = largoKm(pts).toFixed(1);
        document.getElementById('sin-guardar').style.display = sinGuardar ? 'inline' : 'none';
        document.getElementById('btn-deshacer').disabled = !tramos.length || ocupado;
        document.getElementById('btn-borrar').disabled = !tramos.length || ocupado;
        document.getElementById('btn-planta').disabled = !pts.length || ocupado;
        document.getElementById('btn-guardar').disabled = !zona || ocupado || (!sinGuardar);
    }

    function pintarParadas() {
        const lista = PARADAS[zona.id] || [];
        const porId = Object.fromEntries(lista.map(p => [p.id, p]));
        document.getElementById('paradas').innerHTML = lista.length ? lista.map(p => `
            <div class="tr-parada">
                <span class="tr-num" style="${p.sin_gps ? 'background:#94a3b8' : ''}">${p.orden}</span>
                <span style="flex:1">${esc(p.nombre)}
                    ${p.sin_gps ? '<br><span class="tr-aviso">⚠ sin GPS: va al final</span>' : ''}
                    ${p.lejos_m > 300 ? `<br><span class="tr-aviso">⚠ a ${(p.lejos_m / 1000).toFixed(1)} km del camino</span>` : ''}
                </span>
                ${p.km !== null && zona.trazado_km ? `<small style="color:var(--text-muted)">km ${Number(p.km).toFixed(1)}</small>` : ''}
            </div>`).join('') : '<p style="color:var(--text-muted); font-size:.84rem">Esta zona no tiene ganaderos activos.</p>';

        capaZona.clearLayers();
        if (Array.isArray(zona.poligono) && zona.poligono.length > 2) {
            L.polygon(zona.poligono, { color: colorZona(zona), weight: 2, fillOpacity: .06, interactive: false }).addTo(capaZona);
        }
        GANADEROS.filter(g => g.zona_id === zona.id && g.geo_lat !== null).forEach(g => {
            const n = porId[g.id]?.orden ?? '';
            L.marker([g.geo_lat, g.geo_lon], {
                interactive: true, keyboard: false,
                icon: L.divIcon({ className: '', iconSize: [26, 26], iconAnchor: [13, 13],
                    html: `<div style="width:26px;height:26px;border-radius:50%;background:${g.estado === 'Suspendido' ? '#dc2626' : colorZona(zona)};color:#fff;font:700 12px sans-serif;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35)">${n}</div>` })
            }).bindTooltip(esc(g.nombre) + (g.estado === 'Suspendido' ? ' (suspendido)' : '')).addTo(capaZona)
              // un clic sobre el predio también avanza la ruta hasta él
              .on('click', () => agregarHasta([g.geo_lat, g.geo_lon]));
        });
    }

    function pintarOtras() {
        capaOtras.clearLayers();
        ZONAS.filter(z => z !== zona && Array.isArray(z.trazado) && z.trazado.length > 1).forEach(z =>
            L.polyline(z.trazado, { color: colorZona(z), weight: 3, opacity: .35, dashArray: '6 8', interactive: false }).addTo(capaOtras));
    }

    window.trElegir = id => {
        if (sinGuardar && !confirm('Hay cambios sin guardar en esta ruta. ¿Descartarlos?')) return;
        zona = ZONAS.find(z => z.id === id);
        tramos = Array.isArray(zona.trazado) && zona.trazado.length > 1 ? [zona.trazado.map(p => [+p[0], +p[1]])] : [];
        sinGuardar = false;
        aviso('');
        pintarZonas(); pintarParadas(); pintarOtras(); pintarTrazo();
        const pts = puntos();
        const conGps = GANADEROS.filter(g => g.zona_id === id && g.geo_lat !== null).map(g => [g.geo_lat, g.geo_lon]);
        const todos = pts.concat(conGps, [PLANTA]);
        if (todos.length > 1) mapa.fitBounds(L.latLngBounds(todos).pad(0.15));
    };

    // Camino por carretera entre dos puntos (OSRM, el mismo servicio que usa la app); null si no responde
    async function porCarretera(a, b) {
        try {
            const url = `https://router.project-osrm.org/route/v1/driving/${a[1]},${a[0]};${b[1]},${b[0]}?overview=full&geometries=geojson`;
            const r = await fetch(url);
            if (!r.ok) return null;
            const d = await r.json();
            const coords = d.routes?.[0]?.geometry?.coordinates;
            return coords && coords.length > 1 ? coords.map(c => [c[1], c[0]]) : null;
        } catch (e) { return null; }
    }

    async function agregarHasta(destino) {
        if (!zona) { aviso('Primero elige una zona arriba.', 'info'); return; }
        if (ocupado) return;
        const pts = puntos();
        const desde = pts.length ? pts[pts.length - 1] : PLANTA;
        let tramo = [desde, destino];
        if (document.getElementById('carretera').checked) {
            ocupado = true; pintarTrazo(); aviso('Buscando el camino por carretera…', 'info');
            const vial = await porCarretera(desde, destino);
            ocupado = false;
            if (vial) { tramo = [desde, ...vial.slice(1, -1), destino]; aviso(''); }
            else aviso('No se encontró camino por carretera (o no hay internet): se unió en línea recta.', 'info');
        }
        tramos.push(tramo);
        sinGuardar = true;
        pintarTrazo();
    }

    mapa.on('click', e => agregarHasta([e.latlng.lat, e.latlng.lng]));

    document.getElementById('btn-deshacer').addEventListener('click', () => { tramos.pop(); sinGuardar = true; pintarTrazo(); });
    document.getElementById('btn-borrar').addEventListener('click', () => {
        if (!confirm('¿Borrar todo el trazo de ' + zona.nombre + '?')) return;
        tramos = []; sinGuardar = true; pintarTrazo();
    });
    document.getElementById('btn-planta').addEventListener('click', () => agregarHasta(PLANTA));

    document.getElementById('btn-guardar').addEventListener('click', () => {
        const pts = puntos().map(p => [+p[0].toFixed(6), +p[1].toFixed(6)]);
        ocupado = true; pintarTrazo();
        fetch(`${URL_BASE}/${zona.id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ trazado: pts })
        }).then(async r => {
            const d = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(d.message || 'No se pudo guardar.');
            zona.trazado = pts.length ? pts : null;
            zona.trazado_km = d.trazado_km;
            PARADAS[zona.id] = d.paradas;
            sinGuardar = false;
            aviso(d.message);
            pintarZonas(); pintarParadas();
        }).catch(e => aviso(e.message || 'No se pudo guardar. Revisa la conexión.', 'error'))
          .finally(() => { ocupado = false; pintarTrazo(); });
    });

    window.addEventListener('beforeunload', e => { if (sinGuardar) { e.preventDefault(); e.returnValue = ''; } });

    pintarZonas();
    if (ZONAS.length) window.trElegir(ZONAS[0].id);
    else aviso('No hay zonas activas. Créalas primero en «Zonas en el mapa».', 'info');
})();
</script>
@endsection
