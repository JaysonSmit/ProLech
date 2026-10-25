@extends('layouts.admin')

@section('title', 'Detalle Lote ' . $lote->codigo_lote)
@section('page-title', '🏭 Lote ' . $lote->codigo_lote)

@section('content')

<div style="margin-bottom:18px">
    <a href="{{ route('admin.produccion.lotes.create') }}" class="btn btn-ghost">
        ← Volver a Producción
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

    {{-- Info del lote --}}
    <div class="card" style="margin-bottom:0">
        <div class="card-header"><h3>📋 Información del lote</h3></div>
        <div class="card-body">
            <dl style="display:grid;grid-template-columns:auto 1fr;gap:8px 20px;font-size:.88rem">
                <dt style="color:var(--text-muted);font-weight:600">Código</dt>
                <dd><span style="font-family:monospace;font-weight:800;color:var(--brand-light);
                    background:#f0fdf4;padding:2px 10px;border-radius:5px;font-size:.9rem">
                    {{ $lote->codigo_lote }}
                </span></dd>

                <dt style="color:var(--text-muted);font-weight:600">Producto</dt>
                <dd style="font-weight:700">{{ $lote->receta->nombre ?? '—' }}</dd>

                <dt style="color:var(--text-muted);font-weight:600">Fecha</dt>
                <dd>{{ $lote->fecha_produccion->isoFormat('dddd D [de] MMMM YYYY') }}</dd>

                <dt style="color:var(--text-muted);font-weight:600">Cantidad</dt>
                <dd><strong style="font-size:1.1rem">{{ number_format($lote->cantidad_producida) }}</strong>
                    <span style="color:var(--text-muted)"> unidades</span></dd>

                <dt style="color:var(--text-muted);font-weight:600">Costo total</dt>
                <dd><strong style="color:var(--amber);font-size:1.05rem">
                    S/ {{ number_format($lote->costo_total, 2) }}
                </strong></dd>

                <dt style="color:var(--text-muted);font-weight:600">Costo / unidad</dt>
                <dd style="color:var(--text-muted)">
                    @if($lote->cantidad_producida > 0)
                        S/ {{ number_format($lote->costo_total / $lote->cantidad_producida, 4) }}
                    @else —
                    @endif
                </dd>

                <dt style="color:var(--text-muted);font-weight:600">Responsable</dt>
                <dd>{{ $lote->responsable->name ?? '—' }}</dd>

                @if($lote->observaciones)
                <dt style="color:var(--text-muted);font-weight:600">Observaciones</dt>
                <dd style="color:var(--text-2);font-style:italic">{{ $lote->observaciones }}</dd>
                @endif
            </dl>
        </div>
    </div>

    {{-- Ingredientes usados (según receta × cantidad) --}}
    <div class="card" style="margin-bottom:0">
        <div class="card-header">
            <h3>🧪 Insumos consumidos</h3>
            <span style="font-size:.75rem;color:var(--text-muted)">
                según receta × {{ number_format($lote->cantidad_producida) }} u.
            </span>
        </div>
        <div class="card-body" style="padding:0">
            @if($lote->receta && $lote->receta->detalles->isNotEmpty())
            <table style="width:100%;border-collapse:collapse;font-size:.85rem">
                <thead>
                    <tr style="background:var(--bg)">
                        <th style="padding:9px 16px;text-align:left;font-size:.7rem;
                            font-weight:700;text-transform:uppercase;color:var(--text-muted);
                            border-bottom:1px solid var(--border)">Insumo</th>
                        <th style="padding:9px 16px;text-align:right;font-size:.7rem;
                            font-weight:700;text-transform:uppercase;color:var(--text-muted);
                            border-bottom:1px solid var(--border)">Cantidad usada</th>
                        <th style="padding:9px 16px;text-align:right;font-size:.7rem;
                            font-weight:700;text-transform:uppercase;color:var(--text-muted);
                            border-bottom:1px solid var(--border)">Costo parcial</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rendBase = $lote->receta->rendimiento_esperado_unidades;
                    @endphp
                    @foreach($lote->receta->detalles as $det)
                    @php
                        $cantUsada   = ($det->cantidad_requerida / $rendBase) * $lote->cantidad_producida;
                        $costoParcial = $cantUsada * (float) $det->insumo->costo_unitario;
                    @endphp
                    <tr>
                        <td style="padding:10px 16px;border-bottom:1px solid var(--border-soft)">
                            {{ $det->insumo->nombre }}
                        </td>
                        <td style="padding:10px 16px;border-bottom:1px solid var(--border-soft);
                                   text-align:right;font-weight:700">
                            {{ number_format($cantUsada, 3) }}
                            <span style="color:var(--text-muted);font-weight:400">
                                {{ $det->insumo->unidad_medida }}
                            </span>
                        </td>
                        <td style="padding:10px 16px;border-bottom:1px solid var(--border-soft);
                                   text-align:right;color:var(--amber);font-weight:600">
                            S/ {{ number_format($costoParcial, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:var(--bg)">
                        <td colspan="2" style="padding:10px 16px;font-weight:700;
                            font-size:.82rem;text-transform:uppercase;color:var(--text-muted)">
                            Total
                        </td>
                        <td style="padding:10px 16px;text-align:right;font-weight:800;
                            color:var(--amber);font-size:.95rem">
                            S/ {{ number_format($lote->costo_total, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
            @else
                <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:.85rem">
                    Sin detalles de insumos disponibles.
                </div>
            @endif
        </div>
    </div>

</div>

@endsection
