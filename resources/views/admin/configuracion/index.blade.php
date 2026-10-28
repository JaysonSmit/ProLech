@extends('layouts.admin')

@section('title', 'Configuración del Sistema')
@section('page-title', '⚙️ Configuración — Precios y Parámetros')

@section('content')

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:18px">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('admin.configuracion.update') }}">
@csrf

<div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start">

    {{-- ── Precios de Leche ─────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h3>🥛 Precios de Leche (S/ por litro)</h3>
        </div>
        <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:16px">

            <div class="form-group">
                <label class="form-label">Temporada Activa</label>
                <select name="temporada_activa" class="form-control">
                    <option value="normal"   {{ $config->get('temporada_activa')?->valor === 'normal'   ? 'selected' : '' }}>
                        🌞 Normal (precio S/ {{ $config->get('precio_litro_normal')?->valor ?? '1.70' }})
                    </option>
                    <option value="lluvias"  {{ $config->get('temporada_activa')?->valor === 'lluvias'  ? 'selected' : '' }}>
                        🌧️ Lluvias Enero–Febrero (precio S/ {{ $config->get('precio_litro_lluvias')?->valor ?? '1.20' }})
                    </option>
                </select>
                <small style="color:var(--text-muted)">El precio activo se aplica a todas las liquidaciones nuevas.</small>
            </div>

            <div class="form-group">
                <label class="form-label">Precio Normal (S/ / litro)</label>
                <input type="number" step="0.01" min="0.01" max="99.99"
                       name="precio_litro_normal"
                       value="{{ $config->get('precio_litro_normal')?->valor ?? '1.70' }}"
                       class="form-control" required>
                <small style="color:var(--text-muted)">Precio actual: S/ 1.70 (pagan 10–20 ctvs más que otras plantas)</small>
            </div>

            <div class="form-group">
                <label class="form-label">Precio Temporada Lluvias (S/ / litro)</label>
                <input type="number" step="0.01" min="0.01" max="99.99"
                       name="precio_litro_lluvias"
                       value="{{ $config->get('precio_litro_lluvias')?->valor ?? '1.20' }}"
                       class="form-control" required>
                <small style="color:var(--text-muted)">Rango habitual: S/ 1.20–1.30 (otras plantas pagan S/ 0.80–0.90)</small>
            </div>

            <div class="form-group">
                <label class="form-label" style="color:var(--rojo)">⚠️ Precio Penalidad Adulteración >5% (S/ / litro)</label>
                <input type="number" step="0.01" min="0.01" max="99.99"
                       name="precio_litro_penalidad"
                       value="{{ $config->get('precio_litro_penalidad')?->valor ?? '0.65' }}"
                       class="form-control" required>
                <small style="color:var(--text-muted)">Rango S/ 0.60–0.70 — se aplica a TODA la semana del proveedor infractor.</small>
            </div>

            <div class="form-group">
                <label class="form-label">% Agua límite para penalidad</label>
                <input type="number" step="0.1" min="1" max="50"
                       name="agua_limite_retiro"
                       value="{{ $config->get('agua_limite_retiro')?->valor ?? '5.0' }}"
                       class="form-control" required>
                <small style="color:var(--text-muted)">Si supera este %, toda la semana se paga a precio penalidad.</small>
            </div>

            <div class="form-group">
                <label class="form-label">Descuento adulteración leve (S/ por litro)</label>
                <input type="number" step="0.01" min="0.01" max="5"
                       name="descuento_litro_leve"
                       value="{{ $config->get('descuento_litro_leve')?->valor ?? '0.10' }}"
                       class="form-control" required>
                <small style="color:var(--text-muted)">&lt;5% 1ra vez: resta este monto al precio de toda la semana.</small>
            </div>
        </div>
    </div>

    {{-- ── Precios de Queso ─────────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:22px">
        <div class="card">
            <div class="card-header">
                <h3>🧀 Precios de Queso (S/ por molde)</h3>
            </div>
            <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:16px">

                <div class="form-group">
                    <label class="form-label">Mayoristas (Arequipa / Lima)</label>
                    <input type="number" step="0.01" min="0.01" max="999.99"
                           name="precio_queso_mayorista"
                           value="{{ $config->get('precio_queso_mayorista')?->valor ?? '20.00' }}"
                           class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Proveedores de leche</label>
                    <input type="number" step="0.01" min="0.01" max="999.99"
                           name="precio_queso_proveedor"
                           value="{{ $config->get('precio_queso_proveedor')?->valor ?? '18.00' }}"
                           class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Público general (planta)</label>
                    <input type="number" step="0.01" min="0.01" max="999.99"
                           name="precio_queso_publico"
                           value="{{ $config->get('precio_queso_publico')?->valor ?? '21.00' }}"
                           class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Margen de ganancia objetivo (%) — tienda</label>
                    <input type="number" step="1" min="0" max="300"
                           name="margen_ganancia_pct"
                           value="{{ $config->get('margen_ganancia_pct')?->valor ?? '30' }}"
                           class="form-control">
                    <small style="color:var(--text-muted)">Se usa para sugerir el precio de cada producto: costo de la receta × (1 + margen).</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Controles de calidad sorpresa por día</label>
                    <input type="number" step="1" min="0" max="50"
                           name="controles_por_dia"
                           value="{{ $config->get('controles_por_dia')?->valor ?? '3' }}"
                           class="form-control">
                    <small style="color:var(--text-muted)">El sistema los sortea cada madrugada (1 de cada 3 en el predio, el resto en frasco que trae el chofer), más el tanque de cada camión.</small>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>🏭 Parámetros de Producción</h3>
            </div>
            <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:16px">

                <div class="form-group">
                    <label class="form-label">Rendimiento esperado (quesos por 100L)</label>
                    <input type="number" step="0.1" min="1" max="50"
                           name="rendimiento_quesos_100L"
                           value="{{ $config->get('rendimiento_quesos_100L')?->valor ?? '11.5' }}"
                           class="form-control" required>
                    <small style="color:var(--text-muted)">Rango real: 11–12 quesos por cada 100 litros.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Stock mínimo reservado para venta en planta (moldes)</label>
                    <input type="number" step="1" min="0" max="1000"
                           name="stock_minimo_venta_directa"
                           value="{{ $config->get('stock_minimo_venta_directa')?->valor ?? '17' }}"
                           class="form-control" required>
                    <small style="color:var(--text-muted)">Actualmente: 15–20 moldes/día para venta directa en planta.</small>
                </div>
            </div>
        </div>
    </div>

</div>

<div style="margin-top:24px;text-align:right">
    <button type="submit" class="btn btn-primary" style="padding:10px 32px;font-size:1rem">
        💾 Guardar configuración
    </button>
</div>

</form>

@endsection
