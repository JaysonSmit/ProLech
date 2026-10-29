@extends('layouts.admin')

@section('title', $receta ? 'Editar Receta' : 'Nueva Receta')
@section('page-title', $receta ? '✏️ Editar Receta' : '➕ Nueva Receta de Producción')

@section('content')

{{-- ERROR GENERAL --}}
@if($errors->has('general'))
<div style="display:flex;align-items:center;gap:10px;padding:13px 18px;margin-bottom:18px;
    background:#fef2f2;border:1px solid #fca5a5;border-radius:9px;color:#dc2626;font-weight:600">
    ⛔ {{ $errors->first('general') }}
</div>
@endif

<div style="max-width:860px">
    <form method="POST"
          action="{{ $receta ? route('admin.recetas.update', $receta) : route('admin.recetas.store') }}"
          id="form-receta">
        @csrf
        @if($receta) @method('PUT') @endif

        {{-- ── DATOS GENERALES ─────────────────────────────────────────── --}}
        <div class="card" style="margin-bottom:20px">
            <div class="card-header">
                <h3>📝 Datos del producto</h3>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr auto;gap:18px;margin-bottom:16px">

                    {{-- Nombre --}}
                    <div class="form-group">
                        <label for="nombre">
                            Nombre del producto <span class="req">*</span>
                            <span class="hint">— ej: Queso Tipo Paria, Yogurt Natural 1L</span>
                        </label>
                        <input type="text" id="nombre" name="nombre" required
                               maxlength="120"
                               value="{{ old('nombre', $receta?->nombre) }}"
                               placeholder="Ej: Yogurt Frutado 1L"
                               class="{{ $errors->has('nombre') ? 'invalid' : '' }}">
                        @error('nombre')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                    {{-- Rendimiento base --}}
                    <div class="form-group" style="width:160px">
                        <label for="rendimiento_esperado_unidades">
                            Rinde <span class="req">*</span>
                            <span class="hint">— unidades base</span>
                        </label>
                        <input type="number" id="rendimiento_esperado_unidades"
                               name="rendimiento_esperado_unidades"
                               min="1" max="99999" step="1" required
                               value="{{ old('rendimiento_esperado_unidades', $receta?->rendimiento_esperado_unidades ?? 1) }}"
                               class="{{ $errors->has('rendimiento_esperado_unidades') ? 'invalid' : '' }}"
                               style="text-align:center;font-size:1.2rem;font-weight:700">
                        @error('rendimiento_esperado_unidades')
                            <span class="error-msg">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Descripción --}}
                <div class="form-group">
                    <label for="descripcion">
                        Descripción / Proceso
                        <span class="hint">— notas del proceso, tiempos, temperatura</span>
                    </label>
                    <textarea id="descripcion" name="descripcion" rows="2"
                              maxlength="1000"
                              placeholder="Ej: Fermentación 8h a 42°C. Rendimiento ~1 litro por litro de leche."
                              class="{{ $errors->has('descripcion') ? 'invalid' : '' }}">{{ old('descripcion', $receta?->descripcion) }}</textarea>
                    @error('descripcion')<span class="error-msg">{{ $message }}</span>@enderror
                </div>

                <div style="margin-top:10px;padding:10px 14px;background:#fffbeb;
                            border:1px solid #fde68a;border-radius:8px;font-size:.8rem;color:#92400e">
                    💡 El <strong>rendimiento base</strong> es la cantidad de unidades que produce
                    la receta tal como está definida. Si la receta usa 10L de leche y rinde
                    <strong>1</strong> molde, cuando produzcas 1300 moldes el sistema
                    multiplicará todos los insumos × 1300 automáticamente.
                </div>
            </div>
        </div>

        {{-- ── INGREDIENTES ─────────────────────────────────────────────── --}}
        <div class="card" style="margin-bottom:20px">
            <div class="card-header">
                <h3>🧪 Ingredientes / Insumos</h3>
                <button type="button" id="btn-add-fila"
                        class="btn btn-ghost btn-sm"
                        onclick="agregarFila()">
                    ➕ Agregar ingrediente
                </button>
            </div>
            <div class="card-body" style="padding:0">

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.88rem">
                        <thead>
                            <tr style="background:var(--bg)">
                                <th style="padding:10px 16px;text-align:left;font-size:.7rem;
                                           font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                                           color:var(--text-muted);border-bottom:1px solid var(--border)">
                                    Insumo / Materia prima
                                </th>
                                <th style="padding:10px 16px;text-align:center;font-size:.7rem;
                                           font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                                           color:var(--text-muted);border-bottom:1px solid var(--border);
                                           width:180px">
                                    Cantidad base
                                </th>
                                <th style="padding:10px 16px;text-align:center;font-size:.7rem;
                                           font-weight:700;text-transform:uppercase;
                                           color:var(--text-muted);border-bottom:1px solid var(--border);
                                           width:80px">
                                    Unidad
                                </th>
                                <th style="padding:10px 16px;text-align:center;font-size:.7rem;
                                           font-weight:700;text-transform:uppercase;
                                           color:var(--text-muted);border-bottom:1px solid var(--border);
                                           width:120px">
                                    Stock actual
                                </th>
                                <th style="padding:10px;width:48px;
                                           border-bottom:1px solid var(--border)"></th>
                            </tr>
                        </thead>
                        <tbody id="tbody-ingredientes">

                            {{-- Filas existentes (edición) --}}
                            @php
                                $filasExistentes = old('insumos',
                                    $receta?->detalles->map(fn($d) => [
                                        'insumo_id'          => $d->insumo_id,
                                        'cantidad_requerida' => $d->cantidad_requerida,
                                    ])->toArray() ?? []
                                );
                            @endphp

                            @foreach($filasExistentes as $i => $fila)
                            <tr class="fila-ingrediente" data-idx="{{ $i }}">
                                <td style="padding:10px 16px;border-bottom:1px solid var(--border-soft)">
                                    <select name="insumos[{{ $i }}][insumo_id]" required
                                            onchange="actualizarUnidadFila(this)"
                                            style="width:100%;padding:7px 10px;border:1.5px solid var(--border);
                                                   border-radius:7px;font-size:.85rem;background:#fff;font-family:inherit">
                                        <option value="">— Selecciona —</option>
                                        @foreach($insumos as $ins)
                                        <option value="{{ $ins->id }}"
                                                data-unidad="{{ $ins->unidad_medida }}"
                                                data-stock="{{ $ins->stock_actual }}"
                                                {{ (int)($fila['insumo_id'] ?? 0) === $ins->id ? 'selected' : '' }}>
                                            {{ $ins->nombre }} ({{ $ins->unidad_medida }})
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="padding:10px 16px;border-bottom:1px solid var(--border-soft)">
                                    <input type="number"
                                           name="insumos[{{ $i }}][cantidad_requerida]"
                                           min="0.0001" step="0.0001" required
                                           value="{{ $fila['cantidad_requerida'] ?? '' }}"
                                           placeholder="0.000"
                                           style="width:100%;padding:7px 10px;border:1.5px solid var(--border);
                                                  border-radius:7px;font-size:.9rem;text-align:right;
                                                  font-weight:700;font-family:inherit">
                                </td>
                                <td style="padding:10px 8px;border-bottom:1px solid var(--border-soft);
                                           text-align:center">
                                    <span class="lbl-unidad"
                                          style="font-size:.78rem;color:var(--text-muted);font-weight:600">
                                        @php
                                            $insumoSel = $insumos->firstWhere('id', (int)($fila['insumo_id'] ?? 0));
                                        @endphp
                                        {{ $insumoSel?->unidad_medida ?? '—' }}
                                    </span>
                                </td>
                                <td style="padding:10px 8px;border-bottom:1px solid var(--border-soft);
                                           text-align:center">
                                    <span class="lbl-stock"
                                          style="font-size:.75rem;color:var(--text-muted)">
                                        {{ $insumoSel ? number_format((float)$insumoSel->stock_actual, 1) : '—' }}
                                    </span>
                                </td>
                                <td style="padding:8px;border-bottom:1px solid var(--border-soft);
                                           text-align:center">
                                    <button type="button"
                                            onclick="this.closest('tr').remove()"
                                            style="background:#fef2f2;border:1px solid #fca5a5;
                                                   color:#dc2626;border-radius:6px;width:28px;height:28px;
                                                   cursor:pointer;font-size:.9rem;line-height:1">
                                        ×
                                    </button>
                                </td>
                            </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

                @if(count($filasExistentes) === 0)
                <div id="hint-vacio"
                     style="padding:32px;text-align:center;color:var(--text-muted);font-size:.85rem">
                    <div style="font-size:2rem;margin-bottom:8px">🧪</div>
                    Haz clic en <strong>➕ Agregar ingrediente</strong> para definir
                    los insumos de esta receta.
                </div>
                @endif

                @error('insumos')
                <div style="padding:12px 16px;color:#dc2626;font-size:.82rem;
                            font-weight:600;border-top:1px solid var(--border-soft)">
                    ⚠ {{ $message }}
                </div>
                @enderror

            </div>
        </div>

        {{-- ACCIONES --}}
        <div style="display:flex;gap:12px;align-items:center">
            <button type="submit" class="btn btn-primary btn-lg">
                💾 {{ $receta ? 'Guardar cambios' : 'Crear receta' }}
            </button>
            <a href="{{ route('admin.recetas.index') }}" class="btn btn-ghost btn-lg">
                ← Cancelar
            </a>
        </div>

    </form>
</div>

<script>
(function () {
    'use strict';

    /* Índice global para nombrar inputs de nuevas filas */
    var idx = {{ count($filasExistentes) }};

    /* Datos de insumos para el formulario dinámico */
    var insumos = @json($insumosJs);

    function construirOptions(seleccionado) {
        return '<option value="">— Selecciona un insumo —</option>' +
            insumos.map(function (ins) {
                var sel = (seleccionado && ins.id === seleccionado) ? ' selected' : '';
                return '<option value="' + ins.id + '" data-unidad="' + ins.unidad +
                    '" data-stock="' + ins.stock + '"' + sel + '>' +
                    ins.nombre + ' (' + ins.unidad + ')</option>';
            }).join('');
    }

    window.agregarFila = function () {
        var hint = document.getElementById('hint-vacio');
        if (hint) hint.style.display = 'none';

        var tr = document.createElement('tr');
        tr.className = 'fila-ingrediente';
        tr.dataset.idx = idx;
        tr.innerHTML =
            '<td style="padding:10px 16px;border-bottom:1px solid var(--border-soft)">' +
              '<select name="insumos[' + idx + '][insumo_id]" required ' +
                'onchange="actualizarUnidadFila(this)" ' +
                'style="width:100%;padding:7px 10px;border:1.5px solid var(--border);' +
                'border-radius:7px;font-size:.85rem;background:#fff;font-family:inherit">' +
                construirOptions(null) +
              '</select>' +
            '</td>' +
            '<td style="padding:10px 16px;border-bottom:1px solid var(--border-soft)">' +
              '<input type="number" name="insumos[' + idx + '][cantidad_requerida]" ' +
                'min="0.0001" step="0.0001" required placeholder="0.000" ' +
                'style="width:100%;padding:7px 10px;border:1.5px solid var(--border);' +
                'border-radius:7px;font-size:.9rem;text-align:right;font-weight:700;font-family:inherit">' +
            '</td>' +
            '<td style="padding:10px 8px;border-bottom:1px solid var(--border-soft);text-align:center">' +
              '<span class="lbl-unidad" style="font-size:.78rem;color:var(--text-muted);font-weight:600">—</span>' +
            '</td>' +
            '<td style="padding:10px 8px;border-bottom:1px solid var(--border-soft);text-align:center">' +
              '<span class="lbl-stock" style="font-size:.75rem;color:var(--text-muted)">—</span>' +
            '</td>' +
            '<td style="padding:8px;border-bottom:1px solid var(--border-soft);text-align:center">' +
              '<button type="button" onclick="this.closest(\'tr\').remove()" ' +
                'style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;' +
                'border-radius:6px;width:28px;height:28px;cursor:pointer;font-size:.9rem;line-height:1">×</button>' +
            '</td>';

        document.getElementById('tbody-ingredientes').appendChild(tr);
        idx++;

        /* Foco en el select nuevo */
        tr.querySelector('select').focus();
    };

    window.actualizarUnidadFila = function (sel) {
        var tr    = sel.closest('tr');
        var opt   = sel.options[sel.selectedIndex];
        var unidad = opt.dataset.unidad || '—';
        var stock  = opt.dataset.stock  || '—';

        var lblU = tr.querySelector('.lbl-unidad');
        var lblS = tr.querySelector('.lbl-stock');

        if (lblU) lblU.textContent = unidad;
        if (lblS) lblS.textContent = stock !== '—'
            ? parseFloat(stock).toLocaleString('es-PE', {
                minimumFractionDigits: 1, maximumFractionDigits: 2
              })
            : '—';
    };

}());
</script>

@endsection
