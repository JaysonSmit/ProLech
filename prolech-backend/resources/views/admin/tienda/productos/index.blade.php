@extends('layouts.admin')

@section('title', 'Productos en Tienda')
@section('page-title', '🛒 Gestión de Tienda')

@section('content')

@if(session('success'))
<div class="flash flash-success">✅ {{ session('success') }}
    <button onclick="this.parentNode.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;font-size:1.1rem">×</button>
</div>
@endif
@if(session('error'))
<div class="flash flash-error">⚠️ {{ session('error') }}
    <button onclick="this.parentNode.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;font-size:1.1rem">×</button>
</div>
@endif

{{-- ── HEADER ───────────────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="font-size:1.05rem;font-weight:700;color:var(--text);">Catálogo de Productos</h2>
        <p style="font-size:.8rem;color:var(--text-muted);margin-top:2px;">
            Los productos se publican desde las <a href="{{ route('admin.recetas.index') }}" style="color:var(--brand);font-weight:600;">Recetas de Producción</a>.
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('tienda.index') }}" target="_blank"
           style="padding:8px 16px;background:var(--green-bg);color:var(--green);border:1px solid #bbf7d0;border-radius:8px;font-size:.82rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:6px;">
            🔗 Ver Tienda Pública
        </a>
        <a href="{{ route('admin.recetas.index') }}"
           style="padding:8px 16px;background:var(--brand);color:#fff;border-radius:8px;font-size:.82rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:6px;">
            ➕ Publicar desde Recetas
        </a>
    </div>
</div>

{{-- ── STATS ─────────────────────────────────────────────────────────────────── --}}
@php
    $statTotal    = \App\Models\ProductoTienda::count();
    $statConStock = \App\Models\ProductoTienda::where('estado','activo')->where('stock_disponible','>',0)->count();
    $statSinStock = \App\Models\ProductoTienda::where('estado','activo')->where('stock_disponible','<=',0)->count();
@endphp
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:22px;">
    <div class="stat-card verde">
        <div class="label">Total productos</div>
        <div class="value">{{ $statTotal }}</div>
        <div class="unit">en el catálogo</div>
    </div>
    <div class="stat-card azul">
        <div class="label">Con stock</div>
        <div class="value">{{ $statConStock }}</div>
        <div class="unit">disponibles para vender</div>
    </div>
    <div class="stat-card naranja">
        <div class="label">Sin stock</div>
        <div class="value">{{ $statSinStock }}</div>
        <div class="unit">producir para reponer</div>
    </div>
</div>

{{-- ── FILTROS ──────────────────────────────────────────────────────────────── --}}
<div style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    @foreach([''=>'Todos','activo'=>'Activos','inactivo'=>'Inactivos'] as $val => $label)
    <a href="{{ route('admin.tienda.productos.index', $val ? ['estado'=>$val] : []) }}"
       style="padding:6px 16px;border-radius:20px;font-size:.78rem;font-weight:600;text-decoration:none;
              {{ $estado === $val || ($val === '' && !$estado)
                 ? 'background:var(--brand);color:#fff;'
                 : 'background:var(--bg);color:var(--text-muted);border:1px solid var(--border);' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- ── GRID DE PRODUCTOS ─────────────────────────────────────────────────────── --}}
@if($productos->isEmpty())
<div class="card">
    <div style="padding:60px;text-align:center;color:var(--text-muted);">
        <div style="font-size:3rem;margin-bottom:14px;">🛒</div>
        <div style="font-weight:700;font-size:1rem;margin-bottom:8px;color:var(--text-2);">Sin productos aún</div>
        <p style="font-size:.88rem;margin-bottom:20px;">
            Ve a <strong>Recetas de Producción</strong> y haz clic en <strong>"🛒 Publicar"</strong> en cualquier receta.
        </p>
        <a href="{{ route('admin.recetas.index') }}" class="btn btn-primary">
            📋 Ir a Recetas
        </a>
    </div>
</div>
@else

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:18px;">
    @foreach($productos as $p)
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;display:flex;flex-direction:column;
                transition:box-shadow .15s,transform .15s;position:relative;"
         onmouseenter="this.style.boxShadow='var(--shadow-md)';this.style.transform='translateY(-1px)'"
         onmouseleave="this.style.boxShadow='var(--shadow)';this.style.transform=''">

        {{-- Badge estado flotante --}}
        <div style="position:absolute;top:10px;left:10px;z-index:1;">
            @if($p->estado === 'activo' && $p->stock_disponible > 0)
                <span style="background:#dcfce7;color:#166534;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:12px;border:1px solid #bbf7d0;">
                    ✓ {{ $p->stock_disponible }} uds.
                </span>
            @elseif($p->estado === 'activo' && $p->stock_disponible <= 0)
                <span style="background:#fef3c7;color:#92400e;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:12px;border:1px solid #fde68a;">
                    ⚠ Sin stock
                </span>
            @else
                <span style="background:#fee2e2;color:#991b1b;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:12px;border:1px solid #fca5a5;">
                    ✗ Inactivo
                </span>
            @endif
        </div>

        {{-- Imagen --}}
        <div style="aspect-ratio:4/3;overflow:hidden;background:#f1f5f9;position:relative;">
            @if($p->imagen_url)
                <img src="{{ asset('storage/'.$p->imagen_url) }}"
                     alt="{{ $p->nombre }}"
                     style="width:100%;height:100%;object-fit:cover;">
            @else
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3.5rem;">
                    🧀
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div style="padding:14px;flex:1;display:flex;flex-direction:column;gap:6px;">
            <div style="font-weight:700;font-size:.92rem;color:var(--text);line-height:1.2;">
                {{ $p->nombre }}
            </div>

            @if($p->receta)
                <div style="font-size:.72rem;color:var(--text-muted);display:flex;align-items:center;gap:4px;">
                    📋 Receta: <strong style="color:var(--brand);">{{ $p->receta->nombre }}</strong>
                </div>
            @endif

            <div style="font-size:1.1rem;font-weight:800;color:var(--brand);margin-top:auto;padding-top:8px;">
                S/ {{ number_format($p->precio_venta, 2) }}
            </div>
        </div>

        {{-- Acciones --}}
        <div style="padding:0 14px 14px;display:flex;gap:6px;">
            <a href="{{ route('admin.tienda.productos.edit', $p) }}"
               style="flex:1;text-align:center;padding:8px;background:var(--brand);color:#fff;border-radius:6px;font-size:.78rem;font-weight:700;text-decoration:none;transition:background .15s;"
               onmouseenter="this.style.background='var(--brand-mid)'"
               onmouseleave="this.style.background='var(--brand)'">
                ✏️ Editar
            </a>

            <form method="POST" action="{{ route('admin.tienda.productos.toggle', $p) }}" style="flex:1;">
                @csrf
                <button type="submit"
                    style="width:100%;padding:8px;border-radius:6px;font-size:.78rem;font-weight:700;cursor:pointer;border:1px solid var(--border);
                           background:{{ $p->estado==='activo' ? 'var(--amber-bg)' : 'var(--green-bg)' }};
                           color:{{ $p->estado==='activo' ? 'var(--amber)' : 'var(--green)' }};">
                    {{ $p->estado === 'activo' ? '⏸ Ocultar' : '▶ Activar' }}
                </button>
            </form>

            <form method="POST" action="{{ route('admin.tienda.productos.destroy', $p) }}"
                  onsubmit="return confirm('¿Eliminar «{{ $p->nombre }}»?')">
                @csrf @method('DELETE')
                <button type="submit"
                    style="padding:8px 10px;background:var(--red-bg);color:var(--red);border:1px solid #fca5a5;border-radius:6px;font-size:.82rem;cursor:pointer;">
                    🗑
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>

@if($productos->hasPages())
<div style="margin-top:22px;padding:16px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);">
    {{ $productos->links() }}
</div>
@endif

@endif

@endsection
