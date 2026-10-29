@extends('layouts.admin')

@section('title', 'Ventas y Despachos')
@section('page-title', '💰 Ventas y Gestión de Despachos')

@section('content')

@include('admin.ventas._dashboard')

{{-- ── Formulario nueva venta y despacho ─────────────────────────────────────── --}}
<details id="nueva-venta" class="card" style="margin-bottom:24px" {{ $errors->hasAny(['cliente','tipo_cliente','destino','fecha_venta','lineas','lineas.*']) ? 'open' : '' }}>
    <summary class="card-header" style="cursor:pointer;list-style:none">
        <h3>➕ Registrar nueva venta y programar despacho</h3>
        <span class="card-header-meta">Toca para abrir o cerrar</span>
    </summary>
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

                {{-- ── Carrito: varios productos en la misma venta ── --}}
                <style>
                    #tabla-carrito select, #tabla-carrito input { min-width: 0; }
                    /* Celular: cada línea del carrito es una tarjeta (producto arriba, cantidad/precio/subtotal abajo) */
                    @media (max-width: 700px) {
                        #tabla-carrito thead { display: none; }
                        #tabla-carrito tr { display: grid; grid-template-columns: 1fr 1fr 40px; gap: 6px; padding: 8px 4px; }
                        #tabla-carrito td { padding: 2px 6px !important; }
                        #tabla-carrito td:first-child { grid-column: 1 / -1; }
                        #tabla-carrito td[data-precio] { text-align: left !important; align-self: center; }
                        #tabla-carrito td[data-subtotal] { grid-column: 1 / 3; text-align: left !important; }
                        #tabla-carrito td:last-child { grid-column: 3; grid-row: 2; }
                    }
                </style>
                <div class="form-group" style="grid-column: 1 / -1">
                    <label>Productos de la venta *</label>
                    <div style="border:1.5px solid var(--border);border-radius:12px;overflow:hidden">
                        <table style="width:100%;border-collapse:collapse" id="tabla-carrito">
                            <thead>
                                <tr style="background:#f8fafc">
                                    <th style="text-align:left;padding:8px 12px;font-size:.72rem">Producto</th>
                                    <th style="padding:8px;font-size:.72rem;width:140px">Cantidad</th>
                                    <th style="padding:8px;font-size:.72rem;width:120px;text-align:right">Precio</th>
                                    <th style="padding:8px;font-size:.72rem;width:120px;text-align:right">Subtotal</th>
                                    <th style="width:44px"></th>
                                </tr>
                            </thead>
                            <tbody id="carrito"></tbody>
                        </table>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;
                                    padding:12px;border-top:1px solid var(--border);background:#fbfdff">
                            <button type="button" class="btn btn-sm" onclick="agregarLineaVenta()"
                                    style="background:#eff6ff;color:#1d4ed8;border:1.5px dashed #93c5fd;font-weight:700">
                                ➕ Agregar producto
                            </button>
                            <div style="text-align:right">
                                <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px">Total a cobrar</div>
                                <div id="preview-total-valor" style="font-size:1.6rem;font-weight:800;color:var(--green)">S/ 0.00</div>
                                <div id="preview-precio-unit" style="font-size:.75rem;color:var(--text-muted)">Elige el tipo de cliente para ver los precios</div>
                            </div>
                        </div>
                    </div>
                    @if($errors->has('lineas') || $errors->has('lineas.*'))
                        <span class="error-msg">{{ $errors->first('lineas') ?: collect($errors->get('lineas.*'))->flatten()->first() }}</span>
                    @endif
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px">
                        @foreach($catalogo as $item)
                            <button type="button" class="btn btn-sm" onclick="agregarLineaVenta('{{ $item['clave'] }}')"
                                    style="font-size:.72rem;padding:3px 10px;background:var(--bg);border:1px solid var(--border);color:var(--text)"
                                    {{ $item['stock'] <= 0 ? 'disabled' : '' }}>
                                + {{ $item['nombre'] }} <span style="color:var(--text-muted)">({{ $item['decimales'] ? number_format($item['stock'], 1) . ' kg' : $item['stock'] }})</span>
                            </button>
                        @endforeach
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
                    💾 Registrar venta y programar despacho
                </button>
                <button type="reset" class="btn btn-sm"
                        style="background:var(--border);color:var(--text)"
                        onclick="resetFormVenta()">
                    Limpiar
                </button>
            </div>
        </form>
    </div>
</details>

{{-- ── Pedidos de la tienda web por despachar ─────────────────────────────────── --}}
<div class="card" id="pedidos-web-despacho" style="margin-bottom:20px">
    <div class="card-header" style="flex-wrap:wrap;gap:10px">
        <div>
            <h3>🛒 Pedidos de la tienda web por despachar</h3>
            <span class="card-header-meta">Pago → Despacho → Entrega. Cada paso avisa al cliente por WhatsApp y actualiza «Seguir mi pedido».</span>
        </div>
        <span class="tag">{{ $pedidosWebActivos->count() }} activo(s)</span>
    </div>
    @if($pedidosWebActivos->isEmpty())
        <div style="padding:16px 22px;color:var(--text-muted);font-size:.88rem">No hay pedidos web pendientes de entrega. 🎉</div>
    @else
    <div class="table-wrap">
        <table style="width:100%">
            <thead><tr>
                <th>Pedido</th><th>Cliente</th><th>Productos</th><th>Pago</th><th>Total</th><th>Estado</th><th>Siguiente paso</th>
            </tr></thead>
            <tbody>
            @foreach($pedidosWebActivos as $pw)
                @php $b = $pw->estado_badge; $sig = $pw->siguienteEstado(); @endphp
                <tr>
                    <td><strong>{{ $pw->codigo_pedido }}</strong><br><small style="color:var(--text-muted)">{{ $pw->created_at->format('d/m H:i') }}</small></td>
                    <td>{{ $pw->cliente_nombre }}<br><small style="color:var(--text-muted)">📞 {{ $pw->cliente_telefono }} · {{ $pw->cliente_direccion }}</small></td>
                    <td style="font-size:.8rem">@foreach($pw->detalles as $d){{ $d->cantidad }} × {{ $d->producto?->nombre ?? 'Producto' }}@if(! $loop->last)<br>@endif @endforeach</td>
                    <td style="font-size:.8rem">{{ $pw->metodo_pago }}</td>
                    <td><strong>S/ {{ number_format($pw->total, 2) }}</strong></td>
                    <td><span class="badge" style="background:{{ $b['bg'] }};color:{{ $b['color'] }}">{{ $b['label'] }}</span>
                        @if($pw->despachado_at)<br><small style="color:var(--text-muted)">salió {{ $pw->despachado_at->format('d/m H:i') }}</small>@endif</td>
                    <td>
                        @if($sig)
                        <form method="POST" action="{{ route('admin.ventas.pedidos-web.estado', $pw) }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                            @csrf
                            <input type="hidden" name="estado_pedido" value="{{ $sig }}">
                            @if($sig === 'Enviado')
                                <input name="notas_despacho" maxlength="250" placeholder="Repartidor / notas" style="width:130px;padding:5px 8px;border:1px solid var(--border);border-radius:6px;font-size:.78rem">
                            @endif
                            <button class="btn btn-sm btn-primary">{{ \App\Models\PedidoWeb::ACCION_SIGUIENTE[$sig] }}</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── Historial de ventas y despachos con filtros ──────────────────────────── --}}
<div class="card" id="historial-ventas">
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

    @if($errors->hasAny(['anulacion', 'motivo_anulacion', 'despacho']))
        <div style="margin:12px 22px 0;padding:10px 14px;border-radius:8px;background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;font-size:.85rem;font-weight:600">
            ⛔ {{ $errors->first('anulacion') ?: ($errors->first('motivo_anulacion') ?: $errors->first('despacho')) }}
        </div>
    @endif

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
                        <th>Productos</th>
                        <th>Total</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ventas as $venta)
                    @php $badge = $venta->estado_despacho_badge; @endphp
                    <tr @if($venta->anulada) style="background:#fafafa;opacity:.7" @endif>
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
                            @if($venta->anulada)
                            <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5">
                                🚫 Anulada
                            </span>
                            <span style="display:block;font-size:.72rem;color:var(--text-muted);margin-top:2px;max-width:180px" title="{{ $venta->motivo_anulacion }}">
                                {{ $venta->anulada_at->format('d/m/Y') }} · {{ $venta->anulador?->name }}<br>{{ \Illuminate\Support\Str::limit($venta->motivo_anulacion, 40) }}
                            </span>
                            @else
                            <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;background:{{ $badge['bg'] }};color:{{ $badge['color'] }};border:1px solid {{ $badge['border'] }}">
                                {{ $badge['icon'] }} {{ $badge['label'] }}
                            </span>
                            @if($venta->fecha_despacho)
                                <span style="display:block;font-size:.72rem;color:var(--text-muted);margin-top:2px">
                                    {{ $venta->fecha_despacho->format('d/m/Y') }}
                                </span>
                            @endif
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
                        <td style="font-size:.8rem;min-width:180px">
                            @forelse($venta->detalles as $d)
                                <div>{{ rtrim(rtrim(number_format($d->cantidad, 3), '0'), '.') }} {{ $d->unidad }} · {{ $d->descripcion }}
                                    <span style="color:var(--text-muted)">× S/ {{ number_format($d->precio_unitario, 2) }}</span></div>
                            @empty
                                {{-- Ventas anteriores al carrito: solo queso de planta --}}
                                <div>{{ number_format($venta->cantidad_kg, 2) }} kg · Queso de planta
                                    <span style="color:var(--text-muted)">× S/ {{ number_format($venta->precio_unitario, 2) }}</span></div>
                            @endforelse
                        </td>
                        <td><strong style="color:{{ $venta->anulada ? 'var(--text-muted)' : 'var(--green)' }};{{ $venta->anulada ? 'text-decoration:line-through' : '' }}">S/ {{ number_format($venta->total, 2) }}</strong></td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('admin.ventas.comprobante', $venta) }}" target="_blank" class="btn btn-sm"
                               style="background:#f8fafc;color:var(--text);border:1px solid var(--border);font-size:.75rem;padding:4px 8px">
                                🧾 Comprobante
                            </a>
                            @unless($venta->anulada)
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
                            @if(auth()->user()->tieneRol('admin', 'jefe_produccion') && $venta->estado_despacho !== 'entregado')
                            <button type="button" class="btn btn-sm"
                                    style="background:#fef2f2;color:#b91c1c;border:1px solid #fca5a5;font-size:.75rem;padding:4px 8px;cursor:pointer"
                                    data-id="{{ $venta->id }}" data-cliente="{{ $venta->cliente }}"
                                    data-total="{{ number_format($venta->total, 2) }}"
                                    onclick="abrirModalAnular(this)">
                                🚫 Anular
                            </button>
                            @endif
                            @endunless
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

{{-- ── Modal para Anular una venta ───────────────────────────────────────────── --}}
<div id="modalAnular" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:var(--bg-card);border-radius:12px;width:95%;max-width:460px;box-shadow:0 20px 25px -5px rgba(0,0,0,.3);overflow:hidden;border:1px solid var(--border)">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;background:#fef2f2">
            <h4 style="margin:0;font-size:1rem;font-weight:700;color:#b91c1c" id="anularTitulo">🚫 Anular venta</h4>
            <button type="button" onclick="cerrarModalAnular()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--text-muted)">&times;</button>
        </div>
        <form method="POST" id="formAnular" action="" style="padding:20px">
            @csrf
            <p style="margin:0 0 14px;font-size:.85rem;color:var(--text-2)">
                El stock de los productos vuelve al inventario y la venta deja de contar en los totales y reportes.
                El registro se conserva marcado como <strong>anulado</strong>. Esta acción no se puede deshacer.
            </p>
            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px">Motivo de la anulación *</label>
            <input type="text" id="motivo_anulacion" name="motivo_anulacion" required minlength="5" maxlength="250"
                   placeholder="Ej: El cliente canceló el pedido" style="width:100%;padding:8px 12px;border-radius:6px;border:1px solid var(--border);font-size:.9rem">
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px">
                <button type="button" onclick="cerrarModalAnular()" class="btn btn-sm" style="background:var(--border);color:var(--text)">Volver</button>
                <button type="submit" class="btn btn-sm" style="background:#dc2626;color:#fff">Anular venta</button>
            </div>
        </form>
    </div>
</div>

{{-- ── JS — Selector de tarifa y modal ──────────────────────────────────────── --}}
<script>
(function () {
    'use strict';

    var CATALOGO = @json($catalogo);
    var PREVIAS  = @json(collect(old('lineas', []))->values());
    var porClave = {};
    CATALOGO.forEach(function (p) { porClave[p.clave] = p; });

    var selectTipo   = document.getElementById('tipo_cliente');
    var inputDestino = document.getElementById('destino');
    var inputCliente = document.getElementById('cliente');
    var carrito      = document.getElementById('carrito');
    var previewValor = document.getElementById('preview-total-valor');
    var previewUnit  = document.getElementById('preview-precio-unit');
    var btnGuardar   = document.getElementById('btnGuardar');
    var siguiente    = 0;

    function sol(v) { return 'S/ ' + Number(v).toFixed(2); }
    function esc(t) { return String(t).replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }

    function opciones(sel) {
        return '<option value="">— Producto —</option>' + CATALOGO.map(function (p) {
            var stock = p.decimales ? Number(p.stock).toFixed(1) + ' kg' : p.stock + ' und';
            return '<option value="' + p.clave + '"' + (String(sel) === p.clave ? ' selected' : '') +
                (p.stock <= 0 ? ' disabled' : '') + '>' + esc(p.nombre) + ' — stock ' + stock + '</option>';
        }).join('');
    }

    window.agregarLineaVenta = function (clave, cantidad) {
        if (clave) {
            var ya = carrito.querySelector('select option[value="' + clave + '"]:checked');
            if (ya) { ya.closest('tr').querySelector('input').focus(); return; }
        }
        var i = siguiente++;
        var tr = document.createElement('tr');
        tr.style.borderTop = '1px solid var(--border)';
        tr.innerHTML =
            '<td style="padding:8px 12px"><select name="lineas[' + i + '][producto]" required style="width:100%">' + opciones(clave || '') + '</select></td>' +
            '<td style="padding:8px"><input type="number" name="lineas[' + i + '][cantidad]" min="0.001" step="any" required ' +
            'value="' + (cantidad || '') + '" placeholder="0" style="width:100%"><div data-aviso style="font-size:.7rem;color:#dc2626"></div></td>' +
            '<td data-precio style="padding:8px;text-align:right;font-size:.85rem;color:var(--text-muted)">—</td>' +
            '<td data-subtotal style="padding:8px;text-align:right;font-weight:700">—</td>' +
            '<td style="padding:8px"><button type="button" title="Quitar" style="border:none;background:#fef2f2;color:#dc2626;' +
            'border-radius:8px;width:32px;height:32px;cursor:pointer">✕</button></td>';
        tr.querySelector('select').addEventListener('change', recalcular);
        tr.querySelector('input').addEventListener('input', recalcular);
        tr.querySelector('button').addEventListener('click', function () { tr.remove(); recalcular(); });
        carrito.appendChild(tr);
        recalcular();
        (clave ? tr.querySelector('input') : tr.querySelector('select')).focus();
    };

    function recalcular() {
        var tipo = selectTipo.value;
        var total = 0, lineasOk = true, filas = carrito.querySelectorAll('tr');
        Array.prototype.forEach.call(filas, function (tr) {
            var p = porClave[tr.querySelector('select').value];
            var input = tr.querySelector('input');
            var cant = parseFloat(input.value) || 0;
            var aviso = '';
            if (p) {
                input.step = p.decimales ? '0.001' : '1';
                input.max = p.stock;
                if (cant > p.stock) aviso = 'Solo hay ' + (p.decimales ? Number(p.stock).toFixed(1) : p.stock);
                if (!p.decimales && cant % 1 !== 0) aviso = 'Solo unidades enteras';
            }
            var precio = p && tipo ? p[tipo] : 0;
            tr.querySelector('[data-precio]').textContent = precio ? sol(precio) + ' / ' + p.unidad : '—';
            tr.querySelector('[data-subtotal]').textContent = precio && cant ? sol(precio * cant) : '—';
            tr.querySelector('[data-aviso]').textContent = aviso;
            if (!p || cant <= 0 || aviso) lineasOk = false;
            total += precio * cant;
        });
        previewValor.textContent = sol(total);
        previewUnit.textContent = !tipo ? 'Elige el tipo de cliente para ver los precios'
            : filas.length + (filas.length === 1 ? ' producto · tarifa ' : ' productos · tarifa ') + (tipo === 'mayorista' ? 'mayorista' : 'público');
        var clienteOk = inputCliente.value.trim().length > 0, destinoOk = inputDestino.value.trim().length > 0;
        btnGuardar.disabled = !(tipo && filas.length > 0 && lineasOk && clienteOk && destinoOk);
    }

    window.resetFormVenta = function () {
        setTimeout(function () { carrito.innerHTML = ''; agregarLineaVenta(); }, 0);
    };
    window.recalcular = recalcular;

    selectTipo.addEventListener('change', recalcular);
    inputCliente.addEventListener('input', recalcular);
    inputDestino.addEventListener('input', recalcular);

    if (PREVIAS.length) {
        PREVIAS.forEach(function (l) { agregarLineaVenta(l.producto, l.cantidad); });
    } else {
        agregarLineaVenta();
    }
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

function abrirModalAnular(btn) {
    var d = btn.dataset;
    document.getElementById('anularTitulo').textContent = '🚫 Anular venta #' + d.id + ' — ' + d.cliente + ' (S/ ' + d.total + ')';
    document.getElementById('formAnular').action = '/admin/ventas/' + d.id + '/anular';
    document.getElementById('motivo_anulacion').value = '';
    document.getElementById('modalAnular').style.display = 'flex';
    document.getElementById('motivo_anulacion').focus();
}

function cerrarModalAnular() {
    document.getElementById('modalAnular').style.display = 'none';
}

function cerrarModalDespacho() {
    document.getElementById('modalDespacho').style.display = 'none';
}
</script>

@endsection
