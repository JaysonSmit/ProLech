@extends('layouts.admin')

@section('title', 'Ventas y Despachos')
@section('page-title', '💰 Ventas y Gestión de Despachos')

@section('content')

{{-- ── Tarifas y Métricas de Stock y Despacho ─────────────────────────────────── --}}
<div class="info-boxes" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:14px;margin-bottom:20px">
    <div class="info-box">
        <div class="ib-label">Tarifa Mayorista</div>
        <div class="ib-value">
            <span class="precio-tag">S/ {{ number_format($precios['mayorista'], 2) }} / kg</span>
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px">Destinos regionales</div>
    </div>

    <div class="info-box">
        <div class="ib-label">Tarifa Público General</div>
        <div class="ib-value">
            <span class="precio-tag">S/ {{ number_format($precios['publico'], 2) }} / kg</span>
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px">Venta directa planta</div>
    </div>

    <div class="info-box" style="border-left: 4px solid {{ $alertaStockQuesos ? 'var(--red)' : 'var(--green)' }}">
        <div class="ib-label">Stock Quesos Planta</div>
        <div class="ib-value" style="color:{{ $alertaStockQuesos ? 'var(--red)' : 'var(--green)' }}">
            {{ number_format($stockActualQuesos, 1) }} moldes
        </div>
        <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
            Reserva mín: {{ $stockMinimoQuesos }} moldes
            @if($alertaStockQuesos)
                <span style="color:var(--red);font-weight:700">· ⚠️ Stock crítico</span>
            @endif
        </div>
    </div>

    <div class="info-box" style="border-left: 4px solid #f59e0b">
        <div class="ib-label">En Preparación</div>
        <div class="ib-value" style="color:#d97706">
            {{ $despachosPendientes }}
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px">📦 Lotes por embalar</div>
    </div>

    <div class="info-box" style="border-left: 4px solid #3b82f6">
        <div class="ib-label">En Camino</div>
        <div class="ib-value" style="color:#2563eb">
            {{ $despachosEnCamino }}
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px">🚚 En ruta a destino</div>
    </div>

    <div class="info-box" style="border-left: 4px solid #10b981">
        <div class="ib-label">Entregados</div>
        <div class="ib-value" style="color:#059669">
            {{ $despachosEntregados }}
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px">✅ Conforme en destino</div>
    </div>
</div>

{{-- ── Resumen de Distribución por Mercado Destino ─────────────────────────────── --}}
@if($resumenDestino->isNotEmpty())
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h3>📍 Distribución por Mercados de Destino</h3>
        <span class="card-header-meta">Resumen de volumen y facturación regional</span>
    </div>
    <div class="card-body" style="padding:16px 20px">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:14px">
            @foreach($resumenDestino as $dest)
            <div style="background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px 14px">
                <div style="font-size:.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;display:flex;align-items:center;gap:4px">
                    <span>📍</span> {{ $dest->destino ?: 'Planta / Local' }}
                </div>
                <div style="font-size:1.3rem;font-weight:800;color:var(--text);margin-top:4px">
                    {{ number_format($dest->total_kg, 1) }} <span style="font-size:.8rem;font-weight:500">kg</span>
                </div>
                <div style="font-size:.85rem;color:var(--green);font-weight:700;margin-top:2px">
                    S/ {{ number_format($dest->total_monto, 2) }}
                </div>
                <div style="font-size:.72rem;color:var(--text-muted);margin-top:4px">
                    {{ $dest->num_envios }} despacho(s)
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Formulario nueva venta y despacho ─────────────────────────────────────── --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h3>➕ Registrar Nueva Venta y Programar Despacho</h3>
    </div>
    <div class="card-body">
        @if($alertaStockQuesos)
        <div style="margin-bottom:18px;padding:12px 16px;background:var(--red-bg);border:1px solid #fecaca;border-radius:8px;color:#991b1b;font-size:.85rem;display:flex;align-items:center;gap:10px">
            <span style="font-size:1.3rem">⚠️</span>
            <div>
                <strong>Atención: Reserva mínima de planta comprometida.</strong>
                El stock actual es de <strong>{{ number_format($stockActualQuesos, 1) }} moldes</strong> (reserva mínima configurada: {{ $stockMinimoQuesos }} moldes).
                Verifique que este despacho no desabastezca la venta directa local.
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.ventas.store') }}" id="formVenta">
            @csrf

            <div class="form-grid">

                <div class="form-group">
                    <label for="cliente">Cliente / Mayorista *</label>
                    <input type="text" id="cliente" name="cliente"
                           maxlength="150" required
                           value="{{ old('cliente') }}"
                           class="{{ $errors->has('cliente') ? 'invalid' : '' }}"
                           placeholder="Nombre, distribuidor o razón social">
                    @error('cliente')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="tipo_cliente">Tipo de cliente *</label>
                    <select id="tipo_cliente" name="tipo_cliente" required
                            class="{{ $errors->has('tipo_cliente') ? 'invalid' : '' }}">
                        <option value="">— Seleccionar —</option>
                        <option value="mayorista" {{ old('tipo_cliente') === 'mayorista' ? 'selected' : '' }}>
                            Mayorista (Regional)
                        </option>
                        <option value="publico" {{ old('tipo_cliente') === 'publico' ? 'selected' : '' }}>
                            Público General / Local
                        </option>
                    </select>
                    @error('tipo_cliente')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="destino">Destino de entrega *</label>
                    <input type="text" id="destino" name="destino"
                           maxlength="100" required
                           value="{{ old('destino', 'Planta / Local') }}"
                           class="{{ $errors->has('destino') ? 'invalid' : '' }}"
                           placeholder="Ej: Arequipa, Lima, Juliaca, Puno, Planta">
                    <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:6px">
                        @foreach(['Arequipa', 'Lima', 'Juliaca', 'Puno', 'Planta / Local'] as $destPreset)
                            <button type="button" class="btn btn-sm"
                                    style="font-size:.72rem;padding:2px 8px;background:var(--bg);border:1px solid var(--border);color:var(--text);cursor:pointer"
                                    onclick="document.getElementById('destino').value='{{ $destPreset }}'; recalcular();">
                                {{ $destPreset }}
                            </button>
                        @endforeach
                    </div>
                    @error('destino')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="precio_display">Precio unitario fijado</label>
                    <input type="text" id="precio_display"
                           readonly
                           value="{{ old('tipo_cliente') ? number_format($precios[old('tipo_cliente')], 2) : '' }}"
                           style="background:#f8fafc;cursor:not-allowed;color:var(--green);font-weight:700;font-size:1rem"
                           placeholder="Automático según tipo">
                    <span style="font-size:.72rem;color:var(--text-muted)">
                        Mayorista S/{{ number_format($precios['mayorista'],2) }} | Público S/{{ number_format($precios['publico'],2) }}
                    </span>
                </div>

                <div class="form-group">
                    <label for="cantidad_kg">Cantidad (kg / moldes) *</label>
                    <input type="number" id="cantidad_kg" name="cantidad_kg"
                           step="0.001" min="0.001" required
                           value="{{ old('cantidad_kg') }}"
                           class="{{ $errors->has('cantidad_kg') ? 'invalid' : '' }}"
                           placeholder="Ej: 50.000">
                    @error('cantidad_kg')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="fecha_venta">Fecha de venta *</label>
                    <input type="date" id="fecha_venta" name="fecha_venta"
                           required
                           value="{{ old('fecha_venta', now()->toDateString()) }}"
                           max="{{ now()->toDateString() }}"
                           class="{{ $errors->has('fecha_venta') ? 'invalid' : '' }}">
                    @error('fecha_venta')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="guia_remision">N° Guía de Remisión (opcional)</label>
                    <input type="text" id="guia_remision" name="guia_remision"
                           maxlength="60"
                           value="{{ old('guia_remision') }}"
                           class="{{ $errors->has('guia_remision') ? 'invalid' : '' }}"
                           placeholder="Ej: GR-001-00234">
                    @error('guia_remision')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Total en tiempo real --}}
                <div class="form-group" style="justify-content:flex-end;align-items:flex-start">
                    <div id="preview-total"
                         style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;min-width:180px">
                        <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px">
                            Total a cobrar
                        </div>
                        <div id="preview-total-valor"
                             style="font-size:1.6rem;font-weight:700;color:var(--green);margin-top:2px">
                            S/ 0.00
                        </div>
                        <div id="preview-precio-unit"
                             style="font-size:.75rem;color:var(--text-muted);margin-top:2px">
                            Precio: — / kg
                        </div>
                    </div>
                </div>

                <div class="form-group" style="grid-column: 1 / -1">
                    <label for="notas_despacho">Notas de Despacho y Transporte (opcional)</label>
                    <input type="text" id="notas_despacho" name="notas_despacho"
                           maxlength="500"
                           value="{{ old('notas_despacho') }}"
                           class="{{ $errors->has('notas_despacho') ? 'invalid' : '' }}"
                           placeholder="Ej: Envío por Agencia Flores / Embalaje en jabas plásticas">
                    @error('notas_despacho')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

            </div>{{-- /form-grid --}}

            <div style="margin-top:20px;display:flex;gap:12px;align-items:center">
                <button type="submit" class="btn btn-success" id="btnGuardar" disabled>
                    💾 Registrar Venta y Programar Despacho
                </button>
                <button type="reset" class="btn btn-sm"
                        style="background:var(--border);color:var(--text)"
                        onclick="resetFormVenta()">
                    Limpiar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Historial de ventas y despachos con filtros ──────────────────────────── --}}
<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:12px">
        <div>
            <h3>📋 Historial de Ventas y Despachos</h3>
            <span class="card-header-meta">{{ $ventas->total() }} registro(s)</span>
        </div>

        {{-- Filtros Rápidos --}}
        <form method="GET" action="{{ route('admin.ventas') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <select name="destino" onchange="this.form.submit()" style="font-size:.82rem;padding:5px 10px;border-radius:6px;border:1px solid var(--border)">
                <option value="">— Todos los destinos —</option>
                @foreach($resumenDestino->pluck('destino')->unique() as $d)
                    @if($d)
                    <option value="{{ $d }}" {{ $destinoFiltro === $d ? 'selected' : '' }}>📍 {{ $d }}</option>
                    @endif
                @endforeach
            </select>

            <select name="despacho" onchange="this.form.submit()" style="font-size:.82rem;padding:5px 10px;border-radius:6px;border:1px solid var(--border)">
                <option value="">— Todo estado despacho —</option>
                <option value="preparacion" {{ $despachoFiltro === 'preparacion' ? 'selected' : '' }}>📦 En Preparación</option>
                <option value="en_camino"   {{ $despachoFiltro === 'en_camino' ? 'selected' : '' }}>🚚 En Camino</option>
                <option value="entregado"   {{ $despachoFiltro === 'entregado' ? 'selected' : '' }}>✅ Entregado</option>
            </select>

            @if($destinoFiltro || $despachoFiltro)
                <a href="{{ route('admin.ventas') }}" class="btn btn-sm" style="background:var(--border);color:var(--text);font-size:.78rem">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="card-body" style="padding:0">
        @if($ventas->isEmpty())
            <div style="text-align:center;padding:40px 20px;color:var(--text-muted)">
                <div style="font-size:2rem;margin-bottom:8px">📦</div>
                <div>No se encontraron ventas con los filtros aplicados.</div>
            </div>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Destino</th>
                        <th>Despacho</th>
                        <th>Guía Remisión</th>
                        <th>Kg</th>
                        <th>Precio/kg</th>
                        <th>Total</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ventas as $venta)
                    @php $badge = $venta->estado_despacho_badge; @endphp
                    <tr>
                        <td style="color:var(--text-muted);font-size:.8rem">{{ $venta->id }}</td>
                        <td style="white-space:nowrap">{{ $venta->fecha_venta->format('d/m/Y') }}</td>
                        <td>
                            <strong>{{ $venta->cliente }}</strong>
                            <div style="font-size:.72rem">
                                <span class="badge badge-{{ $venta->tipo_cliente }}">
                                    {{ $venta->tipo_cliente === 'mayorista' ? 'Mayorista' : 'Público' }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight:600;display:inline-flex;align-items:center;gap:3px">
                                📍 {{ $venta->destino ?: 'Planta / Local' }}
                            </span>
                        </td>
                        <td>
                            <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;background:{{ $badge['bg'] }};color:{{ $badge['color'] }};border:1px solid {{ $badge['border'] }}">
                                {{ $badge['icon'] }} {{ $badge['label'] }}
                            </span>
                            @if($venta->fecha_despacho)
                                <span style="display:block;font-size:.72rem;color:var(--text-muted);margin-top:2px">
                                    {{ $venta->fecha_despacho->format('d/m/Y') }}
                                </span>
                            @endif
                        </td>
                        <td style="font-size:.82rem">
                            @if($venta->guia_remision)
                                <code style="background:var(--bg);padding:2px 6px;border-radius:4px;border:1px solid var(--border)">
                                    {{ $venta->guia_remision }}
                                </code>
                            @else
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                            @if($venta->notas_despacho)
                                <div style="font-size:.72rem;color:var(--text-muted);max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="{{ $venta->notas_despacho }}">
                                    📝 {{ $venta->notas_despacho }}
                                </div>
                            @endif
                        </td>
                        <td style="font-weight:600">{{ number_format($venta->cantidad_kg, 2) }} kg</td>
                        <td style="font-size:.82rem">S/ {{ number_format($venta->precio_unitario, 2) }}</td>
                        <td><strong style="color:var(--green)">S/ {{ number_format($venta->total, 2) }}</strong></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-despacho"
                                    style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-size:.75rem;padding:4px 8px;cursor:pointer"
                                    data-id="{{ $venta->id }}"
                                    data-cliente="{{ $venta->cliente }}"
                                    data-destino="{{ $venta->destino ?? 'Planta' }}"
                                    data-estado="{{ $venta->estado_despacho }}"
                                    data-guia="{{ $venta->guia_remision ?? '' }}"
                                    data-fecha="{{ $venta->fecha_despacho?->toDateString() ?? '' }}"
                                    data-notas="{{ $venta->notas_despacho ?? '' }}"
                                    onclick="abrirModalDespachoDesdeBtn(this)">
                                🚚 Despacho
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding:16px 22px">
            {{ $ventas->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ── Modal para Actualizar Despacho ────────────────────────────────────────── --}}
<div id="modalDespacho" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:var(--bg-card);border-radius:12px;width:95%;max-width:500px;box-shadow:0 20px 25px -5px rgba(0,0,0,.3);overflow:hidden;border:1px solid var(--border)">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;background:var(--bg)">
            <h4 style="margin:0;font-size:1rem;font-weight:700" id="modalTitulo">🚚 Gestionar Despacho</h4>
            <button type="button" onclick="cerrarModalDespacho()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--text-muted)">&times;</button>
        </div>
        <form method="POST" id="formDespacho" action="" style="padding:20px">
            @csrf
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px">Estado de Despacho *</label>
                <select id="modal_estado_despacho" name="estado_despacho" required style="width:100%;padding:8px 12px;border-radius:6px;border:1px solid var(--border);font-size:.9rem">
                    <option value="preparacion">📦 En Preparación (Planta)</option>
                    <option value="en_camino">🚚 En Camino (En Ruta)</option>
                    <option value="entregado">✅ Entregado (Conforme)</option>
                </select>
            </div>

            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px">N° Guía de Remisión</label>
                <input type="text" id="modal_guia_remision" name="guia_remision" maxlength="60" placeholder="Ej: GR-001-00234" style="width:100%;padding:8px 12px;border-radius:6px;border:1px solid var(--border);font-size:.9rem">
            </div>

            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px">Fecha de Despacho / Salida</label>
                <input type="date" id="modal_fecha_despacho" name="fecha_despacho" style="width:100%;padding:8px 12px;border-radius:6px;border:1px solid var(--border);font-size:.9rem">
            </div>

            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px">Notas de Despacho / Transporte</label>
                <input type="text" id="modal_notas_despacho" name="notas_despacho" maxlength="500" placeholder="Ej: Chofer Juan Pérez / Transporte Flores" style="width:100%;padding:8px 12px;border-radius:6px;border:1px solid var(--border);font-size:.9rem">
            </div>

            <div style="margin-bottom:18px">
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px">Motivo del Cambio (Auditoría)</label>
                <input type="text" id="modal_motivo_cambio" name="motivo_cambio" maxlength="250" placeholder="Ej: Confirmación de recepción de mercadería" style="width:100%;padding:8px 12px;border-radius:6px;border:1px solid var(--border);font-size:.9rem">
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" onclick="cerrarModalDespacho()" class="btn btn-sm" style="background:var(--border);color:var(--text)">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-success">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- ── JS — Selector de tarifa y modal ──────────────────────────────────────── --}}
<script>
(function () {
    'use strict';

    var PRECIOS = {
        mayorista: {{ $precios['mayorista'] }},
        publico:   {{ $precios['publico'] }}
    };

    var selectTipo     = document.getElementById('tipo_cliente');
    var inputKg        = document.getElementById('cantidad_kg');
    var inputDestino   = document.getElementById('destino');
    var inputCliente   = document.getElementById('cliente');
    var displayPrecio  = document.getElementById('precio_display');
    var previewValor   = document.getElementById('preview-total-valor');
    var previewUnit    = document.getElementById('preview-precio-unit');
    var btnGuardar     = document.getElementById('btnGuardar');

    function getPrecio() {
        return PRECIOS[selectTipo.value] || 0;
    }

    function recalcular() {
        var precio = getPrecio();
        var kg     = parseFloat(inputKg.value) || 0;
        var total  = precio * kg;

        if (precio > 0) {
            displayPrecio.value = precio.toFixed(2);
            previewUnit.textContent = 'Precio: S/ ' + precio.toFixed(2) + ' / kg';
        } else {
            displayPrecio.value = '';
            previewUnit.textContent = 'Precio: — / kg';
        }

        previewValor.textContent = 'S/ ' + total.toFixed(2);

        var clienteOk = inputCliente.value.trim().length > 0;
        var destinoOk = inputDestino.value.trim().length > 0;
        btnGuardar.disabled = !(precio > 0 && kg > 0 && clienteOk && destinoOk);
    }

    function resetFormVenta() {
        displayPrecio.value = '';
        previewValor.textContent = 'S/ 0.00';
        previewUnit.textContent = 'Precio: — / kg';
        btnGuardar.disabled = true;
    }

    window.resetFormVenta = resetFormVenta;
    window.recalcular = recalcular;

    selectTipo.addEventListener('change', recalcular);
    inputKg.addEventListener('input', recalcular);
    inputCliente.addEventListener('input', recalcular);
    inputDestino.addEventListener('input', recalcular);

    recalcular();
}());

function abrirModalDespachoDesdeBtn(btn) {
    var d = btn.dataset;
    var modal = document.getElementById('modalDespacho');
    var form = document.getElementById('formDespacho');
    document.getElementById('modalTitulo').textContent = '🚚 Despacho #' + d.id + ' — ' + d.cliente + ' (' + (d.destino || 'Planta') + ')';
    form.action = '/admin/ventas/' + d.id + '/despacho';
    document.getElementById('modal_estado_despacho').value = d.estado || 'preparacion';
    document.getElementById('modal_guia_remision').value = d.guia || '';
    document.getElementById('modal_fecha_despacho').value = d.fecha || '';
    document.getElementById('modal_notas_despacho').value = d.notas || '';
    document.getElementById('modal_motivo_cambio').value = '';
    modal.style.display = 'flex';
}

function cerrarModalDespacho() {
    document.getElementById('modalDespacho').style.display = 'none';
}
</script>

@endsection
