@extends('layouts.admin')

@section('title', 'Recetas de Producción')
@section('page-title', '📋 Recetas de Producción')

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
@if($errors->any())
<div class="flash flash-error">⚠️ {{ $errors->first() }}
    <button onclick="this.parentNode.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;font-size:1.1rem">×</button>
</div>
@endif

{{-- ── HEADER ───────────────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px">
    <div style="display:flex;gap:6px;background:#f1f5f9;border-radius:10px;padding:4px">
        <a href="{{ route('admin.produccion.lotes.create') }}"
           style="padding:7px 18px;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;color:var(--text-muted)">
            🏭 Producción
        </a>
        <a href="{{ route('admin.recetas.index') }}"
           style="padding:7px 18px;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;background:#fff;color:var(--brand-light);box-shadow:0 1px 3px rgba(0,0,0,.1)">
            📋 Recetas
        </a>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('admin.tienda.productos.index') }}"
           style="padding:8px 16px;background:var(--green-bg);color:var(--green);border:1px solid #bbf7d0;border-radius:8px;font-size:.82rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:6px;">
            🛒 Ver Tienda
        </a>
        <a href="{{ route('admin.recetas.create') }}" class="btn btn-primary">
            ➕ Nueva Receta
        </a>
    </div>
</div>

{{-- ── STATS ─────────────────────────────────────────────────────────────────── --}}
@php
    $activas     = $recetas->where('activa', true)->count();
    $inactivas   = $recetas->where('activa', false)->count();
    $totalLotes  = $recetas->sum('lotes_count');
    $publicadas  = $recetas->filter(fn($r) => isset($publicadasIds[$r->id]))->count();
@endphp
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:22px">
    <div class="stat-card verde">
        <div class="label">Recetas activas</div>
        <div class="value">{{ $activas }}</div>
        <div class="unit">listas para producir</div>
    </div>
    <div class="stat-card azul">
        <div class="label">Total recetas</div>
        <div class="value">{{ $recetas->count() }}</div>
        <div class="unit">{{ $inactivas }} inactivas</div>
    </div>
    <div class="stat-card naranja">
        <div class="label">Lotes producidos</div>
        <div class="value">{{ number_format($totalLotes) }}</div>
        <div class="unit">en total</div>
    </div>
    <div class="stat-card azul">
        <div class="label">En Tienda</div>
        <div class="value">{{ $publicadas }}</div>
        <div class="unit">productos publicados</div>
    </div>
</div>

{{-- ── GRID DE RECETAS ──────────────────────────────────────────────────────── --}}
@if($recetas->isEmpty())
<div class="card">
    <div style="padding:64px;text-align:center;color:var(--text-muted)">
        <div style="font-size:3.5rem;margin-bottom:14px">🧀</div>
        <div style="font-weight:700;font-size:1.05rem;margin-bottom:6px">Sin recetas configuradas</div>
        <div style="font-size:.88rem;margin-bottom:20px">Crea la primera receta para producir y publicar en la tienda.</div>
        <a href="{{ route('admin.recetas.create') }}" class="btn btn-primary">➕ Crear primera receta</a>
    </div>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:18px">
    @foreach($recetas as $receta)
    @php
        $yaPublicada = isset($publicadasIds[$receta->id]);
        $productoId  = $publicadasIds[$receta->id] ?? null;
    @endphp
    <div class="card" style="margin-bottom:0;overflow:visible;
        border-top:3px solid {{ $receta->activa ? 'var(--brand-light)' : 'var(--border)' }};
        opacity:{{ $receta->activa ? '1' : '.75' }};">

        {{-- Cabecera de la tarjeta --}}
        <div style="padding:16px 18px 12px;border-bottom:1px solid var(--border-soft);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                <div>
                    <div style="font-weight:800;font-size:1rem;color:var(--text);line-height:1.2;">
                        {{ $receta->nombre }}
                    </div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-top:3px;">
                        🏭 Rinde <strong>{{ $receta->rendimiento_esperado_unidades }}</strong> {{ Str::plural('unidad', $receta->rendimiento_esperado_unidades) }}
                        &nbsp;·&nbsp;
                        🧪 {{ $receta->detalles->count() }} ingredientes
                    </div>
                </div>
                {{-- Badge estado tienda --}}
                @if($yaPublicada)
                <span style="flex-shrink:0;padding:3px 10px;border-radius:20px;font-size:.68rem;font-weight:700;background:#dcfce7;color:#15803d;white-space:nowrap;">
                    🛒 En Tienda
                </span>
                @elseif($receta->activa)
                <span style="flex-shrink:0;padding:3px 10px;border-radius:20px;font-size:.68rem;font-weight:700;background:#f1f5f9;color:var(--text-muted);white-space:nowrap;">
                    Sin publicar
                </span>
                @endif
            </div>

            @if($receta->descripcion)
            <div style="font-size:.8rem;color:var(--text-muted);line-height:1.5;margin-top:8px;">
                {{ Str::limit($receta->descripcion, 90) }}
            </div>
            @endif
        </div>

        {{-- Ingredientes --}}
        <div style="padding:12px 18px;">
            @if($receta->detalles->isEmpty())
                <div style="font-size:.78rem;color:var(--text-muted);font-style:italic;text-align:center;padding:8px;">
                    Sin ingredientes configurados
                </div>
            @else
                <div style="display:flex;flex-direction:column;gap:4px;max-height:160px;overflow-y:auto;">
                    @foreach($receta->detalles as $det)
                    <div style="display:flex;align-items:center;justify-content:space-between;font-size:.79rem;padding:3px 0;border-bottom:1px solid var(--border-soft);">
                        <span style="color:var(--text-2);">📦 {{ $det->insumo->nombre }}</span>
                        <span style="font-weight:700;color:var(--text);white-space:nowrap;">
                            {{ number_format((float)$det->cantidad_requerida, 3) }} {{ $det->insumo->unidad_medida }}
                        </span>
                    </div>
                    @endforeach
                </div>
            @endif

            {{-- ── ACCIONES ────────────────────────────────────────────────── --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;padding-top:10px;border-top:1px solid var(--border-soft);gap:6px;flex-wrap:wrap;">

                <span style="font-size:.75rem;color:var(--text-muted);">
                    🏭 {{ $receta->lotes_count }} {{ $receta->lotes_count == 1 ? 'lote' : 'lotes' }}
                </span>

                <div style="display:flex;gap:6px;">
                    <a href="{{ route('admin.recetas.edit', $receta) }}"
                       style="padding:5px 10px;background:var(--bg);border:1px solid var(--border);border-radius:6px;font-size:.76rem;font-weight:600;color:var(--text-2);text-decoration:none;">
                        ✏️ Editar
                    </a>

                    <form method="POST" action="{{ route('admin.recetas.toggle', $receta) }}" style="display:inline;">
                        @csrf
                        <button type="submit"
                            style="padding:5px 10px;background:var(--bg);border:1px solid var(--border);border-radius:6px;font-size:.76rem;font-weight:600;color:{{ $receta->activa ? '#dc2626' : '#15803d' }};cursor:pointer;">
                            {{ $receta->activa ? '⏸' : '▶' }}
                        </button>
                    </form>

                    {{-- BOTÓN PUBLICAR EN TIENDA --}}
                    @if($yaPublicada)
                        <a href="{{ route('admin.tienda.productos.edit', $productoId) }}"
                           style="padding:5px 12px;background:var(--green-bg);border:1px solid #bbf7d0;border-radius:6px;font-size:.76rem;font-weight:700;color:var(--green);text-decoration:none;">
                            🛒 Editar precio
                        </a>
                    @elseif($receta->activa)
                        <button type="button"
                            onclick="abrirModalPublicar({{ $receta->id }}, {{ json_encode($receta->nombre) }}, {{ json_encode($receta->descripcion ?? '') }})"
                            style="padding:5px 12px;background:var(--brand);border:none;border-radius:6px;font-size:.76rem;font-weight:700;color:#fff;cursor:pointer;transition:background .15s;"
                            onmouseenter="this.style.background='var(--brand-mid)'"
                            onmouseleave="this.style.background='var(--brand)'">
                            🛒 Publicar
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL — Publicar en Tienda
     Aparece al hacer clic en "🛒 Publicar" en cualquier card de receta
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-overlay"
     onclick="cerrarModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:400;animation:fadeIn .2s ease;">
</div>

<div id="modal-publicar"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            width:min(500px,95vw);background:#fff;border-radius:16px;z-index:401;
            box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;animation:slideUp .25s ease;">

    {{-- Header del modal --}}
    <div style="background:var(--brand);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <div style="color:#86efac;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:3px;">
                🛒 Publicar en Tienda
            </div>
            <div id="modal-nombre-receta" style="color:#fff;font-weight:800;font-size:1.05rem;"></div>
        </div>
        <button onclick="cerrarModal()"
                style="background:rgba(255,255,255,.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;">
            ✕
        </button>
    </div>

    {{-- Formulario --}}
    <form id="form-publicar" method="POST" action="{{ route('admin.recetas.publicar') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="modal-receta-id" name="receta_id">
        <input type="hidden" id="modal-descripcion" name="descripcion">

        <div style="padding:24px;">

            {{-- Preview de nombre --}}
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <span style="font-size:1.5rem;">🧀</span>
                <div>
                    <div id="modal-preview-nombre" style="font-weight:700;font-size:.92rem;color:var(--text);"></div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-top:1px;">El nombre del producto se toma de la receta</div>
                </div>
            </div>

            {{-- Precio --}}
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;">
                    Precio de Venta (S/) <span style="color:#ef4444;">*</span>
                </label>
                <div style="position:relative;">
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:.9rem;font-weight:700;color:var(--brand);">S/</span>
                    <input type="number" name="precio_venta" id="modal-precio"
                           min="0.50" step="0.50" required
                           placeholder="0.00"
                           style="width:100%;padding:11px 13px 11px 36px;border:1.5px solid var(--border);border-radius:8px;font-size:1rem;font-weight:700;color:var(--brand);font-family:inherit;transition:border-color .15s;"
                           onfocus="this.style.borderColor='var(--brand)'"
                           onblur="this.style.borderColor='var(--border)'">
                </div>
                <div style="font-size:.72rem;color:var(--text-muted);margin-top:4px;">
                    Podrás cambiar el precio en cualquier momento desde la gestión de productos.
                </div>
            </div>

            {{-- Imagen --}}
            <div style="margin-bottom:4px;">
                <label style="display:block;font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;">
                    Imagen del Producto <span style="color:var(--text-muted);font-weight:400;">(opcional)</span>
                </label>

                {{-- Área drag & drop --}}
                <div id="img-drop-area"
                     onclick="document.getElementById('modal-imagen').click()"
                     ondragover="event.preventDefault();this.style.borderColor='var(--brand)';this.style.background='#f0fdf4';"
                     ondragleave="this.style.borderColor='var(--border)';this.style.background='#f8fafc';"
                     ondrop="handleDrop(event)"
                     style="border:2px dashed var(--border);border-radius:8px;padding:20px;text-align:center;cursor:pointer;background:#f8fafc;transition:all .15s;">
                    <div id="img-placeholder">
                        <div style="font-size:2rem;margin-bottom:6px;">📸</div>
                        <div style="font-size:.82rem;color:var(--text-muted);font-weight:600;">Arrastra una imagen o haz clic para seleccionar</div>
                        <div style="font-size:.72rem;color:var(--text-muted);margin-top:3px;">JPG, PNG, WebP — máx. 2 MB</div>
                    </div>
                    <img id="img-preview" src="" alt="" style="display:none;max-height:100px;border-radius:6px;object-fit:cover;">
                </div>
                <input type="file" id="modal-imagen" name="imagen"
                       accept="image/jpg,image/jpeg,image/png,image/webp"
                       style="display:none;"
                       onchange="previewImagen(this)">
            </div>

        </div>

        {{-- Footer del modal --}}
        <div style="padding:0 24px 24px;display:flex;gap:10px;">
            <button type="submit"
                    style="flex:1;padding:12px;background:var(--brand);color:#fff;border:none;border-radius:8px;font-weight:700;font-size:.92rem;cursor:pointer;transition:background .15s;"
                    onmouseenter="this.style.background='var(--brand-mid)'"
                    onmouseleave="this.style.background='var(--brand)'">
                🛒 Publicar en la Tienda
            </button>
            <button type="button" onclick="cerrarModal()"
                    style="padding:12px 20px;background:var(--bg);border:1.5px solid var(--border);border-radius:8px;font-weight:600;color:var(--text-muted);cursor:pointer;font-size:.88rem;">
                Cancelar
            </button>
        </div>
    </form>
</div>

@push('scripts')
<style>
@keyframes fadeIn  { from{opacity:0}  to{opacity:1} }
@keyframes slideUp { from{opacity:0;transform:translate(-50%,-46%)} to{opacity:1;transform:translate(-50%,-50%)} }
</style>
<script>
// ── Abrir modal ──────────────────────────────────────────────────
function abrirModalPublicar(id, nombre, descripcion) {
    document.getElementById('modal-receta-id').value    = id;
    document.getElementById('modal-descripcion').value  = descripcion;
    document.getElementById('modal-nombre-receta').textContent  = nombre;
    document.getElementById('modal-preview-nombre').textContent = nombre;
    document.getElementById('modal-precio').value = '';

    // Reset imagen
    resetImagen();

    document.getElementById('modal-overlay').style.display = '';
    document.getElementById('modal-publicar').style.display = '';
    document.body.style.overflow = 'hidden';

    setTimeout(() => document.getElementById('modal-precio').focus(), 100);
}

function cerrarModal() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('modal-publicar').style.display = 'none';
    document.body.style.overflow = '';
}

// Cerrar con Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });

// ── Preview de imagen ────────────────────────────────────────────
function previewImagen(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('img-placeholder').style.display = 'none';
        const prev = document.getElementById('img-preview');
        prev.src = e.target.result;
        prev.style.display = '';

        const area = document.getElementById('img-drop-area');
        area.style.borderColor = 'var(--brand)';
        area.style.background  = '#f0fdf4';
    };
    reader.readAsDataURL(input.files[0]);
}

function handleDrop(e) {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const input = document.getElementById('modal-imagen');
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    previewImagen(input);
    e.currentTarget.style.borderColor = 'var(--border)';
    e.currentTarget.style.background  = '#f8fafc';
}

function resetImagen() {
    document.getElementById('modal-imagen').value = '';
    document.getElementById('img-preview').style.display = 'none';
    document.getElementById('img-preview').src = '';
    document.getElementById('img-placeholder').style.display = '';
    const area = document.getElementById('img-drop-area');
    area.style.borderColor = 'var(--border)';
    area.style.background  = '#f8fafc';
}
</script>
@endpush

@endsection
