@extends('layouts.admin')

@section('title', 'Liquidaciones')
@section('page-title', '📑 Liquidaciones Semanales')

@section('content')

{{-- ══════════════════════════════════════════════════════════
     GUÍA RÁPIDA — colapsable
══════════════════════════════════════════════════════════ --}}
<details style="margin-bottom:20px;background:var(--blue-bg);border:1px solid #bfdbfe;
                border-radius:var(--radius);padding:14px 18px;cursor:pointer">
    <summary style="font-weight:700;color:var(--blue);font-size:.9rem;list-style:none;
                    display:flex;align-items:center;gap:8px">
        <span>ℹ️</span> ¿Cómo funciona el pago semanal? (haz clic para ver)
    </summary>
    <div style="margin-top:14px;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
        @foreach([
            ['1️⃣','Miércoles','Cierre de semana. Los acopios del jueves anterior al miércoles se contabilizan.'],
            ['2️⃣','Jueves','Haz clic en "Generar liquidaciones". El sistema calcula cuánto le toca a cada proveedor automáticamente.'],
            ['3️⃣','Jueves','Revisa cada liquidación. Si está bien, haz clic en "✓ Confirmar".'],
            ['4️⃣','Viernes','Día de pago. Sal con el efectivo o abre Yape. Marca cada pago como completado.'],
            ['5️⃣','Viernes','Al marcar "💵 Pagar", elige el método (Efectivo/Yape) y anota la referencia si usas Yape.'],
        ] as [$n, $dia, $desc])
        <div style="background:var(--bg-card);border-radius:var(--radius-sm);padding:12px 14px">
            <div style="font-size:1.4rem;margin-bottom:4px">{{ $n }}</div>
            <div style="font-weight:700;font-size:.85rem;color:var(--blue);margin-bottom:4px">{{ $dia }}</div>
            <div style="font-size:.78rem;color:var(--text-muted);line-height:1.5">{{ $desc }}</div>
        </div>
        @endforeach
    </div>
    <div style="margin-top:12px;padding:10px 12px;background:var(--amber-bg);border:1px solid #fde68a;
                border-radius:var(--radius-sm);font-size:.82rem;color:#92400e">
        💡 <strong>Precio base:</strong> S/ 1.80 por litro aprobado.
        <strong>Leche adulterada:</strong> se paga a S/ 1.50 (penalidad).
        <strong>Descuento quesos:</strong> S/ 17.00 por queso adelantado.
    </div>
</details>

{{-- ══════════════════════════════════════════════════════════
     CABECERA DE SEMANA
══════════════════════════════════════════════════════════ --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:22px">

    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);
                    padding:9px 16px;font-size:.88rem;font-weight:600">
            📅 {{ $semanaInicio->format('d/m/Y') }} → {{ $semanaFin->format('d/m/Y') }}
        </div>
        <div class="tag" style="font-weight:600">
            💵 Viernes de pago: {{ $fechaPago->isoFormat('D [de] MMMM YYYY') }}
        </div>
        @php
            $hoyEsViernes = now()->dayOfWeek === \Carbon\Carbon::FRIDAY;
            $semanaActual = $semanaInicio->toDateString() === \App\Models\Liquidacion::inicioSemana(now())->toDateString();
        @endphp
        @if($hoyEsViernes && $semanaActual)
        <span class="badge badge-aprobado" style="font-size:.78rem;padding:5px 12px">
            🎉 HOY ES DÍA DE PAGO
        </span>
        @endif
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        @php
            $semAnterior  = $semanaInicio->copy()->subWeek();
            $semSiguiente = $semanaInicio->copy()->addWeek();
        @endphp
        <a href="{{ route('admin.liquidaciones.index', ['semana_inicio' => $semAnterior->toDateString()]) }}"
           class="btn btn-ghost btn-sm">← Anterior</a>
        <a href="{{ route('admin.liquidaciones.index') }}" class="btn btn-ghost btn-sm">Esta semana</a>
        <a href="{{ route('admin.liquidaciones.index', ['semana_inicio' => $semSiguiente->toDateString()]) }}"
           class="btn btn-ghost btn-sm">Siguiente →</a>

        <form method="POST" action="{{ route('admin.liquidaciones.generar') }}">
            @csrf
            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Esto generará las liquidaciones para la semana del {{ $semanaInicio->format('d/m') }} al {{ $semanaFin->format('d/m/Y') }}.\n\nSolo genera para proveedores sin liquidación existente en este período.')">
                ⚙️ Generar liquidaciones
            </button>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     STATS
══════════════════════════════════════════════════════════ --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
    <div class="stat-card azul">
        <div class="label">Proveedores esta semana</div>
        <div class="value">{{ $resumen['total_ganaderos'] }}</div>
        <div class="unit">con entrega registrada</div>
    </div>
    <div class="stat-card verde">
        <div class="label">Total litros acopiados</div>
        <div class="value">{{ number_format($resumen['total_litros'], 0) }}</div>
        <div class="unit">litros aprobados</div>
    </div>
    <div class="stat-card naranja">
        <div class="label">Total a pagar esta semana</div>
        <div class="value">S/ {{ number_format($resumen['total_pagar'], 0) }}</div>
        <div class="unit">monto neto total</div>
    </div>
    <div class="stat-card {{ $resumen['pendientes_pago'] > 0 ? 'rojo' : 'verde' }}">
        <div class="label">Pendientes de pago</div>
        <div class="value">{{ $resumen['pendientes_pago'] }}</div>
        <div class="unit">{{ $resumen['pendientes_pago'] > 0 ? 'confirmadas, sin pagar aún' : '✓ Todo pagado' }}</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     TABLA PRINCIPAL
══════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-header">
        <h3>📋 Liquidaciones — {{ $semanaInicio->format('d/m') }} al {{ $semanaFin->format('d/m/Y') }}</h3>
        <span class="card-header-meta">{{ $liquidaciones->total() }} proveedores</span>
    </div>

    @if($liquidaciones->isEmpty())
        <div style="padding:60px;text-align:center;color:var(--text-muted)">
            <div style="font-size:3rem;margin-bottom:12px">📑</div>
            <div style="font-weight:700;font-size:1rem;margin-bottom:6px">
                Sin liquidaciones para esta semana
            </div>
            <div style="font-size:.85rem;margin-bottom:20px;max-width:440px;margin-left:auto;margin-right:auto">
                Haz clic en <strong>"⚙️ Generar liquidaciones"</strong> para calcular automáticamente
                los pagos de todos los proveedores que entregaron leche entre
                el {{ $semanaInicio->format('d/m') }} y el {{ $semanaFin->format('d/m/Y') }}.
            </div>
            <form method="POST" action="{{ route('admin.liquidaciones.generar') }}" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg">⚙️ Generar liquidaciones de esta semana</button>
            </form>
        </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Ruta</th>
                    <th title="Litros normales + adulterados">Litros</th>
                    <th title="Precio base S/1.80/L">Pago normal</th>
                    <th title="Adulterados a S/1.50/L">Penalidad</th>
                    <th>Descuentos</th>
                    <th style="font-size:.85rem;font-weight:800">Total a pagar</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($liquidaciones as $liq)
                <tr style="{{ $liq->estado === 'pagada' ? 'opacity:.7' : '' }}">

                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            {{-- Avatar con método de pago preferido --}}
                            @php $mpref = $liq->ganadero->metodo_pago_preferido ?? 'efectivo'; @endphp
                            <div style="width:32px;height:32px;border-radius:50%;
                                        background:{{ $mpref === 'yape' ? '#6d28d9' : ($mpref === 'plin' ? '#0284c7' : 'var(--brand)') }};
                                        display:flex;align-items:center;justify-content:center;
                                        color:#fff;font-size:.72rem;font-weight:700;flex-shrink:0"
                                 title="{{ $mpref === 'yape' ? 'Paga por Yape' : ($mpref === 'plin' ? 'Paga por Plin' : 'Paga en Efectivo') }}">
                                {{ $mpref === 'yape' ? 'Y' : ($mpref === 'plin' ? 'P' : 'E') }}
                            </div>
                            <div>
                                <strong style="font-size:.88rem">{{ $liq->ganadero->nombre ?? '—' }}</strong>
                                <div style="font-size:.72rem;color:var(--text-muted)">
                                    DNI: {{ $liq->ganadero->dni ?? '—' }}
                                    @if($liq->ganadero->telefono && $mpref !== 'efectivo')
                                        · 📱 {{ $liq->ganadero->telefono }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    <td><span class="tag">{{ $liq->ganadero->ruta_asignada ?? '—' }}</span></td>

                    <td>
                        <div>
                            <strong style="color:var(--green)">{{ number_format($liq->litros_aprobados, 1) }} L</strong>
                            <span style="font-size:.72rem;color:var(--text-muted)"> aprobados</span>
                        </div>
                        @if($liq->litros_adulterados > 0)
                        <div style="font-size:.75rem;color:var(--red)">
                            + {{ number_format($liq->litros_adulterados, 1) }} L adulterados
                        </div>
                        @endif
                    </td>

                    <td>
                        S/ {{ number_format($liq->subtotal_normal, 2) }}
                        <div style="font-size:.7rem;color:var(--text-muted)">× S/ {{ number_format($liq->precio_litro, 2) }}</div>
                    </td>

                    <td>
                        @if($liq->litros_adulterados > 0)
                            <span style="color:var(--red);font-weight:600">
                                - S/ {{ number_format($liq->subtotal_penalidad, 2) }}
                            </span>
                            <div style="font-size:.7rem;color:var(--red)">
                                ({{ $liq->num_adulteraciones }} adulteración{{ $liq->num_adulteraciones != 1 ? 'es' : '' }})
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    <td>
                        @if($liq->total_descuentos > 0)
                            <span style="color:var(--amber)">- S/ {{ number_format($liq->total_descuentos, 2) }}</span>
                            @if($liq->detalle_descuentos)
                            <div style="font-size:.7rem;color:var(--text-muted)">{{ Str::limit($liq->detalle_descuentos, 40) }}</div>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    <td>
                        <strong style="font-size:1.05rem;color:var(--brand-light)">
                            S/ {{ number_format($liq->total_neto, 2) }}
                        </strong>
                        @if($liq->estado === 'pagada')
                        <div style="font-size:.7rem;margin-top:2px">
                            @if($liq->metodo_pago === 'yape')   <span style="color:#6d28d9">📱 Yape</span>
                            @elseif($liq->metodo_pago === 'plin') <span style="color:#0284c7">📱 Plin</span>
                            @else <span style="color:var(--green)">💵 Efectivo</span>
                            @endif
                            @if($liq->referencia_pago)
                                <span class="mono" style="color:var(--text-muted)">· {{ $liq->referencia_pago }}</span>
                            @endif
                        </div>
                        @endif
                    </td>

                    <td>
                        <span class="badge @if($liq->estado==='borrador') badge-borrador @elseif($liq->estado==='confirmada') badge-observado @else badge-aprobado @endif">
                            @if($liq->estado === 'borrador')    📝 Borrador
                            @elseif($liq->estado === 'confirmada') ✅ Confirmada
                            @else 💵 Pagada
                            @endif
                        </span>
                    </td>

                    <td>
                        <div style="display:flex;gap:5px;flex-wrap:wrap">
                            <a href="{{ route('admin.liquidaciones.show', $liq) }}"
                               class="btn btn-ghost btn-sm">Ver</a>

                            <a href="{{ route('admin.liquidaciones.sobre-pdf', $liq) }}"
                               target="_blank"
                               class="btn btn-ghost btn-sm"
                               title="Imprimir sobre de pago">🖨️</a>

                            @if($liq->estado === 'borrador')
                            <form method="POST" action="{{ route('admin.liquidaciones.confirmar', $liq) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm"
                                        style="background:var(--green-bg);color:var(--green);border:1px solid #bbf7d0">
                                    ✓ Confirmar
                                </button>
                            </form>
                            @endif

                            @if($liq->estado === 'confirmada')
                            <button class="btn btn-sm"
                                    style="background:var(--amber-bg);color:var(--amber);border:1px solid #fde68a"
                                    onclick="abrirPago({{ $liq->id }},
                                        '{{ addslashes($liq->ganadero->nombre ?? '') }}',
                                        '{{ number_format($liq->total_neto, 2) }}',
                                        '{{ $liq->ganadero->metodo_pago_preferido ?? 'efectivo' }}',
                                        '{{ $liq->ganadero->telefono ?? '' }}')">
                                💵 Pagar
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>

            {{-- Fila de total --}}
            <tfoot>
                <tr style="background:var(--bg)">
                    <td colspan="6" style="text-align:right;font-weight:700;padding:14px 16px;color:var(--text-muted);font-size:.85rem">
                        TOTAL A PAGAR ESTA SEMANA →
                    </td>
                    <td style="padding:14px 16px">
                        <strong style="font-size:1.2rem;color:var(--brand-light)">
                            S/ {{ number_format($resumen['total_pagar'], 2) }}
                        </strong>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div style="padding:14px 22px">{{ $liquidaciones->links() }}</div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════
     MODAL DE PAGO
══════════════════════════════════════════════════════════ --}}
<div id="modalPago" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);
                            z-index:1000;display:none;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:var(--radius);width:100%;max-width:420px;
                box-shadow:var(--shadow-lg);overflow:hidden;margin:16px">

        <div style="background:var(--brand);padding:18px 22px;display:flex;justify-content:space-between;align-items:center">
            <div>
                <h3 style="color:#fff;font-size:1rem;font-weight:700">💵 Registrar pago</h3>
                <p id="modal-nombre" style="color:rgba(255,255,255,.75);font-size:.83rem;margin-top:2px"></p>
            </div>
            <button onclick="cerrarPago()"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;
                           width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:1.1rem">✕</button>
        </div>

        <div style="padding:22px">
            <div style="text-align:center;margin-bottom:20px">
                <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">
                    Monto a entregar
                </div>
                <div id="modal-monto" style="font-size:2.2rem;font-weight:800;color:var(--brand-light)"></div>
            </div>

            <form method="POST" id="formPago" action="">
                @csrf

                {{-- Método de pago --}}
                <div style="margin-bottom:16px">
                    <label style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                                  letter-spacing:.5px;color:var(--text-muted);display:block;margin-bottom:8px">
                        Método de pago *
                    </label>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
                        @foreach(['efectivo' => ['💵','Efectivo','var(--green)'], 'yape' => ['📱','Yape','#6d28d9'], 'plin' => ['📱','Plin','#0284c7']] as $val => [$ico, $label, $color])
                        <label style="cursor:pointer">
                            <input type="radio" name="metodo_pago" value="{{ $val }}"
                                   id="mp-{{ $val }}" onchange="toggleReferencia()"
                                   style="display:none">
                            <div class="metodo-btn" data-val="{{ $val }}"
                                 style="text-align:center;padding:10px 6px;border:2px solid var(--border);
                                        border-radius:var(--radius-sm);transition:all .15s;font-size:.82rem;
                                        cursor:pointer"
                                 onclick="selMetodo('{{ $val }}')">
                                <div style="font-size:1.4rem;margin-bottom:3px">{{ $ico }}</div>
                                <div style="font-weight:600">{{ $label }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Referencia (Yape/Plin) --}}
                <div id="grupoReferencia" style="margin-bottom:16px;display:none">
                    <label style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                                  letter-spacing:.5px;color:var(--text-muted);display:block;margin-bottom:6px">
                        Número de operación / referencia
                        <span style="color:var(--text-dim);font-weight:400;text-transform:none">— aparece en el comprobante Yape</span>
                    </label>
                    <input type="text" name="referencia_pago" id="inputRef"
                           maxlength="100" placeholder="Ej: 12345678"
                           style="width:100%;padding:9px 12px;border:1.5px solid var(--border);
                                  border-radius:var(--radius-sm);font-family:inherit">
                    <div id="tel-proveedor" style="font-size:.75rem;color:var(--text-muted);margin-top:5px"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg"
                        style="width:100%;justify-content:center;margin-top:4px"
                        id="btnConfPago" disabled>
                    💵 Confirmar pago
                </button>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var BASE = "{{ url('/admin/liquidaciones') }}";
    var seleccionado = null;

    window.abrirPago = function (id, nombre, monto, metodoPreferido, telefono) {
        document.getElementById('modal-nombre').textContent = nombre;
        document.getElementById('modal-monto').textContent  = 'S/ ' + monto;
        document.getElementById('formPago').action = BASE + '/' + id + '/pagar';
        document.getElementById('tel-proveedor').textContent =
            telefono ? 'Número Yape del proveedor: ' + telefono : 'Sin número registrado';
        // Pre-seleccionar el método preferido del proveedor
        selMetodo(metodoPreferido || 'efectivo');
        document.getElementById('modalPago').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.cerrarPago = function () {
        document.getElementById('modalPago').style.display = 'none';
        document.body.style.overflow = '';
    };

    window.selMetodo = function (val) {
        seleccionado = val;
        document.querySelectorAll('.metodo-btn').forEach(function (b) {
            b.style.borderColor = 'var(--border)';
            b.style.background  = '#fff';
        });
        var colors = { efectivo: 'var(--green)', yape: '#6d28d9', plin: '#0284c7' };
        var bgs    = { efectivo: 'var(--green-bg)', yape: '#f5f3ff', plin: '#e0f2fe' };
        var btn    = document.querySelector('[data-val="' + val + '"]');
        if (btn) {
            btn.style.borderColor = colors[val];
            btn.style.background  = bgs[val];
        }
        var radio = document.getElementById('mp-' + val);
        if (radio) radio.checked = true;
        toggleReferencia();
        document.getElementById('btnConfPago').disabled = false;
    };

    window.toggleReferencia = function () {
        var show = seleccionado === 'yape' || seleccionado === 'plin';
        document.getElementById('grupoReferencia').style.display = show ? 'block' : 'none';
    };

    // Cerrar con clic fuera del modal
    document.getElementById('modalPago').addEventListener('click', function (e) {
        if (e.target === this) window.cerrarPago();
    });
}());
</script>

@endsection
