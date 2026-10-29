<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ProLech · @yield('title', 'Panel Admin')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* ══════════════════════════════════════════════════════════════════
       PROLECH ADMIN — Design System v3 "Leche & Lago"
       Paleta: azul leche + marino profundo + caramelo
    ══════════════════════════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        /* Colores principales */
        --brand:        #1f6feb;
        --brand-mid:    #1858c4;
        --brand-light:  #3b8bff;
        --brand-xlight: #7cb4ff;
        --brand-dark:   #0b1f44;
        --sky:          #38bdf8;
        --grad:         linear-gradient(135deg, #1f6feb 0%, #38bdf8 100%);
        --accent:       #f2a541;

        /* Grises */
        --bg:           #f4f7fc;
        --bg-card:      #ffffff;
        --border:       #e6ecf5;
        --border-soft:  #eef2f8;
        --text:         #0f172a;
        --text-2:       #334155;
        --text-muted:   #64748b;
        --text-dim:     #94a3b8;

        /* Semánticos */
        --green:        #16a34a;
        --green-bg:     #dcfce7;
        --red:          #dc2626;
        --red-bg:       #fee2e2;
        --amber:        #d97706;
        --amber-bg:     #fef3c7;
        --blue:         #2563eb;
        --blue-bg:      #dbeafe;
        --purple:       #7c3aed;
        --purple-bg:    #ede9fe;

        /* Nombres de la paleta anterior: aún los usan algunas vistas (solicitudes de ruta, reportes…) */
        --blanco:       #ffffff;
        --azul:         var(--blue);
        --verde:        var(--green);
        --rojo:         var(--red);
        --naranja:      var(--amber);
        --texto:        var(--text);
        --texto-suave:  var(--text-muted);
        --gris-borde:   var(--border);

        /* Layout */
        --sidebar-w:    256px;
        --header-h:     64px;
        --radius:       18px;
        --radius-sm:    11px;
        --shadow:       0 1px 2px rgba(11,31,68,.04), 0 4px 14px -6px rgba(11,31,68,.08);
        --shadow-md:    0 10px 24px -12px rgba(11,31,68,.18);
        --shadow-lg:    0 24px 48px -20px rgba(31,111,235,.35);
    }

    html { font-size: 15px; }

    body {
        font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background:
            radial-gradient(circle at 100% 0%, rgba(56,189,248,.10), transparent 35%),
            var(--bg);
        background-attachment: fixed;
        color: var(--text);
        min-height: 100vh;
        -webkit-font-smoothing: antialiased;
    }

    /* ── Scrollbar ─────────────────────────────────────────────────────── */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

    /* ══════════════════════════════════════════════════════════════════
       SIDEBAR
    ══════════════════════════════════════════════════════════════════ */
    .sidebar {
        position: fixed;
        top: 0; left: 0;
        width: var(--sidebar-w);
        height: 100vh;
        background: linear-gradient(180deg, #0b1f44 0%, #0f2a5c 60%, #123a7a 100%);
        box-shadow: 8px 0 30px -18px rgba(11,31,68,.6);
        display: flex;
        flex-direction: column;
        z-index: 200;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Logo */
    .sidebar-logo {
        padding: 22px 20px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255,255,255,.1);
    }

    .logo-icon {
        width: 40px; height: 40px;
        background: var(--grad);
        border-radius: 12px;
        box-shadow: 0 8px 18px -8px rgba(56,189,248,.8);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .logo-text h1 {
        color: #fff;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -.3px;
        line-height: 1.1;
    }

    .logo-text span {
        color: rgba(255,255,255,.5);
        font-size: .68rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: .8px;
    }

    /* Navegación */
    .sidebar-nav {
        padding: 14px 10px;
        flex: 1;
    }

    .nav-section {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(158,197,255,.55);
        padding: 12px 12px 6px;
        margin-top: 4px;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        color: rgba(220,232,255,.78);
        text-decoration: none;
        font-size: .875rem;
        font-weight: 500;
        border-radius: var(--radius-sm);
        transition: all .15s;
        margin-bottom: 2px;
        position: relative;
    }

    .nav-link:hover {
        background: rgba(255,255,255,.08);
        color: #fff;
    }

    .nav-link.active {
        background: var(--grad);
        color: #fff;
        font-weight: 700;
        box-shadow: 0 10px 20px -10px rgba(56,189,248,.9);
    }

    .nav-link.active::before {
        content: '';
        position: absolute;
        left: 0; top: 50%;
        transform: translateY(-50%);
        width: 0; height: 60%;
        background: transparent;
        border-radius: 0 4px 4px 0;
    }

    .nav-icon {
        width: 20px;
        text-align: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .nav-badge {
        margin-left: auto;
        background: var(--accent);
        color: var(--brand-dark);
        font-size: .65rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 20px;
        min-width: 20px;
        text-align: center;
    }

    /* Footer del sidebar */
    .sidebar-footer {
        padding: 14px 16px;
        border-top: 1px solid rgba(255,255,255,.1);
    }

    .user-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: rgba(255,255,255,.08);
        border-radius: var(--radius-sm);
        margin-bottom: 10px;
    }

    .user-avatar {
        width: 34px; height: 34px;
        background: var(--grad);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
    }

    .user-info strong { color: #fff; font-size: .82rem; display: block; }
    .user-info span   { color: rgba(255,255,255,.5); font-size: .72rem; }

    .btn-logout {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.12);
        color: rgba(255,255,255,.7);
        padding: 8px;
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: .8rem;
        font-weight: 500;
        font-family: inherit;
        transition: all .15s;
    }
    .btn-logout:hover { background: rgba(220,38,38,.2); border-color: rgba(220,38,38,.3); color: #fca5a5; }

    /* ══════════════════════════════════════════════════════════════════
       MAIN AREA
    ══════════════════════════════════════════════════════════════════ */
    .main {
        margin-left: var(--sidebar-w);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Topbar */
    .topbar {
        height: var(--header-h);
        background: rgba(255,255,255,.8);
        backdrop-filter: saturate(160%) blur(12px);
        -webkit-backdrop-filter: saturate(160%) blur(12px);
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        padding: 0 28px;
        gap: 16px;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 1px 0 rgba(0,0,0,.04);
    }

    .topbar-title {
        flex: 1;
    }

    .topbar-title h2 {
        font-size: 1.1rem;
        font-weight: 800;
        letter-spacing: -.3px;
        color: var(--text);
        line-height: 1.2;
    }

    .topbar-title p {
        font-size: .75rem;
        color: var(--text-muted);
        margin-top: 1px;
    }

    .topbar-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .topbar-pill {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 999px;
        font-size: .76rem;
        color: var(--text-2);
        font-weight: 600;
        box-shadow: var(--shadow);
    }

    .topbar-pill .dot-live {
        width: 7px; height: 7px;
        background: var(--green);
        border-radius: 50%;
        animation: pulse-dot 2s ease-in-out infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: .6; transform: scale(1.3); }
    }

    /* Contenido */
    .content {
        padding: 30px 32px;
        flex: 1;
        max-width: 1400px;
    }

    /* ── Flash messages ─────────────────────────────────────────────── */
    .flash {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 18px;
        border-radius: var(--radius-sm);
        margin-bottom: 22px;
        font-size: .88rem;
        font-weight: 500;
        animation: slideDown .25s ease;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .flash-success { background: var(--green-bg);  color: #15803d; border: 1px solid #bbf7d0; }
    .flash-error   { background: var(--red-bg);    color: #b91c1c; border: 1px solid #fecaca; }
    .flash-warn    { background: var(--amber-bg);  color: #92400e; border: 1px solid #fde68a; }

    /* ══════════════════════════════════════════════════════════════════
       CARDS & STATS
    ══════════════════════════════════════════════════════════════════ */
    .card {
        background: var(--bg-card);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        margin-bottom: 22px;
        overflow: hidden;
    }

    .card-header {
        padding: 16px 22px;
        border-bottom: 1px solid var(--border-soft);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .card-header h3 {
        font-size: .98rem;
        font-weight: 800;
        color: var(--text);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-header-meta {
        font-size: .78rem;
        color: var(--text-muted);
        font-weight: 400;
    }

    .card-body { padding: 22px; }

    /* Stats grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
        gap: 16px;
        margin-bottom: 26px;
    }

    .stat-card {
        background: var(--bg-card);
        border-radius: var(--radius);
        padding: 20px 22px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        transition: box-shadow .2s, transform .2s;
        position: relative;
        overflow: hidden;
    }

    .stat-card::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        border-radius: var(--radius) var(--radius) 0 0;
    }

    .stat-card.azul::after    { background: var(--grad); }
    .stat-card.verde::after   { background: var(--green); }
    .stat-card.rojo::after    { background: var(--red); }
    .stat-card.naranja::after { background: var(--amber); }

    .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }

    .stat-card .label {
        font-size: .72rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .6px;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .stat-card .value {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -1px;
    }

    .stat-card .unit {
        font-size: .75rem;
        color: var(--text-muted);
        margin-top: 5px;
    }

    .stat-card.azul    .value { color: var(--brand); }
    .stat-card.verde   .value { color: var(--green); }
    .stat-card.rojo    .value { color: var(--red); }
    .stat-card.naranja .value { color: var(--amber); }

    /* ══════════════════════════════════════════════════════════════════
       TABLA
    ══════════════════════════════════════════════════════════════════ */
    .table-wrap { overflow-x: auto; }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: .85rem;
    }

    thead th {
        background: #f7f9fd;
        padding: 10px 16px;
        text-align: left;
        font-weight: 700;
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }

    tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-soft);
        vertical-align: middle;
        color: var(--text-2);
    }

    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #f3f7ff; }

    /* ══════════════════════════════════════════════════════════════════
       BADGES
    ══════════════════════════════════════════════════════════════════ */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        white-space: nowrap;
    }

    .badge-aprobado  { background: var(--green-bg);  color: #15803d; }
    .badge-rechazado { background: var(--red-bg);    color: #b91c1c; }
    .badge-observado { background: var(--amber-bg);  color: #92400e; }
    .badge-mayorista { background: var(--blue-bg);   color: #1d4ed8; }
    .badge-publico   { background: var(--purple-bg); color: #6d28d9; }
    .badge-pendiente { background: var(--amber-bg);  color: #92400e; }
    .badge-pagada    { background: var(--green-bg);  color: #15803d; }
    .badge-borrador  { background: var(--bg);        color: var(--text-muted); border: 1px solid var(--border); }

    /* ══════════════════════════════════════════════════════════════════
       FORMULARIOS
    ══════════════════════════════════════════════════════════════════ */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 18px;
    }

    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full { grid-column: 1 / -1; }
    .form-group.span2 { grid-column: span 2; }

    label {
        font-size: .72rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    label .req { color: var(--red); margin-left: 2px; }
    label .hint { color: var(--text-dim); font-weight: 400; text-transform: none; letter-spacing: 0; font-size: .7rem; margin-left: 4px; }

    input[type="text"],
    input[type="number"],
    input[type="date"],
    input[type="email"],
    select,
    textarea {
        padding: 10px 13px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: .88rem;
        color: var(--text);
        background: var(--bg-card);
        font-family: inherit;
        transition: border-color .15s, box-shadow .15s;
        width: 100%;
        -webkit-appearance: none;
    }

    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: var(--brand);
        box-shadow: 0 0 0 4px rgba(31,111,235,.13);
    }

    textarea { resize: vertical; min-height: 90px; }

    .invalid, input.invalid, select.invalid, textarea.invalid {
        border-color: var(--red) !important;
        background: #fff8f8;
    }

    .error-msg {
        color: var(--red);
        font-size: .75rem;
        font-weight: 500;
        margin-top: 1px;
    }

    /* ══════════════════════════════════════════════════════════════════
       BOTONES
    ══════════════════════════════════════════════════════════════════ */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 10px 20px;
        border-radius: 999px;
        font-size: .85rem;
        font-weight: 700;
        cursor: pointer;
        border: none;
        transition: all .15s;
        text-decoration: none;
        font-family: inherit;
        white-space: nowrap;
        letter-spacing: .1px;
    }

    .btn:active { transform: scale(.97); }
    .btn:disabled { opacity: .5; cursor: not-allowed; transform: none !important; }

    .btn-primary { background: var(--grad); color: #fff; box-shadow: 0 10px 20px -10px rgba(31,111,235,.85); }
    .btn-primary:hover { filter: brightness(1.07); transform: translateY(-1px); }

    .btn-danger  { background: var(--red); color: #fff; }
    .btn-danger:hover { background: #b91c1c; }

    .btn-ghost { background: #fff; border: 1.5px solid var(--border); color: var(--text-2); }
    .btn-ghost:hover { background: #f3f7ff; border-color: var(--brand); color: var(--brand); }

    .btn-sm { padding: 6px 14px; font-size: .78rem; }
    .btn-lg { padding: 12px 26px; font-size: .95rem; }

    /* ══════════════════════════════════════════════════════════════════
       UTILIDADES
    ══════════════════════════════════════════════════════════════════ */
    .divider { height: 1px; background: var(--border-soft); margin: 20px 0; }

    .text-muted  { color: var(--text-muted); }
    .text-green  { color: var(--green); }
    .text-red    { color: var(--red); }
    .text-amber  { color: var(--amber); }
    .text-blue   { color: var(--blue); }
    .fw-700      { font-weight: 700; }
    .mono        { font-family: 'SF Mono', 'Fira Code', monospace; font-size: .82em; }

    .tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 2px 9px;
        font-size: .75rem;
        font-weight: 500;
        color: var(--text-2);
    }

    /* Paginación */
    nav[aria-label] { margin-top: 14px; }

    /* Entrada suave del contenido */
    /* "backwards": al terminar, cada elemento vuelve a su propia opacidad. Con "both" la animación
       dejaba opacity:1 fija y un modal oculto con opacity:0 (Solicitudes de ruta) quedaba visible. */
    .content > :not(.modal-overlay) { animation: fadeUp .35s ease backwards; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

    /* Responsive */
    @media (max-width: 1024px) {
        .sidebar { width: 64px; }
        .sidebar .logo-text,
        .sidebar .nav-link span:not(.nav-icon),
        .sidebar .nav-badge,
        .sidebar .nav-section,
        .sidebar .user-info,
        .sidebar-footer form { display: none; }
        .sidebar-logo { justify-content: center; padding: 16px; }
        .sidebar .user-card { justify-content: center; }
        .nav-link { justify-content: center; padding: 10px; }
        .main { margin-left: 64px; }
    }
    </style>
</head>
<body>
@include('partials.pantalla-carga')

{{-- ── Sidebar ──────────────────────────────────────────────────────────── --}}
<aside class="sidebar">

    <div class="sidebar-logo">
        <div class="logo-icon">🧀</div>
        <div class="logo-text">
            <h1>ProLech</h1>
            <span>Huata · Puno</span>
        </div>
    </div>

    @php $webUser = auth()->user(); @endphp
    <nav class="sidebar-nav">

        @if($webUser->seccionWebPermitida('dashboard'))
        <div class="nav-section">Principal</div>
        <a href="{{ route('admin.dashboard') }}"
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span class="nav-icon">⊞</span>
            <span>Dashboard</span>
        </a>
        @endif

        @if($webUser->seccionWebPermitida('produccion') || $webUser->seccionWebPermitida('ventas') || $webUser->seccionWebPermitida('liquidaciones'))
        <div class="nav-section" style="margin-top:8px">Operaciones</div>
        @endif

        @if($webUser->seccionWebPermitida('produccion'))
        <a href="{{ route('admin.produccion.lotes.create') }}"
           class="nav-link {{ request()->routeIs('admin.produccion*') && !request()->routeIs('admin.recetas*') && !request()->routeIs('admin.inventario*') ? 'active' : '' }}">
            <span class="nav-icon">🏭</span>
            <span>Producción</span>
        </a>
        <a href="{{ route('admin.recetas.index') }}"
           class="nav-link {{ request()->routeIs('admin.recetas*') ? 'active' : '' }}"
           style="padding-left:28px;font-size:.82rem">
            <span class="nav-icon" style="font-size:.85rem">📋</span>
            <span>Recetas</span>
        </a>
        <a href="{{ route('admin.inventario.index') }}"
           class="nav-link {{ request()->routeIs('admin.inventario*') ? 'active' : '' }}"
           style="padding-left:28px;font-size:.82rem">
            <span class="nav-icon" style="font-size:.85rem">📦</span>
            <span>Inventario</span>
            @php
                $stockBajoCount = \App\Models\Insumo::whereColumn('stock_actual','<=','stock_minimo')->count();
            @endphp
            @if($stockBajoCount > 0)
            <span class="nav-badge" style="background:var(--amber)">{{ $stockBajoCount }}</span>
            @endif
        </a>
        @endif

        @if($webUser->seccionWebPermitida('ventas'))
        <a href="{{ route('admin.ventas') }}"
           class="nav-link {{ request()->routeIs('admin.ventas') ? 'active' : '' }}">
            <span class="nav-icon">💰</span>
            <span>Ventas</span>
        </a>
        @endif

        {{-- ── TIENDA E-COMMERCE ──────────────────────────────────────── --}}
        @if($webUser->isAdmin())
        <div class="nav-section" style="margin-top:8px">E-Commerce</div>
        <a href="{{ route('admin.tienda.productos.index') }}"
           class="nav-link {{ request()->routeIs('admin.tienda.productos*') ? 'active' : '' }}">
            <span class="nav-icon">🏪</span>
            <span>Productos Tienda</span>
        </a>
        <a href="{{ route('admin.tienda.pedidos.index') }}"
           class="nav-link {{ request()->routeIs('admin.tienda.pedidos*') ? 'active' : '' }}">
            <span class="nav-icon">📦</span>
            <span>Pedidos Web</span>
            @php
                $pedidosPendientes = \App\Models\PedidoWeb::where('estado_pedido','Pendiente')->count();
            @endphp
            @if($pedidosPendientes > 0)
            <span class="nav-badge">{{ $pedidosPendientes }}</span>
            @endif
        </a>
        <a href="{{ route('tienda.index') }}" target="_blank"
           class="nav-link" style="opacity:.75;">
            <span class="nav-icon">🔗</span>
            <span>Ver Tienda Pública</span>
        </a>
        @endif

        @if($webUser->seccionWebPermitida('liquidaciones') && Route::has('admin.liquidaciones.index'))
        <a href="{{ route('admin.liquidaciones.index') }}"
           class="nav-link {{ request()->routeIs('admin.liquidaciones*') ? 'active' : '' }}">
            <span class="nav-icon">📑</span>
            <span>Liquidaciones</span>
        </a>
        @endif

        @if($webUser->isAdmin())
        <div class="nav-section" style="margin-top:8px">Logística</div>
        <a href="{{ route('admin.operaciones.en-vivo') }}"
           class="nav-link {{ request()->routeIs('admin.operaciones.en-vivo*') ? 'active' : '' }}">
            <span class="nav-icon">📡</span>
            <span>Panel en vivo</span>
        </a>
        <a href="{{ route('admin.mapa') }}"
           class="nav-link {{ request()->routeIs('admin.mapa*') ? 'active' : '' }}">
            <span class="nav-icon">🗺️</span>
            <span>Mapa GPS</span>
        </a>
        <a href="{{ route('admin.planilla.index') }}"
           class="nav-link {{ request()->routeIs('admin.planilla*') ? 'active' : '' }}">
            <span class="nav-icon">💼</span>
            <span>Planilla choferes</span>
        </a>
        <a href="{{ route('admin.operaciones.recorrido') }}"
           class="nav-link {{ request()->routeIs('admin.operaciones.recorrido*') ? 'active' : '' }}">
            <span class="nav-icon">🛰️</span>
            <span>Recorridos y km</span>
        </a>
        <a href="{{ route('admin.operaciones.zonas') }}"
           class="nav-link {{ request()->routeIs('admin.operaciones.zonas*') ? 'active' : '' }}">
            <span class="nav-icon">🧭</span>
            <span>Zonas en el mapa</span>
        </a>
        <a href="{{ route('admin.operaciones.trazar') }}"
           class="nav-link {{ request()->routeIs('admin.operaciones.trazar*') ? 'active' : '' }}">
            <span class="nav-icon">🗺️</span>
            <span>Rutas y choferes</span>
        </a>
        <a href="{{ route('admin.operaciones.exportar') }}"
           class="nav-link {{ request()->routeIs('admin.operaciones.exportar*') ? 'active' : '' }}">
            <span class="nav-icon">📤</span>
            <span>Exportar</span>
        </a>
        <a href="{{ route('admin.rutas.solicitudes') }}"
           class="nav-link {{ request()->routeIs('admin.rutas*') ? 'active' : '' }}">
            <span class="nav-icon">🛣️</span>
            <span>Solicitudes de Ruta</span>
            @php $pendRutas = \App\Models\SolicitudRuta::whereNotNull('chofer_id')->where('estado','pendiente')->count(); @endphp
            @if($pendRutas > 0)
            <span class="nav-badge">{{ $pendRutas }}</span>
            @endif
        </a>
        @if(Route::has('admin.rotacion.index'))
        <a href="{{ route('admin.rotacion.index') }}"
           class="nav-link {{ request()->routeIs('admin.rotacion*') ? 'active' : '' }}">
            <span class="nav-icon">🔄</span>
            <span>Rotación Zonas</span>
        </a>
        @endif
        @endif

        @if($webUser->seccionWebPermitida('ganaderos') || $webUser->seccionWebPermitida('reclamos') || $webUser->isAdmin())
        <div class="nav-section" style="margin-top:8px">Personal</div>
        @endif

        @if($webUser->seccionWebPermitida('ganaderos'))
        <a href="{{ route('admin.ganaderos.index') }}"
           class="nav-link {{ request()->routeIs('admin.ganaderos*') ? 'active' : '' }}">
            <span class="nav-icon">🐄</span>
            <span>Proveedores</span>
            <span class="nav-badge" style="background:rgba(255,255,255,.15)">
                {{ \App\Models\Ganadero::where('activo',true)->count() }}
            </span>
        </a>
        @endif

        @if($webUser->seccionWebPermitida('reclamos'))
        @php $reclamosPendientes = \App\Models\ReclamoLitraje::where('estado','pendiente')->count(); @endphp
        <a href="{{ route('admin.reclamos.index') }}"
           class="nav-link {{ request()->routeIs('admin.reclamos*') ? 'active' : '' }}">
            <span class="nav-icon">📢</span>
            <span>Reclamos litraje</span>
            @if($reclamosPendientes > 0)
            <span class="nav-badge" style="background:var(--amber)">{{ $reclamosPendientes }}</span>
            @endif
        </a>
        @endif

        @if($webUser->seccionWebPermitida('comunicados') && Route::has('admin.comunicados.index'))
        <a href="{{ route('admin.comunicados.index') }}"
           class="nav-link {{ request()->routeIs('admin.comunicados*') ? 'active' : '' }}">
            <span class="nav-icon">📣</span>
            <span>Comunicados</span>
        </a>
        @endif

        @if($webUser->isAdmin())
        <a href="{{ route('admin.usuarios.index') }}"
           class="nav-link {{ request()->routeIs('admin.usuarios*') ? 'active' : '' }}">
            <span class="nav-icon">👥</span>
            <span>Usuarios</span>
        </a>
        @endif

        @if($webUser->seccionWebPermitida('reportes') || $webUser->seccionWebPermitida('reuniones'))
        <div class="nav-section" style="margin-top:8px">Análisis</div>
        @endif

        @if($webUser->seccionWebPermitida('reportes'))
        <a href="{{ route('admin.reportes') }}"
           class="nav-link {{ request()->routeIs('admin.reportes') ? 'active' : '' }}">
            <span class="nav-icon">📊</span>
            <span>Reportes</span>
        </a>
        @endif

        @if($webUser->seccionWebPermitida('reuniones'))
        <a href="{{ route('admin.reuniones') }}"
           class="nav-link {{ request()->routeIs('admin.reuniones*') ? 'active' : '' }}">
            <span class="nav-icon">📅</span>
            <span>Reuniones</span>
        </a>
        @endif

        @if($webUser->isAdmin() && Route::has('admin.configuracion.index'))
        <a href="{{ route('admin.configuracion.index') }}"
           class="nav-link {{ request()->routeIs('admin.configuracion*') ? 'active' : '' }}">
            <span class="nav-icon">⚙️</span>
            <span>Configuración</span>
        </a>
        @endif

        @if($webUser->isAdmin() && Route::has('admin.auditorias.index'))
        <a href="{{ route('admin.auditorias.index') }}"
           class="nav-link {{ request()->routeIs('admin.auditorias*') ? 'active' : '' }}">
            <span class="nav-icon">🛡️</span>
            <span>Auditoría</span>
        </a>
        @endif

        @if($webUser->seccionWebPermitida('calidad_plan') && Route::has('admin.calidad.plan.index'))
        <div class="nav-section" style="margin-top:8px">Calidad</div>
        <a href="{{ route('admin.calidad.control.index') }}"
           class="nav-link {{ request()->routeIs('admin.calidad.control*') ? 'active' : '' }}">
            <span class="nav-icon">🧪</span>
            <span>Control sorpresa</span>
        </a>
        <a href="{{ route('admin.calidad.plan.index') }}"
           class="nav-link {{ request()->routeIs('admin.calidad.plan*') ? 'active' : '' }}"
           style="border-left:3px solid #ffc107">
            <span class="nav-icon">🔒</span>
            <span>Plan inopinado</span>
        </a>
        @endif
        @if($webUser->seccionWebPermitida('calidad_planta') && Route::has('admin.calidad.planta.index'))
        <a href="{{ route('admin.calidad.planta.index') }}"
           class="nav-link {{ request()->routeIs('admin.calidad.planta*') ? 'active' : '' }}">
            <span class="nav-icon">🏭</span>
            <span>Calidad en planta</span>
        </a>
        @endif
        @if($webUser->seccionWebPermitida('calidad_capacitaciones') && Route::has('admin.calidad.capacitaciones.index'))
        <a href="{{ route('admin.calidad.capacitaciones.index') }}"
           class="nav-link {{ request()->routeIs('admin.calidad.capacitaciones*') ? 'active' : '' }}">
            <span class="nav-icon">📚</span>
            <span>Capacitaciones ATOS</span>
        </a>
        @endif

    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="user-info">
                <strong>{{ Str::limit(auth()->user()->name, 20) }}</strong>
                <span>{{ \App\Support\RolWeb::etiquetas()[$webUser->rol] ?? ucfirst($webUser->rol) }}</span>
            </div>
        </div>
        <a href="{{ route('2fa.setup') }}" class="btn-logout" style="margin-bottom: 8px; text-decoration: none;" title="Configurar o cambiar número de WhatsApp">
            <span>📱</span> Cambiar WhatsApp
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                <span>⎋</span> Cerrar sesión
            </button>
        </form>
    </div>

</aside>

{{-- ── Main ─────────────────────────────────────────────────────────────── --}}
<main class="main">
    <div class="topbar">
        <div class="topbar-title">
            <h2>@yield('page-title', 'Panel Administrador')</h2>
            <p>{{ now()->isoFormat('dddd, D [de] MMMM YYYY') }}</p>
        </div>
        <div class="topbar-actions">
            <div class="topbar-pill">
                <span class="dot-live"></span>
                Sistema activo
            </div>
            <div class="topbar-pill">
                🧀 ProLech · Huata
            </div>
        </div>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="flash flash-success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="flash flash-error">❌ {{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="flash flash-warn">⚠️ {{ session('warning') }}</div>
        @endif

        @yield('content')
    </div>
</main>

{{-- Scripts y estilos que agregan las vistas con @push('scripts') (p. ej. el modal de publicar receta) --}}
@stack('scripts')
</body>
</html>
