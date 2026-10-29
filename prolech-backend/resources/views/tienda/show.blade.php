@extends('layouts.tienda')

@section('title', $producto->nombre)

@php
    $imagenDe = fn ($p) => $p->imagen_url
        ? asset('storage/' . $p->imagen_url)
        : asset('images/producto-placeholder.png');
    $stock = (int) $producto->stock_disponible;
@endphp

@push('styles')
<style>
    .pd-wrap { padding-top: 28px; padding-bottom: 40px; }
    .crumbs { font-size: .82rem; color: var(--text-muted); margin-bottom: 22px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .crumbs a { color: var(--brand); font-weight: 700; }

    .pd-grid { display: grid; grid-template-columns: 1.05fr 1fr; gap: 48px; align-items: start; }

    .pd-media {
        position: relative;
        border-radius: 28px;
        background:
            radial-gradient(circle at 30% 20%, rgba(56,189,248,.25), transparent 50%),
            radial-gradient(circle at 80% 90%, rgba(242,165,65,.18), transparent 45%),
            var(--cream);
        padding: 28px;
        box-shadow: var(--shadow-md);
    }
    .pd-media img {
        width: 100%; aspect-ratio: 1; object-fit: cover; display: block;
        border-radius: 20px; background: #fff;
        box-shadow: 0 30px 60px -28px rgba(11,31,68,.45);
    }
    .pd-sticker {
        position: absolute; top: 44px; left: 44px;
        background: rgba(255,255,255,.95); color: var(--brand-dark);
        padding: 7px 14px; border-radius: 999px; font-size: .78rem; font-weight: 800;
        box-shadow: var(--shadow-md);
    }

    .pd-info { position: sticky; top: 96px; }
    .pill {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: .75rem; font-weight: 800; letter-spacing: .4px;
        padding: 6px 12px; border-radius: 999px;
    }
    .pill.ok  { background: #dcfce7; color: #166534; }
    .pill.low { background: #ffedd5; color: #9a3412; }
    .pill.out { background: #fee2e2; color: #b91c1c; }

    .pd-title {
        font-size: clamp(1.9rem, 3.4vw, 2.6rem); font-weight: 800; letter-spacing: -1px;
        color: var(--brand-dark); line-height: 1.1; margin: 14px 0 10px;
    }
    .pd-price { display: flex; align-items: baseline; gap: 6px; margin-bottom: 18px; flex-wrap: wrap; }
    .pd-price strong { font-size: 2.4rem; font-weight: 800; color: var(--brand-dark); letter-spacing: -1px; }
    .pd-price small { font-size: 1rem; color: var(--brand); font-weight: 800; }
    .pd-price span { font-size: .82rem; color: var(--text-muted); margin-left: 6px; }
    .pd-desc { color: var(--text-2); line-height: 1.75; font-size: .95rem; margin-bottom: 24px; }

    .buy-box {
        background: #fff; border: 1px solid var(--border); border-radius: 22px;
        padding: 20px; box-shadow: var(--shadow-md);
    }
    .buy-row { display: flex; gap: 12px; align-items: stretch; }
    .stepper {
        display: inline-flex; align-items: center; gap: 4px;
        background: var(--bg); border: 1.5px solid var(--border); border-radius: 999px; padding: 4px;
    }
    .stepper button {
        width: 40px; height: 40px; border-radius: 50%; border: none; background: #fff;
        font-size: 1.15rem; font-weight: 800; color: var(--brand-dark); cursor: pointer;
        box-shadow: var(--shadow); transition: background .15s, color .15s;
    }
    .stepper button:hover { background: var(--brand); color: #fff; }
    .stepper button:disabled { opacity: .4; cursor: not-allowed; background: #fff; color: var(--brand-dark); }
    .stepper output { min-width: 36px; text-align: center; font-weight: 800; font-size: 1.05rem; }

    .add-big {
        flex: 1; border: none; border-radius: 999px; cursor: pointer;
        background: var(--grad); color: #fff; font: inherit; font-weight: 800; font-size: 1rem;
        padding: 0 22px; min-height: 52px;
        box-shadow: 0 14px 28px -12px rgba(31,111,235,.85);
        transition: filter .15s, transform .15s;
    }
    .add-big:hover { filter: brightness(1.07); transform: translateY(-1px); }
    .add-big.done { background: linear-gradient(135deg, #16a34a, #22c55e); }
    .subtotal-line { display: flex; justify-content: space-between; margin-top: 14px; font-size: .88rem; color: var(--text-muted); }
    .subtotal-line b { color: var(--brand-dark); font-size: 1rem; }

    .perks { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 18px; }
    .perk { background: var(--cream); border-radius: 14px; padding: 12px; font-size: .76rem; color: var(--text-2); font-weight: 600; text-align: center; }
    .perk b { display: block; font-size: 1.2rem; margin-bottom: 4px; }

    .back-link { display: inline-flex; gap: 6px; margin-top: 18px; color: var(--brand); font-weight: 700; font-size: .88rem; }

    .rel-title { font-size: 1.5rem; font-weight: 800; color: var(--brand-dark); letter-spacing: -.5px; margin: 70px 0 20px; }
    .rel-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 20px; }
    .rel-card { background: #fff; border: 1px solid var(--border); border-radius: 20px; overflow: hidden; transition: transform .2s, box-shadow .2s; }
    .rel-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
    .rel-card img { width: 100%; aspect-ratio: 4/3; object-fit: cover; background: var(--cream); display: block; }
    .rel-card div { padding: 14px 16px; }
    .rel-card h3 { font-size: .95rem; font-weight: 700; color: var(--text); }
    .rel-card p { font-weight: 800; color: var(--brand); margin-top: 4px; }

    @media (max-width: 860px) {
        .pd-grid { grid-template-columns: 1fr; gap: 28px; }
        .pd-info { position: static; }
        .pd-media { padding: 16px; border-radius: 22px; }
        .pd-sticker { top: 28px; left: 28px; }
    }
    @media (max-width: 480px) {
        .buy-row { flex-direction: column; }
        .stepper { justify-content: space-between; }
        .perks { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="container pd-wrap">

    <nav class="crumbs" aria-label="Ruta de navegación">
        <a href="{{ route('tienda.index') }}">Tienda</a> <span>›</span>
        <a href="{{ route('tienda.index') }}#catalogo">Catálogo</a> <span>›</span>
        <span>{{ $producto->nombre }}</span>
    </nav>

    <div class="pd-grid">
        <div class="pd-media">
            <img src="{{ $imagenDe($producto) }}" alt="{{ $producto->nombre }}"
                 onerror="this.src='{{ asset('images/producto-placeholder.png') }}'">
            <span class="pd-sticker">🥛 Hecho en Huata</span>
        </div>

        <div class="pd-info">
            @if($stock === 0)
                <span class="pill out">✗ Agotado</span>
            @elseif($stock <= 5)
                <span class="pill low">🔥 ¡Últimas {{ $stock }} unidades!</span>
            @else
                <span class="pill ok">✓ En stock · {{ $stock }} disponibles</span>
            @endif

            <h1 class="pd-title">{{ $producto->nombre }}</h1>

            <div class="pd-price">
                <small>S/</small><strong>{{ number_format($producto->precio_venta, 2) }}</strong>
                <span>por unidad · IGV incluido</span>
            </div>

            @if($producto->descripcion)
                <p class="pd-desc">{{ $producto->descripcion }}</p>
            @endif

            @if($stock > 0)
            <div class="buy-box">
                <div class="buy-row">
                    <div class="stepper" role="group" aria-label="Cantidad">
                        <button type="button" id="menos" onclick="cambiarCantidad(-1)" aria-label="Restar">−</button>
                        <output id="cantidad" aria-live="polite">1</output>
                        <button type="button" id="mas" onclick="cambiarCantidad(1)" aria-label="Sumar">+</button>
                    </div>
                    <button type="button" class="add-big" id="add-btn" onclick="agregarAlCarrito()">
                        🛒 Añadir al carrito
                    </button>
                </div>
                <div class="subtotal-line">
                    <span>Subtotal</span>
                    <b id="subtotal">S/ {{ number_format($producto->precio_venta, 2) }}</b>
                </div>
            </div>
            @else
            <div class="buy-box" style="text-align:center; color:#b91c1c; font-weight:700;">
                Producto agotado temporalmente. ¡Vuelve pronto!
            </div>
            @endif

            <div class="perks">
                <div class="perk"><b>🔬</b>Leche con control de calidad</div>
                <div class="perk"><b>💳</b>Yape, tarjeta o efectivo</div>
                <div class="perk"><b>🧾</b>Boleta al finalizar</div>
            </div>

            <a href="{{ route('tienda.index') }}#catalogo" class="back-link">← Seguir comprando</a>
        </div>
    </div>

    @if($relacionados->isNotEmpty())
        <h2 class="rel-title">También te puede gustar</h2>
        <div class="rel-grid">
            @foreach($relacionados as $rel)
                <a href="{{ route('tienda.show', $rel->slug) }}" class="rel-card">
                    <img src="{{ $imagenDe($rel) }}" alt="{{ $rel->nombre }}" loading="lazy">
                    <div>
                        <h3>{{ $rel->nombre }}</h3>
                        <p>S/ {{ number_format($rel->precio_venta, 2) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Se usa la directiva json de Blade: el doble llaveado escapaba las comillas a &quot; y rompía el script
    const PRODUCTO = {
        id:     {{ $producto->id }},
        nombre: @json($producto->nombre),
        precio: {{ (float) $producto->precio_venta }},
        imagen: @json($imagenDe($producto)),
        stock:  {{ $stock }}
    };
    let cantidad = 1;

    function pintar() {
        document.getElementById('cantidad').textContent = cantidad;
        document.getElementById('subtotal').textContent = 'S/ ' + (PRODUCTO.precio * cantidad).toFixed(2);
        document.getElementById('menos').disabled = cantidad <= 1;
        document.getElementById('mas').disabled = cantidad >= PRODUCTO.stock;
    }

    function cambiarCantidad(delta) {
        cantidad = Math.max(1, Math.min(PRODUCTO.stock, cantidad + delta));
        pintar();
    }

    function agregarAlCarrito() {
        if (!ProLechCart.addMany(PRODUCTO, cantidad)) return;
        const btn = document.getElementById('add-btn');
        btn.classList.add('done');
        btn.textContent = '✓ ¡Añadido!';
        setTimeout(() => {
            btn.classList.remove('done');
            btn.textContent = '🛒 Añadir al carrito';
        }, 1600);
    }

    if (document.getElementById('cantidad')) pintar();
</script>
@endpush
