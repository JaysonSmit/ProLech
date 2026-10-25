<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ProLech Tienda · @yield('title', 'Productos Lácteos')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        /* Paleta "Leche & Lago": azul leche, marino profundo y caramelo */
        --brand:        #1f6feb;
        --brand-mid:    #1858c4;
        --brand-light:  #3b8bff;
        --brand-dark:   #0b1f44;
        --sky:          #38bdf8;
        --grad:         linear-gradient(135deg, #1f6feb 0%, #38bdf8 100%);
        --grad-dark:    linear-gradient(135deg, #0b1f44 0%, #163a7a 100%);
        --accent:       #f2a541;
        --accent-soft:  #fde4bc;
        --cream:        #eef5ff;
        --cream-2:      #dbe9ff;
        --bg:           #f6f9fe;
        --bg-card:      #ffffff;
        --border:       #e3e9f3;
        --text:         #0f172a;
        --text-2:       #334155;
        --text-muted:   #64748b;
        --radius:       18px;
        --radius-sm:    10px;
        --shadow:       0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        --shadow-md:    0 10px 24px -10px rgba(15,23,42,.18);
        --shadow-lg:    0 24px 48px -16px rgba(31,111,235,.35);
        --font-display: 'Plus Jakarta Sans', 'Inter', sans-serif;
    }

    html { font-size: 15px; scroll-behavior: smooth; }
    body {
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        -webkit-font-smoothing: antialiased;
    }

    a { color: inherit; text-decoration: none; }

    /* ── BARRA SUPERIOR ───────────────────────────────────────────── */
    .topbar {
        background: var(--grad-dark);
        color: #cfe1ff;
        font-size: .78rem;
        font-weight: 500;
        text-align: center;
        padding: 8px 16px;
    }
    .topbar strong { color: #fff; font-weight: 700; }

    /* ── NAV ──────────────────────────────────────────────────────── */
    .nav {
        background: rgba(255,255,255,.82);
        backdrop-filter: saturate(160%) blur(12px);
        -webkit-backdrop-filter: saturate(160%) blur(12px);
        padding: 0 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 68px;
        position: sticky;
        top: 0;
        z-index: 100;
        border-bottom: 1px solid rgba(11,31,68,.07);
    }

    .nav-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--brand-dark);
        font-family: var(--font-display);
        font-weight: 800;
        font-size: 1.3rem;
        letter-spacing: -.3px;
    }

    .nav-logo {
        width: 38px; height: 38px;
        border-radius: 12px;
        background: var(--grad);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.15rem;
        box-shadow: 0 8px 18px -8px rgba(31,111,235,.8);
    }

    .nav-brand em { font-style: normal; font-weight: 800; background: var(--grad); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }

    .nav-links { display: flex; align-items: center; gap: 4px; }

    .nav-links a {
        color: var(--text-2);
        font-size: .88rem;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 20px;
        transition: background .15s, color .15s;
    }

    .nav-links a:hover,
    .nav-links a.active { background: var(--cream); color: var(--brand); }

    /* Botón carrito */
    .cart-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--brand-dark);
        color: #fff;
        font-weight: 700;
        font-size: .88rem;
        padding: 9px 18px;
        margin-left: 8px;
        border-radius: 999px;
        border: none;
        cursor: pointer;
        box-shadow: 0 8px 18px -8px rgba(11,31,68,.7);
        transition: background .15s, transform .15s;
    }

    .cart-btn:hover { background: var(--brand); transform: translateY(-1px); }

    .cart-count {
        background: var(--accent);
        color: var(--brand-dark);
        font-size: .72rem;
        font-weight: 800;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .cart-count.bump { animation: bump .45s cubic-bezier(.3,1.6,.5,1); }
    @keyframes bump { 0% { transform: scale(1); } 40% { transform: scale(1.5); } 100% { transform: scale(1); } }

    /* ── CONTENEDOR PRINCIPAL ──────────────────────────────────────── */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* ── CARRITO LATERAL (drawer) ──────────────────────────────────── */
    .cart-overlay {
        position: fixed; inset: 0; background: rgba(11,31,68,.4);
        backdrop-filter: blur(2px);
        z-index: 200; opacity: 0; pointer-events: none;
        transition: opacity .25s;
    }

    .cart-overlay.open { opacity: 1; pointer-events: all; }

    .cart-drawer {
        position: fixed; top: 0; right: -440px; bottom: 0;
        width: min(420px, 100vw);
        background: #fff;
        z-index: 201;
        box-shadow: -12px 0 40px rgba(0,0,0,.18);
        transition: right .35s cubic-bezier(.4,0,.2,1);
        display: flex;
        flex-direction: column;
    }

    .cart-drawer.open { right: 0; }

    .cart-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 20px 22px;
        background: var(--cream);
        border-bottom: 1px solid var(--border);
        font-family: var(--font-display);
        font-weight: 700;
        font-size: 1.2rem;
        color: var(--brand);
    }

    .cart-close {
        width: 34px; height: 34px; border-radius: 50%;
        background: #fff; border: 1px solid var(--border); cursor: pointer;
        font-size: 1rem; color: var(--text-muted);
        transition: background .15s;
    }
    .cart-close:hover { background: var(--cream-2); }

    .cart-items { flex: 1; overflow-y: auto; padding: 8px 22px; }

    .cart-item {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 0;
        border-bottom: 1px dashed var(--border);
    }

    .cart-item:last-child { border-bottom: none; }

    .cart-item-img {
        width: 60px; height: 60px; border-radius: 12px;
        object-fit: cover; background: var(--cream);
        flex-shrink: 0;
    }

    .cart-item-info { flex: 1; min-width: 0; }
    .cart-item-name { font-weight: 600; font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cart-item-price { font-size: .8rem; color: var(--text-muted); margin-top: 2px; }

    .qty-ctrl {
        display: inline-flex; align-items: center; gap: 4px;
        background: var(--bg); border-radius: 999px; padding: 3px;
        border: 1px solid var(--border);
    }

    .qty-btn {
        width: 26px; height: 26px; border-radius: 50%;
        border: none;
        background: #fff; color: var(--text);
        font-size: .9rem; font-weight: 700;
        cursor: pointer; display: inline-flex;
        align-items: center; justify-content: center;
        box-shadow: var(--shadow);
        transition: background .12s, color .12s;
    }

    .qty-btn:hover { background: var(--brand); color: #fff; }
    .qty-num { font-weight: 700; min-width: 22px; text-align: center; font-size: .88rem; }

    .cart-item-remove {
        background: none; border: none; cursor: pointer;
        color: #ef4444; font-size: .78rem; font-weight: 600; padding: 4px 0;
    }

    .cart-footer {
        padding: 18px 22px 22px;
        border-top: 1px solid var(--border);
        background: #fff;
    }

    .cart-total {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 14px;
    }

    .cart-total-label { font-weight: 600; font-size: .95rem; color: var(--text-2); }
    .cart-total-value { font-family: var(--font-display); font-weight: 800; font-size: 1.5rem; color: var(--brand); }

    .btn-checkout {
        display: block; width: 100%;
        padding: 14px;
        background: var(--grad);
        color: #fff;
        font-weight: 700;
        font-size: .95rem;
        border: none;
        border-radius: 999px;
        cursor: pointer;
        text-align: center;
        box-shadow: 0 12px 24px -10px rgba(31,111,235,.8);
        transition: filter .15s, transform .15s;
    }

    .btn-checkout:hover { filter: brightness(1.06); transform: translateY(-1px); }
    .btn-checkout:disabled { background: #cbd5e1; box-shadow: none; cursor: not-allowed; transform: none; }

    /* ── FOOTER ────────────────────────────────────────────────────── */
    .site-footer {
        background: var(--brand-dark);
        color: rgba(255,255,255,.72);
        font-size: .86rem;
        margin-top: 100px;
        position: relative;
    }
    .site-footer::before {
        content: ""; position: absolute; left: 0; right: 0; top: -39px; height: 40px;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 40' preserveAspectRatio='none'%3E%3Cpath d='M0 40V22C240 2 480 0 720 14s480 26 720 6v20z' fill='%230b1f44'/%3E%3C/svg%3E") no-repeat bottom / 100% 100%;
    }
    .footer-grid {
        max-width: 1200px; margin: 0 auto;
        padding: 44px 20px 28px;
        display: grid; gap: 36px;
        grid-template-columns: 1.4fr 1fr 1fr;
    }
    .footer-brand { font-family: var(--font-display); font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 10px; }
    .footer-brand em { color: var(--sky); font-style: normal; }
    .footer-title { color: #fff; font-weight: 700; font-size: .75rem; text-transform: uppercase; letter-spacing: 1.4px; margin-bottom: 14px; }
    .footer-list { list-style: none; display: grid; gap: 10px; }
    .footer-list a { transition: color .15s; }
    .footer-list a:hover { color: var(--accent-soft); }
    .footer-bottom {
        border-top: 1px solid rgba(255,255,255,.1);
        text-align: center; padding: 16px 20px; font-size: .78rem; color: rgba(255,255,255,.5);
    }

    /* ── UTILIDADES ─────────────────────────────────────────────────── */
    .btn-primary {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 24px;
        background: var(--grad);
        color: #fff;
        font-weight: 700;
        font-size: .92rem;
        border: none;
        border-radius: 999px;
        cursor: pointer;
        box-shadow: 0 12px 24px -10px rgba(31,111,235,.8);
        transition: filter .15s, transform .15s;
        text-decoration: none;
    }

    .btn-primary:hover { filter: brightness(1.06); transform: translateY(-1px); }

    .btn-ghost {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 22px;
        background: #fff;
        color: var(--brand);
        font-weight: 700;
        font-size: .92rem;
        border: 1.5px solid var(--cream-2);
        border-radius: 999px;
        cursor: pointer;
        transition: border-color .15s, background .15s;
    }
    .btn-ghost:hover { border-color: var(--brand); background: var(--cream); }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }

    .empty-state .icon { font-size: 3rem; margin-bottom: 14px; }
    .empty-state h3 { font-size: 1.1rem; margin-bottom: 8px; color: var(--text-2); }

    @media (max-width: 760px) {
        .footer-grid { grid-template-columns: 1fr; gap: 26px; }
    }

    @media (max-width: 640px) {
        .nav { padding: 0 14px; height: 60px; }
        .nav-links a { display: none; }
        .cart-btn .cart-label { display: none; }
        .topbar { font-size: .72rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { animation: none !important; transition: none !important; }
        html { scroll-behavior: auto; }
    }
    </style>
    @stack('styles')
</head>
<body>

<div class="topbar">
    🥛 Leche fresca de ganaderos de <strong>Huata, Puno</strong> · paga con Yape, tarjeta o contra entrega
</div>

{{-- NAV --}}
<nav class="nav">
    <a href="{{ route('tienda.index') }}" class="nav-brand">
        <span class="nav-logo">🧀</span> ProLech <em>Tienda</em>
    </a>
    <div class="nav-links">
        <a href="{{ route('tienda.index') }}#catalogo" class="{{ request()->routeIs('tienda.index') ? 'active' : '' }}">
            Catálogo
        </a>
        <a href="{{ route('tienda.index') }}#proceso">Del campo a tu mesa</a>
        <button class="cart-btn" onclick="ProLechCart.toggleDrawer()" aria-label="Carrito">
            🛒
            <span class="cart-label">Carrito</span>
            <span class="cart-count" id="cart-count">0</span>
        </button>
    </div>
</nav>

{{-- OVERLAY + DRAWER CARRITO --}}
<div class="cart-overlay" id="cart-overlay" onclick="ProLechCart.closeDrawer()"></div>

<div class="cart-drawer" id="cart-drawer" role="dialog" aria-label="Carrito de compras">
    <div class="cart-header">
        <span>Tu carrito</span>
        <button class="cart-close" onclick="ProLechCart.closeDrawer()" aria-label="Cerrar">✕</button>
    </div>

    <div class="cart-items" id="cart-items-list">
        <div class="empty-state" id="cart-empty" style="padding:50px 0;">
            <div class="icon">🧺</div>
            <h3>Tu carrito está vacío</h3>
            <p style="font-size:.85rem;">Añade algún producto del catálogo.</p>
        </div>
    </div>

    <div class="cart-footer">
        <div class="cart-total">
            <span class="cart-total-label">Total</span>
            <span class="cart-total-value" id="cart-total-display">S/ 0.00</span>
        </div>
        <button class="btn-checkout" id="cart-checkout-btn"
                onclick="ProLechCart.irAlCheckout()" disabled>
            Proceder al pago →
        </button>
    </div>
</div>

{{-- CONTENIDO --}}
<main>
    @yield('content')
</main>

{{-- FOOTER --}}
<footer class="site-footer">
    <div class="footer-grid">
        <div>
            <div class="footer-brand">ProLech <em>Tienda</em></div>
            <p style="line-height:1.7; max-width:340px;">
                Lácteos elaborados en nuestra planta con leche acopiada cada día
                a ganaderos de Huata, a orillas del Titicaca.
            </p>
        </div>
        <div>
            <div class="footer-title">Tienda</div>
            <ul class="footer-list">
                <li><a href="{{ route('tienda.index') }}#catalogo">Catálogo</a></li>
                <li><a href="{{ route('tienda.index') }}#proceso">Del campo a tu mesa</a></li>
                <li><a href="#" onclick="ProLechCart.openDrawer(); return false;">Mi carrito</a></li>
            </ul>
        </div>
        <div>
            <div class="footer-title">Dónde estamos</div>
            <ul class="footer-list">
                <li>📍 Huata, Puno — Perú</li>
                <li>🏭 Planta de procesamiento ProLech</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        © {{ date('Y') }} ProLech — Sistema de Acopio y Venta de Lácteos
    </div>
</footer>

{{-- ══════════════════════════════════════════════════════════════════
     CARRITO JS — localStorage persistente
     Objeto global: ProLechCart
══════════════════════════════════════════════════════════════════ --}}
<script>
const ProLechCart = (() => {
    // ── Estado ─────────────────────────────────────────────────────
    const STORAGE_KEY = 'prolech_cart_v1';

    let items = [];         // [{ id, nombre, precio, imagen, cantidad, stock }]
    let drawerOpen = false;

    // ── Persistencia ───────────────────────────────────────────────
    function load() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            items = raw ? JSON.parse(raw) : [];
        } catch {
            items = [];
        }
    }

    function save() {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(items)); } catch {}
    }

    function clear() {
        items = [];
        try { localStorage.removeItem(STORAGE_KEY); } catch {}
        render();
    }

    // ── Operaciones ────────────────────────────────────────────────
    function add(producto) {
        // producto: { id, nombre, precio, imagen, stock }
        const idx = items.findIndex(i => i.id === producto.id);

        if (idx >= 0) {
            if (items[idx].cantidad < producto.stock) {
                items[idx].cantidad++;
            } else {
                showToast(`Solo hay ${producto.stock} unidades disponibles.`, 'warn');
                return;
            }
        } else {
            if (producto.stock < 1) {
                showToast('Sin stock disponible.', 'warn');
                return;
            }
            items.push({ ...producto, cantidad: 1 });
        }

        save();
        render();
        openDrawer();
        showToast(`"${producto.nombre}" añadido al carrito.`);
    }

    /** Añade [cantidad] unidades de una vez (página de detalle). */
    function addMany(producto, cantidad) {
        const idx = items.findIndex(i => i.id === producto.id);
        const actual = idx >= 0 ? items[idx].cantidad : 0;
        const nueva = Math.min(producto.stock, actual + cantidad);
        if (nueva === actual) {
            showToast(`Ya tienes las ${producto.stock} unidades disponibles en tu carrito.`, 'warn');
            return false;
        }
        if (idx >= 0) items[idx].cantidad = nueva;
        else items.push({ ...producto, cantidad: nueva });
        save();
        render();
        openDrawer();
        showToast(nueva - actual < cantidad
            ? `Solo se añadieron ${nueva - actual} (stock máximo).`
            : `${cantidad} × "${producto.nombre}" añadido al carrito.`);
        return true;
    }

    function remove(id) {
        items = items.filter(i => i.id !== id);
        save();
        render();
    }

    function setQty(id, qty) {
        const idx = items.findIndex(i => i.id === id);
        if (idx < 0) return;

        if (qty < 1) {
            remove(id);
            return;
        }

        const stock = items[idx].stock;
        if (qty > stock) {
            showToast(`Máximo disponible: ${stock} uds.`, 'warn');
            items[idx].cantidad = stock;
        } else {
            items[idx].cantidad = qty;
        }

        save();
        render();
    }

    // ── Cálculos ───────────────────────────────────────────────────
    function totalItems() {
        return items.reduce((s, i) => s + i.cantidad, 0);
    }

    function totalPrice() {
        return items.reduce((s, i) => s + i.precio * i.cantidad, 0);
    }

    function getItemsJson() {
        return JSON.stringify(
            items.map(i => ({ id: i.id, cantidad: i.cantidad }))
        );
    }

    // ── Render ─────────────────────────────────────────────────────
    function render() {
        const list    = document.getElementById('cart-items-list');
        const empty   = document.getElementById('cart-empty');
        const counter = document.getElementById('cart-count');
        const total   = document.getElementById('cart-total-display');
        const btn     = document.getElementById('cart-checkout-btn');

        if (!list) return;

        const nuevoTotal = String(totalItems());
        if (counter.textContent !== nuevoTotal) {
            counter.classList.remove('bump');
            void counter.offsetWidth; // reinicia la animación
            counter.classList.add('bump');
        }
        counter.textContent = nuevoTotal;
        total.textContent   = 'S/ ' + totalPrice().toFixed(2);
        btn.disabled        = items.length === 0;

        // Limpiar ítems previos (mantener el empty state que está en el DOM)
        list.querySelectorAll('.cart-item').forEach(el => el.remove());

        if (items.length === 0) {
            empty.style.display = '';
            return;
        }

        empty.style.display = 'none';

        items.forEach(item => {
            const el = document.createElement('div');
            el.className = 'cart-item';
            el.dataset.id = item.id;
            el.innerHTML = `
                <img src="${escHtml(item.imagen)}" alt="${escHtml(item.nombre)}"
                     class="cart-item-img"
                     onerror="this.src='/images/producto-placeholder.png'">
                <div class="cart-item-info">
                    <div class="cart-item-name">${escHtml(item.nombre)}</div>
                    <div class="cart-item-price">S/ ${item.precio.toFixed(2)} c/u</div>
                    <div class="qty-ctrl" style="margin-top:8px;">
                        <button class="qty-btn" onclick="ProLechCart.setQty(${item.id}, ${item.cantidad - 1})"
                                aria-label="Restar">−</button>
                        <span class="qty-num">${item.cantidad}</span>
                        <button class="qty-btn" onclick="ProLechCart.setQty(${item.id}, ${item.cantidad + 1})"
                                aria-label="Sumar">+</button>
                    </div>
                </div>
                <div style="text-align:right; flex-shrink:0;">
                    <div style="font-weight:800; font-size:.95rem; color:#1f6feb;">
                        S/ ${(item.precio * item.cantidad).toFixed(2)}
                    </div>
                    <button class="cart-item-remove" onclick="ProLechCart.remove(${item.id})"
                            aria-label="Eliminar ${escHtml(item.nombre)}">Quitar</button>
                </div>
            `;
            list.appendChild(el);
        });
    }

    // ── Drawer ─────────────────────────────────────────────────────
    function openDrawer() {
        drawerOpen = true;
        document.getElementById('cart-drawer').classList.add('open');
        document.getElementById('cart-overlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawerOpen = false;
        document.getElementById('cart-drawer').classList.remove('open');
        document.getElementById('cart-overlay').classList.remove('open');
        document.body.style.overflow = '';
    }

    function toggleDrawer() {
        drawerOpen ? closeDrawer() : openDrawer();
    }

    // ── Checkout ───────────────────────────────────────────────────
    function irAlCheckout() {
        if (items.length === 0) return;
        closeDrawer();
        window.location.href = '{{ route("tienda.checkout") }}';
    }

    // ── Toast ──────────────────────────────────────────────────────
    function showToast(msg, type = 'ok') {
        const t = document.createElement('div');
        t.style.cssText = `
            position:fixed; bottom:24px; left:50%; transform:translateX(-50%);
            background:${type === 'warn' ? '#9a3412' : '#0b1f44'};
            color:#fff; padding:11px 22px; border-radius:999px;
            font-size:.85rem; font-weight:600; z-index:999;
            box-shadow:0 10px 24px rgba(0,0,0,.25);
            animation: fadeInUp .25s ease;
        `;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 2800);
    }

    // ── Utilidad ───────────────────────────────────────────────────
    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Init ───────────────────────────────────────────────────────
    load();
    document.addEventListener('DOMContentLoaded', render);

    // Cerrar drawer con Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && drawerOpen) closeDrawer();
    });

    // API pública
    return { add, addMany, remove, setQty, clear, render,
             openDrawer, closeDrawer, toggleDrawer,
             irAlCheckout, getItemsJson, totalItems, totalPrice, items: () => items };
})();
</script>

<style>
@keyframes fadeInUp {
    from { opacity:0; transform:translateX(-50%) translateY(10px); }
    to   { opacity:1; transform:translateX(-50%) translateY(0); }
}
</style>

@stack('scripts')
</body>
</html>
