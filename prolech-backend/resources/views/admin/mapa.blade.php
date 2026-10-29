@extends('layouts.admin')

@section('title', 'Mapa GPS')
@section('page-title', '🗺️ Choferes en Ruta — Tiempo Real')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<style>
    /* ── Layout ─────────────────────────────────────────────────────── */
    .mapa-layout {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 20px;
        height: calc(100vh - 145px);
        min-height: 520px;
    }

    /* ── Contenedor del mapa ────────────────────────────────────────── */
    .mapa-box {
        position: relative;
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
        background: #e8f4f8;
    }

    #map { width: 100%; height: 100%; display: block; }

    .mapa-loader {
        position: absolute; inset: 0;
        background: rgba(255,255,255,.95);
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        z-index: 10; gap: 14px;
    }
    .mapa-loader.oculto { display: none; }

    .spinner {
        width: 38px; height: 38px;
        border: 3px solid var(--border);
        border-top-color: var(--brand-light);
        border-radius: 50%;
        animation: spin .7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Badge de actualización flotante */
    .mapa-badge {
        position: absolute;
        top: 12px; right: 12px;
        background: rgba(255,255,255,.97);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 5px 14px;
        font-size: .75rem;
        color: var(--text-muted);
        display: flex; align-items: center; gap: 6px;
        z-index: 500;
        box-shadow: var(--shadow);
    }

    .mapa-badge .pulse {
        width: 7px; height: 7px;
        background: var(--green);
        border-radius: 50%;
        animation: pulse-dot 2s ease-in-out infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: .5; transform: scale(1.4); }
    }

    .custom-div-icon { background: transparent !important; border: none !important; }

    /* ── Panel lateral ──────────────────────────────────────────────── */
    .panel {
        display: flex;
        flex-direction: column;
        gap: 14px;
        overflow-y: auto;
    }

    .panel-stat {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 14px 18px;
        box-shadow: var(--shadow);
    }

    .ps-label {
        font-size: .7rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .5px;
        color: var(--text-muted); margin-bottom: 6px;
    }

    .ps-value {
        font-size: 1.9rem; font-weight: 800;
        letter-spacing: -1px; line-height: 1;
    }

    .ps-sub { font-size: .75rem; color: var(--text-muted); margin-top: 4px; }

    /* Lista de choferes */
    .chofer-list {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .chofer-list-header {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-soft);
        font-size: .72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .5px;
        color: var(--text-muted);
    }

    .chofer-items { overflow-y: auto; flex: 1; }

    .chofer-item {
        padding: 11px 14px;
        border-bottom: 1px solid var(--border-soft);
        cursor: pointer;
        transition: background .12s;
        display: flex; align-items: center; gap: 10px;
    }

    .chofer-item:last-child { border-bottom: none; }

    .chofer-item:hover { background: var(--bg); }

    .chofer-item.selected {
        background: #f0fdf4;
        border-left: 3px solid var(--brand-light);
        padding-left: 11px;
    }

    .chofer-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .78rem; font-weight: 700;
        flex-shrink: 0;
    }

    .chofer-avatar.activo {
        background: #dcfce7; color: #15803d;
        border: 2px solid #22c55e;
    }

    .chofer-avatar.inactivo {
        background: var(--bg); color: var(--text-muted);
        border: 2px solid var(--border);
    }

    .chofer-info { flex: 1; min-width: 0; }

    .chofer-nombre {
        font-size: .85rem; font-weight: 600; color: var(--text);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    .chofer-meta {
        font-size: .72rem; color: var(--text-muted); margin-top: 2px;
        display: flex; align-items: center; gap: 5px;
    }

    .status-dot {
        width: 7px; height: 7px;
        border-radius: 50%; flex-shrink: 0;
    }
    .status-dot.verde   { background: #22c55e; }
    .status-dot.naranja { background: var(--amber); }
    .status-dot.gris    { background: var(--text-dim); }

    /* Sin choferes vacío */
    .vacío-estado {
        padding: 30px 16px;
        text-align: center;
        color: var(--text-muted);
    }

    .vacío-estado .icon { font-size: 2rem; margin-bottom: 8px; }
    .vacío-estado p { font-size: .83rem; line-height: 1.5; }

    /* Sin señal warning */
    .sin-senial {
        background: var(--amber-bg);
        border: 1px solid #fde68a;
        border-radius: var(--radius-sm);
        padding: 10px 12px;
    }

    .sin-senial h4 {
        font-size: .78rem; font-weight: 700;
        color: #92400e; margin-bottom: 6px;
    }

    .sin-senial-item {
        font-size: .75rem; color: #78350f;
        padding: 2px 0;
        display: flex; align-items: center; gap: 5px;
    }
</style>

<div class="mapa-layout">

    {{-- ── MAPA ──────────────────────────────────────────────────────── --}}
    <div class="mapa-box">
        <div class="mapa-loader" id="mapaLoader">
            <div class="spinner"></div>
            <div style="font-size:.85rem;color:var(--text-muted)">Cargando mapa de Huata…</div>
        </div>

        <div id="map"></div>

        <div class="mapa-badge" id="badgeRefresh">
            <span class="pulse" id="pulseDot"></span>
            Actualiza cada 15 s &nbsp;·&nbsp; <strong id="tsUltima">—</strong>
        </div>
    </div>

    {{-- ── PANEL LATERAL ─────────────────────────────────────────────── --}}
    <aside class="panel">

        {{-- Activos --}}
        <div class="panel-stat">
            <div class="ps-label">Choferes activos ahora</div>
            <div class="ps-value" style="color:var(--green)" id="contActivos">
                {{ $choferes->count() }}
            </div>
            <div class="ps-sub">
                @if($choferes->count() > 0)
                    {{ $choferes->count() }} en camino · actualizados &lt;30 min
                @else
                    Ninguno con señal activa
                @endif
            </div>
        </div>

        {{-- Total choferes --}}
        <div class="panel-stat">
            <div class="ps-label">Total choferes registrados</div>
            <div class="ps-value" style="color:var(--blue)">{{ $totalChoferes }}</div>
            <div class="ps-sub">En el sistema ProLech</div>
        </div>

        {{-- Lista de choferes con tracking --}}
        <div class="chofer-list">
            <div class="chofer-list-header">🚛 En ruta ahora</div>
            <div class="chofer-items" id="choferList">

                @forelse($todosConTracking as $ch)
                @php
                    $activo  = $ch['activo'];
                    $inicial = strtoupper(substr($ch['nombre'], 0, 1));
                    $mins    = $ch['minutos_desde'];
                    $dotClass = $activo ? 'verde' : ($mins < 120 ? 'naranja' : 'gris');
                    $statusText = $activo
                        ? 'En camino · ' . $ch['ultima_actualizacion']
                        : 'Última señal: ' . $ch['ultima_actualizacion'];
                @endphp
                <div class="chofer-item {{ $activo ? '' : '' }}"
                     id="item-{{ $ch['id'] }}"
                     onclick="centrarChofer({{ $ch['id'] }})">
                    <div class="chofer-avatar {{ $activo ? 'activo' : 'inactivo' }}">
                        {{ $inicial }}
                    </div>
                    <div class="chofer-info">
                        <div class="chofer-nombre">{{ $ch['nombre'] }}</div>
                        <div class="chofer-meta">
                            <span class="status-dot {{ $dotClass }}"></span>
                            {{ $statusText }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="vacío-estado">
                    <div class="icon">📡</div>
                    <p>Sin datos de ubicación aún.<br>Los choferes deben abrir la app y activar el GPS.</p>
                </div>
                @endforelse

            </div>
        </div>

        @if($sinSenial->isNotEmpty())
        <div class="sin-senial">
            <h4>⚠️ Sin señal (más de 30 min)</h4>
            @foreach($sinSenial as $ss)
            <div class="sin-senial-item">
                <span>🚛</span>
                <span>{{ $ss['nombre'] }}</span>
                <span style="margin-left:auto;font-size:.7rem">{{ $ss['ultima_actualizacion'] }}</span>
            </div>
            @endforeach
        </div>
        @endif

    </aside>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    'use strict';

    // ── Datos del servidor ─────────────────────────────────────────────
    var CENTRO   = [{{ $centroHuata['lat'] }}, {{ $centroHuata['lng'] }}];
    var INICIAL  = {!! $choforesJson !!};
    var AJAX_URL = "{{ route('admin.mapa.ubicaciones') }}";

    var map, marcadores = {}, trazos = {}, seleccionado = null;
    // Un color por chofer para distinguir los recorridos
    var COLORES = ['#1f6feb', '#f2a541', '#8b5cf6', '#ef4444', '#10b981', '#0ea5e9', '#ec4899'];
    function colorDe(id) { return COLORES[id % COLORES.length]; }

    // ── Iniciar mapa ───────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        map = L.map('map', { zoomControl: true }).setView(CENTRO, 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://osm.org">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(map);

        document.getElementById('mapaLoader').classList.add('oculto');

        INICIAL.forEach(function (ch) { colocarMarcador(ch); });

        // Ajustar vista si hay marcadores
        var keys = Object.keys(marcadores);
        if (keys.length > 0) {
            var grupo = L.featureGroup(keys.map(function (k) { return marcadores[k]; }));
            map.fitBounds(grupo.getBounds().pad(.2));
        }

        // Polling cada 15 s
        setInterval(actualizarUbicaciones, 15000);
    });

    // ── Icono personalizado ────────────────────────────────────────────
    function icono(activo) {
        var bg   = activo ? '#1f6feb' : '#94a3b8';
        var brd  = activo ? '#dbe9ff' : '#e2e8f0';
        var svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 42 42">'
                 + '<circle cx="21" cy="21" r="19" fill="' + bg + '" stroke="' + brd + '" stroke-width="3"/>'
                 + '<text x="21" y="28" text-anchor="middle" font-size="18">🚛</text>'
                 + '</svg>';
        return L.divIcon({
            className: 'custom-div-icon',
            html: svg,
            iconSize: [42, 42],
            iconAnchor: [21, 21],
            popupAnchor: [0, -24]
        });
    }

    // ── Colocar / actualizar marcador ──────────────────────────────────
    function dibujarRecorrido(ch) {
        var puntos = ch.recorrido || [];
        if (puntos.length < 2) return;
        if (trazos[ch.id]) {
            trazos[ch.id].setLatLngs(puntos);
        } else {
            trazos[ch.id] = L.polyline(puntos, {
                color: colorDe(ch.id), weight: 5, opacity: .85, lineJoin: 'round'
            }).addTo(map);
            // Punto de inicio del recorrido del día
            L.circleMarker(puntos[0], { radius: 6, color: colorDe(ch.id), fillColor: '#fff', fillOpacity: 1, weight: 3 })
             .bindTooltip('Inicio de ' + ch.nombre).addTo(map);
        }
    }

    function colocarMarcador(ch) {
        dibujarRecorrido(ch);
        var popup = buildPopup(ch);
        if (marcadores[ch.id]) {
            marcadores[ch.id].setLatLng([ch.latitud, ch.longitud]);
            marcadores[ch.id].setIcon(icono(ch.activo));
            marcadores[ch.id].setPopupContent(popup);
        } else {
            var m = L.marker([ch.latitud, ch.longitud], { icon: icono(ch.activo) }).addTo(map);
            m.bindPopup(popup, { maxWidth: 220 });
            m.on('click', function () { resaltarItem(ch.id); });
            marcadores[ch.id] = m;
        }
    }

    function buildPopup(ch) {
        var statusColor = ch.activo ? '#1f6feb' : '#94a3b8';
        var statusText  = ch.activo ? '● En camino' : '● Sin señal';
        return '<div style="font-family:Inter,sans-serif;min-width:190px;padding:2px">'
             + '<div style="font-weight:700;font-size:.95rem;margin-bottom:8px">🚛 ' + ch.nombre + '</div>'
             + '<div style="font-size:.78rem;color:#64748b;margin-bottom:3px">📍 '
             +   parseFloat(ch.latitud).toFixed(5) + ', ' + parseFloat(ch.longitud).toFixed(5)
             + '</div>'
             + '<div style="font-size:.78rem;color:' + statusColor + ';font-weight:600;margin-bottom:3px">'
             +   statusText
             + '</div>'
             + (ch.zona ? '<div style="font-size:.78rem;color:#334155;margin-bottom:3px">🗺️ Zona: <b>' + ch.zona + '</b></div>' : '')
             + '<div style="font-size:.78rem;color:#334155;margin-bottom:3px">〰️ ' + (ch.recorrido ? ch.recorrido.length : 0) + ' puntos hoy</div>'
             + '<div style="font-size:.72rem;color:#94a3b8">' + ch.ultima_actualizacion + '</div>'
             + '</div>';
    }

    // ── Centrar en chofer ──────────────────────────────────────────────
    window.centrarChofer = function (id) {
        var m = marcadores[id];
        if (!m) return;
        map.flyTo(m.getLatLng(), 15, { duration: .8 });
        m.openPopup();
        resaltarItem(id);
    };

    function resaltarItem(id) {
        document.querySelectorAll('.chofer-item').forEach(function (el) {
            el.classList.remove('selected');
        });
        var el = document.getElementById('item-' + id);
        if (el) { el.classList.add('selected'); el.scrollIntoView({ block: 'nearest' }); }
        seleccionado = id;
    }

    // ── Polling AJAX ───────────────────────────────────────────────────
    function actualizarUbicaciones() {
        fetch(AJAX_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                // Hora
                var d = new Date();
                document.getElementById('tsUltima').textContent =
                    d.getHours().toString().padStart(2,'0') + ':' +
                    d.getMinutes().toString().padStart(2,'0') + ':' +
                    d.getSeconds().toString().padStart(2,'0');

                document.getElementById('contActivos').textContent = data.total_activos;

                // Actualizar marcadores
                data.choferes.forEach(function (ch) { colocarMarcador(ch); });

                // Quitar marcadores de choferes que ya no reportan
                var idsActuales = data.choferes.map(function (c) { return String(c.id); });
                Object.keys(marcadores).forEach(function (id) {
                    if (idsActuales.indexOf(id) === -1) {
                        map.removeLayer(marcadores[id]);
                        delete marcadores[id];
                        if (trazos[id]) { map.removeLayer(trazos[id]); delete trazos[id]; }
                    }
                });
            })
            .catch(function () {
                // Sin red — silenCioso
            });
    }
}());
</script>

@endsection
