@extends('layouts.admin')

@section('title', 'Detalle Liquidación')
@section('page-title', '📑 Detalle de Liquidación')

@section('content')

<div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;flex-wrap:wrap">
    <a href="{{ route('admin.liquidaciones.index') }}" class="btn btn-ghost btn-sm">← Volver</a>
    <h2 style="font-size:1.1rem;font-weight:700;color:var(--text)">
        {{ $liquidacion->ganadero->nombre }}
        &nbsp;·&nbsp;
        <span style="color:var(--text-muted);font-weight:500;font-size:.9rem">
            {{ $liquidacion->semana_inicio->format('d/m/Y') }} → {{ $liquidacion->semana_fin->format('d/m/Y') }}
        </span>
    </h2>

    {{-- Botón imprimir sobre --}}
    <a href="{{ route('admin.liquidaciones.sobre-pdf', $liquidacion) }}"
       target="_blank"
       class="btn btn-primary btn-sm"
       style="margin-left:auto">
        🖨️ Imprimir sobre de pago
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start">

    {{-- ── Acopios de la semana ─────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h3>📋 Acopios registrados en la semana</h3>
            <span class="card-header-meta">{{ $acopios->count() }} registro(s)</span>
        </div>
        <div class="card-body" style="padding:0">
            @if($acopios->isEmpty())
                <p style="padding:24px;color:var(--text-muted);text-align:center">Sin acopios registrados para esta semana.</p>
            @else
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:var(--bg)">
                        <th style="padding:9px 14px;font-size:.78rem;text-align:left;color:var(--text-muted);font-weight:600">FECHA</th>
                        <th style="padding:9px 14px;font-size:.78rem;text-align:left;color:var(--text-muted);font-weight:600">CHOFER</th>
                        <th style="padding:9px 14px;font-size:.78rem;text-align:right;color:var(--text-muted);font-weight:600">LITROS</th>
                        <th style="padding:9px 14px;font-size:.78rem;text-align:center;color:var(--text-muted);font-weight:600">% AGUA</th>
                        <th style="padding:9px 14px;font-size:.78rem;text-align:center;color:var(--text-muted);font-weight:600">ESTADO</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($acopios as $a)
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:9px 14px;font-size:.85rem">
                            {{ \Carbon\Carbon::parse($a->fecha)->isoFormat('ddd D MMM') }}
                        </td>
                        <td style="padding:9px 14px;font-size:.85rem;color:var(--text-muted)">
                            {{ $a->chofer->name ?? '—' }}
                        </td>
                        <td style="padding:9px 14px;font-size:.85rem;text-align:right;font-weight:700;color:var(--green)">
                            {{ number_format($a->litros, 1) }} L
                        </td>
                        <td style="padding:9px 14px;font-size:.85rem;text-align:center">
                            @if($a->porcentaje_agua !== null)
                                <span style="color:{{ $a->porcentaje_agua > 5 ? 'var(--rojo)' : ($a->porcentaje_agua > 1 ? 'var(--amber)' : 'var(--text)') }};font-weight:{{ $a->porcentaje_agua > 5 ? '700' : '400' }}">
                                    {{ number_format($a->porcentaje_agua, 2) }}%
                                </span>
                            @else — @endif
                        </td>
                        <td style="padding:9px 14px;text-align:center">
                            <span class="badge badge-{{ $a->estado }}">{{ ucfirst($a->estado) }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:var(--green-bg)">
                        <td colspan="2" style="padding:10px 14px;font-weight:800;font-size:.88rem;color:var(--green-dark)">
                            Total
                        </td>
                        <td style="padding:10px 14px;text-align:right;font-weight:900;font-size:1rem;color:var(--green)">
                            {{ number_format($acopios->sum('litros'), 1) }} L
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>
    </div>

    {{-- ── Resumen financiero ───────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:18px">

        <div class="card">
            <div class="card-header">
                <h3>💰 Resumen de pago</h3>
                <span class="badge badge-{{ $liquidacion->estado }}">{{ ucfirst($liquidacion->estado) }}</span>
            </div>
            <div class="card-body" style="padding:16px;display:flex;flex-direction:column;gap:10px">

                @php
                    function filaLiq($label, $valor, $color = null) {
                        echo '<div style="display:flex;justify-content:space-between;align-items:center;font-size:.88rem">';
                        echo '<span style="color:var(--text-muted)">' . $label . '</span>';
                        echo '<span style="font-weight:700;' . ($color ? "color:$color" : '') . '">' . $valor . '</span>';
                        echo '</div>';
                    }
                @endphp

                @php filaLiq('Litros aprobados', number_format($liquidacion->litros_aprobados, 1) . ' L') @endphp
                @php filaLiq('Precio por litro', 'S/ ' . number_format($liquidacion->precio_litro, 2)) @endphp
                @php filaLiq('Subtotal leche', 'S/ ' . number_format($liquidacion->subtotal_normal, 2)) @endphp

                @if($liquidacion->litros_adulterados > 0)
                    <hr style="border:none;border-top:1px dashed var(--border)">
                    @php filaLiq('Litros adulterados', number_format($liquidacion->litros_adulterados, 1) . ' L', 'var(--rojo)') @endphp
                    @php filaLiq('Precio penalidad', 'S/ ' . number_format($liquidacion->precio_penalidad, 2) . '/L', 'var(--rojo)') @endphp
                @endif

                @if($liquidacion->total_descuentos > 0)
                    <hr style="border:none;border-top:1px dashed var(--border)">
                    @php filaLiq('Descuentos', '− S/ ' . number_format($liquidacion->total_descuentos, 2), 'var(--rojo)') @endphp
                @endif

                @if($liquidacion->detalle_descuentos)
                    <p style="font-size:.77rem;color:var(--text-muted);background:var(--amber-bg);
                              padding:6px 10px;border-radius:4px">
                        ℹ️ {{ $liquidacion->detalle_descuentos }}
                    </p>
                @endif

                <div style="background:var(--green-bg);border:1.5px solid var(--green-border);
                            border-radius:8px;padding:12px 16px;margin-top:6px;
                            display:flex;justify-content:space-between;align-items:center">
                    <span style="font-weight:800;font-size:.9rem;color:var(--green-dark)">TOTAL NETO</span>
                    <span style="font-weight:900;font-size:1.3rem;color:var(--green)">
                        S/ {{ number_format($liquidacion->total_neto, 2) }}
                    </span>
                </div>

                <div style="font-size:.8rem;color:var(--text-muted);text-align:center">
                    📅 Fecha de pago: <strong>{{ $liquidacion->fecha_pago->isoFormat('dddd D [de] MMMM') }}</strong>
                </div>
            </div>
        </div>

        {{-- Acciones --}}
        @if($liquidacion->estado === 'borrador')
        <form method="POST" action="{{ route('admin.liquidaciones.confirmar', $liquidacion) }}">
            @csrf
            <button type="submit" class="btn btn-primary" style="width:100%">
                ✓ Confirmar liquidación
            </button>
        </form>
        @elseif($liquidacion->estado === 'confirmada')
        <form method="POST" action="{{ route('admin.liquidaciones.pagar', $liquidacion) }}">
            @csrf
            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:10px">
                <select name="metodo_pago" class="form-control" required>
                    <option value="">Método de pago...</option>
                    <option value="efectivo">💵 Efectivo</option>
                    <option value="yape">📱 Yape</option>
                    <option value="plin">📱 Plin</option>
                </select>
                <input type="text" name="referencia_pago" class="form-control"
                       placeholder="Referencia (opcional)">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;background:var(--green)">
                💵 Marcar como pagada
            </button>
        </form>
        @else
        <div style="background:var(--green-bg);border:1px solid var(--green-border);
                    border-radius:8px;padding:12px 16px;text-align:center;font-size:.88rem">
            ✅ <strong>Pagada</strong>
            @if($liquidacion->pagada_at)
                el {{ $liquidacion->pagada_at->format('d/m/Y H:i') }}
            @endif
            @if($liquidacion->metodo_pago)
                via {{ ucfirst($liquidacion->metodo_pago) }}
            @endif
        </div>
        @endif

        {{-- Botón imprimir --}}
        <a href="{{ route('admin.liquidaciones.sobre-pdf', $liquidacion) }}"
           target="_blank"
           class="btn btn-ghost"
           style="width:100%;text-align:center">
            🖨️ Imprimir sobre de pago
        </a>

    </div>
</div>

@endsection
