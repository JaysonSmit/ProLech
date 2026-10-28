@extends('layouts.admin')

@section('title', 'Producción — ERP ProLech')
@section('page-title', '🏭 Módulo de Producción')

@section('content')

{{-- ══════════════════════════════════════════════════════════════════════
     FLASH MESSAGES
══════════════════════════════════════════════════════════════════════ --}}
@if(isset($porVencer) && $porVencer->isNotEmpty())
<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:14px 18px;margin-bottom:16px">
    <div style="font-weight:800;color:#92400e;margin-bottom:6px">⏰ Productos por vencer en la tienda</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
        @foreach($porVencer as $f)
        <span style="background:#fff;border:1px solid #fde68a;border-radius:10px;padding:6px 10px;font-size:.8rem">
            <strong>{{ $f['producto']->nombre }}</strong> · {{ $f['en_stock'] }} und. · lote {{ $f['lote']->codigo_lote }} ·
            <span style="color:{{ $f['estado'] === 'vencido' ? '#b91c1c' : '#b45309' }};font-weight:700">{{ $f['estado'] === 'vencido' ? 'vencido' : ($f['dias'] === 0 ? 'vence hoy' : 'vence en '.$f['dias'].' días') }}</span>
        </span>
        @endforeach
    </div>
    <div style="font-size:.75rem;color:#92400e;margin-top:6px">Véndelos primero (ofertas en tienda o en planta). Lo vencido se retira solo cada mañana como merma.</div>
</div>
@endif
@if(session('success'))
<div id="msg-ok" style="display:flex;align-items:center;gap:12px;padding:14px 18px;
    margin-bottom:20px;background:#dcfce7;border:1px solid #86efac;border-radius:10px;
    color:#15803d;font-weight:600;font-size:.9rem;animation:slideDown .25s ease">
    ✅ <span>{{ session('success') }}</span>
    <button onclick="this.parentNode.remove()" style="margin-left:auto;background:none;
        border:none;cursor:pointer;color:#15803d;font-size:1.2rem;line-height:1">×</button>
</div>
@endif
@if($errors->has('stock'))
<div id="msg-err" style="display:flex;align-items:flex-start;gap:12px;padding:14px 18px;
    margin-bottom:20px;background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;
    color:#dc2626;font-size:.88rem;animation:slideDown .25s ease">
    <span style="font-size:1.3rem;flex-shrink:0">⛔</span>
    <div><strong style="display:block;margin-bottom:3px">Lote no registrado</strong>
    {{ $errors->first('stock') }}</div>
    <button onclick="this.parentNode.remove()" style="margin-left:auto;background:none;
        border:none;cursor:pointer;color:#dc2626;font-size:1.2rem;line-height:1;flex-shrink:0">×</button>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     HEADER — TABS DE NAVEGACIÓN
══════════════════════════════════════════════════════════════════════ --}}
<div style="display:flex;align-items:center;justify-content:space-between;
            margin-bottom:22px;flex-wrap:wrap;gap:12px">
    <div style="display:flex;gap:6px;background:#f1f5f9;border-radius:10px;padding:4px">
        <a href="{{ route('admin.produccion.lotes.create') }}"
           style="padding:7px 18px;border-radius:8px;font-size:.85rem;font-weight:600;
                  text-decoration:none;background:#fff;color:var(--brand-light);
                  box-shadow:0 1px 3px rgba(0,0,0,.1)">
            🏭 Producción
        </a>
        <a href="{{ route('admin.recetas.index') }}"
           style="padding:7px 18px;border-radius:8px;font-size:.85rem;font-weight:600;
                  text-decoration:none;color:var(--text-muted)">
            📋 Recetas
        </a>
    </div>
    <a href="{{ route('admin.recetas.create') }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;
              background:var(--bg);border:1.5px solid var(--border);color:var(--text-2);
              border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none">
        ➕ Nueva Receta
    </a>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     MÉTRICAS RÁPIDAS DEL MES
══════════════════════════════════════════════════════════════════════ --}}
@php
    $totalUnidades = $lotes->getCollection()->sum('cantidad_producida');
    $totalCosto    = $lotes->getCollection()->sum('costo_total');
    $lotesHoy      = $lotes->getCollection()->where('fecha_produccion', today())->count();
@endphp
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:22px">
    <div class="stat-card verde">
        <div class="label">Lotes registrados</div>
        <div class="value">{{ $lotes->total() }}</div>
        <div class="unit">total acumulado · {{ $lotesHoy }} hoy</div>
    </div>
    <div class="stat-card azul">
        <div class="label">Unidades producidas</div>
        <div class="value">{{ number_format($totalUnidades) }}</div>
        <div class="unit">en los últimos registros</div>
    </div>
    <div class="stat-card naranja">
        <div class="label">Costo total acumulado</div>
        <div class="value" style="font-size:1.5rem">S/ {{ number_format($totalCosto, 0) }}</div>
        <div class="unit">materia prima + insumos</div>
    </div>
    <div class="stat-card" style="background:#faf5ff;border-color:#e9d5ff">
        <div class="label" style="color:#7c3aed">Recetas activas</div>
        <div class="value" style="color:#7c3aed">{{ $recetas->count() }}</div>
        <div class="unit">productos configurados</div>
    </div>
</div>

<div style="margin-bottom:22px">@include('admin.costos._costo_leche')</div>

{{-- ══════════════════════════════════════════════════════════════════════
     GRID PRINCIPAL — FORMULARIO + PROYECCIÓN
══════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:1fr 380px;gap:22px;align-items:start;margin-bottom:28px">

    {{-- ── FORMULARIO ──────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h3>➕ Nueva orden de producción</h3>
            <span style="font-size:.75rem;color:var(--text-muted)">
                Un lote por producto · PRD-{{ now()->format('Ymd') }}-NNN · orden ORD-{{ now()->format('Ymd') }}-NNN
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.produccion.lotes.store') }}"
                  id="form-lote" autocomplete="off">
                @csrf

                {{-- PRODUCTOS DE LA ORDEN (varias líneas) --}}
                <div style="margin-bottom:18px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <label style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">
                            Productos a producir <span style="color:var(--red)">*</span>
                            <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.7rem">
                                — puedes agregar varios en la misma orden
                            </span>
                        </label>
                        <span id="lineas-contador" style="font-size:.75rem;color:var(--text-muted)"></span>
                    </div>

                    <div id="lineas" style="display:flex;flex-direction:column;gap:10px"></div>
                    <style>
                        #lineas select, #lineas input { min-width: 0; }
                        @media (max-width: 700px) {
                            #lineas .linea { grid-template-columns: 1fr 1fr 36px !important; }
                            #lineas .linea select { grid-column: 1 / -1; }
                        }
                    </style>

                    @if($errors->has('lineas') || $errors->has('lineas.*'))
                        <span style="color:var(--red);font-size:.78rem;display:block;margin-top:6px">
                            {{ $errors->first('lineas') ?: collect($errors->get('lineas.*'))->flatten()->first() }}
                        </span>
                    @endif

                    <button type="button" onclick="agregarLinea()" id="btn-agregar"
                            style="margin-top:10px;width:100%;padding:10px;border:1.5px dashed var(--brand-light);
                                   background:#f5f9ff;color:var(--brand-light);border-radius:10px;font-weight:700;
                                   font-size:.85rem;cursor:pointer;font-family:inherit">
                        ➕ Agregar otro producto
                    </button>

                    {{-- Chips: agregan el producto a la orden --}}
                    @if($recetas->count() > 0)
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px">
                        @foreach($recetas as $r)
                        <button type="button" onclick="agregarLinea({{ $r->id }})"
                                style="padding:4px 12px;border:1.5px solid var(--border);background:var(--bg);
                                       border-radius:20px;font-size:.75rem;cursor:pointer;font-weight:500;color:var(--text-2)">
                            + {{ $r->nombre }}
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- OBSERVACIONES --}}
                <div style="margin-bottom:22px">
                    <label for="observaciones" style="display:block;font-size:.72rem;font-weight:700;
                        text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:6px">
                        Observaciones
                    </label>
                    <textarea id="observaciones" name="observaciones" rows="2" maxlength="1000"
                              placeholder="Ej: Temperatura 14°C. Sin incidencias."
                              style="width:100%;padding:9px 12px;border:1.5px solid var(--border);
                                     border-radius:8px;font-size:.88rem;font-family:inherit;resize:vertical">{{ old('observaciones') }}</textarea>
                </div>

                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                    <button type="submit" id="btn-guardar" disabled
                            class="btn btn-primary btn-lg"
                            style="opacity:.45;cursor:not-allowed">
                        💾 Registrar producción
                    </button>
                    <button type="button" class="btn btn-ghost" onclick="limpiarOrden()">
                        Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── PANEL DE PROYECCIÓN ─────────────────────────────────────────── --}}
    <div style="position:sticky;top:76px;display:flex;flex-direction:column;gap:14px">

        <div class="card" id="card-proy">
            <div class="card-header" style="padding:14px 18px">
                <h3 style="font-size:.88rem">📊 Proyección de consumo</h3>
                <span id="proy-badge-ok"
                      style="display:none;background:#dcfce7;color:#15803d;
                             padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:700">
                    ✓ Stock OK
                </span>
                <span id="proy-badge-err"
                      style="display:none;background:#fef2f2;color:#dc2626;
                             padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:700">
                    ⚠ Insuficiente
                </span>
            </div>

            {{-- Placeholder --}}
            <div id="proy-empty" style="padding:36px 20px;text-align:center;color:var(--text-muted)">
                <div style="font-size:2.2rem;margin-bottom:8px">🧮</div>
                <div style="font-size:.83rem;line-height:1.5">
                    Agrega productos para ver<br>los insumos que se consumirán en total.
                </div>
            </div>

            {{-- Contenido --}}
            <div id="proy-content" style="display:none;padding:16px 18px">

                {{-- Producto seleccionado --}}
                <div id="proy-producto"
                     style="font-size:.82rem;font-weight:700;color:var(--brand-light);
                            margin-bottom:14px;padding-bottom:12px;
                            border-bottom:1px solid var(--border-soft)"></div>

                {{-- Costo total --}}
                <div style="text-align:center;margin-bottom:16px;
                            padding-bottom:14px;border-bottom:1px solid var(--border-soft)">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;
                                letter-spacing:.5px;color:var(--text-muted);margin-bottom:4px">
                        Costo total estimado
                    </div>
                    <div id="proy-total"
                         style="font-size:2.2rem;font-weight:800;color:var(--brand-light);
                                letter-spacing:-1px;line-height:1">
                        S/ 0.00
                    </div>
                    <div id="proy-por-unidad"
                         style="font-size:.76rem;color:var(--text-muted);margin-top:5px">
                        —
                    </div>
                </div>

                {{-- Lista de insumos --}}
                <div id="proy-lista" style="display:flex;flex-direction:column;gap:8px"></div>

                {{-- Alertas --}}
                <div id="proy-alerta-err"
                     style="display:none;margin-top:12px;padding:10px 13px;
                            background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;
                            font-size:.8rem;color:#dc2626;font-weight:600">
                    ⛔ Stock insuficiente para la orden completa — baja cantidades o repón insumos.
                </div>
                <div id="proy-alerta-ok"
                     style="display:none;margin-top:12px;padding:10px 13px;
                            background:#dcfce7;border:1px solid #86efac;border-radius:8px;
                            font-size:.8rem;color:#15803d;font-weight:600">
                    ✅ Stock suficiente para toda la orden.
                </div>
            </div>
        </div>

        {{-- Recetas rápidas --}}
        @if($recetas->count() > 0)
        <div class="card">
            <div class="card-header" style="padding:12px 16px">
                <h3 style="font-size:.85rem">📋 Productos disponibles</h3>
                <a href="{{ route('admin.recetas.index') }}"
                   style="font-size:.75rem;color:var(--brand-light);text-decoration:none;font-weight:600">
                    Gestionar →
                </a>
            </div>
            <div style="padding:10px 12px;display:flex;flex-direction:column;gap:4px">
                @foreach($recetas as $r)
                <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:7px 10px;border-radius:7px;transition:background .12s"
                     onmouseover="this.style.background='#f8fafc'"
                     onmouseout="this.style.background='transparent'">
                    <div>
                        <div style="font-size:.83rem;font-weight:600">{{ $r->nombre }}</div>
                        <div style="font-size:.72rem;color:var(--text-muted)">
                            {{ $r->detalles->count() }} insumos ·
                            rinde {{ $r->rendimiento_esperado_unidades }} u. base
                        </div>
                    </div>
                    <button type="button"
                            onclick="agregarLinea({{ $r->id }})"
                            style="padding:4px 12px;background:var(--brand-light);color:#fff;
                                   border:none;border-radius:6px;font-size:.75rem;cursor:pointer;
                                   font-weight:600">
                        + Agregar
                    </button>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     HISTORIAL DE LOTES
══════════════════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-header">
        <h3>📋 Historial de lotes producidos</h3>
        <span class="card-header-meta">{{ $lotes->total() }} registros en total</span>
    </div>

    @if($lotes->isEmpty())
        <div style="padding:48px;text-align:center;color:var(--text-muted)">
            <div style="font-size:3rem;margin-bottom:10px">🏭</div>
            <div style="font-weight:700;margin-bottom:4px">Sin lotes registrados aún</div>
            <div style="font-size:.85rem">Usa el formulario de arriba para registrar el primer lote.</div>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Fecha</th>
                        <th>Vence</th>
                        <th style="text-align:center">Unidades</th>
                        <th style="text-align:center">En tienda</th>
                        <th>Costo total</th>
                        <th>Costo / und.</th>
                        <th>Responsable</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lotes as $lote)
                    <tr>
                        <td>
                            <span style="font-family:monospace;font-weight:700;
                                         color:var(--brand-light);font-size:.82rem;
                                         background:#f0fdf4;padding:2px 8px;border-radius:5px">
                                {{ $lote->codigo_lote }}
                            </span>
                            @if($lote->orden_codigo)
                                <div style="font-size:.68rem;color:var(--text-muted);margin-top:3px">📦 {{ $lote->orden_codigo }}</div>
                            @endif
                        </td>
                        <td style="font-weight:600;font-size:.88rem">
                            {{ $lote->receta->nombre ?? '—' }}
                        </td>
                        <td style="white-space:nowrap;font-size:.85rem">
                            {{ $lote->fecha_produccion->format('d/m/Y') }}
                            <div style="font-size:.7rem;color:var(--text-muted)">
                                {{ $lote->fecha_produccion->isoFormat('dddd') }}
                            </div>
                        </td>
                        <td style="white-space:nowrap;font-size:.82rem">
                            @php $dias = $lote->diasParaVencer(); $est = $lote->estadoVencimiento(); @endphp
                            @if($lote->fecha_vencimiento)
                                {{ $lote->fecha_vencimiento->format('d/m/Y') }}
                                <div>
                                    <span style="font-size:.68rem;font-weight:700;padding:1px 7px;border-radius:10px;
                                        background:{{ $est === 'vencido' ? '#fee2e2' : ($est === 'por_vencer' ? '#fef3c7' : '#dcfce7') }};
                                        color:{{ $est === 'vencido' ? '#b91c1c' : ($est === 'por_vencer' ? '#b45309' : '#15803d') }}">
                                        {{ $est === 'vencido' ? 'Vencido' : ($dias === 0 ? 'Vence hoy' : "Faltan {$dias} días") }}
                                    </span>
                                </div>
                            @else — @endif
                        </td>
                        <td style="font-weight:800;text-align:center;
                                   font-size:1rem;color:var(--text)">
                            {{ number_format($lote->cantidad_producida) }}
                            @if($lote->merma)<div style="font-size:.68rem;color:#b91c1c;font-weight:600">−{{ $lote->merma }} merma</div>@endif
                        </td>
                        <td style="text-align:center;font-weight:700;color:var(--brand)">
                            {{ $enTienda[$lote->id] ?? 0 }}
                        </td>
                        <td>
                            <strong style="color:var(--amber)">
                                S/ {{ number_format($lote->costo_total, 2) }}
                            </strong>
                        </td>
                        <td style="color:var(--text-muted);font-size:.83rem">
                            @if($lote->cantidad_producida > 0)
                                S/ {{ number_format($lote->costo_total / $lote->cantidad_producida, 3) }}
                            @else —
                            @endif
                        </td>
                        <td style="font-size:.8rem;color:var(--text-muted)">
                            {{ $lote->responsable->name ?? '—' }}
                        </td>
                        <td>
                            <a href="{{ route('admin.produccion.lotes.show', $lote) }}"
                               class="btn btn-ghost btn-sm">Ver →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:14px 22px">{{ $lotes->links() }}</div>
    @endif
</div>

@php
    $recetasJs = $recetas->mapWithKeys(fn ($r) => [$r->id => [
        'nombre'      => $r->nombre,
        'rendimiento' => max(1, (int) $r->rendimiento_esperado_unidades),
        'insumos'     => $r->detalles->map(fn ($d) => [
            'id'             => $d->insumo->id,
            'nombre'         => $d->insumo->nombre,
            'unidad'         => $d->insumo->unidad_medida,
            'cantidad_base'  => (float) $d->cantidad_requerida,
            'costo_unitario' => (float) $d->insumo->costo_unitario,
            'stock_actual'   => (float) $d->insumo->stock_actual,
        ])->values(),
    ]]);
    $lineasPrevias = collect(old('lineas', []))->values();
@endphp
{{-- ══════════════════════════════════════════════════════════════════════
     JAVASCRIPT — Orden con varios productos y proyección sumada de insumos
     Los insumos compartidos (leche cruda, azúcar…) se suman entre productos:
     el botón solo se activa si alcanza el stock para TODA la orden.
══════════════════════════════════════════════════════════════════════ --}}
<script>
(function () {
    'use strict';

    var RECETAS = @json($recetasJs);
    var PREVIAS = @json($lineasPrevias);
    var contenedor = document.getElementById('lineas');
    var btnGuardar = document.getElementById('btn-guardar');
    var contador   = document.getElementById('lineas-contador');
    var proEmpty   = document.getElementById('proy-empty');
    var proContent = document.getElementById('proy-content');
    var proProducto= document.getElementById('proy-producto');
    var proTotal   = document.getElementById('proy-total');
    var porUnidad  = document.getElementById('proy-por-unidad');
    var proLista   = document.getElementById('proy-lista');
    var proAErr    = document.getElementById('proy-alerta-err');
    var proAOk     = document.getElementById('proy-alerta-ok');
    var badgeOk    = document.getElementById('proy-badge-ok');
    var badgeErr   = document.getElementById('proy-badge-err');
    var siguiente  = 0;

    function fmt(v, d) {
        d = d !== undefined ? d : 2;
        return Number(v).toLocaleString('es-PE', { minimumFractionDigits: d, maximumFractionDigits: d });
    }
    function sol(v) { return 'S/ ' + fmt(v, 2); }
    function esc(t) { return String(t).replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }

    function opciones(seleccion) {
        var html = '<option value="">— Producto —</option>';
        Object.keys(RECETAS).forEach(function (id) {
            html += '<option value="' + id + '"' + (String(seleccion) === id ? ' selected' : '') + '>' + esc(RECETAS[id].nombre) + '</option>';
        });
        return html;
    }

    window.agregarLinea = function (recetaId, cantidad) {
        // Si el producto ya está en la orden, solo se enfoca su cantidad
        if (recetaId) {
            var existente = contenedor.querySelector('select[data-receta-sel] option[value="' + recetaId + '"]:checked');
            if (existente) {
                existente.closest('.linea').querySelector('input[type=number]').focus();
                return;
            }
        }
        var i = siguiente++;
        var fila = document.createElement('div');
        fila.className = 'linea';
        fila.style.cssText = 'display:grid;grid-template-columns:1fr 150px auto 36px;gap:8px;align-items:center;' +
            'padding:10px;border:1.5px solid var(--border);border-radius:10px;background:#fff';
        fila.innerHTML =
            '<select data-receta-sel name="lineas[' + i + '][receta_id]" required ' +
            'style="padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:.88rem;font-family:inherit;background:#fff">' +
            opciones(recetaId || '') + '</select>' +
            '<input type="number" name="lineas[' + i + '][cantidad]" min="1" max="99999" step="1" required placeholder="Cantidad" ' +
            'value="' + (cantidad || '') + '" style="padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;' +
            'font-size:1rem;font-weight:800;font-family:inherit">' +
            '<span data-costo style="font-size:.78rem;color:var(--text-muted);white-space:nowrap;min-width:90px;text-align:right">—</span>' +
            '<button type="button" title="Quitar" style="border:none;background:#fef2f2;color:#dc2626;border-radius:8px;' +
            'height:36px;font-size:1rem;cursor:pointer">✕</button>';
        fila.querySelector('select').addEventListener('change', actualizarProyeccion);
        fila.querySelector('input').addEventListener('input', actualizarProyeccion);
        fila.querySelector('button').addEventListener('click', function () { fila.remove(); actualizarProyeccion(); });
        contenedor.appendChild(fila);
        actualizarProyeccion();
        (recetaId ? fila.querySelector('input') : fila.querySelector('select')).focus();
    };

    window.limpiarOrden = function () {
        contenedor.innerHTML = '';
        document.getElementById('observaciones').value = '';
        agregarLinea();
    };

    function leerLineas() {
        return Array.prototype.map.call(contenedor.querySelectorAll('.linea'), function (fila) {
            return {
                fila: fila,
                recetaId: fila.querySelector('select').value,
                cantidad: Math.max(0, parseInt(fila.querySelector('input').value, 10) || 0)
            };
        });
    }

    function actualizarProyeccion() {
        var lineas = leerLineas();
        var validas = lineas.filter(function (l) { return l.recetaId && RECETAS[l.recetaId] && l.cantidad > 0; });
        contador.textContent = lineas.length + (lineas.length === 1 ? ' producto' : ' productos');

        // Insumos sumados entre todos los productos
        var insumos = {}, costoTotal = 0, unidadesTotal = 0;
        lineas.forEach(function (l) {
            var costoLinea = 0;
            var r = RECETAS[l.recetaId];
            if (r && l.cantidad > 0) {
                r.insumos.forEach(function (d) {
                    var necesario = (d.cantidad_base / r.rendimiento) * l.cantidad;
                    var item = insumos[d.id] || (insumos[d.id] = {
                        nombre: d.nombre, unidad: d.unidad, stock: d.stock_actual, necesario: 0, costo: 0, productos: []
                    });
                    item.necesario += necesario;
                    item.costo += necesario * d.costo_unitario;
                    if (item.productos.indexOf(r.nombre) < 0) item.productos.push(r.nombre);
                    costoLinea += necesario * d.costo_unitario;
                });
                unidadesTotal += l.cantidad;
            }
            costoTotal += costoLinea;
            l.fila.querySelector('[data-costo]').textContent = costoLinea > 0 ? sol(costoLinea) : '—';
        });

        if (validas.length === 0) {
            proEmpty.style.display = 'block';
            proContent.style.display = 'none';
            badgeOk.style.display = badgeErr.style.display = 'none';
            setBoton(false);
            return;
        }

        var hayProblema = false;
        var filas = Object.keys(insumos).map(function (id) {
            var f = insumos[id];
            f.alcanza = f.stock >= f.necesario;
            f.pct = f.stock > 0 ? Math.min(100, Math.round(f.necesario / f.stock * 100)) : 100;
            if (!f.alcanza) hayProblema = true;
            return f;
        });

        proProducto.innerHTML = validas.map(function (l) {
            return '🧀 ' + esc(RECETAS[l.recetaId].nombre) + ' <span style="color:var(--text-muted);font-weight:500">× ' + fmt(l.cantidad, 0) + '</span>';
        }).join('<br>');

        proLista.innerHTML = filas.map(function (f) {
            var cFondo = f.alcanza ? '#f0fdf4' : '#fef2f2', cBorde = f.alcanza ? '#86efac' : '#fca5a5';
            var cTexto = f.alcanza ? '#15803d' : '#dc2626';
            var cBarra = !f.alcanza ? '#ef4444' : (f.pct > 85 ? '#f59e0b' : '#22c55e');
            return '<div style="padding:10px 12px;border:1px solid ' + cBorde + ';border-radius:8px;background:' + cFondo + ';font-size:.81rem">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">' +
                '<span style="font-weight:700">' + (f.alcanza ? '✅' : '⚠️') + ' ' + esc(f.nombre) + '</span>' +
                '<span style="font-weight:700;color:' + cTexto + '">' + sol(f.costo) + '</span></div>' +
                '<div style="display:grid;grid-template-columns:1fr 1fr;gap:3px 10px;font-size:.76rem;color:var(--text-muted);margin-bottom:7px">' +
                '<div>Necesario: <strong style="color:#0f172a">' + fmt(f.necesario, 3) + ' ' + esc(f.unidad) + '</strong></div>' +
                '<div>En stock: <strong style="color:' + cTexto + '">' + fmt(f.stock, 2) + ' ' + esc(f.unidad) + '</strong></div>' +
                (f.productos.length > 1 ? '<div style="grid-column:1/-1">Compartido por: ' + f.productos.map(esc).join(', ') + '</div>' : '') +
                (!f.alcanza ? '<div style="color:#dc2626;font-weight:600;grid-column:1/-1">⛔ Faltan ' + fmt(f.necesario - f.stock, 3) + ' ' + esc(f.unidad) + '</div>' : '') +
                '</div><div style="background:rgba(0,0,0,.07);border-radius:4px;height:4px;overflow:hidden">' +
                '<div style="width:' + f.pct + '%;height:100%;background:' + cBarra + ';border-radius:4px;transition:width .4s ease"></div></div></div>';
        }).join('');

        proTotal.textContent = sol(costoTotal);
        proTotal.style.color = hayProblema ? '#dc2626' : 'var(--brand-light)';
        porUnidad.textContent = validas.length + (validas.length === 1 ? ' producto · ' : ' productos · ') + fmt(unidadesTotal, 0) + ' unidades';
        proAErr.style.display = hayProblema ? 'block' : 'none';
        proAOk.style.display = hayProblema ? 'none' : 'block';
        badgeErr.style.display = hayProblema ? 'inline-block' : 'none';
        badgeOk.style.display = hayProblema ? 'none' : 'inline-block';
        proEmpty.style.display = 'none';
        proContent.style.display = 'block';

        var todasCompletas = lineas.every(function (l) { return l.recetaId && l.cantidad > 0; });
        setBoton(todasCompletas && !hayProblema, validas.length);
    }

    function setBoton(activo, cuantos) {
        btnGuardar.disabled = !activo;
        btnGuardar.style.opacity = activo ? '1' : '.45';
        btnGuardar.style.cursor = activo ? 'pointer' : 'not-allowed';
        btnGuardar.textContent = cuantos > 1 ? '💾 Registrar orden (' + cuantos + ' lotes)' : '💾 Registrar producción';
    }

    window.actualizarProyeccion = actualizarProyeccion;

    // Volver con errores: se restauran las líneas enviadas
    if (PREVIAS.length) {
        PREVIAS.forEach(function (l) { agregarLinea(l.receta_id, l.cantidad); });
    } else {
        agregarLinea();
    }
}());
</script>

@endsection
