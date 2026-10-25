@extends('layouts.admin')

@section('title', 'Zonas en el mapa')
@section('page-title', '🧭 Zonas y ubicación de ganaderos')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    .zm-grid { display: grid; grid-template-columns: 320px 1fr; gap: 18px; }
    #mapa-zonas { height: 600px; border-radius: var(--radius); border: 1px solid var(--border); }
    .g-item { padding: 9px 10px; border-radius: 10px; cursor: pointer; font-size: .84rem; display: flex; gap: 8px; align-items: center; }
    .g-item:hover { background: #f3f7ff; } .g-item.sel { background: #dbe9ff; }
    .punto { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; border: 2px solid #fff; box-shadow: 0 0 0 1px #cbd5e1; }
    .leyenda span { display: inline-flex; align-items: center; gap: 6px; margin-right: 12px; font-size: .8rem; }
    .etq-zona { background: transparent; border: 0; box-shadow: none; font-weight: 700; color: #0f2a4d; font-size: .85rem; }
    .aviso-mover { background: #fef3c7; color: #92400e; padding: 10px 12px; border-radius: 12px; font-size: .84rem; margin-bottom: 10px; display: none; }
    @media (max-width: 1000px) { .zm-grid { grid-template-columns: 1fr; } }
</style>

<div class="leyenda" id="leyenda" style="margin-bottom:12px"></div>

<div class="zm-grid">
    <div class="card" style="margin:0; max-height:640px; display:flex; flex-direction:column">
        <div class="card-header">
            <h3>🐄 Ganaderos ({{ $ganaderos->count() }})</h3>
        </div>
        <div style="padding:10px">
            <input type="text" id="buscar" placeholder="Buscar por nombre o DNI…">
        </div>
        <div class="card-body" id="lista" style="padding:4px 10px; overflow:auto; flex:1"></div>
    </div>
    <div class="card" style="margin:0">
        <div class="card-body">
            <div class="aviso-mover" id="aviso-mover">📍 Haz clic en el mapa donde está el predio de <b id="mover-nombre"></b> · <a href="#" id="cancelar-mover">Cancelar</a></div>
            <div id="mapa-zonas"></div>
            <p style="color:var(--text-muted); font-size:.8rem; margin-top:8px">
                Toca un ganadero para cambiar su zona. Arrastra el marcador (o usa "Ubicar en el mapa" y haz clic) para corregir su GPS;
                si cae dentro de una zona, se le asigna automáticamente.
                la app del chofer usa estos datos para ordenar la ruta.
            </p>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const ZONAS = @json($zonas);
    let GANADEROS = @json($ganaderos);
    const PLANTA = [{{ $planta['lat'] }}, {{ $planta['lon'] }}];
    const URL_BASE = @json(url('admin/zonas/mapa'));
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const COLORES = ['#1f6feb', '#f2a541', '#8b5cf6', '#10b981', '#ef4444', '#0ea5e9', '#ec4899'];
    const colorZona = id => { const i = ZONAS.findIndex(z => z.id === id); return i < 0 ? '#94a3b8' : (ZONAS[i].color || COLORES[i % COLORES.length]); };
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

    document.getElementById('leyenda').innerHTML = ZONAS.map(z => `<span><i class="punto" style="background:${colorZona(z.id)}"></i>${esc(z.nombre)}</span>`).join('')
        + '<span><i class="punto" style="background:#94a3b8"></i>Sin zona</span>';

    const mapa = L.map('mapa-zonas').setView(PLANTA, 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(mapa);
    L.marker(PLANTA).addTo(mapa).bindTooltip('🏭 Planta');
    // Polígono de cada zona: al ubicar un ganadero dentro, el servidor le asigna esa zona
    ZONAS.filter(z => Array.isArray(z.poligono) && z.poligono.length > 2).forEach(z =>
        L.polygon(z.poligono, { color: colorZona(z.id), weight: 2, fillOpacity: .08, interactive: false })
            .addTo(mapa).bindTooltip(z.nombre, { permanent: true, direction: 'center', className: 'etq-zona' }));
    const marcadores = {};
    let moviendo = null;

    const icono = color => L.divIcon({ className: '', iconSize: [22, 22], iconAnchor: [11, 11],
        html: `<div style="width:22px;height:22px;border-radius:50%;background:${color};border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35)"></div>` });

    function popup(g) {
        const opciones = ['<option value="">Sin zona</option>'].concat(ZONAS.map(z =>
            `<option value="${z.id}" ${z.id === g.zona_id ? 'selected' : ''}>${esc(z.nombre)}</option>`)).join('');
        return `<div style="min-width:210px;font-family:inherit">
            <b>${esc(g.nombre)}</b><br><small>DNI ${esc(g.dni)} ${g.estado === 'Suspendido' ? '· <b style="color:#dc2626">SUSPENDIDO</b>' : ''}</small>
            <div style="margin-top:8px"><select onchange="window.zmCambiarZona(${g.id}, this.value)">${opciones}</select></div>
            <div style="margin-top:6px"><a href="#" onclick="window.zmMover(${g.id});return false">📍 Ubicar en el mapa</a></div>
        </div>`;
    }

    function pintarMarcador(g) {
        if (marcadores[g.id]) { mapa.removeLayer(marcadores[g.id]); delete marcadores[g.id]; }
        if (g.geo_lat === null || g.geo_lon === null) return;
        const m = L.marker([g.geo_lat, g.geo_lon], { icon: icono(colorZona(g.zona_id)), draggable: true, title: g.nombre })
            .addTo(mapa).bindPopup(popup(g));
        m.on('dragend', e => guardar(g.id, { geo_lat: e.target.getLatLng().lat, geo_lon: e.target.getLatLng().lng }));
        marcadores[g.id] = m;
    }

    function pintarLista() {
        const q = document.getElementById('buscar').value.toLowerCase();
        document.getElementById('lista').innerHTML = GANADEROS
            .filter(g => !q || g.nombre.toLowerCase().includes(q) || String(g.dni).includes(q))
            .map(g => `<div class="g-item" onclick="window.zmEnfocar(${g.id})">
                <i class="punto" style="background:${colorZona(g.zona_id)}"></i>
                <span style="flex:1">${esc(g.nombre)}<br><small style="color:var(--text-muted)">${g.geo_lat === null ? '⚠ sin GPS' : 'DNI ' + esc(g.dni)}</small></span>
            </div>`).join('');
    }

    function guardar(id, cambios) {
        fetch(`${URL_BASE}/${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(cambios)
        }).then(r => r.ok ? r.json() : Promise.reject(r))
          .then(d => {
              const g = GANADEROS.find(x => x.id === id);
              Object.assign(g, d.ganadero);
              pintarMarcador(g); pintarLista();
          }).catch(() => alert('No se pudo guardar. Revisa la conexión.'));
    }

    window.zmCambiarZona = (id, zonaId) => guardar(id, { zona_id: zonaId === '' ? null : +zonaId });
    window.zmEnfocar = id => {
        const g = GANADEROS.find(x => x.id === id);
        if (marcadores[id]) { mapa.flyTo(marcadores[id].getLatLng(), 15); marcadores[id].openPopup(); }
        else window.zmMover(id);
    };
    window.zmMover = id => {
        moviendo = GANADEROS.find(x => x.id === id);
        document.getElementById('mover-nombre').textContent = moviendo.nombre;
        document.getElementById('aviso-mover').style.display = 'block';
        mapa.closePopup();
    };
    document.getElementById('cancelar-mover').addEventListener('click', e => {
        e.preventDefault(); moviendo = null; document.getElementById('aviso-mover').style.display = 'none';
    });
    mapa.on('click', e => {
        if (!moviendo) return;
        guardar(moviendo.id, { geo_lat: e.latlng.lat, geo_lon: e.latlng.lng });
        moviendo = null; document.getElementById('aviso-mover').style.display = 'none';
    });
    document.getElementById('buscar').addEventListener('input', pintarLista);

    GANADEROS.forEach(pintarMarcador);
    pintarLista();
    const conGps = GANADEROS.filter(g => g.geo_lat !== null).map(g => [g.geo_lat, g.geo_lon]);
    if (conGps.length) mapa.fitBounds(L.latLngBounds(conGps.concat([PLANTA])).pad(0.1));
})();
</script>
@endsection
