@extends('layouts.admin')

@section('title', 'Historial · ' . $insumo->nombre . ' — ProLech')
@section('page-title', '📦 Inventario › ' . $insumo->nombre)

@section('content')

{{-- ── BREADCRUMB + BACK ────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:10px;margin-bottom:22px;flex-wrap:wrap">
    <a href="{{ route('admin.inventario.index') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;
              background:var(--bg);border:1.5px solid var(--border);border-radius:8px;
              font-size:.82rem;font-weight:600;color:var(--text-2);text-decoration:none">
        ← Inventario
    </a>
    <span style="color:var(--text-muted);font-size:.85rem">›</span>
    <span style="font-weight:700;color:var(--text)">{{ $insumo->nombre }}</span>
    <span class="tag">{{ $insumo->unidad_medida }}</span>
</div>

{{-- ── MÉTRICAS DEL INSUMO ──────────────────────────────────────────────── --}}
@php
    $stockActual = (float) $insumo->stock_actual;
    $stockMinimo = (float) $insumo->stock_minimo;
    $pct = $stockMinimo > 0 ? min(100, ($stockActual / $stockMinimo) * 100) : 100;
    $estadoColor = $stockActual <= 0 ? 'var(--red)' : ($stockActual <= $stockMinimo ? 'var(--amber)' : 'var(--green)');
    $estadoLabel = $stockActual <= 0 ? '⛔ Agotado' : ($stockActual <= $stockMinimo ? '⚠️ Stock bajo' : '✅ OK');
@endphp

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
    <div class="stat-card verde">
        <div class="label">Stock actual</div>
        <div class="value" style="color:{{ $estadoColor }}">
            {{ number_format($stockActual, 2) }}
        </div>
        <div class="unit">{{ $insumo->unidad_medida }} · {{ $estadoLabel }}</div>
    </div>
    <div class="stat-card azul">
        <div class="label">Total ingresos</div>
        <div class="value">{{ number_format($totalIngresos, 2) }}</div>
        <div class="unit">{{ $insumo->unidad_medida }} acumulados</div>
    </div>
    <div class="stat-card naranja">
        <div class="label">Total egresos</div>
        <div class="value">{{ number_format($totalEgresos, 2) }}</div>
        <div class="unit">{{ $insumo->unidad_medida }} consumidos</div>
    </div>
    <div class="stat-card">
        <div class="label" style="color:var(--text-muted)">Costo unitario</div>
        <div class="value" style="color:var(--text);font-size:1.5rem">
            S/ {{ number_format($insumo->costo_unitario, 4) }}
        </div>
        <div class="unit">por {{ $insumo->unidad_medida }}</div>
    </div>
</div>

{{-- ── BARRA DE PROGRESO DE STOCK ───────────────────────────────────────── --}}
<div class="card" style="margin-bottom:22px">
    <div class="card-body" style="padding:16px 22px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <span style="font-size:.82rem;font-weight:600;color:var(--text-2)">
                Nivel de stock: <strong style="color:{{ $estadoColor }}">{{ number_format($pct,1) }}%</strong>
                del mínimo requerido
            </span>
            <span style="font-size:.8rem;color:var(--text-muted)">
                Mín: {{ number_format($stockMinimo, 2) }} {{ $insumo->unidad_medida }}
            </span>
        </div>
        <div style="height:10px;background:var(--border);border-radius:10px;overflow:hidden">
            <div style="height:100%;width:{{ min(100,$pct) }}%;background:{{ $estadoColor }};
                        border-radius:10px;transition:width .4s"></div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     FILTROS
══════════════════════════════════════════════════════════════════════ --}}
<div class="card" style="margin-bottom:22px">
    <div class="card-body" style="padding:16px 22px">
        <form method="GET" action="{{ route('admin.inventario.movimientos', $insumo) }}"
              style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">

            <div style="display:flex;flex-direction:column;gap:5px">
                <label style="font-size:.7rem;font-weight:700;text-transform:uppercase;
                               letter-spacing:.5px;color:var(--text-muted)">Tipo</label>
                <select name="tipo"
                        style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;
                               font-size:.85rem;background:#fff;font-family:inherit;min-width:130px">
                    <option value="">Todos</option>
                    <option value="ingreso"  {{ $tipo === 'ingreso'  ? 'selected' : '' }}>📥 Ingresos</option>
                    <option value="egreso"   {{ $tipo === 'egreso'   ? 'selected' : '' }}>📤 Egresos</option>
                </select>
            </div>

            <div style="display:flex;flex-direction:column;gap:5px">
                <label style="font-size:.7rem;font-weight:700;text-transform:uppercase;
                               letter-spacing:.5px;color:var(--text-muted)">Desde</label>
                <input type="date" name="desde" value="{{ $desde }}"
                       style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;
                              font-size:.85rem;font-family:inherit">
            </div>

            <div style="display:flex;flex-direction:column;gap:5px">
                <label style="font-size:.7rem;font-weight:700;text-transform:uppercase;
                               letter-spacing:.5px;color:var(--text-muted)">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}"
                       style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;
                              font-size:.85rem;font-family:inherit">
            </div>

            <button type="submit"
                    style="padding:9px 20px;background:var(--brand-light);color:#fff;border:none;
                           border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;
                           font-family:inherit">
                🔍 Filtrar
            </button>

            @if($tipo || $desde || $hasta)
            <a href="{{ route('admin.inventario.movimientos', $insumo) }}"
               style="padding:9px 16px;border:1.5px solid var(--border);border-radius:8px;
                      font-size:.82rem;font-weight:500;color:var(--text-2);text-decoration:none;
                      display:inline-flex;align-items:center">
                ✕ Limpiar
            </a>
            @endif

        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     TABLA DE MOVIMIENTOS
══════════════════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-header">
        <h3>📋 Historial de movimientos</h3>
        <span class="card-header-meta">
            {{ $movimientos->total() }} registro(s)
            @if($tipo) · filtro: {{ $tipo }} @endif
        </span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Motivo / Referencia</th>
                    <th>Registrado por</th>
                    <th>Fecha y hora</th>
                </tr>
            </thead>
            <tbody>
            @forelse($movimientos as $mov)
            @php
                $esIngreso = $mov->tipo === 'ingreso';
                $iconTipo  = $esIngreso ? '📥' : '📤';
                $colorCant = $esIngreso ? 'var(--green)' : 'var(--amber)';
                $bgTipo    = $esIngreso ? 'var(--green-bg)' : 'var(--amber-bg)';
                $colorTipo = $esIngreso ? '#15803d' : '#92400e';
            @endphp
            <tr>
                <td style="color:var(--text-dim);font-size:.8rem;font-family:monospace">
                    #{{ $mov->id }}
                </td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;
                                 padding:4px 11px;border-radius:20px;font-size:.72rem;
                                 font-weight:700;text-transform:uppercase;
                                 background:{{ $bgTipo }};color:{{ $colorTipo }}">
                        {{ $iconTipo }} {{ $mov->tipo }}
                    </span>
                </td>
                <td>
                    <span style="font-weight:700;font-size:.95rem;color:{{ $colorCant }}">
                        {{ $esIngreso ? '+' : '−' }}{{ number_format($mov->cantidad, 3) }}
                    </span>
                    <span style="color:var(--text-muted);font-size:.78rem">
                        {{ $insumo->unidad_medida }}
                    </span>
                </td>
                <td style="max-width:280px">
                    <div style="font-size:.85rem;color:var(--text-2)">
                        {{ $mov->motivo ?? '—' }}
                    </div>
                    @if($mov->referencia_id)
                    <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px">
                        Ref: {{ class_basename($mov->referencia_tipo) }} #{{ $mov->referencia_id }}
                    </div>
                    @endif
                </td>
                <td style="font-size:.83rem;color:var(--text-2)">
                    {{ $mov->registrador?->name ?? '—' }}
                </td>
                <td style="font-size:.82rem;color:var(--text-muted);white-space:nowrap">
                    {{ $mov->created_at->format('d/m/Y H:i') }}
                    <div style="font-size:.7rem;color:var(--text-dim)">
                        {{ $mov->created_at->diffForHumans() }}
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6"
                    style="text-align:center;padding:40px;color:var(--text-muted);font-size:.9rem">
                    @if($tipo || $desde || $hasta)
                        Sin movimientos con los filtros aplicados.
                        <a href="{{ route('admin.inventario.movimientos', $insumo) }}"
                           style="color:var(--blue)">Ver todos</a>
                    @else
                        Sin movimientos registrados para este insumo todavía.
                    @endif
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if($movimientos->hasPages())
    <div style="padding:16px 22px;border-top:1px solid var(--border-soft)">
        {{ $movimientos->links() }}
    </div>
    @endif
</div>

@endsection
