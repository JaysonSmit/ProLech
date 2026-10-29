@extends('layouts.admin')

@section('title', 'Pedido ' . $pedido->codigo_pedido)
@section('page-title', '📦 Detalle Pedido')

@section('content')
@php
    $badge  = $pedido->estado_badge;
    $metodo = $pedido->metodo_badge;
@endphp

<div style="display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start;">

    {{-- Detalles del pedido --}}
    <div class="card">
        @php
            $sunat = match ($pedido->sunat_estado) {
                'aceptada'  => ['✅ Aceptada por SUNAT', '#dcfce7', '#166534'],
                'enviada'   => ['📨 Enviada a SUNAT', '#dbeafe', '#1d4ed8'],
                'rechazada' => ['❌ Rechazada', '#fee2e2', '#991b1b'],
                'error'     => ['⚠️ Error de conexión', '#fef3c7', '#92400e'],
                'simulada'  => ['🧪 Interna (Nubefact sin configurar)', '#f1f5f9', '#475569'],
                default     => ['⏳ Sin enviar', '#f1f5f9', '#475569'],
            };
        @endphp
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 22px; background:{{ $sunat[1] }}; color:{{ $sunat[2] }}; font-size:.82rem; font-weight:600">
            <span>Boleta electrónica: {{ $sunat[0] }} @if($pedido->sunat_mensaje)· <span style="font-weight:400">{{ \Illuminate\Support\Str::limit($pedido->sunat_mensaje, 120) }}</span>@endif</span>
            <span style="display:flex; gap:8px">
                @if($pedido->sunat_pdf_url)<a href="{{ $pedido->sunat_pdf_url }}" target="_blank" rel="noopener">PDF SUNAT</a>@endif
                @if(in_array($pedido->sunat_estado, [null, 'rechazada', 'error', 'simulada'], true))
                <form method="POST" action="{{ route('admin.tienda.pedidos.sunat', $pedido) }}" style="margin:0">@csrf
                    <button class="btn btn-ghost btn-sm" type="submit">↻ Enviar a SUNAT</button>
                </form>
                @endif
            </span>
        </div>
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 class="card-title" style="font-size:1.1rem; font-family:monospace;">
                    {{ $pedido->codigo_pedido }}
                </h3>
                <p style="color:var(--text-muted); font-size:.8rem; margin-top:2px;">
                    Realizado el {{ $pedido->created_at->format('d/m/Y \a \l\a\s H:i') }}
                </p>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                <a href="{{ URL::signedRoute('tienda.boleta', ['codigo' => $pedido->codigo_pedido]) }}"
                   target="_blank" rel="noopener" class="btn btn-ghost btn-sm">
                    🧾 Boleta {{ $pedido->numero_comprobante }}
                </a>
                <a href="{{ $pedido->whatsapp_url }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" style="color:#15803d">
                    💬 WhatsApp
                </a>
                <span class="badge" style="background:{{ $badge['bg'] }}; color:{{ $badge['color'] }}; font-size:.82rem;">
                    {{ $badge['label'] }}
                </span>
            </div>
        </div>

        <div class="card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="text-align:right;">Precio</th>
                            <th style="text-align:center;">Cantidad</th>
                            <th style="text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedido->detalles as $d)
                        <tr>
                            <td style="font-weight:600;">
                                {{ $d->producto?->nombre ?? '(producto eliminado)' }}
                            </td>
                            <td style="text-align:right; color:var(--text-muted);">
                                S/ {{ number_format($d->precio_unitario, 2) }}
                            </td>
                            <td style="text-align:center;">{{ $d->cantidad }}</td>
                            <td style="text-align:right; font-weight:700; color:var(--brand);">
                                S/ {{ number_format($d->subtotal, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right; font-weight:700; padding:14px 16px; border-top:2px solid var(--border);">
                                TOTAL
                            </td>
                            <td style="text-align:right; font-size:1.15rem; font-weight:800; color:var(--brand); padding:14px 16px; border-top:2px solid var(--border);">
                                S/ {{ number_format($pedido->total, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($pedido->notas)
                <div style="margin-top:18px; padding:12px 16px; background:var(--amber-bg); border-radius:var(--radius-sm); border-left:3px solid var(--amber);">
                    <div style="font-size:.75rem; font-weight:700; color:var(--amber); text-transform:uppercase; margin-bottom:4px;">Notas del cliente</div>
                    <div style="font-size:.88rem; color:var(--text-2);">{{ $pedido->notas }}</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Panel lateral: cliente + acciones --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Datos del cliente --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">👤 Cliente</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:10px;">
                    @foreach([
                        ['Nombre',    $pedido->cliente_nombre],
                        ['Teléfono',  $pedido->cliente_telefono],
                        ['Dirección', $pedido->cliente_direccion],
                        ['Email',     $pedido->cliente_email ?? '—'],
                    ] as [$label, $valor])
                    <div>
                        <div style="font-size:.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px;">{{ $label }}</div>
                        <div style="font-size:.9rem; color:var(--text); margin-top:2px;">{{ $valor }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Método de pago + comprobante --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">💰 Pago</h3></div>
            <div class="card-body">
                <span style="font-size:.88rem; font-weight:700; padding:5px 12px; border-radius:20px;
                             background:{{ $metodo['bg'] }}; color:{{ $metodo['color'] }};">
                    {{ $metodo['icon'] }} {{ $metodo['label'] }}
                </span>

                @if($pedido->comprobante_yape_url)
                    <div style="margin-top:14px;">
                        <div style="font-size:.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:6px;">Comprobante Yape</div>
                        <a href="{{ asset('storage/'.$pedido->comprobante_yape_url) }}" target="_blank">
                            <img src="{{ asset('storage/'.$pedido->comprobante_yape_url) }}"
                                 alt="Comprobante Yape"
                                 style="width:100%; border-radius:8px; border:1px solid var(--border); object-fit:cover;">
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Cambiar estado --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">🔄 Cambiar Estado</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.tienda.pedidos.estado', $pedido) }}">
                    @csrf
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="estado_pedido">Nuevo estado</label>
                        <select id="estado_pedido" name="estado_pedido">
                            @foreach(['Pendiente' => 'Pendiente', 'Pagado' => 'Pagado', 'Enviado' => 'En camino (despachado)', 'Entregado' => 'Entregado', 'Cancelado' => 'Cancelado'] as $e => $txt)
                                <option value="{{ $e }}" {{ $pedido->estado_pedido === $e ? 'selected' : '' }}>
                                    {{ $txt }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        Actualizar Estado
                    </button>
                </form>

                <div style="margin-top:14px;">
                    <a href="{{ route('admin.tienda.pedidos.index') }}"
                       style="display:block; text-align:center; padding:8px; font-size:.82rem; color:var(--text-muted); text-decoration:none;">
                        ← Volver a la lista
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
