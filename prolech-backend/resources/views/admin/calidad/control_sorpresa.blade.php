@extends('layouts.admin')

@section('title', 'Control sorpresa')
@section('page-title', '🧪 Control de calidad sorpresa')

@section('content')
@php
    $tipos = \App\Models\ControlCalidad::TIPOS;
    $resultados = \App\Models\ControlCalidad::RESULTADOS;
    $colorNivel = ['rojo' => ['#fee2e2', '#b91c1c'], 'amarillo' => ['#fef3c7', '#b45309'], 'verde' => ['#dcfce7', '#15803d']];
    $colorRes = ['normal' => ['#dcfce7', '#15803d'], 'leve' => ['#fef3c7', '#b45309'], 'grave' => ['#fee2e2', '#b91c1c']];
@endphp
<style>
    .cs-kpis { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
    .cs-kpi { background:#fff; border:1px solid var(--border); border-radius:18px; padding:14px 16px; position:relative; overflow:hidden; }
    .cs-kpi::before { content:""; position:absolute; inset:0 0 auto 0; height:4px; background:var(--c); }
    .cs-kpi .l { font-size:.7rem; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--text-muted); }
    .cs-kpi .v { font-size:1.6rem; font-weight:800; margin-top:4px; }
    .cs-kpi .s { font-size:.75rem; color:var(--text-muted); }
    .cs-acciones { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:16px; }
    .cs-btn { display:inline-flex; align-items:center; gap:6px; padding:10px 16px; border-radius:12px; font-weight:700; font-size:.85rem; border:1px solid var(--border); background:#fff; cursor:pointer; color:var(--text); text-decoration:none; }
    .cs-btn.prim { background:var(--grad); color:#fff; border:0; }
    .cs-grid { display:grid; grid-template-columns:2fr 1fr; gap:16px; margin-bottom:16px; }
    .cs-panel { background:#fff; border:1px solid var(--border); border-radius:18px; padding:16px 18px; }
    .cs-panel h3 { font-size:.98rem; margin:0 0 12px; display:flex; justify-content:space-between; align-items:center; }
    .cs-panel h3 small { font-weight:500; color:var(--text-muted); font-size:.75rem; }
    .cs-ctrl { display:flex; gap:12px; align-items:center; padding:12px; border:1px solid var(--border); border-radius:14px; margin-bottom:10px; }
    .cs-ctrl .ico { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
    .cs-pill { display:inline-block; font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:10px; }
    .cs-fila { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--border-soft); font-size:.84rem; }
    .cs-fila:last-child { border-bottom:0; }
    .cs-modal-fondo { position:fixed; inset:0; background:rgba(15,23,42,.5); z-index:1000; display:none; align-items:center; justify-content:center; padding:16px; }
    .cs-modal { background:#fff; border-radius:18px; width:min(520px,100%); max-height:92vh; overflow:auto; }
    .cs-modal header { background:var(--brand); color:#fff; padding:16px 20px; border-radius:18px 18px 0 0; }
    .cs-modal .cuerpo { padding:18px 20px; }
    .cs-campos { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
    .cs-campos label, .cs-modal label { font-size:.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; }
    .cs-res { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin:12px 0; }
    .cs-res input { display:none; }
    .cs-res span { display:block; text-align:center; padding:10px; border-radius:12px; border:2px solid var(--border); font-weight:800; cursor:pointer; }
    .cs-res input:checked + span { border-color:currentColor; }
    @media (max-width:1100px) { .cs-kpis { grid-template-columns:repeat(2,1fr); } .cs-grid { grid-template-columns:1fr; } .cs-campos { grid-template-columns:repeat(2,1fr); } }
</style>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:14px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error" style="margin-bottom:14px">{{ session('error') }}</div>@endif

<div class="cs-kpis">
    <div class="cs-kpi" style="--c:#1f6feb"><div class="l">Controles pendientes</div><div class="v">{{ $resumen['hoy'] }}</div><div class="s">de hoy y atrasados</div></div>
    <div class="cs-kpi" style="--c:#dc2626"><div class="l">Semáforo rojo</div><div class="v" style="color:#b91c1c">{{ $resumen['rojos'] }}</div><div class="s">inspección recomendada</div></div>
    <div class="cs-kpi" style="--c:#f59e0b"><div class="l">En observación</div><div class="v" style="color:#b45309">{{ $resumen['amarillos'] }}</div><div class="s">semáforo amarillo</div></div>
    <div class="cs-kpi" style="--c:#7c3aed"><div class="l">Bloqueados</div><div class="v">{{ $resumen['bloqueados'] }}</div><div class="s">sin poder entregar</div></div>
    <div class="cs-kpi" style="--c:#16a34a"><div class="l">Controles del mes</div><div class="v">{{ (int) $resumen['mes']->total }}</div><div class="s">{{ (int) $resumen['mes']->graves }} graves · {{ (int) $resumen['mes']->leves }} leves</div></div>
</div>

<div class="cs-acciones">
    <form method="POST" action="{{ route('admin.calidad.control.sortear') }}">@csrf
        <button class="cs-btn prim" {{ $yaSorteado ? 'disabled style=opacity:.55' : '' }}>🎲 {{ $yaSorteado ? 'Sorteo de hoy listo' : 'Sortear controles de hoy' }}</button>
    </form>
    <button type="button" class="cs-btn" onclick="csProgramar()">📌 Programar control</button>
    <a class="cs-btn" href="#semaforo">🚦 Semáforo</a>
    <a class="cs-btn" href="#historial">📜 Resultados</a>
    <span style="font-size:.78rem;color:var(--text-muted);align-self:center">🔒 Confidencial: los choferes y ganaderos no ven esta sección.</span>
</div>

<div class="cs-grid">
    <div class="cs-panel">
        <h3>📋 Controles de hoy <small>{{ now()->format('d/m/Y') }}</small></h3>
        @forelse($hoy as $c)
            @php
            $col = ['predio' => '#dbeafe', 'muestra' => '#fef3c7', 'tanque' => '#ede9fe'][$c->tipo];
            $datosModal = ['id' => $c->id, 'nombre' => $c->ganadero?->nombre ?? 'Tanque · ' . $c->zona?->nombre, 'tipo' => $tipos[$c->tipo], 'frasco' => $c->frasco_codigo];
        @endphp
            <div class="cs-ctrl">
                <div class="ico" style="background:{{ $col }}">{{ mb_substr($tipos[$c->tipo], 0, 2) }}</div>
                <div style="flex:1;min-width:0">
                    <strong>{{ $c->ganadero?->nombre ?? 'Tanque · ' . $c->zona?->nombre }}</strong>
                    <div style="font-size:.76rem;color:var(--text-muted)">
                        {{ $tipos[$c->tipo] }} · {{ $c->zona?->nombre ?? 'sin ruta' }}
                        @if($c->frasco_codigo) · frasco <strong>{{ $c->frasco_codigo }}</strong>@endif
                        · {{ $c->motivo }}
                        @if($c->fecha->lt(today())) · <span style="color:#b91c1c">atrasado ({{ $c->fecha->format('d/m') }})</span>@endif
                    </div>
                    <span class="cs-pill" style="margin-top:4px;background:{{ $c->estado === 'muestra_tomada' ? '#dcfce7' : '#f1f5f9' }};color:{{ $c->estado === 'muestra_tomada' ? '#15803d' : '#475569' }}">
                        {{ $c->estado === 'muestra_tomada' ? '✓ Frasco en la planta' . ($c->acopio ? ' · ' . $c->acopio->litros . ' L' : '') : ($c->tipo === 'muestra' ? '⏳ Esperando al chofer' : '⏳ Por visitar') }}
                    </span>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px">
                    <button type="button" class="cs-btn prim" style="padding:7px 12px"
                        onclick='csResultado(@json($datosModal))'>Registrar resultado</button>
                    <form method="POST" action="{{ route('admin.calidad.control.cancelar', $c) }}" onsubmit="return confirm('¿Cancelar este control?')">@csrf
                        <button class="cs-btn" style="padding:5px 10px;font-size:.75rem;width:100%">Cancelar</button></form>
                </div>
            </div>
        @empty
            <p style="color:var(--text-muted);font-size:.88rem">No hay controles pendientes. {{ $yaSorteado ? '' : 'Usa «Sortear controles de hoy» o programa uno a mano.' }}</p>
        @endforelse
        @if($proximos->isNotEmpty())
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:8px">Próximos: {{ $proximos->map(fn ($p) => $p->ganadero?->nombre . ' (' . $p->fecha->format('d/m') . ')')->implode(' · ') }}</div>
        @endif
    </div>

    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="cs-panel">
            <h3>👀 Alertas de los choferes <small>7 días</small></h3>
            @forelse($alertas as $a)
                <div class="cs-fila">
                    <span style="font-size:1.2rem">{{ mb_substr($observaciones[$a->observacion] ?? '✏️', 0, 2) }}</span>
                    <div style="flex:1;min-width:0"><strong>{{ $a->ganadero?->nombre }}</strong>
                        <div style="font-size:.74rem;color:var(--text-muted)">{{ $observaciones[$a->observacion] ?? $a->observacion }}{{ $a->observacion_nota ? ' — ' . $a->observacion_nota : '' }} · {{ $a->chofer?->name }} · {{ $a->fecha->format('d/m') }}</div></div>
                    <button type="button" class="cs-btn" style="padding:4px 8px;font-size:.72rem" onclick="csProgramar({{ $a->ganadero_id }})">Controlar</button>
                </div>
            @empty
                <p style="color:var(--text-muted);font-size:.84rem">Sin observaciones de los choferes esta semana.</p>
            @endforelse
        </div>
        <div class="cs-panel">
            <h3>🚫 Bloqueados y suspendidos</h3>
            @forelse($bloqueados as $b)
                <div class="cs-fila">
                    <div style="flex:1;min-width:0"><strong>{{ $b->nombre }}</strong>
                        <div style="font-size:.74rem;color:#b91c1c">{{ $b->estaSuspendido() ? 'Suspendido' : "Faltan {$b->entregas_bloqueadas} entrega(s)" }}{{ $b->bloqueo_motivo ? ' · ' . $b->bloqueo_motivo : '' }}</div></div>
                    @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('admin.calidad.control.desbloquear', $b) }}" onsubmit="return confirm('¿Levantar el bloqueo de {{ addslashes($b->nombre) }}?')">@csrf
                        <button class="cs-btn" style="padding:4px 8px;font-size:.72rem">🔓 Levantar</button></form>
                    @endif
                </div>
            @empty
                <p style="color:var(--text-muted);font-size:.84rem">✅ Ningún ganadero bloqueado.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="cs-panel" id="semaforo" style="margin-bottom:16px">
    <h3>🚦 Semáforo de ganaderos <small>últimos 21 días · observación del chofer +2 · acopio observado +3 / rechazado +4 · muestra leve +3 / grave +6 · verde 0–2 · amarillo 3–5 · rojo 6+</small></h3>
    <div class="table-wrap"><table>
        <thead><tr><th>Ganadero</th><th>Ruta</th><th>Semáforo</th><th>Por qué</th><th>Última entrega</th><th>Último control</th><th></th></tr></thead>
        <tbody>
        @foreach($semaforo as $s)
            <tr>
                <td><strong>{{ $s['ganadero']->nombre }}</strong>
                    @if($s['ganadero']->entregas_bloqueadas)<span class="cs-pill" style="background:#fee2e2;color:#b91c1c">bloqueado</span>@endif</td>
                <td style="font-size:.82rem">{{ $s['ganadero']->zona?->nombre ?? '—' }}</td>
                <td style="white-space:nowrap"><span class="cs-pill" style="background:{{ $colorNivel[$s['nivel']][0] }};color:{{ $colorNivel[$s['nivel']][1] }}">● {{ ucfirst($s['nivel']) }} · {{ $s['puntaje'] }}</span></td>
                <td style="font-size:.74rem;max-width:280px">
                    @forelse($s['motivos'] as $m)<span class="cs-pill" style="background:#f1f5f9;color:#334155;margin:1px 2px 1px 0;display:inline-block">{{ $m }}</span>@empty<span style="color:var(--text-muted)">Sin señales</span>@endforelse
                </td>
                <td style="font-size:.8rem;white-space:nowrap">
                    @if($u = $s['ultima_entrega'])
                        {{ \Carbon\Carbon::parse($u->fecha)->format('d/m') }} · {{ number_format($u->litros, 1) }} L
                        <span class="badge badge-{{ $u->estado }}" style="font-size:.65rem">{{ ucfirst($u->estado) }}</span>
                    @else <span style="color:var(--text-muted)">—</span> @endif
                </td>
                <td style="font-size:.8rem">{{ $s['ultimo_control']?->format('d/m/Y') ?? 'Nunca' }}</td>
                <td style="white-space:nowrap">
                    <button type="button" class="cs-btn" style="padding:4px 8px;font-size:.72rem" onclick="csProgramar({{ $s['ganadero']->id }})">📌 Control</button>
                    <form method="POST" action="{{ route('admin.calidad.control.programar') }}" style="display:inline">@csrf
                        <input type="hidden" name="ganadero_id" value="{{ $s['ganadero']->id }}"><input type="hidden" name="tipo" value="muestra"><input type="hidden" name="seguimiento" value="1">
                        <button class="cs-btn" style="padding:4px 8px;font-size:.72rem" title="Frasco en sus próximas 3 entregas">🔍 Seguimiento</button></form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</div>

<div class="cs-panel" id="historial">
    <h3>📜 Resultados <small>{{ $historial->total() }} controles analizados</small></h3>
    <div class="table-wrap"><table>
        <thead><tr><th>Fecha</th><th>Ganadero / tanque</th><th>Tipo</th><th>Agua · densidad · pH · grasa</th><th>Resultado</th><th>Sanción</th><th>Inspector</th></tr></thead>
        <tbody>
        @forelse($historial as $h)
            <tr>
                <td style="font-size:.82rem">{{ $h->fecha->format('d/m/Y') }}</td>
                <td><strong>{{ $h->ganadero?->nombre ?? 'Tanque · ' . $h->zona?->nombre }}</strong></td>
                <td style="font-size:.78rem">{{ $tipos[$h->tipo] }}</td>
                <td style="font-size:.78rem">{{ $h->agua ?? '—' }} % · {{ $h->densidad ?? '—' }} · {{ $h->ph ?? '—' }} · {{ $h->grasa ?? '—' }} %</td>
                <td><span class="cs-pill" style="background:{{ $colorRes[$h->resultado][0] }};color:{{ $colorRes[$h->resultado][1] }}">{{ $resultados[$h->resultado] }}</span></td>
                <td style="font-size:.78rem">{{ $h->sancion ?? '—' }}</td>
                <td style="font-size:.78rem">{{ $h->inspector?->name }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px">Aún no hay resultados.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding-top:10px">{{ $historial->links() }}</div>
</div>

{{-- Modal: registrar resultado --}}
<div class="cs-modal-fondo" id="cs-modal-res">
    <form class="cs-modal" method="POST" id="cs-form-res" enctype="multipart/form-data">@csrf
        <header><div style="font-size:.72rem;opacity:.85;font-weight:700">REGISTRAR RESULTADO</div><div id="cs-res-nombre" style="font-weight:800;font-size:1.05rem"></div><div id="cs-res-tipo" style="font-size:.8rem;opacity:.85"></div></header>
        <div class="cuerpo">
            <div class="cs-campos">
                <div><label>Agua %</label><input type="number" step="0.01" min="0" max="100" name="agua" class="cs-medida"></div>
                <div><label>Densidad</label><input type="number" step="0.0001" min="0.9" max="1.1" name="densidad" placeholder="1.030" class="cs-medida"></div>
                <div><label>pH</label><input type="number" step="0.01" min="0" max="14" name="ph" placeholder="6.6" class="cs-medida"></div>
                <div><label>Grasa %</label><input type="number" step="0.01" min="0" max="15" name="grasa"></div>
                <div><label>Temp. °C</label><input type="number" step="0.1" min="-5" max="60" name="temperatura"></div>
                <div><label>Foto</label><input type="file" name="foto" accept="image/*"></div>
            </div>
            <label style="display:block;margin-top:12px">Resultado <span id="cs-sugerido" style="text-transform:none;font-weight:600"></span></label>
            <div class="cs-res">
                <label style="color:#15803d"><input type="radio" name="resultado" value="normal"><span>✓ Normal</span></label>
                <label style="color:#b45309"><input type="radio" name="resultado" value="leve"><span>⚠ Leve</span></label>
                <label style="color:#b91c1c"><input type="radio" name="resultado" value="grave"><span>✗ Grave</span></label>
            </div>
            <div id="cs-consecuencia" style="font-size:.8rem;padding:10px;border-radius:10px;background:#f8fafc;margin-bottom:10px"></div>
            <label>Notas</label><textarea name="notas" rows="2" maxlength="1000"></textarea>
            <div style="display:flex;gap:10px;margin-top:14px">
                <button class="cs-btn prim" style="flex:1;justify-content:center">💾 Guardar y avisar</button>
                <button type="button" class="cs-btn" onclick="csCerrar('cs-modal-res')">Cancelar</button>
            </div>
        </div>
    </form>
</div>

{{-- Modal: programar control --}}
<div class="cs-modal-fondo" id="cs-modal-prog">
    <form class="cs-modal" method="POST" action="{{ route('admin.calidad.control.programar') }}">@csrf
        <header><div style="font-weight:800">📌 Programar control</div></header>
        <div class="cuerpo">
            <label>Ganadero</label>
            <select name="ganadero_id" id="cs-prog-g" required>
                @foreach($semaforo as $s)<option value="{{ $s['ganadero']->id }}">{{ $s['ganadero']->nombre }} · {{ $s['nivel'] }}</option>@endforeach
            </select>
            <label style="display:block;margin-top:10px">Tipo</label>
            <select name="tipo"><option value="predio">🏡 Visita sorpresa al predio</option><option value="muestra">🧪 Frasco que trae el chofer</option></select>
            <label style="display:block;margin-top:10px">Fecha</label>
            <input type="date" name="fecha" value="{{ today()->toDateString() }}" min="{{ today()->toDateString() }}">
            <div style="display:flex;gap:10px;margin-top:14px">
                <button class="cs-btn prim" style="flex:1;justify-content:center">Programar</button>
                <button type="button" class="cs-btn" onclick="csCerrar('cs-modal-prog')">Cancelar</button>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    const RES_URL = @json(url('admin/calidad/control'));
    const consecuencias = {
        normal: '✓ Sin sanción. Baja en el semáforo.',
        leve: '⚠ Advertencia y descuento en esa entrega. Se avisa al ganadero, al chofer y al administrador.',
        grave: '✗ Esa leche NO se paga y queda bloqueado 3 entregas (6 si reincide en 30 días; a la 3.ª, suspensión). Se avisa a todos.',
    };
    window.csCerrar = id => document.getElementById(id).style.display = 'none';
    window.csResultado = c => {
        const f = document.getElementById('cs-form-res');
        f.reset();
        f.action = `${RES_URL}/${c.id}/resultado`;
        document.getElementById('cs-res-nombre').textContent = c.nombre;
        document.getElementById('cs-res-tipo').textContent = c.tipo + (c.frasco ? ' · frasco ' + c.frasco : '');
        document.getElementById('cs-sugerido').textContent = '';
        document.getElementById('cs-consecuencia').textContent = 'Ingresa las medidas: el sistema sugiere el resultado.';
        document.getElementById('cs-modal-res').style.display = 'flex';
    };
    window.csProgramar = id => {
        if (id) document.getElementById('cs-prog-g').value = id;
        document.getElementById('cs-modal-prog').style.display = 'flex';
    };
    // Sugerencia con las mismas reglas del servidor (Acopio::calcularEstado)
    function sugerir() {
        const f = document.getElementById('cs-form-res');
        const agua = parseFloat(f.agua.value) || 0, ph = parseFloat(f.ph.value) || 0, den = parseFloat(f.densidad.value) || null;
        let r = 'normal';
        if (agua >= 10 || (ph && (ph < 5.5 || ph > 8.5))) r = 'grave';
        else if (agua > 5 || (ph && (ph < 6.4 || ph > 6.8)) || (den && (den < 1.028 || den > 1.033)) || agua >= 0.5) r = 'leve';
        f.querySelector(`input[name=resultado][value=${r}]`).checked = true;
        document.getElementById('cs-sugerido').textContent = '— sugerido: ' + r;
        pintar();
    }
    function pintar() {
        const sel = document.querySelector('#cs-form-res input[name=resultado]:checked');
        document.getElementById('cs-consecuencia').textContent = sel ? consecuencias[sel.value] : '';
    }
    document.querySelectorAll('.cs-medida').forEach(i => i.addEventListener('input', sugerir));
    document.querySelectorAll('#cs-form-res input[name=resultado]').forEach(i => i.addEventListener('change', pintar));
    document.querySelectorAll('.cs-modal-fondo').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.style.display = 'none'; }));
})();
</script>
@endsection
