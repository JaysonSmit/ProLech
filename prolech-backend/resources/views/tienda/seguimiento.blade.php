@extends('layouts.tienda')

@section('title', 'Seguir mi pedido')

@push('styles')
<style>
    .seg { max-width: 640px; padding-top: 40px; padding-bottom: 20px; }
    .seg h1 { font-size: clamp(1.8rem, 4vw, 2.4rem); font-weight: 800; color: var(--brand-dark); letter-spacing: -1px; }
    .seg .sub { color: var(--text-muted); margin: 6px 0 22px; }
    .buscador { background: #fff; border: 1px solid var(--border); border-radius: 22px; padding: 20px; box-shadow: var(--shadow-md);
        display: grid; grid-template-columns: 1.3fr 1fr auto; gap: 10px; align-items: end; }
    .buscador label { font-size: .78rem; font-weight: 700; color: var(--text-2); display: block; margin-bottom: 6px; }
    .buscador input { width: 100%; padding: 12px 14px; border-radius: 14px; border: 1.5px solid var(--border); background: var(--bg); font: inherit; }
    .buscador input:focus { outline: none; border-color: var(--brand); background: #fff; }
    .err { margin-top: 14px; background: #fef2f2; color: #b91c1c; border-radius: 14px; padding: 12px 16px; font-weight: 600; font-size: .9rem; }
    .estado { margin-top: 22px; background: #fff; border: 1px solid var(--border); border-radius: 22px; padding: 22px; box-shadow: var(--shadow); }
    .pasos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin: 18px 0; position: relative; }
    .paso { text-align: center; font-size: .78rem; color: var(--text-muted); font-weight: 600; }
    .paso .bola { width: 42px; height: 42px; border-radius: 50%; margin: 0 auto 6px; display: flex; align-items: center; justify-content: center;
        background: var(--bg); border: 2px solid var(--border); font-size: 1.1rem; }
    .paso.on { color: var(--brand-dark); } .paso.on .bola { background: var(--grad); color: #fff; border-color: transparent; }
    .paso.cancel .bola { background: #fee2e2; border-color: #fecaca; }
    .linea { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed var(--border); font-size: .9rem; }
    @media (max-width: 560px) { .buscador { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="container seg">
    <h1>📦 Seguir mi pedido</h1>
    <p class="sub">Escribe el código que recibiste (ej. WEB-1001) y los últimos 3 dígitos de tu celular.</p>

    <form class="buscador" method="GET">
        <div>
            <label for="codigo">Código del pedido</label>
            <input id="codigo" name="codigo" value="{{ $codigo }}" placeholder="WEB-1001" required autocomplete="off">
        </div>
        <div>
            <label for="telefono">Últimos dígitos del celular</label>
            <input id="telefono" name="telefono" inputmode="numeric" maxlength="9" placeholder="777" required>
        </div>
        <button class="btn-primary" type="submit">Buscar</button>
    </form>

    @if($error)<div class="err">⚠️ {{ $error }}</div>@endif

    @if($pedido)
        @php
            $orden = ['Pendiente' => 1, 'Pagado' => 2, 'Enviado' => 3];
            $nivel = $orden[$pedido->estado_pedido] ?? 0;
            $cancelado = $pedido->estado_pedido === 'Cancelado';
        @endphp
        <div class="estado">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px">
                <div>
                    <div style="font-size:.75rem; color:var(--text-muted); font-weight:700; letter-spacing:1px">PEDIDO</div>
                    <div style="font-size:1.6rem; font-weight:800; color:var(--brand-dark)">{{ $pedido->codigo_pedido }}</div>
                </div>
                <div style="font-size:1.5rem; font-weight:800">S/ {{ number_format($pedido->total, 2) }}</div>
            </div>

            @if($cancelado)
                <div class="err" style="margin-top:16px">Este pedido fue cancelado. Si tienes dudas, escríbenos por WhatsApp.</div>
            @else
                <div class="pasos">
                    <div class="paso on"><div class="bola">✓</div>Recibido</div>
                    <div class="paso {{ $nivel >= 2 ? 'on' : '' }}"><div class="bola">💳</div>Pago confirmado</div>
                    <div class="paso {{ $nivel >= 3 ? 'on' : '' }}"><div class="bola">🚚</div>En camino</div>
                    <div class="paso {{ $nivel >= 3 ? 'on' : '' }}"><div class="bola">🏠</div>Entrega</div>
                </div>
            @endif

            @foreach($pedido->detalles as $d)
                <div class="linea"><span>{{ $d->cantidad }} × {{ $d->producto?->nombre ?? 'Producto' }}</span><b>S/ {{ number_format($d->subtotal, 2) }}</b></div>
            @endforeach
            <div style="display:flex; gap:10px; margin-top:16px; flex-wrap:wrap">
                <a href="{{ $urlBoleta }}" class="btn-primary" target="_blank" rel="noopener">🧾 Ver boleta {{ $pedido->numero_comprobante }}</a>
                <a href="{{ route('tienda.index') }}#catalogo" class="btn-ghost">Seguir comprando</a>
            </div>
        </div>
    @endif
</div>
@endsection
