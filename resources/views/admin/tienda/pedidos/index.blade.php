@extends('layouts.admin')

@section('title', 'Pedidos Web')
@section('page-title', '📦 Pedidos de la Tienda')

@section('content')

{{-- Stats rápidos --}}
<div class="stats-grid" style="margin-bottom:22px;">
    @foreach(['Pendiente'=>['naranja','⏳'],'Pagado'=>['azul','💳'],'Enviado'=>['verde','🚚'],'Cancelado'=>['rojo','❌']] as $estado=>[$cls,$icon])
    <div class="stat-card {{ $cls }}">
        <div class="label">{{ $icon }} {{ $estado }}</div>
        <div class="value">{{ $contadores[$estado] ?? 0 }}</div>
        <div class="unit">pedidos</div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h3 class="card-title">Listado de Pedidos</h3>
        <a href="{{ route('tienda.index') }}" target="_blank"
           style="padding:7px 14px; background:var(--green-bg); color:var(--green); border-radius:var(--radius-sm); font-size:.82rem; font-weight:600; text-decoration:none;">
            🏪 Ver Tienda Pública →
        </a>
    </div>

    {{-- Filtros --}}
    <div style="padding:14px 22px; border-bottom:1px solid var(--border); display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <span style="font-size:.78rem; font-weight:600; color:var(--text-muted);">Estado:</span>
        @foreach(['' => 'Todos', 'Pendiente'=>'Pendiente','Pagado'=>'Pagado','Enviado'=>'Enviado','Cancelado'=>'Cancelado'] as $val => $label)
            <a href="{{ route('admin.tienda.pedidos.index', array_merge(request()->query(), ['estado' => $val ?: null])) }}"
               style="padding:4px 12px; border-radius:20px; font-size:.76rem; font-weight:600; text-decoration:none;
                      {{ $estadoFiltro === $val || ($val === '' && !$estadoFiltro) ? 'background:var(--brand); color:#fff;' : 'background:var(--bg); color:var(--text-muted); border:1px solid var(--border);' }}">
                {{ $label }}
            </a>
        @endforeach

        <span style="margin-left:12px; font-size:.78rem; font-weight:600; color:var(--text-muted);">Pago:</span>
        @foreach(['' => 'Todos', 'Yape' => '💜 Yape', 'Efectivo' => '💵 Efectivo', 'Tarjeta' => '💳 Tarjeta'] as $val => $label)
            <a href="{{ route('admin.tienda.pedidos.index', array_merge(request()->query(), ['metodo_pago' => $val ?: null])) }}"
               style="padding:4px 12px; border-radius:20px; font-size:.76rem; font-weight:600; text-decoration:none;
                      {{ $metodoPago === $val || ($val === '' && !$metodoPago) ? 'background:var(--brand); color:#fff;' : 'background:var(--bg); color:var(--text-muted); border:1px solid var(--border);' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Productos</th>
                        <th>Total</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pedidos as $pedido)
                    @php $badge = $pedido->estado_badge; $metodo = $pedido->metodo_badge; @endphp
                    <tr>
                        <td style="font-weight:700; font-family:monospace; font-size:.92rem;">
                            {{ $pedido->codigo_pedido }}
                        </td>
                        <td>
                            <div style="font-weight:600;">{{ $pedido->cliente_nombre }}</div>
                            <div style="font-size:.75rem; color:var(--text-muted);">{{ $pedido->cliente_telefono }}</div>
                        </td>
                        <td style="font-size:.82rem; color:var(--text-2);">
                            @foreach($pedido->detalles as $d)
                                <div>{{ $d->cantidad }}× {{ $d->producto?->nombre ?? '(eliminado)' }}</div>
                            @endforeach
                        </td>
                        <td style="font-weight:700; color:var(--brand);">
                            S/ {{ number_format($pedido->total, 2) }}
                        </td>
                        <td>
                            <span style="font-size:.8rem; font-weight:600; padding:3px 8px; border-radius:12px;
                                         background:{{ $metodo['bg'] }}; color:{{ $metodo['color'] }};">
                                {{ $metodo['icon'] }} {{ $metodo['label'] }}
                            </span>
                            @if($pedido->comprobante_yape_url)
                                <a href="{{ asset('storage/'.$pedido->comprobante_yape_url) }}" target="_blank"
                                   style="font-size:.72rem; color:var(--blue); display:block; margin-top:2px;">
                                    📎 Ver comprobante
                                </a>
                            @endif
                        </td>
                        <td>
                            <span class="badge"
                                  style="background:{{ $badge['bg'] }}; color:{{ $badge['color'] }};">
                                {{ $badge['label'] }}
                            </span>
                        </td>
                        <td style="font-size:.8rem; color:var(--text-muted); white-space:nowrap;">
                            {{ $pedido->created_at->format('d/m/Y') }}<br>
                            <span style="font-size:.72rem;">{{ $pedido->created_at->format('H:i') }}</span>
                        </td>
                        <td>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                {{-- Cambio rápido a Enviado --}}
                                @if($pedido->estado_pedido !== 'Enviado' && $pedido->estado_pedido !== 'Cancelado')
                                <form method="POST"
                                      action="{{ route('admin.tienda.pedidos.estado', $pedido) }}">
                                    @csrf
                                    <input type="hidden" name="estado_pedido"
                                           value="{{ $pedido->estado_pedido === 'Pendiente' ? 'Pagado' : 'Enviado' }}">
                                    <button type="submit"
                                        style="padding:4px 10px; border:none; cursor:pointer; border-radius:6px; font-size:.76rem; font-weight:600;
                                               background:{{ $pedido->estado_pedido === 'Pendiente' ? 'var(--blue-bg)' : 'var(--green-bg)' }};
                                               color:{{ $pedido->estado_pedido === 'Pendiente' ? 'var(--blue)' : 'var(--green)' }};">
                                        {{ $pedido->estado_pedido === 'Pendiente' ? '💳 Pagado' : '🚚 Enviado' }}
                                    </button>
                                </form>
                                @endif
                                <a href="{{ route('admin.tienda.pedidos.show', $pedido) }}"
                                   style="padding:4px 10px; background:var(--bg); color:var(--text-2); border:1px solid var(--border); border-radius:6px; font-size:.76rem; font-weight:600; text-decoration:none;">
                                    Ver
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:40px; color:var(--text-muted);">
                            No hay pedidos aún.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pedidos->hasPages())
            <div style="padding:16px 22px; border-top:1px solid var(--border);">
                {{ $pedidos->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
