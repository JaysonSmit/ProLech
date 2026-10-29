@extends('layouts.admin')

@section('title', 'Rotación de Zonas')
@section('page-title', '🔄 Rotación Mensual de Choferes')

@section('content')

{{-- ── Selector de mes ─────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:24px">

    <div style="display:flex;align-items:center;gap:12px">
        @php
            $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                      'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        @endphp
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);
                    padding:10px 18px;font-size:.95rem;font-weight:700">
            📅 {{ $meses[$mes] }} {{ $anio }}
        </div>

        @php
            $mesPrev  = $mes === 1  ? 12 : $mes - 1;
            $anioPrev = $mes === 1  ? $anio - 1 : $anio;
            $mesNext  = $mes === 12 ? 1  : $mes + 1;
            $anioNext = $mes === 12 ? $anio + 1 : $anio;
        @endphp

        <a href="{{ route('admin.rotacion.index', ['mes' => $mesPrev, 'anio' => $anioPrev]) }}"
           class="btn btn-ghost btn-sm">← Mes anterior</a>
        <a href="{{ route('admin.rotacion.index') }}" class="btn btn-ghost btn-sm">Mes actual</a>
        <a href="{{ route('admin.rotacion.index', ['mes' => $mesNext, 'anio' => $anioNext]) }}"
           class="btn btn-ghost btn-sm">Mes siguiente →</a>
    </div>

    <form method="POST" action="{{ route('admin.rotacion.generar') }}">
        @csrf
        <input type="hidden" name="mes"  value="{{ $mesNext }}">
        <input type="hidden" name="anio" value="{{ $anioNext }}">
        <button type="submit" class="btn btn-primary"
                onclick="return confirm('¿Generar rotación automática para {{ $meses[$mesNext] }} {{ $anioNext }}?')">
            ⚙️ Generar rotación {{ $meses[$mesNext] }}
        </button>
    </form>
</div>

{{-- ── Grid de zonas ───────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-bottom:24px">

    @foreach($zonas as $zona)
    @php
        $asig = $mapaZona->get($zona->id);
        $chofer = $asig?->chofer;
    @endphp
    <div class="card" style="border-top:3px solid var(--brand-light)">
        <div style="padding:16px">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--text-muted);margin-bottom:6px">
                Zona {{ $zona->orden }}
            </div>
            <div style="font-size:1.1rem;font-weight:800;margin-bottom:12px">
                {{ $zona->nombre }}
            </div>

            @if($chofer)
                <div style="display:flex;align-items:center;gap:10px;
                            background:var(--green-bg);border:1px solid #bbf7d0;
                            border-radius:var(--radius-sm);padding:10px 12px">
                    <div style="width:36px;height:36px;border-radius:50%;background:var(--brand-light);
                                display:flex;align-items:center;justify-content:center;
                                color:#fff;font-weight:700;font-size:.9rem;flex-shrink:0">
                        {{ strtoupper(substr($chofer->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:.88rem;color:var(--text)">
                            {{ $chofer->name }}
                        </div>
                        <div style="font-size:.72rem;color:var(--text-muted)">Titular {{ $meses[$mes] }}</div>
                    </div>
                </div>
            @else
                <div style="background:var(--bg);border:1.5px dashed var(--border);
                            border-radius:var(--radius-sm);padding:16px;text-align:center;
                            color:var(--text-muted);font-size:.82rem">
                    Sin chofer asignado
                </div>
            @endif
        </div>
    </div>
    @endforeach

    {{-- Volante --}}
    <div class="card" style="border-top:3px solid var(--amber)">
        <div style="padding:16px">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--text-muted);margin-bottom:6px">
                Rol especial
            </div>
            <div style="font-size:1.1rem;font-weight:800;margin-bottom:12px">
                🔄 Volante
            </div>

            @if($volante && $volante->chofer)
                <div style="display:flex;align-items:center;gap:10px;
                            background:var(--amber-bg);border:1px solid #fde68a;
                            border-radius:var(--radius-sm);padding:10px 12px">
                    <div style="width:36px;height:36px;border-radius:50%;background:var(--amber);
                                display:flex;align-items:center;justify-content:center;
                                color:#fff;font-weight:700;font-size:.9rem;flex-shrink:0">
                        {{ strtoupper(substr($volante->chofer->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:.88rem">{{ $volante->chofer->name }}</div>
                        <div style="font-size:.72rem;color:var(--text-muted)">
                            Cubre días libres y feriados
                        </div>
                    </div>
                </div>
            @else
                <div style="background:var(--bg);border:1.5px dashed var(--border);
                            border-radius:var(--radius-sm);padding:16px;text-align:center;
                            color:var(--text-muted);font-size:.82rem">
                    Sin volante asignado
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Asignación manual ───────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:22px">

    <div class="card">
        <div class="card-header"><h3>✏️ Asignación manual</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.rotacion.asignar') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label>Chofer <span class="req">*</span></label>
                        <select name="chofer_id" required>
                            <option value="">Seleccionar…</option>
                            @foreach($choferes as $ch)
                            <option value="{{ $ch->id }}">{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Rol del mes <span class="req">*</span></label>
                        <select name="rol_mes" required id="selRol" onchange="toggleZona()">
                            <option value="titular">Titular (zona fija)</option>
                            <option value="volante">Volante (cobertura)</option>
                        </select>
                    </div>
                    <div class="form-group" id="grupoZona">
                        <label>Zona <span class="req">*</span></label>
                        <select name="zona_id">
                            <option value="">Seleccionar zona…</option>
                            @foreach($zonas as $z)
                            <option value="{{ $z->id }}">{{ $z->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mes <span class="req">*</span></label>
                        <select name="mes" required>
                            @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ $m === $mes ? 'selected' : '' }}>
                                {{ $meses[$m] }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Año <span class="req">*</span></label>
                        <input type="number" name="anio" value="{{ $anio }}" min="2024" max="2030" required>
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <input type="text" name="notas" placeholder="Opcional…">
                    </div>
                </div>
                <div style="margin-top:16px">
                    <button type="submit" class="btn btn-primary">💾 Guardar asignación</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Historial de rotaciones --}}
    <div class="card">
        <div class="card-header"><h3>📅 Historial de rotaciones</h3></div>
        <div class="card-body" style="padding:0">
            @if($historial->isEmpty())
                <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:.85rem">
                    Sin historial de rotaciones registrado.
                </div>
            @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Período</th>
                            <th>Chofer</th>
                            <th>Zona / Rol</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historial as $periodo => $asigs)
                        @foreach($asigs as $a)
                        <tr>
                            @if($loop->first)
                            <td rowspan="{{ $asigs->count() }}" style="white-space:nowrap;font-weight:600;vertical-align:top;padding-top:12px">
                                @php [$y,$m2] = explode('-', $periodo) @endphp
                                {{ $meses[(int)$m2] }} {{ $y }}
                            </td>
                            @endif
                            <td>{{ $a->chofer?->name ?? '—' }}</td>
                            <td>
                                @if($a->rol_mes === 'volante')
                                    <span class="badge" style="background:var(--amber-bg);color:var(--amber)">🔄 Volante</span>
                                @else
                                    <span class="tag">{{ $a->zona?->nombre ?? '—' }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function toggleZona() {
    var esVolante = document.getElementById('selRol').value === 'volante';
    document.getElementById('grupoZona').style.opacity = esVolante ? '.4' : '1';
    document.querySelector('#grupoZona select').required = !esVolante;
}
</script>

@endsection
