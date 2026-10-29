@extends('layouts.admin')

@section('title', 'Inventario de Insumos — ProLech')
@section('page-title', '📦 Inventario de Insumos')

@section('content')

{{-- ── FLASH MESSAGES ───────────────────────────────────────────────────── --}}
@if($errors->has('ingreso'))
<div class="flash flash-error">⛔ {{ $errors->first('ingreso') }}</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     MÉTRICAS RÁPIDAS
══════════════════════════════════════════════════════════════════════ --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
    <div class="stat-card verde">
        <div class="label">Total insumos</div>
        <div class="value">{{ $totalInsumos }}</div>
        <div class="unit">productos en catálogo</div>
    </div>
    <div class="stat-card naranja">
        <div class="label">Stock bajo</div>
        <div class="value">{{ $stockBajo }}</div>
        <div class="unit">≤ stock mínimo</div>
    </div>
    <div class="stat-card rojo">
        <div class="label">Sin stock</div>
        <div class="value">{{ $sinStock }}</div>
        <div class="unit">agotados</div>
    </div>
    <div class="stat-card azul">
        <div class="label">Último movimiento</div>
        <div class="value" style="font-size:1rem;padding-top:4px">
            {{ $ultimosMovimientos->first()?->created_at?->diffForHumans() ?? '—' }}
        </div>
        <div class="unit">{{ $ultimosMovimientos->first()?->insumo?->nombre ?? 'Sin registros' }}</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     GRID PRINCIPAL — TABLA + FORMULARIO INGRESO
══════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:1fr 380px;gap:22px;align-items:start">

    {{-- ── TABLA DE INSUMOS ─────────────────────────────────────────── --}}
    <div class="card" style="margin-bottom:0">
        <div class="card-header">
            <h3>📋 Estado actual del inventario</h3>
            <div style="display:flex;gap:8px;align-items:center">
                {{-- Buscador JS --}}
                <input type="text" id="buscar-insumo" placeholder="Buscar insumo..."
                       oninput="filtrarInsumos(this.value)"
                       style="padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;
                              font-size:.82rem;width:180px">
                <span style="font-size:.75rem;color:var(--text-muted)">
                    {{ $insumos->count() }} insumos
                </span>
            </div>
        </div>

        <div class="table-wrap">
            <table id="tabla-insumos">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="sel-todos" title="Seleccionar todos"
                                   onchange="seleccionarTodos(this.checked)"
                                   style="cursor:pointer;width:15px;height:15px"></th>
                        <th>Insumo</th>
                        <th>Unidad</th>
                        <th>Stock actual</th>
                        <th>Stock mínimo</th>
                        <th>Costo unit.</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($insumos as $insumo)
                @php
                    $stockActual  = (float) $insumo->stock_actual;
                    $stockMinimo  = (float) $insumo->stock_minimo;
                    $pct          = $stockMinimo > 0 ? min(100, ($stockActual / $stockMinimo) * 100) : 100;
                    $estadoColor  = $stockActual <= 0
                        ? 'var(--red)'
                        : ($stockActual <= $stockMinimo ? 'var(--amber)' : 'var(--green)');
                    $estadoLabel  = $stockActual <= 0
                        ? 'Agotado'
                        : ($stockActual <= $stockMinimo ? 'Stock bajo' : 'OK');
                    $estadoBg     = $stockActual <= 0
                        ? 'var(--red-bg)'
                        : ($stockActual <= $stockMinimo ? 'var(--amber-bg)' : 'var(--green-bg)');
                @endphp
                <tr class="fila-insumo" data-nombre="{{ strtolower($insumo->nombre) }}">
                    {{-- Checkbox de selección --}}
                    <td>
                        <input type="checkbox"
                               class="chk-insumo"
                               data-id="{{ $insumo->id }}"
                               data-nombre="{{ $insumo->nombre }}"
                               data-unidad="{{ $insumo->unidad_medida }}"
                               onchange="actualizarSeleccion()"
                               style="cursor:pointer;width:15px;height:15px">
                    </td>
                    {{-- Nombre --}}
                    <td>
                        <span style="font-weight:600;color:var(--text)">{{ $insumo->nombre }}</span>
                    </td>
                    {{-- Unidad --}}
                    <td>
                        <span class="tag">{{ $insumo->unidad_medida }}</span>
                    </td>
                    {{-- Stock actual con barra de progreso --}}
                    <td>
                        <div style="min-width:110px">
                            <span style="font-weight:700;color:{{ $estadoColor }};font-size:.9rem">
                                {{ number_format($stockActual, 2) }}
                            </span>
                            <span style="color:var(--text-muted);font-size:.78rem"> {{ $insumo->unidad_medida }}</span>
                            <div style="margin-top:4px;height:4px;background:var(--border);border-radius:4px;overflow:hidden">
                                <div style="height:100%;width:{{ min(100,$pct) }}%;
                                            background:{{ $estadoColor }};border-radius:4px;
                                            transition:width .3s"></div>
                            </div>
                        </div>
                    </td>
                    {{-- Stock mínimo --}}
                    <td style="color:var(--text-muted);font-size:.85rem">
                        {{ number_format($stockMinimo, 2) }} {{ $insumo->unidad_medida }}
                    </td>
                    {{-- Costo --}}
                    <td style="font-family:'SF Mono',monospace;font-size:.82rem;color:var(--text-2)">
                        S/ {{ number_format($insumo->costo_unitario, 4) }}
                    </td>
                    {{-- Badge estado --}}
                    <td>
                        <span style="display:inline-block;padding:3px 10px;border-radius:20px;
                                     font-size:.7rem;font-weight:700;text-transform:uppercase;
                                     background:{{ $estadoBg }};color:{{ $estadoColor }}">
                            {{ $estadoLabel }}
                        </span>
                    </td>
                    {{-- Acción --}}
                    <td>
                        <a href="{{ route('admin.inventario.movimientos', $insumo) }}"
                           style="font-size:.78rem;color:var(--blue);text-decoration:none;
                                  white-space:nowrap;font-weight:500">
                            Ver historial →
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted)">
                    Sin insumos registrados. Corre el seeder de producción.
                </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── PANEL INGRESO DE STOCK ───────────────────────────────────── --}}
    <div style="position:sticky;top:80px">

        {{-- Formulario de ingreso --}}
        <div class="card" id="panel-ingreso" style="margin-bottom:16px">
            <div class="card-header">
                <h3>➕ Ingresar stock</h3>
                <span class="card-header-meta" id="contador-sel">0 seleccionados</span>
            </div>
            <div class="card-body">

                {{-- Selección vacía --}}
                <div id="hint-seleccion" style="text-align:center;padding:24px 0;color:var(--text-muted)">
                    <div style="font-size:2rem;margin-bottom:8px">☑️</div>
                    <div style="font-size:.85rem">Selecciona uno o más insumos<br>de la tabla para cargar stock</div>
                </div>

                <form method="POST" action="{{ route('admin.inventario.ingreso') }}"
                      id="form-ingreso" style="display:none">
                    @csrf

                    {{-- Filas dinámicas de insumos seleccionados --}}
                    <div id="filas-ingreso" style="display:flex;flex-direction:column;gap:12px;
                                                    margin-bottom:16px;max-height:380px;
                                                    overflow-y:auto;padding-right:2px">
                    </div>

                    {{-- Motivo --}}
                    <div style="margin-bottom:14px">
                        <label for="motivo" style="display:block;font-size:.72rem;font-weight:700;
                            text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);
                            margin-bottom:6px">
                            Motivo del ingreso <span style="color:var(--red)">*</span>
                        </label>
                        <input type="text" name="motivo" id="motivo" required
                               value="{{ old('motivo') }}"
                               placeholder="Ej: Compra proveedor Arequipa"
                               style="width:100%;padding:9px 12px;border:1.5px solid var(--border);
                                      border-radius:8px;font-size:.88rem;font-family:inherit">
                        {{-- Chips de motivos frecuentes --}}
                        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px">
                            @foreach(['Compra proveedor', 'Ajuste de inventario', 'Devolución', 'Donación'] as $m)
                            <button type="button"
                                    onclick="document.getElementById('motivo').value='{{ $m }}'"
                                    style="padding:3px 10px;border:1.5px solid var(--border);
                                           background:var(--bg);border-radius:20px;font-size:.72rem;
                                           cursor:pointer;color:var(--text-2);font-weight:500">
                                {{ $m }}
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" id="btn-registrar"
                            style="width:100%;padding:11px;background:var(--brand-light);
                                   color:#fff;border:none;border-radius:8px;font-size:.9rem;
                                   font-weight:700;cursor:pointer;font-family:inherit;
                                   display:flex;align-items:center;justify-content:center;gap:8px">
                        📥 Registrar ingreso
                    </button>
                </form>
            </div>
        </div>

        {{-- Actividad reciente --}}
        <div class="card" style="margin-bottom:0">
            <div class="card-header">
                <h3>🕐 Actividad reciente</h3>
            </div>
            <div style="padding:0 4px">
                @forelse($ultimosMovimientos as $mov)
                <div style="display:flex;align-items:flex-start;gap:10px;
                             padding:10px 18px;border-bottom:1px solid var(--border-soft)">
                    <span style="font-size:1.1rem;margin-top:1px">
                        {{ $mov->tipo === 'ingreso' ? '📥' : '📤' }}
                    </span>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:.82rem;font-weight:600;color:var(--text);
                                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $mov->insumo?->nombre ?? '—' }}
                        </div>
                        <div style="font-size:.75rem;color:var(--text-muted)">
                            {{ $mov->tipo === 'ingreso' ? '+' : '−' }}{{ number_format($mov->cantidad, 2) }}
                            {{ $mov->insumo?->unidad_medida }}
                            · {{ $mov->motivo ?? '—' }}
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <span style="display:inline-block;padding:2px 8px;border-radius:20px;
                                     font-size:.65rem;font-weight:700;text-transform:uppercase;
                                     background:{{ $mov->tipo === 'ingreso' ? 'var(--green-bg)' : 'var(--amber-bg)' }};
                                     color:{{ $mov->tipo === 'ingreso' ? '#15803d' : '#92400e' }}">
                            {{ $mov->tipo }}
                        </span>
                        <div style="font-size:.7rem;color:var(--text-dim);margin-top:2px">
                            {{ $mov->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @empty
                <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.85rem">
                    Sin movimientos registrados aún.
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     JAVASCRIPT — Selección múltiple + panel dinámico
══════════════════════════════════════════════════════════════════════ --}}
<script>
// ── Estado de selección ──────────────────────────────────────────────────────
const seleccionados = new Map(); // id → { nombre, unidad }

function seleccionarTodos(checked) {
    document.querySelectorAll('.chk-insumo').forEach(chk => {
        const fila = chk.closest('tr');
        // Solo seleccionar filas visibles (no filtradas)
        if (fila.style.display !== 'none') {
            chk.checked = checked;
            if (checked) {
                seleccionados.set(chk.dataset.id, {
                    nombre: chk.dataset.nombre,
                    unidad: chk.dataset.unidad
                });
            } else {
                seleccionados.delete(chk.dataset.id);
            }
        }
    });
    actualizarPanel();
}

function actualizarSeleccion() {
    document.querySelectorAll('.chk-insumo').forEach(chk => {
        if (chk.checked) {
            seleccionados.set(chk.dataset.id, {
                nombre: chk.dataset.nombre,
                unidad: chk.dataset.unidad
            });
        } else {
            seleccionados.delete(chk.dataset.id);
        }
    });
    actualizarPanel();
}

// ── Actualizar panel de ingreso ──────────────────────────────────────────────
function actualizarPanel() {
    const n       = seleccionados.size;
    const hint    = document.getElementById('hint-seleccion');
    const form    = document.getElementById('form-ingreso');
    const filas   = document.getElementById('filas-ingreso');
    const contador = document.getElementById('contador-sel');

    contador.textContent = n + (n === 1 ? ' seleccionado' : ' seleccionados');

    if (n === 0) {
        hint.style.display = 'block';
        form.style.display = 'none';
        return;
    }

    hint.style.display = 'none';
    form.style.display = 'block';

    // Reconstruir filas de cantidad
    filas.innerHTML = '';
    seleccionados.forEach(({ nombre, unidad }, id) => {
        const div = document.createElement('div');
        div.style.cssText = 'background:var(--bg);border-radius:8px;padding:10px 12px';
        div.innerHTML = `
            <input type="hidden" name="insumos[]" value="${id}">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                <div style="flex:1;min-width:0">
                    <div style="font-size:.82rem;font-weight:600;color:var(--text);
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        ${nombre}
                    </div>
                    <div style="font-size:.72rem;color:var(--text-muted)">${unidad}</div>
                </div>
                <input type="number" name="cantidades[${id}]"
                       min="0.001" step="0.001" placeholder="Cantidad"
                       required
                       style="width:110px;padding:7px 10px;border:1.5px solid var(--border);
                              border-radius:7px;font-size:.88rem;text-align:right;
                              font-family:inherit">
            </div>
        `;
        filas.appendChild(div);
    });
}

// ── Filtro de búsqueda ────────────────────────────────────────────────────────
function filtrarInsumos(texto) {
    const q = texto.toLowerCase().trim();
    document.querySelectorAll('.fila-insumo').forEach(fila => {
        const nombre = fila.dataset.nombre || '';
        fila.style.display = nombre.includes(q) ? '' : 'none';
    });
}

// ── Confirmar envío si hay cantidades en 0 ────────────────────────────────────
document.getElementById('form-ingreso').addEventListener('submit', function (e) {
    const inputs = this.querySelectorAll('input[name^="cantidades"]');
    const todosCero = [...inputs].every(i => parseFloat(i.value || 0) <= 0);
    if (todosCero) {
        e.preventDefault();
        alert('Ingresa al menos una cantidad mayor a 0.');
    }
});
</script>

@endsection
