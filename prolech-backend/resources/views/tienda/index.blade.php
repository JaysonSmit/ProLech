@extends('layouts.tienda')

@section('title', 'Catálogo de Productos')

@php
    // Categoría deducida del nombre (la tabla no tiene columna de categoría)
    $categoriaDe = function (string $nombre): array {
        $n = Str::lower(Str::ascii($nombre));
        return match (true) {
            str_contains($n, 'queso')                               => ['Quesos', '🧀'],
            str_contains($n, 'yogur')                               => ['Yogures', '🥣'],
            str_contains($n, 'manjar'), str_contains($n, 'dulce')   => ['Dulces', '🍯'],
            str_contains($n, 'mantequilla')                         => ['Mantequilla', '🧈'],
            str_contains($n, 'leche')                               => ['Leche', '🥛'],
            default                                                 => ['Otros', '🥄'],
        };
    };

    $imagenDe = fn ($p) => $p->imagen_url
        ? asset('storage/' . $p->imagen_url)
        : asset('images/producto-placeholder.png');

    $categorias  = $productos->map(fn ($p) => $categoriaDe($p->nombre))->unique(fn ($c) => $c[0])->values();
    $disponibles = $productos->where('stock_disponible', '>', 0);
    $destacado   = $disponibles->first(fn ($p) => $p->imagen_url) ?? $disponibles->first() ?? $productos->first();
    $precioDesde = $disponibles->min('precio_venta');
@endphp

@push('styles')
<style>
    /* ── HERO ───────────────────────────────────────────────────────── */
    .hero {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at 85% 15%, rgba(56,189,248,.28), transparent 45%),
            radial-gradient(circle at 5% 95%, rgba(242,165,65,.18), transparent 40%),
            linear-gradient(180deg, var(--cream) 0%, var(--bg) 100%);
        padding: 64px 0 84px;
    }
    .hero-grid {
        display: grid;
        grid-template-columns: 1.1fr .9fr;
        gap: 48px;
        align-items: center;
    }
    .eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        background: #fff;
        border: 1px solid var(--cream-2);
        color: var(--brand);
        font-size: .78rem; font-weight: 700;
        padding: 6px 14px 6px 8px;
        border-radius: 999px;
        box-shadow: var(--shadow);
    }
    .eyebrow .dot {
        width: 22px; height: 22px; border-radius: 50%;
        background: var(--cream); display: inline-flex; align-items: center; justify-content: center;
        font-size: .75rem;
    }
    .hero h1 {
        font-family: var(--font-display);
        font-size: clamp(2.3rem, 5vw, 3.6rem);
        line-height: 1.05;
        letter-spacing: -1.6px;
        font-weight: 800;
        color: var(--brand-dark);
        margin: 20px 0 18px;
    }
    .hero h1 em {
        font-style: normal;
        background: var(--grad);
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent;
        position: relative;
        white-space: nowrap;
        z-index: 0;
    }
    .hero h1 em::after {
        content: ""; position: absolute; left: 0; right: 0; bottom: 4px; height: 10px;
        background: rgba(242,165,65,.35); border-radius: 6px; z-index: -1;
    }
    .hero-lead { font-size: 1.08rem; line-height: 1.7; color: var(--text-2); max-width: 520px; }
    .hero-ctas { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
    .hero-stats { display: flex; flex-wrap: wrap; gap: 28px; margin-top: 38px; }
    .hero-stat strong {
        display: block;
        font-family: var(--font-display);
        font-size: 1.7rem; font-weight: 800; color: var(--brand-dark); line-height: 1.1;
    }
    .hero-stat span { font-size: .8rem; color: var(--text-muted); font-weight: 500; }

    /* Composición visual del hero */
    .hero-visual { position: relative; aspect-ratio: 1; max-width: 460px; justify-self: center; width: 100%; }
    .hero-blob {
        position: absolute; inset: 6%;
        background: linear-gradient(140deg, #38bdf8 0%, #1f6feb 55%, #0b1f44 100%);
        border-radius: 42% 58% 55% 45% / 48% 42% 58% 52%;
        animation: morph 12s ease-in-out infinite;
        box-shadow: var(--shadow-lg);
    }
    @keyframes morph {
        0%, 100% { border-radius: 42% 58% 55% 45% / 48% 42% 58% 52%; }
        50%      { border-radius: 56% 44% 42% 58% / 40% 58% 42% 60%; }
    }
    .hero-plate {
        position: absolute; inset: 17%;
        background: #fff;
        border-radius: 50%;
        overflow: hidden;
        box-shadow: 0 30px 60px -20px rgba(0,0,0,.45), inset 0 0 0 10px var(--cream);
        animation: float 6s ease-in-out infinite;
    }
    .hero-plate img { width: 100%; height: 100%; object-fit: cover; }
    @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    .hero-tag {
        position: absolute;
        background: #fff;
        border-radius: 16px;
        padding: 12px 16px;
        box-shadow: var(--shadow-md);
        display: flex; align-items: center; gap: 10px;
        font-size: .78rem; color: var(--text-muted);
    }
    .hero-tag b { display: block; font-size: .95rem; color: var(--text); }
    .hero-tag .ico {
        width: 36px; height: 36px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }
    .hero-tag.t1 { left: -4%; bottom: 14%; }
    .hero-tag.t2 { right: -2%; top: 10%; }
    .hero-wave { position: absolute; left: 0; right: 0; bottom: -1px; height: 50px; width: 100%; }

    /* ── FRANJA DE CONFIANZA ───────────────────────────────────────── */
    .trust {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
        margin-top: -36px; position: relative; z-index: 2;
    }
    .trust-item {
        background: #fff; border-radius: var(--radius);
        border: 1px solid var(--border);
        padding: 18px 20px;
        display: flex; gap: 14px; align-items: flex-start;
        box-shadow: var(--shadow-md);
    }
    .trust-item .ico {
        flex-shrink: 0; width: 44px; height: 44px; border-radius: 12px;
        background: var(--cream); display: inline-flex; align-items: center; justify-content: center; font-size: 1.3rem;
    }
    .trust-item h3 { font-size: .92rem; margin-bottom: 3px; }
    .trust-item p  { font-size: .8rem; color: var(--text-muted); line-height: 1.5; }

    /* ── CATÁLOGO ───────────────────────────────────────────────────── */
    .section-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 22px; }
    .section-kicker { color: var(--brand); font-weight: 700; font-size: .78rem; letter-spacing: 1.5px; text-transform: uppercase; }
    .section-title { font-family: var(--font-display); font-weight: 800; font-size: clamp(1.7rem, 3vw, 2.3rem); color: var(--brand-dark); letter-spacing: -.6px; margin-top: 6px; }

    .toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .search { position: relative; }
    .search input {
        width: 260px; max-width: 100%;
        padding: 11px 16px 11px 40px;
        border-radius: 999px;
        border: 1.5px solid var(--border);
        background: #fff;
        font: inherit; font-size: .88rem;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
    }
    .search input:focus { border-color: var(--brand); box-shadow: 0 0 0 4px rgba(31,111,235,.14); }
    .search::before {
        content: "🔍"; position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: .85rem; opacity: .6;
    }
    .sort {
        padding: 11px 14px; border-radius: 999px; border: 1.5px solid var(--border);
        background: #fff; font: inherit; font-size: .85rem; font-weight: 600; color: var(--text-2); cursor: pointer;
    }

    .chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 26px; }
    .chip {
        padding: 8px 16px; border-radius: 999px;
        border: 1.5px solid var(--border); background: #fff;
        font: inherit; font-size: .84rem; font-weight: 600; color: var(--text-2);
        cursor: pointer; transition: all .15s;
    }
    .chip:hover { border-color: var(--brand); color: var(--brand); }
    .chip.active { background: var(--brand-dark); border-color: var(--brand-dark); color: #fff; box-shadow: 0 8px 16px -8px rgba(11,31,68,.7); }

    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 24px;
    }

    .card {
        background: var(--bg-card);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        overflow: hidden;
        display: flex; flex-direction: column;
        transition: transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s;
        animation: cardIn .5s cubic-bezier(.2,.8,.2,1) both;
    }
    @keyframes cardIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    .card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); }

    .card-media {
        position: relative; display: block;
        aspect-ratio: 1 / .85;
        background: radial-gradient(circle at 50% 40%, #fff 0%, var(--cream) 70%);
        overflow: hidden;
    }
    .card-media img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s cubic-bezier(.2,.8,.2,1); }
    .card:hover .card-media img { transform: scale(1.07); }
    .card-media .ver {
        position: absolute; left: 50%; bottom: 14px; transform: translate(-50%, 12px);
        background: rgba(255,255,255,.95); color: var(--brand);
        font-size: .78rem; font-weight: 700;
        padding: 7px 14px; border-radius: 999px;
        opacity: 0; transition: all .25s; box-shadow: var(--shadow-md);
        white-space: nowrap;
    }
    .card:hover .card-media .ver { opacity: 1; transform: translate(-50%, 0); }

    .badge {
        position: absolute; top: 12px;
        font-size: .72rem; font-weight: 700;
        padding: 5px 10px; border-radius: 999px;
    }
    .badge.cat { left: 12px; background: rgba(255,255,255,.94); color: var(--text-2); box-shadow: var(--shadow); }
    .badge.low { right: 12px; background: #fef3c7; color: #92400e; }
    .badge.out { right: 12px; background: #fee2e2; color: #b91c1c; }

    .card-body { padding: 18px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
    .card-title { font-family: var(--font-display); font-size: 1.1rem; font-weight: 800; color: var(--text); line-height: 1.25; }
    .card-desc { font-size: .82rem; color: var(--text-muted); line-height: 1.55; }

    .stock-bar { height: 5px; background: #f1f5f9; border-radius: 999px; overflow: hidden; margin-top: 4px; }
    .stock-bar span { display: block; height: 100%; background: var(--grad); border-radius: inherit; }
    .stock-txt { font-size: .74rem; color: var(--text-muted); font-weight: 600; }

    .card-foot { display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 14px; }
    .price { font-family: var(--font-display); font-size: 1.5rem; font-weight: 800; color: var(--brand-dark); line-height: 1; }
    .price small { color: var(--brand); font-size: .78rem; font-weight: 700; margin-right: 3px; }

    .add-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 16px;
        background: var(--grad); color: #fff;
        border: none; border-radius: 999px;
        font: inherit; font-weight: 700; font-size: .84rem;
        cursor: pointer;
        box-shadow: 0 10px 18px -8px rgba(31,111,235,.8);
        transition: background .15s, transform .15s;
    }
    .add-btn:hover { filter: brightness(1.07); transform: translateY(-1px); }
    .add-btn:active { transform: scale(.95); }
    .add-btn.added { background: var(--accent); color: var(--brand-dark); }
    .sold-out { padding: 9px 14px; background: #f1f5f9; color: var(--text-muted); border-radius: 999px; font-size: .8rem; font-weight: 700; }

    .no-results { display: none; text-align: center; padding: 50px 20px; color: var(--text-muted); }

    /* ── PROCESO ───────────────────────────────────────────────────── */
    .process {
        margin-top: 90px;
        background: var(--grad-dark);
        border-radius: 28px;
        padding: 56px 48px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .process::after {
        content: "🐄"; position: absolute; right: 30px; bottom: -30px; font-size: 11rem; opacity: .07;
    }
    .process .section-kicker { color: var(--sky); }
    .process .section-title { color: #fff; }
    .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; margin-top: 34px; position: relative; z-index: 1; }
    .step {
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: var(--radius);
        padding: 24px;
        transition: background .2s, transform .2s;
    }
    .step:hover { background: rgba(255,255,255,.1); transform: translateY(-4px); }
    .step-num {
        width: 42px; height: 42px; border-radius: 12px;
        background: var(--grad); color: #fff;
        font-family: var(--font-display); font-weight: 800; font-size: 1.2rem;
        display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;
    }
    .step h3 { font-size: 1.05rem; margin-bottom: 8px; }
    .step p { font-size: .86rem; line-height: 1.65; color: rgba(255,255,255,.72); }

    /* ── CTA FINAL ─────────────────────────────────────────────────── */
    .cta {
        margin-top: 40px;
        background: linear-gradient(120deg, #fde4bc 0%, #f2a541 100%);
        border-radius: 24px;
        padding: 36px 40px;
        display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap;
    }
    .cta h2 { font-family: var(--font-display); font-weight: 800; font-size: 1.7rem; color: var(--brand-dark); letter-spacing: -.4px; }
    .cta p { color: #7c4a0b; margin-top: 4px; font-size: .92rem; }

    @media (max-width: 900px) {
        .hero { padding: 40px 0 70px; }
        .hero-grid { grid-template-columns: 1fr; gap: 30px; }
        .hero-visual { max-width: 340px; order: -1; }
    }
    @media (max-width: 560px) {
        .hero-visual { max-width: 250px; }
        .trust, .steps { grid-template-columns: 1fr; }
        .process { padding: 40px 24px; }
    }
    @media (max-width: 560px) {
        .hero-tag { display: none; }
        .toolbar, .search, .search input { width: 100%; }
        .sort { width: 100%; }
        .cta { padding: 28px 24px; }
    }
</style>
@endpush

@section('content')

{{-- ═══════════════════════════ HERO ═══════════════════════════ --}}
<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="eyebrow"><span class="dot">🌿</span> Directo de nuestra planta en Huata, Puno</span>
            <h1>Lácteos frescos,<br>del campo <em>a tu mesa</em></h1>
            <p class="hero-lead">
                Quesos, yogures, manjar y más, elaborados con leche que acopiamos
                cada mañana a ganaderos de Huata y que pasa control de calidad antes de entrar a planta.
            </p>
            <div class="hero-ctas">
                <a href="#catalogo" class="btn-primary">Ver productos ↓</a>
                <a href="#proceso" class="btn-ghost">Cómo trabajamos</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <strong>{{ $disponibles->count() }}</strong>
                    <span>{{ $disponibles->count() === 1 ? 'producto disponible' : 'productos disponibles' }}</span>
                </div>
                @if($precioDesde)
                <div class="hero-stat">
                    <strong>S/ {{ number_format($precioDesde, 2) }}</strong>
                    <span>precios desde</span>
                </div>
                @endif
                <div class="hero-stat">
                    <strong>100%</strong>
                    <span>producción local</span>
                </div>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-blob" aria-hidden="true"></div>
            <div class="hero-plate" aria-hidden="true">
                <img src="{{ $destacado ? $imagenDe($destacado) : asset('images/producto-placeholder.png') }}" alt="">
            </div>
            @if($destacado)
            <a class="hero-tag t1" href="{{ route('tienda.show', $destacado->slug) }}">
                <span class="ico" style="background:var(--cream);">⭐</span>
                <span>Destacado<b>{{ Str::limit($destacado->nombre, 22) }}</b></span>
            </a>
            <div class="hero-tag t2">
                <span class="ico" style="background:var(--accent-soft);">🏷️</span>
                <span>Precio<b>S/ {{ number_format($destacado->precio_venta, 2) }}</b></span>
            </div>
            @endif
        </div>
    </div>
    <svg class="hero-wave" viewBox="0 0 1440 50" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 50V28C200 8 420 0 720 18s560 30 720 4v28z" fill="#f6f9fe"/>
    </svg>
</section>

<div class="container">

    {{-- ═══════════════════ FRANJA DE CONFIANZA ═══════════════════ --}}
    <div class="trust">
        <div class="trust-item">
            <span class="ico">🔬</span>
            <div>
                <h3>Calidad verificada</h3>
                <p>Cada entrega de leche se mide en agua y densidad antes de procesarse.</p>
            </div>
        </div>
        <div class="trust-item">
            <span class="ico">🏡</span>
            <div>
                <h3>Ganaderos de Huata</h3>
                <p>Compramos directo a productores de la zona, sin intermediarios.</p>
            </div>
        </div>
        <div class="trust-item">
            <span class="ico">🛒</span>
            <div>
                <h3>Pedido en minutos</h3>
                <p>Añade al carrito, confirma tus datos y listo.</p>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════ CATÁLOGO ═══════════════════════ --}}
    <section id="catalogo" style="padding-top:72px; scroll-margin-top:80px;">
        <div class="section-head">
            <div>
                <div class="section-kicker">Nuestro catálogo</div>
                <h2 class="section-title">Elaborados en nuestra planta</h2>
            </div>
            @if($productos->isNotEmpty())
            <div class="toolbar">
                <label class="search">
                    <input type="search" id="buscar" placeholder="Buscar producto…" aria-label="Buscar producto">
                </label>
                <select class="sort" id="ordenar" aria-label="Ordenar productos">
                    <option value="destacados">Destacados</option>
                    <option value="precio-asc">Precio: menor a mayor</option>
                    <option value="precio-desc">Precio: mayor a menor</option>
                    <option value="nombre">Nombre A–Z</option>
                </select>
            </div>
            @endif
        </div>

        @if($productos->isEmpty())
            <div class="empty-state">
                <div class="icon">📦</div>
                <h3>No hay productos disponibles en este momento.</h3>
                <p style="margin-top:6px; font-size:.88rem;">Vuelve pronto, estamos preparando nuevos lotes.</p>
            </div>
        @else
            @if($categorias->count() > 1)
            <div class="chips">
                <button class="chip active" data-cat="todos">Todos</button>
                @foreach($categorias as [$nombreCat, $icono])
                    <button class="chip" data-cat="{{ $nombreCat }}">{{ $icono }} {{ $nombreCat }}</button>
                @endforeach
            </div>
            @endif

            <div class="grid" id="grid">
                @foreach($productos as $producto)
                    @php
                        [$cat, $catIcono] = $categoriaDe($producto->nombre);
                        $stock = (int) $producto->stock_disponible;
                        $pocas = $stock > 0 && $stock <= 5;
                        $nivel = min(100, max(8, $stock * 5));
                    @endphp
                    <article class="card"
                             style="animation-delay: {{ $loop->index * 70 }}ms"
                             data-nombre="{{ Str::lower(Str::ascii($producto->nombre)) }}"
                             data-cat="{{ $cat }}"
                             data-precio="{{ (float) $producto->precio_venta }}"
                             data-orden="{{ $loop->index }}">
                        <a href="{{ route('tienda.show', $producto->slug) }}" class="card-media">
                            <img src="{{ $imagenDe($producto) }}" alt="{{ $producto->nombre }}" loading="lazy"
                                 onerror="this.src='{{ asset('images/producto-placeholder.png') }}'">
                            <span class="badge cat">{{ $catIcono }} {{ $cat }}</span>
                            @if($stock === 0)
                                <span class="badge out">Agotado</span>
                            @elseif($pocas)
                                <span class="badge low">¡Últimas {{ $stock }}!</span>
                            @endif
                            <span class="ver">Ver detalle →</span>
                        </a>

                        <div class="card-body">
                            <a href="{{ route('tienda.show', $producto->slug) }}">
                                <h3 class="card-title">{{ $producto->nombre }}</h3>
                            </a>
                            @if($producto->descripcion)
                                <p class="card-desc">{{ Str::limit($producto->descripcion, 90) }}</p>
                            @endif

                            @if($stock > 0)
                                <div class="stock-bar" aria-hidden="true"><span style="width: {{ $nivel }}%"></span></div>
                                <div class="stock-txt">{{ $stock }} {{ $stock === 1 ? 'unidad disponible' : 'unidades disponibles' }}</div>
                            @endif

                            <div class="card-foot">
                                <div class="price"><small>S/</small>{{ number_format($producto->precio_venta, 2) }}</div>
                                @if($stock > 0)
                                    <button class="add-btn"
                                            onclick='agregar(this, {
                                                id: {{ $producto->id }},
                                                nombre: @json($producto->nombre),
                                                precio: {{ (float) $producto->precio_venta }},
                                                imagen: @json($imagenDe($producto)),
                                                stock: {{ $stock }}
                                            })'
                                            aria-label="Añadir {{ $producto->nombre }} al carrito">
                                        <span aria-hidden="true">＋</span> Añadir
                                    </button>
                                @else
                                    <span class="sold-out">Agotado</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="no-results" id="sin-resultados">
                <div style="font-size:2.4rem; margin-bottom:10px;">🔎</div>
                <p>No encontramos productos con ese nombre.</p>
            </div>
        @endif
    </section>

    {{-- ═══════════════════ DEL CAMPO A TU MESA ═══════════════════ --}}
    <section class="process" id="proceso" style="scroll-margin-top:80px;">
        <div class="section-kicker">Del campo a tu mesa</div>
        <h2 class="section-title">Así llega la leche a tu casa</h2>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Acopio en la ruta</h3>
                <p>Cada mañana nuestros choferes recorren Huata y registran la leche de cada ganadero, incluso sin señal.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>Control de calidad</h3>
                <p>Medimos agua añadida y densidad en cada entrega. La leche observada o rechazada no entra a planta.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Elaboración en planta</h3>
                <p>Seguimos recetas por lotes con trazabilidad de insumos, y lo publicamos aquí apenas sale.</p>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════ CTA ═══════════════════════ --}}
    <section class="cta">
        <div>
            <h2>¿Ya elegiste tus lácteos?</h2>
            <p>Revisa tu carrito y confirma tu pedido en un par de pasos.</p>
        </div>
        <button class="btn-primary" style="background:var(--brand-dark)" onclick="ProLechCart.openDrawer()">Ver mi carrito 🛒</button>
    </section>
</div>
@endsection

@push('scripts')
<script>
    // Añadir con feedback visual en el botón
    function agregar(btn, producto) {
        ProLechCart.add(producto);
        btn.classList.add('added');
        btn.innerHTML = '✓ Añadido';
        setTimeout(() => {
            btn.classList.remove('added');
            btn.innerHTML = '<span aria-hidden="true">＋</span> Añadir';
        }, 1400);
    }

    // Búsqueda, filtro por categoría y orden — todo en el cliente
    (() => {
        const grid = document.getElementById('grid');
        if (!grid) return;
        const cards   = [...grid.querySelectorAll('.card')];
        const buscar  = document.getElementById('buscar');
        const ordenar = document.getElementById('ordenar');
        const vacio   = document.getElementById('sin-resultados');
        let categoria = 'todos';

        const normalizar = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

        function aplicar() {
            const q = normalizar(buscar.value.trim());
            let visibles = 0;
            cards.forEach(c => {
                const ok = (categoria === 'todos' || c.dataset.cat === categoria)
                        && (!q || c.dataset.nombre.includes(q));
                c.style.display = ok ? '' : 'none';
                if (ok) visibles++;
            });
            vacio.style.display = visibles ? 'none' : 'block';

            const criterio = ordenar.value;
            [...cards].sort((a, b) => {
                if (criterio === 'precio-asc')  return a.dataset.precio - b.dataset.precio;
                if (criterio === 'precio-desc') return b.dataset.precio - a.dataset.precio;
                if (criterio === 'nombre')      return a.dataset.nombre.localeCompare(b.dataset.nombre);
                return a.dataset.orden - b.dataset.orden;
            }).forEach(c => grid.appendChild(c));
        }

        document.querySelectorAll('.chip').forEach(chip => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                categoria = chip.dataset.cat;
                aplicar();
            });
        });
        buscar.addEventListener('input', aplicar);
        ordenar.addEventListener('change', aplicar);
    })();
</script>
@endpush
