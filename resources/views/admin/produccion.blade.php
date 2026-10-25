@extends('layouts.admin')

@section('title', 'Producción')
@section('page-title', '🏭 Producción de Queso')

@section('content')

{{-- ══ ACCESO RÁPIDO: Módulo ERP de Lotes por Receta ══════════════════════ --}}
<div style="display:flex;align-items:center;justify-content:space-between;
            padding:16px 20px;margin-bottom:22px;
            background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);
            border:1px solid #86efac;border-radius:12px;gap:16px;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:14px">
        <div style="font-size:2rem">🧀</div>
        <div>
            <div style="font-weight:700;color:#15803d;font-size:.95rem">
                Módulo ERP — Producción por Receta
            </div>
            <div style="font-size:.8rem;color:#166534;margin-top:2px">
                Control de inventario, costeo dinámico y trazabilidad por lotes (PRD-YYYYMMDD-NNN)
            </div>
        </div>
    </div>
    <a href="{{ route('admin.produccion.lotes.create') }}"
       style="display:inline-flex;align-items:center;gap:8px;
              padding:10px 22px;background:#16a34a;color:#fff;
              border-radius:8px;font-weight:700;font-size:.88rem;
              text-decoration:none;white-space:nowrap;
              box-shadow:0 2px 4px rgba(22,163,74,.3);
              transition:background .15s"
       onmouseover="this.style.background='#166534'"
       onmouseout="this.style.background='#16a34a'">
        ➕ Nuevo Lote por Receta
    </a>
</div>

{{-- RF-PROD-02/04 — Campo vs planta (hoy) --}}
@php
    $cvp = $campoVsPlanta;
@endphp
<div class="card" style="margin-bottom:22px;border-left:4px solid {{ $cvp['alertaRendimiento'] ? 'var(--red)' : 'var(--green)' }}">
    <div class="card-header">
        <h3>⚖️ Campo vs planta — {{ now()->isoFormat('dddd D [de] MMMM') }}</h3>
        @if($cvp['alertaRendimiento'])
            <span class="tag" style="background:var(--red-bg);color:var(--red)">⚠ Rendimiento bajo</span>
        @else
            <span class="tag" style="background:var(--green-bg);color:var(--green)">En parámetros</span>
        @endif
    </div>
    <div class="card-body">
        <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:0">
            <div class="stat-card azul">
                <div class="label">Litros acopiados (campo)</div>
                <div class="value">{{ number_format($cvp['litrosCampoHoy'], 0) }}</div>
                <div class="unit">registrados hoy en rutas</div>
            </div>
            <div class="stat-card verde">
                <div class="label">Litros en planta</div>
                <div class="value">{{ number_format($cvp['litrosPlantaHoy'], 0) }}</div>
                <div class="unit">lotes registrados hoy</div>
            </div>
            <div class="stat-card naranja">
                <div class="label">Moldes / quesos hoy</div>
                <div class="value">{{ number_format($cvp['moldesHoy'], 0) }}</div>
                <div class="unit">esperados ~{{ number_format($cvp['moldesEsperados'], 0) }} ({{ $cvp['quesosPor100L'] }}/100L)</div>
            </div>
            <div class="stat-card {{ $cvp['alertaRendimiento'] ? 'rojo' : 'verde' }}">
                <div class="label">Rendimiento real</div>
                <div class="value">{{ $cvp['rendimientoReal'] !== null ? number_format($cvp['rendimientoReal'], 1) : '—' }}</div>
                <div class="unit">quesos por 100 L procesados</div>
            </div>
        </div>
        @if($cvp['litrosCampoHoy'] > 0 && abs($cvp['litrosCampoHoy'] - $cvp['litrosPlantaHoy']) > ($cvp['litrosCampoHoy'] * 0.05))
        <div style="margin-top:14px;padding:10px 14px;background:var(--amber-bg);border-radius:8px;font-size:.82rem;color:#92400e">
            ⚠ Diferencia campo/planta &gt; 5%: revisar recepción física vs registros de acopio.
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     MÉTRICAS DEL MES
══════════════════════════════════════════════════════════════════════ --}}
<div class="stats-grid">

    <div class="stat-card azul">
        <div class="label">Litros usados (mes)</div>
        <div class="value">{{ number_format($totalMes['litros'], 0) }}</div>
        <div class="unit">litros de leche procesados</div>
    </div>

    <div class="stat-card verde">
        <div class="label">Kg queso producido (mes)</div>
        <div class="value">{{ number_format($totalMes['kg_queso'], 1) }}</div>
        <div class="unit">kilogramos obtenidos</div>
    </div>

    <div class="stat-card naranja">
        <div class="label">Costo total (mes)</div>
        <div class="value">S/ {{ number_format($totalMes['costo'], 0) }}</div>
        <div class="unit">insumos + materia prima</div>
    </div>

    <div class="stat-card {{ ($totalMes['kg_queso'] > 0 && ($totalMes['litros']/$totalMes['kg_queso']) <= 10.5) ? 'verde' : 'azul' }}">
        <div class="label">Rendimiento promedio</div>
        @if($totalMes['kg_queso'] > 0)
            <div class="value">{{ number_format($totalMes['litros'] / $totalMes['kg_queso'], 1) }}</div>
            <div class="unit">litros por kg de queso
                @if($totalMes['litros'] / $totalMes['kg_queso'] <= 10.5)
                    · <span style="color:var(--green);font-weight:600">✓ Óptimo</span>
                @elseif($totalMes['litros'] / $totalMes['kg_queso'] <= 11.5)
                    · <span style="color:var(--amber)">Normal</span>
                @else
                    · <span style="color:var(--red)">⚠ Alto</span>
                @endif
            </div>
        @else
            <div class="value" style="font-size:1.2rem;color:var(--text-muted)">—</div>
            <div class="unit">sin lotes este mes</div>
        @endif
    </div>

    <div class="stat-card {{ $alertaStockQuesos ? 'rojo' : 'verde' }}">
        <div class="label">Stock en planta (reserva)</div>
        <div class="value">{{ number_format($stockActualQuesos, 1) }}</div>
        <div class="unit">
            moldes disponibles
            @if($alertaStockQuesos)
                <span style="color:var(--red);font-weight:700">· ⚠️ Bajo mínimo ({{ $stockMinimoQuesos }})</span>
            @else
                <span style="color:var(--green)">· OK (mín: {{ $stockMinimoQuesos }})</span>
            @endif
        </div>
    </div>

</div>

{{-- Explicación de métricas --}}
<div style="display:flex;gap:10px;margin-bottom:26px;flex-wrap:wrap">
    <div class="tag">🧀 10L leche → 1 kg queso (referencia operativa)</div>
    <div class="tag">💡 Rendimiento ideal: 9.5–10.5 L/kg</div>
    <div class="tag">📅 {{ now()->isoFormat('MMMM YYYY') }}</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     LAYOUT PRINCIPAL — FORMULARIO + BARRA DE CONTEXTO
══════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:1fr 320px;gap:22px;align-items:start">

    {{-- ── FORMULARIO NUEVO LOTE ──────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h3>➕ Registrar nuevo lote de producción</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.produccion.store') }}" id="formProd">
                @csrf

                {{-- Fila 1: Leche y precio --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

                    <div class="form-group">
                        <label for="litros_usados">
                            Litros de leche usados <span class="req">*</span>
                            <span class="hint">— cuántos litros entran al proceso</span>
                        </label>
                        <input type="number" id="litros_usados" name="litros_usados"
                               step="0.01" min="1" max="15000" required
                               value="{{ old('litros_usados') }}"
                               placeholder="Ej: 1300"
                               class="{{ $errors->has('litros_usados') ? 'invalid' : '' }}">
                        @error('litros_usados')
                            <span class="error-msg">{{ $message }}</span>
                        @enderror
                        <span style="font-size:.72rem;color:var(--text-muted)">Capacidad diaria: ~13,000 L / 300 proveedores</span>
                    </div>

                    <div class="form-group">
                        <label for="precio_litro">
                            Precio por litro (S/) <span class="req">*</span>
                            <span class="hint">— costo de compra al proveedor</span>
                        </label>
                        <input type="number" id="precio_litro" name="precio_litro"
                               step="0.001" min="0" required
                               value="{{ old('precio_litro', '1.80') }}"
                               placeholder="1.80"
                               class="{{ $errors->has('precio_litro') ? 'invalid' : '' }}">
                        @error('precio_litro')
                            <span class="error-msg">{{ $message }}</span>
                        @enderror
                        <span style="font-size:.72rem;color:var(--text-muted)">Precio base operación: S/ 1.80 / litro</span>
                    </div>

                </div>

                {{-- Separador con título --}}
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                    <div style="flex:1;height:1px;background:var(--border-soft)"></div>
                    <span style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">
                        Costos de insumos
                    </span>
                    <div style="flex:1;height:1px;background:var(--border-soft)"></div>
                </div>

                {{-- Fila 2: Costos --}}
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px">

                    <div class="form-group">
                        <label for="costo_cuajo">
                            Cuajo (S/) <span class="req">*</span>
                            <span class="hint">— enzima coagulante</span>
                        </label>
                        <input type="number" id="costo_cuajo" name="costo_cuajo"
                               step="0.01" min="0" required
                               value="{{ old('costo_cuajo', '0.00') }}"
                               placeholder="0.00"
                               class="{{ $errors->has('costo_cuajo') ? 'invalid' : '' }}">
                        @error('costo_cuajo')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label for="costo_sal">
                            Sal (S/) <span class="req">*</span>
                            <span class="hint">— para salmuera del queso</span>
                        </label>
                        <input type="number" id="costo_sal" name="costo_sal"
                               step="0.01" min="0" required
                               value="{{ old('costo_sal', '0.00') }}"
                               placeholder="0.00"
                               class="{{ $errors->has('costo_sal') ? 'invalid' : '' }}">
                        @error('costo_sal')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label for="costo_otros">
                            Otros (S/)
                            <span class="hint">— empaques, energía, etc.</span>
                        </label>
                        <input type="number" id="costo_otros" name="costo_otros"
                               step="0.01" min="0"
                               value="{{ old('costo_otros', '0.00') }}"
                               placeholder="0.00"
                               class="{{ $errors->has('costo_otros') ? 'invalid' : '' }}">
                        @error('costo_otros')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                </div>

                {{-- Separador --}}
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                    <div style="flex:1;height:1px;background:var(--border-soft)"></div>
                    <span style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">
                        Producción obtenida
                    </span>
                    <div style="flex:1;height:1px;background:var(--border-soft)"></div>
                </div>

                {{-- Fila 3: Resultado y fecha --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

                    <div class="form-group">
                        <label for="kg_queso_estimado">
                            Kg de queso obtenidos <span class="req">*</span>
                            <span class="hint">— moldes × ~1 kg promedio</span>
                        </label>
                        <input type="number" id="kg_queso_estimado" name="kg_queso_estimado"
                               step="0.001" min="0.001" required
                               value="{{ old('kg_queso_estimado') }}"
                               placeholder="Ej: 130.000"
                               class="{{ $errors->has('kg_queso_estimado') ? 'invalid' : '' }}">
                        @error('kg_queso_estimado')
                            <span class="error-msg">{{ $message }}</span>
                        @enderror
                        <span style="font-size:.72rem;color:var(--text-muted)">Producción típica: 1,300–1,400 moldes/día</span>
                    </div>

                    <div class="form-group">
                        <label for="fecha_lote">
                            Fecha del lote <span class="req">*</span>
                        </label>
                        <input type="date" id="fecha_lote" name="fecha_lote"
                               required
                               value="{{ old('fecha_lote', now()->toDateString()) }}"
                               max="{{ now()->toDateString() }}"
                               class="{{ $errors->has('fecha_lote') ? 'invalid' : '' }}">
                        @error('fecha_lote')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                </div>

                {{-- Notas --}}
                <div class="form-group" style="margin-bottom:20px">
                    <label for="notas">Notas del proceso <span class="hint">— incidencias, condiciones, observaciones</span></label>
                    <textarea id="notas" name="notas" rows="2" maxlength="1000"
                              placeholder="Ej: Temperatura ambiente 14°C. Tiempo de cuajado 45 min. Sin incidencias."
                              class="{{ $errors->has('notas') ? 'invalid' : '' }}">{{ old('notas') }}</textarea>
                    @error('notas')<span class="error-msg">{{ $message }}</span>@enderror
                </div>

                <div style="display:flex;gap:10px;align-items:center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        💾 Guardar lote
                    </button>
                    <button type="reset" class="btn btn-ghost" onclick="setTimeout(recalcular,10)">
                        Limpiar
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- ── PANEL CALCULADORA EN TIEMPO REAL ──────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px;position:sticky;top:80px">

        {{-- Calculadora --}}
        <div class="card">
            <div class="card-header">
                <h3>🧮 Calculadora en tiempo real</h3>
            </div>
            <div class="card-body" style="padding:16px">

                <div style="text-align:center;padding:16px 0;border-bottom:1px solid var(--border-soft);margin-bottom:14px">
                    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:6px">
                        Costo total estimado
                    </div>
                    <div id="calc-total" style="font-size:2.2rem;font-weight:800;color:var(--brand-light);letter-spacing:-1px">
                        S/ 0.00
                    </div>
                    <div id="calc-rendimiento" style="font-size:.8rem;color:var(--text-muted);margin-top:6px">
                        Rendimiento: — L/kg
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;font-size:.82rem">
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-soft)">
                        <span style="color:var(--text-muted)">Costo materia prima</span>
                        <strong id="calc-materia">S/ 0.00</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-soft)">
                        <span style="color:var(--text-muted)">Cuajo + Sal + Otros</span>
                        <strong id="calc-insumos">S/ 0.00</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-soft)">
                        <span style="color:var(--text-muted)">Costo por kg queso</span>
                        <strong id="calc-por-kg" style="color:var(--amber)">— S/kg</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:6px 0">
                        <span style="color:var(--text-muted)">Moldes estimados</span>
                        <strong id="calc-moldes" style="color:var(--green)">—</strong>
                    </div>
                </div>

                <div id="calc-alerta" style="display:none;margin-top:12px;padding:10px;background:var(--amber-bg);border:1px solid #fde68a;border-radius:8px;font-size:.78rem;color:#92400e">
                    ⚠️ <strong>Rendimiento fuera del rango óptimo.</strong> Verificar proceso de cuajado.
                </div>

                <div id="calc-ok" style="display:none;margin-top:12px;padding:10px;background:var(--green-bg);border:1px solid #bbf7d0;border-radius:8px;font-size:.78rem;color:#15803d">
                    ✅ <strong>Rendimiento óptimo.</strong> Proceso dentro de parámetros normales.
                </div>
            </div>
        </div>

        {{-- Info rápida de referencia --}}
        <div class="card">
            <div class="card-header">
                <h3>📌 Referencia operativa</h3>
            </div>
            <div class="card-body" style="padding:14px 16px">
                <div style="display:flex;flex-direction:column;gap:10px;font-size:.82rem">
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Leche diaria estimada</span>
                        <strong>~13,000 L</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Proveedores activos</span>
                        <strong>~300</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Producción diaria</span>
                        <strong>1,300–1,400 moldes</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Precio mayorista</span>
                        <strong style="color:var(--green)">S/ 18.00 / kg</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Precio proveedor</span>
                        <strong style="color:var(--blue)">S/ 17.00 / kg</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Insumos por molde</span>
                        <strong>10L leche + cuajo + sal</strong>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>{{-- /layout 2 col --}}

{{-- ══════════════════════════════════════════════════════════════════════
     HISTORIAL DE LOTES
══════════════════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-header">
        <h3>📋 Historial de lotes registrados</h3>
        <span class="card-header-meta">{{ $lotes->total() }} lote(s) en total</span>
    </div>

    @if($lotes->isEmpty())
        <div style="padding:48px;text-align:center;color:var(--text-muted)">
            <div style="font-size:2.5rem;margin-bottom:10px">🏭</div>
            <div style="font-weight:600;margin-bottom:4px">Sin lotes registrados</div>
            <div style="font-size:.85rem">Registra el primer lote de producción usando el formulario de arriba.</div>
        </div>
    @else

    {{-- Mini gráfica de barras del mes (últimos lotes) --}}
    @php
        $lotesChart = $lotes->take(10)->reverse();
        $maxKg = $lotesChart->max('kg_queso_estimado') ?: 1;
    @endphp
    <div style="padding:20px 22px 0">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:10px">
            Últimos {{ $lotesChart->count() }} lotes — Kg queso producido
        </div>
        <div style="display:flex;align-items:flex-end;gap:6px;height:60px">
            @foreach($lotesChart as $l)
            @php $h = max(4, round(($l->kg_queso_estimado / $maxKg) * 56)); @endphp
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px"
                 title="{{ $l->fecha_lote->format('d/m') }} · {{ number_format($l->kg_queso_estimado,1) }} kg">
                <div style="font-size:.6rem;color:var(--text-dim);line-height:1">
                    {{ number_format($l->kg_queso_estimado, 0) }}
                </div>
                <div style="width:100%;height:{{ $h }}px;background:var(--brand-light);border-radius:4px 4px 0 0;
                            opacity:.85;transition:opacity .15s;cursor:default"
                     onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.85">
                </div>
                <div style="font-size:.62rem;color:var(--text-dim)">
                    {{ $l->fecha_lote->format('d/m') }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    <div style="height:1px;background:var(--border-soft);margin:16px 0 0"></div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Litros usados</th>
                    <th>Kg queso</th>
                    <th>Rendimiento</th>
                    <th>Cuajo</th>
                    <th>Sal</th>
                    <th>Otros</th>
                    <th>Costo total</th>
                    <th>Registrado por</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lotes as $lote)
                @php
                    $rend = $lote->kg_queso_estimado > 0
                        ? $lote->litros_usados / $lote->kg_queso_estimado
                        : 0;
                    $rendColor = $rend <= 10.5 ? 'var(--green)' : ($rend <= 11.5 ? 'var(--amber)' : 'var(--red)');
                @endphp
                <tr>
                    <td class="text-muted" style="font-size:.78rem">{{ $lote->id }}</td>
                    <td style="white-space:nowrap;font-weight:600">
                        {{ $lote->fecha_lote->format('d/m/Y') }}
                        <div style="font-size:.72rem;color:var(--text-muted);font-weight:400">
                            {{ $lote->fecha_lote->isoFormat('dddd') }}
                        </div>
                    </td>
                    <td>
                        <strong>{{ number_format($lote->litros_usados, 1) }}</strong>
                        <span class="text-muted"> L</span>
                    </td>
                    <td>
                        <strong style="color:var(--brand-light)">{{ number_format($lote->kg_queso_estimado, 2) }}</strong>
                        <span class="text-muted"> kg</span>
                    </td>
                    <td>
                        @if($rend > 0)
                            <span style="color:{{ $rendColor }};font-weight:700">
                                {{ number_format($rend, 2) }} L/kg
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-muted">S/ {{ number_format($lote->costo_cuajo, 2) }}</td>
                    <td class="text-muted">S/ {{ number_format($lote->costo_sal, 2) }}</td>
                    <td class="text-muted">S/ {{ number_format($lote->costo_otros, 2) }}</td>
                    <td>
                        <strong style="color:var(--amber)">S/ {{ number_format($lote->costo_total, 2) }}</strong>
                    </td>
                    <td style="font-size:.8rem;color:var(--text-muted)">
                        {{ $lote->registrador->name ?? '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="padding:14px 22px">
        {{ $lotes->links() }}
    </div>

    @endif
</div>

<script>
(function () {
    'use strict';

    var ids = ['litros_usados','precio_litro','costo_cuajo','costo_sal','costo_otros','kg_queso_estimado'];

    function n(id) { return parseFloat(document.getElementById(id).value) || 0; }

    function fmt(v) { return 'S/ ' + v.toFixed(2); }

    function recalcular() {
        var litros  = n('litros_usados');
        var precio  = n('precio_litro');
        var cuajo   = n('costo_cuajo');
        var sal     = n('costo_sal');
        var otros   = n('costo_otros');
        var kg      = n('kg_queso_estimado');

        var materia  = litros * precio;
        var insumos  = cuajo + sal + otros;
        var total    = materia + insumos;
        var rend     = kg > 0 ? litros / kg : 0;
        var porKg    = kg > 0 ? total / kg  : 0;
        var moldes   = kg > 0 ? Math.round(kg) : 0;

        document.getElementById('calc-total').textContent     = fmt(total);
        document.getElementById('calc-materia').textContent   = fmt(materia);
        document.getElementById('calc-insumos').textContent   = fmt(insumos);
        document.getElementById('calc-por-kg').textContent    = kg > 0 ? porKg.toFixed(2) + ' S/kg' : '—';
        document.getElementById('calc-moldes').textContent    = moldes > 0 ? '~' + moldes + ' moldes' : '—';

        var quesosPor100L = {{ json_encode($campoVsPlanta['quesosPor100L']) }};
        var moldesEsperadosForm = litros > 0 ? (litros * quesosPor100L / 100) : 0;

        if (rend > 0) {
            var quesosPor100Form = kg > 0 ? (kg / (litros / 100)) : 0;
            document.getElementById('calc-rendimiento').textContent =
                'Rendimiento: ' + rend.toFixed(2) + ' L/kg · ~' + quesosPor100Form.toFixed(1) + ' quesos/100L'
                + (moldesEsperadosForm > 0 ? ' (esp. ' + moldesEsperadosForm.toFixed(0) + ' moldes)' : '');
            var ok    = document.getElementById('calc-ok');
            var alerta = document.getElementById('calc-alerta');
            var rendimientoOk = quesosPor100Form >= quesosPor100L * 0.85;
            if (rend >= 9.5 && rend <= 10.5 && rendimientoOk) {
                ok.style.display = 'block'; alerta.style.display = 'none';
                document.getElementById('calc-total').style.color = 'var(--brand-light)';
            } else {
                ok.style.display = 'none'; alerta.style.display = 'block';
                document.getElementById('calc-total').style.color = rend > 11.5 ? 'var(--red)' : 'var(--amber)';
            }
        } else {
            document.getElementById('calc-rendimiento').textContent = 'Ingresa los kg para ver rendimiento';
            document.getElementById('calc-ok').style.display = 'none';
            document.getElementById('calc-alerta').style.display = 'none';
            document.getElementById('calc-total').style.color = 'var(--brand-light)';
        }
    }

    window.recalcular = recalcular;

    ids.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', recalcular);
    });

    recalcular();
}());
</script>

@endsection
