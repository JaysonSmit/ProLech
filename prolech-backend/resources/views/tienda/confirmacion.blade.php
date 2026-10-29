@extends('layouts.tienda')

@section('title', 'Pedido confirmado — ' . ($pedido?->codigo_pedido ?? $codigo))

@push('styles')
<style>
    .ok-wrap { max-width: 760px; padding-top: 40px; padding-bottom: 20px; }
    .ok-hero { text-align: center; margin-bottom: 30px; }
    .ok-check {
        width: 92px; height: 92px; border-radius: 50%; margin: 0 auto 18px;
        background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; font-size: 2.6rem;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 0 0 12px rgba(34,197,94,.14), 0 20px 40px -16px rgba(22,163,74,.7);
        animation: pop .6s cubic-bezier(.3,1.6,.5,1);
    }
    @keyframes pop { 0% { transform: scale(.3); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
    .ok-hero h1 { font-size: clamp(1.8rem, 4vw, 2.5rem); font-weight: 800; color: var(--brand-dark); letter-spacing: -1px; }
    .ok-hero p { color: var(--text-muted); margin-top: 8px; }

    .ticket {
        background: var(--grad-dark); color: #fff; border-radius: 24px; padding: 24px 28px;
        display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: center;
        box-shadow: var(--shadow-lg); position: relative; overflow: hidden;
    }
    .ticket::after { content: "🧾"; position: absolute; right: -10px; bottom: -30px; font-size: 7rem; opacity: .08; }
    .ticket small { color: #9ec5ff; font-size: .72rem; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; }
    .ticket .code { font-size: 1.9rem; font-weight: 800; letter-spacing: 1.5px; }
    .ticket .boleta-n { font-size: .9rem; color: #cfe1ff; margin-top: 2px; }
    .ticket .monto { text-align: right; }
    .ticket .monto b { display: block; font-size: 1.9rem; font-weight: 800; }

    .cta-row { display: flex; gap: 12px; margin: 18px 0 26px; flex-wrap: wrap; }
    .cta-row a { flex: 1; justify-content: center; min-width: 200px; }

    .box { background: #fff; border: 1px solid var(--border); border-radius: 22px; padding: 20px 22px; margin-bottom: 16px; box-shadow: var(--shadow); }
    .box h3 { font-size: .8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 12px; }
    .line { display: flex; justify-content: space-between; gap: 10px; padding: 9px 0; border-bottom: 1px dashed var(--border); font-size: .9rem; }
    .line:last-child { border-bottom: none; }
    .line small { color: var(--text-muted); display: block; font-size: .78rem; }

    .timeline { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .tl { text-align: center; font-size: .78rem; color: var(--text-muted); font-weight: 600; }
    .tl .dot { width: 40px; height: 40px; border-radius: 50%; margin: 0 auto 6px; display: flex; align-items: center; justify-content: center; background: var(--bg); border: 2px solid var(--border); font-size: 1.1rem; }
    .tl.on .dot { background: var(--grad); border-color: transparent; color: #fff; }
    .tl.on { color: var(--brand-dark); }

    .pay-note { display: flex; gap: 12px; align-items: flex-start; font-size: .88rem; line-height: 1.6; }
    .pay-note .ico { width: 44px; height: 44px; flex-shrink: 0; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }

    @media (max-width: 560px) {
        .ticket { grid-template-columns: 1fr; }
        .ticket .monto { text-align: left; }
    }
</style>
@endpush

@section('content')
<div class="container ok-wrap">

    <div class="ok-hero">
        <div class="ok-check">✓</div>
        <h1>¡Gracias por tu compra!</h1>
        <p>
            @if($pedido)
                {{ explode(' ', $pedido->cliente_nombre)[0] }}, recibimos tu pedido y ya generamos tu boleta.
            @else
                Tu pedido fue registrado correctamente.
            @endif
        </p>
    </div>

    @if($pedido)
        @php $metodo = $pedido->metodo_badge; @endphp

        <div class="ticket">
            <div>
                <small>Número de pedido</small>
                <div class="code">{{ $pedido->codigo_pedido }}</div>
                <div class="boleta-n">Boleta {{ $pedido->numero_comprobante }}</div>
            </div>
            <div class="monto">
                <small>Total</small>
                <b>S/ {{ number_format($pedido->total, 2) }}</b>
            </div>
        </div>

        <div class="cta-row">
            <a href="{{ $urlBoleta }}" class="btn-primary" target="_blank" rel="noopener">🧾 Ver / descargar boleta</a>
            <a href="{{ route('tienda.seguimiento', ['codigo' => $pedido->codigo_pedido]) }}" class="btn-ghost">📦 Seguir mi pedido</a>
        </div>

        <div class="box">
            <h3>Estado del pedido</h3>
            <div class="timeline">
                <div class="tl on"><div class="dot">✓</div>Recibido</div>
                <div class="tl {{ in_array($pedido->estado_pedido, ['Pagado', 'Enviado', 'Entregado']) ? 'on' : '' }}"><div class="dot">💳</div>Pago confirmado</div>
                <div class="tl {{ in_array($pedido->estado_pedido, ['Enviado', 'Entregado']) ? 'on' : '' }}"><div class="dot">🚚</div>En camino</div>
            </div>
        </div>

        <div class="box">
            <h3>Pago</h3>
            <div class="pay-note">
                <span class="ico" style="background: {{ $metodo['bg'] }};">{{ $metodo['icon'] }}</span>
                <div>
                    <b>{{ $metodo['label'] }}</b>@if($pedido->pago_referencia) · {{ $pedido->pago_referencia }}@endif<br>
                    @if($pedido->metodo_pago === 'Efectivo')
                        Pagarás S/ {{ number_format($pedido->total, 2) }} al recibir tu pedido.
                    @elseif($pedido->metodo_pago === 'Yape')
                        Recibimos tu captura. Validaremos el Yape y te escribiremos al {{ $pedido->cliente_telefono }}.
                    @else
                        Pago con tarjeta registrado.
                    @endif
                </div>
            </div>
        </div>

        <div class="box">
            <h3>Productos</h3>
            @foreach($pedido->detalles as $d)
                <div class="line">
                    <div>{{ $d->producto?->nombre ?? 'Producto' }}<small>{{ $d->cantidad }} × S/ {{ number_format($d->precio_unitario, 2) }}</small></div>
                    <b>S/ {{ number_format($d->subtotal, 2) }}</b>
                </div>
            @endforeach
        </div>

        <div class="box">
            <h3>Entrega</h3>
            <div style="font-size:.9rem; line-height:1.7;">
                <b>{{ $pedido->cliente_nombre }}</b> · {{ $pedido->cliente_telefono }}<br>
                {{ $pedido->cliente_direccion }}
            </div>
        </div>
    @else
        <div class="ticket">
            <div>
                <small>Número de pedido</small>
                <div class="code">{{ $codigo }}</div>
            </div>
        </div>
        <div class="cta-row">
            <a href="{{ route('tienda.index') }}" class="btn-primary">🛒 Volver a la tienda</a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Vaciar el carrito solo cuando hay un pedido de esta sesión
    @if($pedido)
        ProLechCart.clear();
    @endif
</script>
@endpush
