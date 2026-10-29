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

@if(session('success'))<div class="flash flash-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="flash flash-error">⚠️ {{ session('error') }}</div>@endif

{{-- ── Rutas sin chofer ────────────────────────────────────────────────────── --}}
@if($libres->isNotEmpty())
<div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px;padding:14px 18px;
            background:#fff7ed;border:1px solid #fed7aa;border-radius:var(--radius)">
    <div style="font-size:.9rem;color:#9a3412">
        ⚠️ <strong>{{ $libres->count() }} ruta(s) sin chofer en {{ $meses[$mes] }}:</strong> {{ $libres->pluck('nombre')->join(', ') }}.
        Sus ganaderos no aparecen en ninguna app. Hay más rutas que choferes: dales una <strong>segunda ruta</strong>.
    </div>
    <form method="POST" action="{{ route('admin.rotacion.cubrir') }}">
        @csrf <input type="hidden" name="mes" value="{{ $mes }}"><input type="hidden" name="anio" value="{{ $anio }}">
        <button class="btn btn-primary btn-sm" title="Primero al volante; luego a los titulares cuya ruta da menos litros">🤝 Cubrir automáticamente</button>
    </form>
</div>
@endif

{{-- ── Grid de zonas ───────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-bottom:24px">

    @foreach($zonas as $zona)
    @php
        $asig = $mapaZona->get($zona->id);
        $chofer = $asig?->chofer;
        $extra = $mapaExtra->get($zona->id);   // la cubre otro chofer como segunda ruta
    @endphp
    <div class="card" style="border-top:3px solid {{ $chofer ? 'var(--brand-light)' : ($extra ? '#8b5cf6' : '#f97316') }}">
        <div style="padding:16px">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--text-muted);margin-bottom:6px">
                Zona {{ $zona->orden }}
            </div>
            <div style="font-size:1.1rem;font-weight:800;margin-bottom:4px">
                {{ $zona->nombre }}
            </div>
            <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:10px">
                {{ $zona->ganaderos()->where('activo', true)->count() }} ganaderos · ~{{ number_format($litrosZona[$zona->id] ?? 0, 0) }} L/día
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
                        @if($asig->zonaExtra)
                            <div style="font-size:.72rem;color:#6d28d9;font-weight:600">+ 2.ª ruta: {{ $asig->zonaExtra->nombre }}</div>
                        @endif
                    </div>
                </div>
                @if(($c = $cargas[$asig->chofer_id] ?? null) && $c['excede'])
                    <div style="margin-top:8px;font-size:.72rem;color:#b91c1c">🚛 ~{{ number_format($c['litros'], 0) }} L/día con sus 2 rutas: supera los {{ number_format($c['capacidad'], 0) }} L del camión (descarga en planta entre rutas).</div>
                @endif
            @elseif($extra)
                <div style="display:flex;align-items:center;gap:10px;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:var(--radius-sm);padding:10px 12px">
                    <div style="width:36px;height:36px;border-radius:50%;background:#8b5cf6;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem;flex-shrink:0">
                        {{ strtoupper(substr($extra->chofer->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:.88rem">{{ $extra->chofer->name }}</div>
                        <div style="font-size:.72rem;color:#6d28d9">2.ª ruta · también hace {{ $extra->zona?->nombre ?? 'volante' }}</div>
                    </div>
                </div>
                @if(($c = $cargas[$extra->chofer_id] ?? null) && $c['excede'])
                    <div style="margin-top:8px;font-size:.72rem;color:#b91c1c">🚛 ~{{ number_format($c['litros'], 0) }} L/día entre las 2 rutas: supera los {{ number_format($c['capacidad'], 0) }} L del camión.</div>
                @endif
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
                        @if($volante->zonaExtra)
                            <div style="font-size:.72rem;color:#6d28d9;font-weight:600">+ recorre {{ $volante->zonaExtra->nombre }}</div>
                        @endif
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

    {{-- Segunda ruta --}}
    <div class="card" style="grid-column:1 / -1">
        <div class="card-header"><h3>🔀 Segunda ruta del mes</h3>
            <span class="card-header-meta">Cuando hay más rutas que choferes: el chofer recorre su ruta y luego la segunda; en la app ve todas las paradas en un solo recorrido.</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.rotacion.segunda') }}" style="display:grid;grid-template-columns:1fr 1fr auto;gap:14px;align-items:end">
                @csrf <input type="hidden" name="mes" value="{{ $mes }}"><input type="hidden" name="anio" value="{{ $anio }}">
                <div class="form-group" style="margin:0">
                    <label>Chofer <span class="req">*</span></label>
                    <select name="chofer_id" required>
                        <option value="">Seleccionar…</option>
                        @foreach($asignaciones->sortBy('chofer.name') as $a)
                            <option value="{{ $a->chofer_id }}">{{ $a->chofer?->name }} — {{ $a->nombreRutas() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Segunda ruta</label>
                    <select name="zona_extra_id">
                        <option value="">— Quitar segunda ruta —</option>
                        @foreach($zonas as $z)
                            @unless($mapaZona->has($z->id))
                                <option value="{{ $z->id }}">{{ $z->nombre }} {{ $mapaExtra->has($z->id) ? '(la cubre ' . $mapaExtra[$z->id]->chofer?->name . ')' : '(sin chofer)' }} · ~{{ number_format($litrosZona[$z->id] ?? 0, 0) }} L/día</option>
                            @endunless
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary">💾 Guardar</button>
            </form>
            @if($asignaciones->isEmpty())
                <p style="margin-top:10px;font-size:.8rem;color:var(--text-muted)">Primero genera o asigna los choferes de {{ $meses[$mes] }}.</p>
            @endif
        </div>
    </div>

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
                                @if($a->zonaExtra)<span class="tag" style="background:#f5f3ff;color:#6d28d9">+ {{ $a->zonaExtra->nombre }}</span>@endif
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
