@extends('layouts.admin')

@section('title', 'Producción — ERP ProLech')
@section('page-title', '🏭 Módulo de Producción')

@section('content')

{{-- ══════════════════════════════════════════════════════════════════════
     FLASH MESSAGES
══════════════════════════════════════════════════════════════════════ --}}
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

{{-- ══════════════════════════════════════════════════════════════════════
     GRID PRINCIPAL — FORMULARIO + PROYECCIÓN
══════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:1fr 380px;gap:22px;align-items:start;margin-bottom:28px">

    {{-- ── FORMULARIO ──────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h3>➕ Registrar nuevo lote</h3>
            <span style="font-size:.75rem;color:var(--text-muted)">
                Código generado automáticamente · PRD-{{ now()->format('Ymd') }}-NNN
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.produccion.lotes.store') }}"
                  id="form-lote" autocomplete="off">
                @csrf

                {{-- RECETA --}}
                <div style="margin-bottom:18px">
                    <label for="receta_id" style="display:block;font-size:.72rem;font-weight:700;
                        text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:6px">
                        Producto / Receta <span style="color:var(--red)">*</span>
                    </label>
                    <select id="receta_id" name="receta_id" required
                            style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('receta_id') ? 'var(--red)' : 'var(--border)' }};
                                   border-radius:8px;font-size:.9rem;background:#fff;font-family:inherit">
                        <option value="">— Selecciona un producto —</option>
                        @foreach($recetas as $receta)
                            <option value="{{ $receta->id }}"
                                data-rendimiento="{{ $receta->rendimiento_esperado_unidades }}"
                                data-nombre="{{ $receta->nombre }}"
                                data-detalles="{{ json_encode(
                                    $receta->detalles->map(fn($d) => [
                                        'nombre'         => $d->insumo->nombre,
                                        'unidad'         => $d->insumo->unidad_medida,
                                        'cantidad_base'  => (float) $d->cantidad_requerida,
                                        'costo_unitario' => (float) $d->insumo->costo_unitario,
                                        'stock_actual'   => (float) $d->insumo->stock_actual,
                                    ])
                                ) }}"
                                {{ old('receta_id') == $receta->id ? 'selected' : '' }}>
                                {{ $receta->nombre }}
                                &nbsp;·&nbsp; rinde {{ $receta->rendimiento_esperado_unidades }}
                                {{ $receta->rendimiento_esperado_unidades == 1 ? 'unidad' : 'unidades' }} base
                            </option>
                        @endforeach
                    </select>
                    @error('receta_id')<span style="color:var(--red);font-size:.75rem">{{ $message }}</span>@enderror

                    {{-- Chips de recetas rápidas --}}
                    @if($recetas->count() > 0)
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px">
                        @foreach($recetas->take(6) as $r)
                        <button type="button"
                                onclick="document.getElementById('receta_id').value='{{ $r->id }}';
                                         document.getElementById('receta_id').dispatchEvent(new Event('change'))"
                                style="padding:4px 12px;border:1.5px solid var(--border);background:var(--bg);
                                       border-radius:20px;font-size:.75rem;cursor:pointer;font-weight:500;
                                       color:var(--text-2);transition:all .15s"
                                onmouseover="this.style.borderColor='var(--brand-light)';this.style.color='var(--brand-light)'"
                                onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-2)'">
                            {{ $r->nombre }}
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- CANTIDAD --}}
                <div style="margin-bottom:18px">
                    <label for="cantidad_producida" style="display:block;font-size:.72rem;font-weight:700;
                        text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:6px">
                        Cantidad a producir <span style="color:var(--red)">*</span>
                        <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.7rem">
                            — unidades / litros / kg según receta
                        </span>
                    </label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="number" id="cantidad_producida" name="cantidad_producida"
                               min="1" max="999999" step="1" required
                               value="{{ old('cantidad_producida') }}"
                               placeholder="Ej: 1300"
                               style="flex:1;padding:12px 16px;border:1.5px solid {{ $errors->has('cantidad_producida') ? 'var(--red)' : 'var(--border)' }};
                                      border-radius:8px;font-size:1.4rem;font-weight:800;
                                      letter-spacing:-0.5px;font-family:inherit">
                        <span id="lbl-unidad"
                              style="font-size:.85rem;color:var(--text-muted);white-space:nowrap;
                                     min-width:60px;text-align:center">unidades</span>
                    </div>
                    @error('cantidad_producida')<span style="color:var(--red);font-size:.75rem">{{ $message }}</span>@enderror

                    {{-- Botones de cantidad rápida --}}
                    <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
                        @foreach([100, 500, 1000, 1300, 1400, 2000] as $v)
                        <button type="button"
                                onclick="document.getElementById('cantidad_producida').value='{{ $v }}';
                                         document.getElementById('cantidad_producida').dispatchEvent(new Event('input'))"
                                style="padding:3px 11px;border:1.5px solid var(--border);background:var(--bg);
                                       border-radius:6px;font-size:.75rem;cursor:pointer;font-weight:600;
                                       color:var(--text-muted)">{{ number_format($v) }}</button>
                        @endforeach
                    </div>
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
                        💾 Registrar lote
                    </button>
                    <button type="reset" class="btn btn-ghost"
                            onclick="setTimeout(actualizarProyeccion, 15)">
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
                    Selecciona un producto para ver<br>los insumos que se consumirán.
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
                    ⛔ Stock insuficiente — repón antes de producir.
                </div>
                <div id="proy-alerta-ok"
                     style="display:none;margin-top:12px;padding:10px 13px;
                            background:#dcfce7;border:1px solid #86efac;border-radius:8px;
                            font-size:.8rem;color:#15803d;font-weight:600">
                    ✅ Stock suficiente. Puedes registrar el lote.
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
                            onclick="seleccionarReceta({{ $r->id }})"
                            style="padding:4px 12px;background:var(--brand-light);color:#fff;
                                   border:none;border-radius:6px;font-size:.75rem;cursor:pointer;
                                   font-weight:600">
                        Usar
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
                        <th style="text-align:center">Unidades</th>
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
                        <td style="font-weight:800;text-align:center;
                                   font-size:1rem;color:var(--text)">
                            {{ number_format($lote->cantidad_producida) }}
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

{{-- ══════════════════════════════════════════════════════════════════════
     JAVASCRIPT — Proyección en tiempo real (BUG CORREGIDO)
     FIX: la proyección se muestra en cuanto se selecciona la receta,
     aunque la cantidad aún esté en 0. El botón queda deshabilitado
     hasta que la cantidad > 0 Y el stock alcance.
══════════════════════════════════════════════════════════════════════ --}}
<script>
(function () {
    'use strict';

    var selReceta  = document.getElementById('receta_id');
    var inputCant  = document.getElementById('cantidad_producida');
    var btnGuardar = document.getElementById('btn-guardar');
    var lblUnidad  = document.getElementById('lbl-unidad');

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

    function fmt(v, d) {
        d = d !== undefined ? d : 2;
        return parseFloat(v).toLocaleString('es-PE', {
            minimumFractionDigits: d, maximumFractionDigits: d
        });
    }
    function sol(v) { return 'S/ ' + fmt(v, 2); }

    function actualizarProyeccion() {
        var opcion   = selReceta.options[selReceta.selectedIndex];
        var recetaId = parseInt(selReceta.value, 10);

        /* ── Sin receta → vacío total ──────────────────────────────────── */
        if (!recetaId) {
            proEmpty.style.display   = 'block';
            proContent.style.display = 'none';
            badgeOk.style.display    = 'none';
            badgeErr.style.display   = 'none';
            setBoton(false, 'Selecciona una receta');
            if (lblUnidad) lblUnidad.textContent = 'unidades';
            return;
        }

        /* ── Cantidad (puede ser 0 mientras escribe) ───────────────────── */
        var cantidad = Math.max(0, parseInt(inputCant.value, 10) || 0);

        /* Leer detalles del <option> */
        var detalles;
        try { detalles = JSON.parse(opcion.dataset.detalles || '[]'); }
        catch (e) { detalles = []; }

        var rendBase = parseInt(opcion.dataset.rendimiento, 10) || 1;
        var nombre   = opcion.dataset.nombre || opcion.text;

        /* Mostrar nombre del producto seleccionado */
        if (proProducto) {
            proProducto.textContent = '🧀 ' + nombre;
        }

        /* Actualizar etiqueta de unidad (primer insumo define la unidad de producción) */
        if (lblUnidad && detalles.length > 0) {
            lblUnidad.textContent = 'unidades';
        }

        /* ── Calcular insumos (regla de 3) ─────────────────────────────── */
        var hayProblema = false;
        var costoTotal  = 0;
        var filas       = [];

        detalles.forEach(function (d) {
            var cantNecesaria = cantidad > 0
                ? (d.cantidad_base / rendBase) * cantidad
                : 0;
            var costoParcial = cantNecesaria * d.costo_unitario;
            var alcanza      = cantidad === 0 || d.stock_actual >= cantNecesaria;
            var faltante     = alcanza ? 0 : cantNecesaria - d.stock_actual;
            var pct          = d.stock_actual > 0 && cantNecesaria > 0
                ? Math.min(100, Math.round((cantNecesaria / d.stock_actual) * 100))
                : 0;

            if (cantidad > 0 && !alcanza) hayProblema = true;
            costoTotal += costoParcial;

            filas.push({ nombre: d.nombre, unidad: d.unidad,
                cantNecesaria: cantNecesaria, costoParcial: costoParcial,
                stockActual: d.stock_actual, alcanza: alcanza,
                faltante: faltante, pct: pct });
        });

        /* ── Renderizar lista de insumos ───────────────────────────────── */
        proLista.innerHTML = filas.length === 0
            ? '<div style="color:var(--text-muted);font-size:.82rem;text-align:center;padding:10px">Sin ingredientes configurados</div>'
            : filas.map(function (f) {
                var cFondo  = f.alcanza ? '#f0fdf4' : '#fef2f2';
                var cBorde  = f.alcanza ? '#86efac' : '#fca5a5';
                var cTexto  = f.alcanza ? '#15803d' : '#dc2626';
                var icono   = cantidad === 0 ? '📦' : (f.alcanza ? '✅' : '⚠️');
                var cBarra  = f.pct > 85 ? '#f59e0b' : '#22c55e';
                if (!f.alcanza) cBarra = '#ef4444';

                return '<div style="padding:10px 12px;border:1px solid ' + cBorde + ';' +
                    'border-radius:8px;background:' + cFondo + ';font-size:.81rem">' +

                    '<div style="display:flex;justify-content:space-between;' +
                    'align-items:center;margin-bottom:6px">' +
                    '<span style="font-weight:700">' + icono + ' ' + f.nombre + '</span>' +
                    '<span style="font-weight:700;color:' + cTexto + '">' +
                    (cantidad > 0 ? sol(f.costoParcial) : '—') + '</span></div>' +

                    '<div style="display:grid;grid-template-columns:1fr 1fr;gap:3px 10px;' +
                    'font-size:.76rem;color:var(--text-muted);margin-bottom:7px">' +
                    '<div>Necesario: <strong style="color:#0f172a">' +
                    (cantidad > 0 ? fmt(f.cantNecesaria, 3) + ' ' + f.unidad : '—') +
                    '</strong></div>' +
                    '<div>En stock: <strong style="color:' + cTexto + '">' +
                    fmt(f.stockActual, 2) + ' ' + f.unidad + '</strong></div>' +
                    ((!f.alcanza && cantidad > 0) ? '<div style="color:#dc2626;font-weight:600;grid-column:1/-1">' +
                    '⛔ Faltan ' + fmt(f.faltante, 3) + ' ' + f.unidad + '</div>' : '') +
                    '</div>' +

                    '<div style="background:rgba(0,0,0,.07);border-radius:4px;height:4px;overflow:hidden">' +
                    '<div style="width:' + (cantidad > 0 ? f.pct : 0) + '%;height:100%;' +
                    'background:' + cBarra + ';border-radius:4px;transition:width .4s ease"></div></div>' +
                    '</div>';
            }).join('');

        /* ── Totales y alertas ─────────────────────────────────────────── */
        proTotal.textContent  = cantidad > 0 ? sol(costoTotal) : 'S/ —';
        proTotal.style.color  = hayProblema ? '#dc2626' : 'var(--brand-light)';
        porUnidad.textContent = (cantidad > 0 && costoTotal > 0)
            ? sol(costoTotal / cantidad) + ' por unidad'
            : 'Ingresa la cantidad para ver el costo';

        if (cantidad === 0) {
            proAErr.style.display  = 'none';
            proAOk.style.display   = 'none';
            badgeOk.style.display  = 'none';
            badgeErr.style.display = 'none';
        } else if (hayProblema) {
            proAErr.style.display  = 'block';
            proAOk.style.display   = 'none';
            badgeErr.style.display = 'inline-block';
            badgeOk.style.display  = 'none';
        } else {
            proAErr.style.display  = 'none';
            proAOk.style.display   = 'block';
            badgeOk.style.display  = 'inline-block';
            badgeErr.style.display = 'none';
        }

        /* ── Mostrar panel ─────────────────────────────────────────────── */
        proEmpty.style.display   = 'none';
        proContent.style.display = 'block';

        /* ── Botón guardar ─────────────────────────────────────────────── */
        var puedeGuardar = recetaId > 0 && cantidad > 0 && !hayProblema;
        setBoton(puedeGuardar, puedeGuardar ? '💾 Registrar lote' : null);
    }

    function setBoton(activo, texto) {
        btnGuardar.disabled      = !activo;
        btnGuardar.style.opacity = activo ? '1' : '.45';
        btnGuardar.style.cursor  = activo ? 'pointer' : 'not-allowed';
        if (texto) btnGuardar.textContent = texto;
        if (activo) btnGuardar.textContent = '💾 Registrar lote';
    }

    /* Seleccionar receta desde los botones "Usar" del panel lateral */
    window.seleccionarReceta = function (id) {
        selReceta.value = id;
        selReceta.dispatchEvent(new Event('change'));
        selReceta.scrollIntoView({ behavior: 'smooth', block: 'center' });
        selReceta.focus();
    };

    window.actualizarProyeccion = actualizarProyeccion;

    selReceta.addEventListener('change', actualizarProyeccion);
    inputCant.addEventListener('input',  actualizarProyeccion);

    /* Restaurar estado al volver con errores (old values) */
    if (selReceta.value) actualizarProyeccion();

}());
</script>

@endsection
