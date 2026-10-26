@extends('layouts.admin')

@section('title', 'Rutas')
@section('page-title', '🧭 Rutas: proveedores, chofer y camino')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    .tr-zonas { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; align-items: center; }
    .tr-zona { border: 1px solid var(--border); background: #fff; border-radius: 999px; padding: 7px 14px; font-size: .85rem; cursor: pointer; display: inline-flex; gap: 8px; align-items: center; }
    .tr-zona.sel { background: #0f2a4d; color: #fff; border-color: #0f2a4d; }
    .tr-zona small { opacity: .75; }
    .tr-nueva { border: 1px dashed #1f6feb; color: #1f6feb; background: #f3f7ff; font-weight: 600; }
    .tr-grid { display: grid; grid-template-columns: 360px 1fr; gap: 18px; }
    #mapa-trazo { height: 640px; border-radius: var(--radius); border: 1px solid var(--border); }
    #mapa-trazo.dibujando { cursor: crosshair; }
    .punto { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
    .tr-tabs { display: flex; border-bottom: 1px solid var(--border); }
    .tr-tab { flex: 1; padding: 11px 6px; border: 0; background: none; cursor: pointer; font-size: .85rem; color: var(--text-muted); border-bottom: 3px solid transparent; }
    .tr-tab.sel { color: #0f2a4d; font-weight: 700; border-bottom-color: #1f6feb; }
    .tr-panel { display: none; } .tr-panel.sel { display: block; }
    .tr-botones { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 10px 0; }
    .tr-btn { padding: 9px 10px; border-radius: 10px; border: 1px solid var(--border); background: #fff; cursor: pointer; font-size: .84rem; }
    .tr-btn:disabled { opacity: .45; cursor: default; }
    .tr-primario { background: #1f6feb !important; color: #fff; border: 0 !important; font-weight: 600; }
    .tr-km { font-size: 1.6rem; font-weight: 700; color: #0f2a4d; }
    .tr-estado { font-size: .82rem; padding: 8px 10px; border-radius: 10px; margin: 10px 0 0; display: none; }
    .tr-parada { display: flex; gap: 10px; align-items: center; padding: 7px 4px; border-bottom: 1px solid #eef2f7; font-size: .84rem; }
    .tr-num { width: 24px; height: 24px; border-radius: 50%; background: #1f6feb; color: #fff; font-size: .75rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .tr-aviso { color: #b45309; font-size: .76rem; }
    .tr-quitar { border: 0; background: none; color: #b42318; cursor: pointer; font-size: .9rem; padding: 2px 6px; border-radius: 6px; }
    .tr-quitar:hover { background: #fdecec; }
    .tr-buscar { position: relative; margin: 10px 0 6px; }
    .tr-sugerencias { position: absolute; left: 0; right: 0; top: 100%; background: #fff; border: 1px solid var(--border); border-radius: 10px; box-shadow: 0 8px 24px rgba(15,42,77,.12); max-height: 260px; overflow: auto; z-index: 500; display: none; }
    .tr-sug { padding: 8px 10px; cursor: pointer; font-size: .84rem; display: flex; justify-content: space-between; gap: 8px; }
    .tr-sug:hover { background: #f3f7ff; }
    .tr-ayuda { font-size: .79rem; color: var(--text-muted); margin: 8px 0; }
    .tr-cab { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 14px 16px 10px; }
    .tr-cab h3 { margin: 0; font-size: 1.05rem; display: flex; gap: 8px; align-items: center; }
    .tr-link { border: 0; background: none; color: #1f6feb; cursor: pointer; font-size: .78rem; padding: 0; }
    .tr-modal-fondo { position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1000; display: none; align-items: center; justify-content: center; }
    .tr-modal { background: #fff; border-radius: 16px; padding: 22px; width: min(420px, 92vw); }
    .tr-modal label { font-size: .8rem; font-weight: 600; display: block; margin: 12px 0 4px; }
    @media (max-width: 1000px) { .tr-grid { grid-template-columns: 1fr; } }
</style>

<div class="tr-zonas" id="zonas"></div>

<div class="tr-grid">
    <div class="card" style="margin:0; display:flex; flex-direction:column; max-height:690px">
        <div class="tr-cab">
            <h3><span class="punto" id="cab-color"></span><span id="cab-nombre">—</span></h3>
            <span>
                <button type="button" class="tr-link" id="btn-renombrar">✎ Editar</button> ·
                <button type="button" class="tr-link" id="btn-eliminar" style="color:#b42318">Eliminar</button>
            </span>
        </div>
        <div class="tr-tabs">
            <button type="button" class="tr-tab sel" data-tab="proveedores">👥 Proveedores <span id="cnt-prov"></span></button>
            <button type="button" class="tr-tab" data-tab="chofer">🚛 Chofer</button>
            <button type="button" class="tr-tab" data-tab="camino">✏️ Camino</button>
        </div>
        <div class="card-body" style="overflow:auto; flex:1">
            {{-- PROVEEDORES --}}
            <div class="tr-panel sel" id="panel-proveedores">
                <div class="tr-buscar">
                    <input type="text" id="buscar" placeholder="➕ Agregar proveedor: escribe su nombre o DNI…" autocomplete="off">
                    <div class="tr-sugerencias" id="sugerencias"></div>
                </div>
                <p class="tr-ayuda">También puedes tocar un proveedor en el mapa para agregarlo o quitarlo. La lista está en el orden en que el chofer los visitará.</p>
                <div id="paradas"></div>
            </div>

            {{-- CHOFER --}}
            <div class="tr-panel" id="panel-chofer">
                <label style="font-size:.8rem; font-weight:600">Chofer titular de <span class="nom-ruta"></span> en {{ \Illuminate\Support\Carbon::create($anio, $mes)->locale('es')->translatedFormat('F Y') }}</label>
                <select id="sel-chofer" style="margin:6px 0 10px">
                    <option value="">— Sin chofer —</option>
                    @foreach($choferes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="tr-btn tr-primario" id="btn-chofer" style="width:100%">Guardar chofer</button>
                <p class="tr-ayuda">Si el chofer ya tenía otra ruta este mes, intercambian rutas automáticamente. Para los próximos meses usa <a href="{{ route('admin.rotacion.index') }}">Rotación Zonas</a>.</p>
                <h4 style="margin:16px 0 6px; font-size:.88rem">Choferes de este mes</h4>
                <div id="resumen-choferes"></div>
            </div>

            {{-- CAMINO --}}
            <div class="tr-panel" id="panel-camino">
                <div style="font-size:.8rem; color:var(--text-muted)">Largo del camino</div>
                <div class="tr-km"><span id="km">0.0</span> km <small id="sin-guardar" style="font-size:.75rem; color:#b45309; display:none">· sin guardar</small></div>
                <label style="display:flex; gap:8px; align-items:center; font-size:.84rem; margin-top:10px">
                    <input type="checkbox" id="carretera" checked style="width:auto"> Seguir la carretera al hacer clic
                </label>
                <div class="tr-botones">
                    <button type="button" class="tr-btn" id="btn-deshacer">↶ Deshacer tramo</button>
                    <button type="button" class="tr-btn" id="btn-borrar">🗑 Borrar camino</button>
                    <button type="button" class="tr-btn" id="btn-planta" style="grid-column:1 / -1">🏭 Volver a la planta</button>
                    <button type="button" class="tr-btn tr-primario" id="btn-guardar" style="grid-column:1 / -1">💾 Guardar camino</button>
                </div>
                <p class="tr-ayuda">
                    Con esta pestaña abierta, haz clic en el mapa para avanzar el camino desde la planta,
                    pasando por los predios en el orden que quieras (también puedes hacer clic sobre un predio).
                    Con «Seguir la carretera» cada tramo va por los caminos del mapa. El chofer verá este camino en su app
                    y visitará a los proveedores en ese sentido.
                </p>
            </div>
            <div class="tr-estado" id="estado"></div>
        </div>
    </div>
    <div class="card" style="margin:0">
        <div class="card-body">
            <div id="mapa-trazo"></div>
            <p class="tr-ayuda" style="margin-bottom:0">● Con número: proveedores de la ruta elegida · ● Gris: proveedores de otras rutas o sin ruta (tócalos para agregarlos).</p>
        </div>
    </div>
</div>

{{-- Modal crear / editar ruta --}}
<div class="tr-modal-fondo" id="modal-ruta">
    <div class="tr-modal">
        <h3 style="margin:0 0 4px" id="modal-titulo">Nueva ruta</h3>
        <p class="tr-ayuda" style="margin:0">Después agrégale proveedores, un chofer y su camino.</p>
        <label for="ruta-nombre">Nombre</label>
        <input type="text" id="ruta-nombre" maxlength="100" placeholder="Ej.: Collana 5, Ruta Pucamocco">
        <label for="ruta-color">Color en el mapa</label>
        <input type="color" id="ruta-color" value="#0ea5e9" style="height:40px; padding:2px">
        <div style="display:flex; gap:8px; margin-top:18px">
            <button type="button" class="tr-btn tr-primario" id="ruta-guardar" style="flex:1">Guardar</button>
            <button type="button" class="tr-btn" id="ruta-cancelar">Cancelar</button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const ZONAS = @json($zonas);
    const GANADEROS = @json($ganaderos);
    const PARADAS = @json($paradas);
    const CHOFERES = @json($choferes);
    let TITULARES = @json((object) $titulares);
    const PLANTA = [{{ $planta['lat'] }}, {{ $planta['lon'] }}];
    const URL_RUTAS = @json(url('admin/rutas'));
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const COLORES = ['#1f6feb', '#f2a541', '#8b5cf6', '#10b981', '#0ea5e9', '#ec4899', '#14b8a6']; // sin rojo: el rojo marca a los suspendidos
    const colorZona = z => z ? (z.color || COLORES[ZONAS.indexOf(z) % COLORES.length]) : '#94a3b8';
    const zonaPorId = id => ZONAS.find(z => z.id === id);
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

    let zona = null, tab = 'proveedores', tramos = [], sinGuardar = false, ocupado = false, editando = null;

    // ── utilidades ──────────────────────────────────────────────────────
    function aviso(texto, tipo = 'ok') {
        const el = document.getElementById('estado');
        el.style.display = texto ? 'block' : 'none';
        el.style.background = tipo === 'error' ? '#fdecec' : tipo === 'info' ? '#fff6e5' : '#e7f6ee';
        el.style.color = tipo === 'error' ? '#b42318' : tipo === 'info' ? '#8a5a12' : '#16683a';
        el.textContent = texto;
    }
    async function enviar(metodo, url, cuerpo) {
        const r = await fetch(url, { method: metodo, body: JSON.stringify(cuerpo),
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const d = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(d.message || (d.errors ? Object.values(d.errors)[0][0] : 'No se pudo guardar. Revisa la conexión.'));
        return d;
    }

    // ── mapa ────────────────────────────────────────────────────────────
    const mapa = L.map('mapa-trazo').setView(PLANTA, 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(mapa);
    L.marker(PLANTA).addTo(mapa).bindTooltip('🏭 Planta', { permanent: true, direction: 'top', offset: [-14, -10] });
    const capaZona = L.layerGroup().addTo(mapa);
    const capaGanaderos = L.layerGroup().addTo(mapa);
    const capaOtras = L.layerGroup().addTo(mapa);
    const linea = L.polyline([], { color: '#1f6feb', weight: 5, opacity: .9 }).addTo(mapa);
    const fin = L.circleMarker(PLANTA, { radius: 7, color: '#fff', weight: 3, fillColor: '#1f6feb', fillOpacity: 1 });

    const puntos = () => tramos.reduce((todos, t) => todos.concat(todos.length ? t.slice(1) : t), []);
    const largoKm = pts => pts.slice(1).reduce((s, p, i) => s + mapa.distance(pts[i], p) / 1000, 0);

    // ── encabezado y rutas ─────────────────────────────────────────────
    function pintarZonas() {
        document.getElementById('zonas').innerHTML = ZONAS.map(z => {
            const ch = TITULARES[z.id]?.nombre;
            const n = GANADEROS.filter(g => g.zona_id === z.id).length;
            return `<button type="button" class="tr-zona ${zona && z.id === zona.id ? 'sel' : ''}" onclick="window.trElegir(${z.id})">
                <span class="punto" style="background:${colorZona(z)}"></span>${esc(z.nombre)}
                <small>${n} prov. · ${ch ? '🚛 ' + esc(ch.split(' ')[0]) : 'sin chofer'}${z.trazado_km ? ' · ✓ camino' : ''}</small>
            </button>`;
        }).join('') + `<button type="button" class="tr-zona tr-nueva" onclick="window.trNueva()">＋ Nueva ruta</button>`;
        if (zona) {
            document.getElementById('cab-nombre').textContent = zona.nombre;
            document.getElementById('cab-color').style.background = colorZona(zona);
            document.querySelectorAll('.nom-ruta').forEach(e => e.textContent = zona.nombre);
            document.getElementById('cnt-prov').textContent = `(${GANADEROS.filter(g => g.zona_id === zona.id).length})`;
        }
    }

    function pintarGanaderos() {
        capaGanaderos.clearLayers();
        const orden = Object.fromEntries((PARADAS[zona.id] || []).map(p => [p.id, p.orden]));
        GANADEROS.filter(g => g.geo_lat !== null).forEach(g => {
            const mia = g.zona_id === zona.id;
            const otra = zonaPorId(g.zona_id);
            const icono = mia
                ? L.divIcon({ className: '', iconSize: [26, 26], iconAnchor: [13, 13],
                    html: `<div style="width:26px;height:26px;border-radius:50%;background:${g.estado === 'Suspendido' ? '#dc2626' : colorZona(zona)};color:#fff;font:700 12px sans-serif;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35)">${orden[g.id] ?? ''}</div>` })
                : L.divIcon({ className: '', iconSize: [14, 14], iconAnchor: [7, 7],
                    html: `<div style="width:14px;height:14px;border-radius:50%;background:#cbd5e1;border:2px solid ${otra ? colorZona(otra) : '#fff'};box-shadow:0 1px 3px rgba(0,0,0,.3)"></div>` });
            const m = L.marker([g.geo_lat, g.geo_lon], { icon: icono, zIndexOffset: mia ? 500 : 0 })
                .bindTooltip(esc(g.nombre) + (mia ? '' : ` · ${otra ? esc(otra.nombre) : 'sin ruta'}`));
            m.on('click', () => {
                if (tab === 'camino' && mia) { agregarHasta([g.geo_lat, g.geo_lon]); return; }
                L.popup().setLatLng([g.geo_lat, g.geo_lon]).setContent(`<div style="min-width:190px">
                    <b>${esc(g.nombre)}</b><br><small>DNI ${esc(g.dni)} · ${otra ? 'Ruta ' + esc(otra.nombre) : 'Sin ruta'}${g.estado === 'Suspendido' ? ' · <b style="color:#dc2626">SUSPENDIDO</b>' : ''}</small>
                    <div style="margin-top:8px">${mia
                        ? `<button class="tr-btn" style="width:100%;color:#b42318" onclick="window.trProveedor(${g.id}, 'quitar')">Quitar de ${esc(zona.nombre)}</button>`
                        : `<button class="tr-btn tr-primario" style="width:100%" onclick="window.trProveedor(${g.id}, 'agregar')">Agregar a ${esc(zona.nombre)}</button>`}</div></div>`).openOn(mapa);
            });
            m.addTo(capaGanaderos);
        });
    }

    function pintarParadas() {
        const lista = PARADAS[zona.id] || [];
        document.getElementById('paradas').innerHTML = lista.length ? lista.map(p => `
            <div class="tr-parada">
                <span class="tr-num" style="${p.sin_gps ? 'background:#94a3b8' : ''}">${p.orden}</span>
                <span style="flex:1">${esc(p.nombre)}
                    ${p.sin_gps ? '<br><span class="tr-aviso">⚠ sin GPS: va al final (ubícalo en «Zonas en el mapa»)</span>' : ''}
                    ${p.lejos_m > 300 ? `<br><span class="tr-aviso">⚠ a ${(p.lejos_m / 1000).toFixed(1)} km del camino</span>` : ''}
                </span>
                ${p.km !== null && zona.trazado_km ? `<small style="color:var(--text-muted)">km ${Number(p.km).toFixed(1)}</small>` : ''}
                <button type="button" class="tr-quitar" title="Quitar de la ruta" onclick="window.trProveedor(${p.id}, 'quitar')">✕</button>
            </div>`).join('') : '<p class="tr-ayuda">Esta ruta aún no tiene proveedores. Búscalos arriba o tócalos en el mapa.</p>';
    }

    function pintarChoferes() {
        document.getElementById('sel-chofer').value = TITULARES[zona.id]?.id ?? '';
        const libres = CHOFERES.filter(c => !Object.values(TITULARES).some(t => t.id === c.id));
        document.getElementById('resumen-choferes').innerHTML = ZONAS.map(z => `
            <div class="tr-parada"><span class="punto" style="background:${colorZona(z)}"></span>
                <span style="flex:1">${esc(z.nombre)}</span><b style="font-weight:600">${esc(TITULARES[z.id]?.nombre ?? '—')}</b></div>`).join('')
            + (libres.length ? `<p class="tr-ayuda">Sin ruta este mes (volantes): ${libres.map(c => esc(c.name)).join(', ')}</p>` : '');
    }

    function pintarOtras() {
        capaOtras.clearLayers();
        capaZona.clearLayers();
        if (Array.isArray(zona.poligono) && zona.poligono.length > 2)
            L.polygon(zona.poligono, { color: colorZona(zona), weight: 2, fillOpacity: .06, interactive: false }).addTo(capaZona);
        ZONAS.filter(z => z !== zona && Array.isArray(z.trazado) && z.trazado.length > 1).forEach(z =>
            L.polyline(z.trazado, { color: colorZona(z), weight: 3, opacity: .3, dashArray: '6 8', interactive: false }).addTo(capaOtras));
    }

    function pintarTrazo() {
        const pts = puntos();
        linea.setLatLngs(pts);
        if (pts.length && tab === 'camino') fin.setLatLng(pts[pts.length - 1]).addTo(mapa); else mapa.removeLayer(fin);
        document.getElementById('km').textContent = largoKm(pts).toFixed(1);
        document.getElementById('sin-guardar').style.display = sinGuardar ? 'inline' : 'none';
        document.getElementById('btn-deshacer').disabled = !tramos.length || ocupado;
        document.getElementById('btn-borrar').disabled = !tramos.length || ocupado;
        document.getElementById('btn-planta').disabled = !pts.length || ocupado;
        document.getElementById('btn-guardar').disabled = ocupado || !sinGuardar;
    }

    function pintarTodo() { pintarZonas(); pintarParadas(); pintarGanaderos(); pintarChoferes(); pintarOtras(); pintarTrazo(); }

    // ── acciones ────────────────────────────────────────────────────────
    window.trElegir = id => {
        if (sinGuardar && !confirm('El camino de esta ruta tiene cambios sin guardar. ¿Descartarlos?')) return;
        zona = zonaPorId(id);
        tramos = Array.isArray(zona.trazado) && zona.trazado.length > 1 ? [zona.trazado.map(p => [+p[0], +p[1]])] : [];
        sinGuardar = false;
        aviso('');
        pintarTodo();
        const pts = puntos().concat(GANADEROS.filter(g => g.zona_id === id && g.geo_lat !== null).map(g => [g.geo_lat, g.geo_lon]), [PLANTA]);
        if (pts.length > 1) mapa.fitBounds(L.latLngBounds(pts).pad(0.15));
    };

    document.querySelectorAll('.tr-tab').forEach(b => b.addEventListener('click', () => {
        tab = b.dataset.tab;
        document.querySelectorAll('.tr-tab').forEach(x => x.classList.toggle('sel', x === b));
        document.querySelectorAll('.tr-panel').forEach(p => p.classList.toggle('sel', p.id === 'panel-' + tab));
        document.getElementById('mapa-trazo').classList.toggle('dibujando', tab === 'camino');
        aviso('');
        pintarTrazo();
    }));

    window.trProveedor = async (id, accion) => {
        mapa.closePopup();
        try {
            const d = await enviar('POST', `${URL_RUTAS}/${zona.id}/proveedores`, { ganadero_ids: [id], accion });
            d.ganaderos.forEach(c => { const g = GANADEROS.find(x => x.id === c.id); if (g) g.zona_id = c.zona_id; });
            // la ruta de la que salió también cambia de orden
            PARADAS[zona.id] = d.paradas;
            ZONAS.forEach(z => { if (z !== zona) PARADAS[z.id] = (PARADAS[z.id] || []).filter(p => p.id !== id || accion === 'quitar'); });
            aviso(d.message);
            pintarZonas(); pintarParadas(); pintarGanaderos();
        } catch (e) { aviso(e.message, 'error'); }
    };

    // Buscador de proveedores para agregar
    const buscar = document.getElementById('buscar'), sug = document.getElementById('sugerencias');
    buscar.addEventListener('input', () => {
        const q = buscar.value.trim().toLowerCase();
        const res = q ? GANADEROS.filter(g => g.zona_id !== zona.id && (g.nombre.toLowerCase().includes(q) || String(g.dni).includes(q))).slice(0, 12) : [];
        sug.style.display = res.length ? 'block' : 'none';
        sug.innerHTML = res.map(g => `<div class="tr-sug" data-id="${g.id}"><span>${esc(g.nombre)}<br><small style="color:var(--text-muted)">DNI ${esc(g.dni)}</small></span>
            <small style="color:var(--text-muted)">${zonaPorId(g.zona_id) ? esc(zonaPorId(g.zona_id).nombre) : 'sin ruta'}</small></div>`).join('');
    });
    sug.addEventListener('click', e => {
        const item = e.target.closest('.tr-sug'); if (!item) return;
        sug.style.display = 'none'; buscar.value = '';
        window.trProveedor(+item.dataset.id, 'agregar');
    });
    document.addEventListener('click', e => { if (!e.target.closest('.tr-buscar')) sug.style.display = 'none'; });

    document.getElementById('btn-chofer').addEventListener('click', async () => {
        const valor = document.getElementById('sel-chofer').value;
        try {
            const d = await enviar('POST', `${URL_RUTAS}/${zona.id}/chofer`, { chofer_id: valor ? +valor : null });
            TITULARES = d.titulares;
            aviso(d.message);
            pintarZonas(); pintarChoferes();
        } catch (e) { aviso(e.message, 'error'); }
    });

    // Crear / editar / eliminar ruta
    const modal = document.getElementById('modal-ruta');
    window.trNueva = () => {
        editando = null;
        document.getElementById('modal-titulo').textContent = 'Nueva ruta';
        document.getElementById('ruta-nombre').value = '';
        document.getElementById('ruta-color').value = COLORES[ZONAS.length % COLORES.length];
        modal.style.display = 'flex';
        setTimeout(() => document.getElementById('ruta-nombre').focus(), 50);
    };
    document.getElementById('btn-renombrar').addEventListener('click', () => {
        editando = zona;
        document.getElementById('modal-titulo').textContent = 'Editar ruta';
        document.getElementById('ruta-nombre').value = zona.nombre;
        document.getElementById('ruta-color').value = colorZona(zona);
        modal.style.display = 'flex';
    });
    document.getElementById('ruta-cancelar').addEventListener('click', () => modal.style.display = 'none');
    document.getElementById('ruta-guardar').addEventListener('click', async () => {
        const datos = { nombre: document.getElementById('ruta-nombre').value.trim(), color: document.getElementById('ruta-color').value };
        try {
            if (editando) {
                const d = await enviar('PUT', `${URL_RUTAS}/${editando.id}`, datos);
                Object.assign(editando, d.zona);
                modal.style.display = 'none';
                aviso(d.message); pintarTodo();
            } else {
                const d = await enviar('POST', URL_RUTAS, datos);
                ZONAS.push(d.zona); PARADAS[d.zona.id] = [];
                modal.style.display = 'none';
                window.trElegir(d.zona.id);
                aviso(d.message);
            }
        } catch (e) { alert(e.message); }
    });
    document.getElementById('btn-eliminar').addEventListener('click', async () => {
        if (!confirm(`¿Eliminar la ruta ${zona.nombre}? Debe estar sin proveedores.`)) return;
        try {
            const d = await enviar('PUT', `${URL_RUTAS}/${zona.id}`, { activa: false });
            ZONAS.splice(ZONAS.indexOf(zona), 1);
            delete TITULARES[zona.id];
            sinGuardar = false;
            if (ZONAS.length) window.trElegir(ZONAS[0].id); else location.reload();
            aviso(d.message);
        } catch (e) { aviso(e.message, 'error'); }
    });

    // ── camino ──────────────────────────────────────────────────────────
    async function porCarretera(a, b) {
        try {
            const r = await fetch(`https://router.project-osrm.org/route/v1/driving/${a[1]},${a[0]};${b[1]},${b[0]}?overview=full&geometries=geojson`);
            if (!r.ok) return null;
            const coords = (await r.json()).routes?.[0]?.geometry?.coordinates;
            return coords && coords.length > 1 ? coords.map(c => [c[1], c[0]]) : null;
        } catch (e) { return null; }
    }

    async function agregarHasta(destino) {
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

    mapa.on('click', e => {
        if (tab === 'camino') agregarHasta([e.latlng.lat, e.latlng.lng]);
        else aviso('Para dibujar el camino abre la pestaña «✏️ Camino».', 'info');
    });
    document.getElementById('btn-deshacer').addEventListener('click', () => { tramos.pop(); sinGuardar = true; pintarTrazo(); });
    document.getElementById('btn-borrar').addEventListener('click', () => {
        if (!confirm('¿Borrar todo el camino de ' + zona.nombre + '?')) return;
        tramos = []; sinGuardar = true; pintarTrazo();
    });
    document.getElementById('btn-planta').addEventListener('click', () => agregarHasta(PLANTA));
    document.getElementById('btn-guardar').addEventListener('click', async () => {
        const pts = puntos().map(p => [+p[0].toFixed(6), +p[1].toFixed(6)]);
        ocupado = true; pintarTrazo();
        try {
            const d = await enviar('POST', `${URL_RUTAS}/trazar/${zona.id}`, { trazado: pts });
            zona.trazado = pts.length ? pts : null;
            zona.trazado_km = d.trazado_km;
            PARADAS[zona.id] = d.paradas;
            sinGuardar = false;
            aviso(d.message);
            pintarZonas(); pintarParadas(); pintarGanaderos();
        } catch (e) { aviso(e.message, 'error'); }
        finally { ocupado = false; pintarTrazo(); }
    });

    window.addEventListener('beforeunload', e => { if (sinGuardar) { e.preventDefault(); e.returnValue = ''; } });

    if (ZONAS.length) window.trElegir(ZONAS[0].id);
    else { pintarZonas(); aviso('Aún no hay rutas. Crea la primera con «＋ Nueva ruta».', 'info'); }
})();
</script>
@endsection
