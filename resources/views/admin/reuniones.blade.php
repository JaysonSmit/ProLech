@extends('layouts.admin')

@section('title', 'Reuniones y Capacitaciones')
@section('page-title', '📅 Reuniones y Capacitaciones')

@section('content')
<style>
    /* ── Layout agenda + formulario ────────────────────────────────────────── */
    .agenda-layout {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 22px;
        align-items: start;
    }

    /* ── Tarjeta de evento ─────────────────────────────────────────────────── */
    .evento-card {
        background: var(--blanco);
        border: 1px solid var(--gris-borde);
        border-radius: 12px;
        padding: 0;
        margin-bottom: 14px;
        overflow: hidden;
        transition: box-shadow .15s;
    }

    .evento-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }

    .evento-header {
        display: flex;
        align-items: stretch;
        gap: 0;
    }

    .evento-fecha-col {
        min-width: 80px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 16px 12px;
        color: #fff;
        text-align: center;
    }

    .evento-fecha-col.capacitacion { background: var(--azul); }
    .evento-fecha-col.asamblea     { background: #7c3aed; }
    .evento-fecha-col.otro         { background: var(--naranja); }

    .ef-dia   { font-size: 1.8rem; font-weight: 800; line-height: 1; }
    .ef-mes   { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; opacity: .85; }
    .ef-hora  { font-size: .78rem; margin-top: 6px; opacity: .9; font-weight: 600; }

    .evento-body {
        flex: 1;
        padding: 14px 18px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .evento-titulo { font-size: 1rem; font-weight: 700; }

    .evento-meta {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        font-size: .78rem;
        color: var(--texto-suave);
    }

    .evento-meta span { display: flex; align-items: center; gap: 4px; }

    .evento-footer {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    .btn-videocall {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 16px;
        background: #16a34a;
        color: #fff;
        border-radius: 8px;
        font-size: .82rem;
        font-weight: 600;
        text-decoration: none;
        transition: background .15s;
        border: none;
        cursor: pointer;
    }
    .btn-videocall:hover { background: #15803d; }

    .btn-cancelar-evt {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        background: #fff;
        color: var(--rojo);
        border: 1px solid #fecaca;
        border-radius: 8px;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-cancelar-evt:hover { background: #fee2e2; }

    .invitados-chips {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
        margin-top: 4px;
    }

    .chip {
        background: #eff6ff;
        color: var(--azul);
        border-radius: 20px;
        padding: 2px 10px;
        font-size: .72rem;
        font-weight: 600;
    }

    .chip.chofer   { background: #eff6ff; color: var(--azul); }
    .chip.ganadero { background: #f0fdf4; color: var(--verde); }
    .chip.mas      { background: #f8fafc; color: var(--texto-suave); }

    /* ── Estado de la reunión ──────────────────────────────────────────────── */
    .badge-programada  { background: #dbeafe; color: #1d4ed8; }
    .badge-en_curso    { background: #dcfce7; color: #15803d; }
    .badge-finalizada  { background: #f1f5f9; color: #64748b; }
    .badge-cancelada   { background: #fee2e2; color: #b91c1c; }

    /* ── Formulario nueva reunión ──────────────────────────────────────────── */
    .form-card {
        background: var(--blanco);
        border: 1px solid var(--gris-borde);
        border-radius: 12px;
        overflow: hidden;
        position: sticky;
        top: 80px;
    }

    .form-card-header {
        background: var(--azul);
        padding: 16px 20px;
        color: #fff;
    }

    .form-card-header h3 { font-size: .95rem; font-weight: 700; }
    .form-card-header p  { font-size: .78rem; opacity: .8; margin-top: 2px; }

    .form-card-body { padding: 20px; }

    /* Selector de invitados */
    .invitados-selector {
        border: 1px solid var(--gris-borde);
        border-radius: 8px;
        max-height: 160px;
        overflow-y: auto;
        padding: 8px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .inv-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: background .1s;
        font-size: .85rem;
    }
    .inv-item:hover { background: #f8fafc; }
    .inv-item input[type="checkbox"] { margin: 0; cursor: pointer; }

    .inv-rol-badge {
        font-size: .65rem;
        font-weight: 600;
        padding: 1px 7px;
        border-radius: 10px;
        text-transform: uppercase;
    }
    .inv-rol-badge.chofer   { background: #dbeafe; color: #1d4ed8; }
    .inv-rol-badge.ganadero { background: #dcfce7; color: #15803d; }

    /* ── Sección pasadas ───────────────────────────────────────────────────── */
    .pasadas-header {
        font-size: .82rem;
        font-weight: 600;
        color: var(--texto-suave);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin: 28px 0 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pasadas-header::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--gris-borde);
    }

    /* Sin eventos */
    .empty-agenda {
        text-align: center;
        padding: 40px 20px;
        color: var(--texto-suave);
    }
    .empty-agenda .ea-icon { font-size: 3rem; margin-bottom: 10px; }
    .empty-agenda p { font-size: .88rem; }
</style>

<div class="agenda-layout">

    {{-- ── Columna izquierda: Agenda ───────────────────────────────────────── --}}
    <div>

        {{-- Próximas reuniones --}}
        <div style="font-size:.82rem;font-weight:600;color:var(--texto-suave);
                    text-transform:uppercase;letter-spacing:.4px;margin-bottom:14px">
            📅 Próximas reuniones ({{ $proximas->count() }})
        </div>

        @forelse($proximas as $reunion)
        <div class="evento-card">
            <div class="evento-header">

                {{-- Columna de fecha --}}
                <div class="evento-fecha-col {{ $reunion->tipo }}">
                    <div class="ef-dia">{{ $reunion->fecha_hora->format('d') }}</div>
                    <div class="ef-mes">{{ $reunion->fecha_hora->isoFormat('MMM') }}</div>
                    <div class="ef-hora">{{ $reunion->fecha_hora->format('H:i') }}</div>
                </div>

                {{-- Cuerpo del evento --}}
                <div class="evento-body">
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="evento-titulo">{{ $reunion->titulo }}</div>
                        <span class="badge badge-{{ $reunion->estado }}">
                            {{ ucfirst(str_replace('_', ' ', $reunion->estado)) }}
                        </span>
                    </div>

                    <div class="evento-meta">
                        <span>{{ $reunion->etiquetaTipo() }}</span>
                        @if($reunion->lugar)
                        <span>📍 {{ $reunion->lugar }}</span>
                        @endif
                        @if($reunion->tieneEnlaceVideo())
                        <span>📹 Videollamada disponible</span>
                        @endif
                        <span>👤 Creado por {{ $reunion->creador->name ?? '—' }}</span>
                    </div>

                    @if($reunion->descripcion)
                    <div style="font-size:.8rem;color:var(--texto-suave);
                                background:#f8fafc;border-radius:6px;padding:8px 10px;margin-top:4px">
                        {{ $reunion->descripcion }}
                    </div>
                    @endif

                    {{-- Chips de invitados --}}
                    @if($reunion->invitados->isNotEmpty())
                    <div class="invitados-chips">
                        @foreach($reunion->invitados->take(5) as $inv)
                        <span class="chip {{ $inv->rol }}">{{ $inv->name }}</span>
                        @endforeach
                        @if($reunion->invitados->count() > 5)
                        <span class="chip mas">+{{ $reunion->invitados->count() - 5 }} más</span>
                        @endif
                    </div>
                    @else
                    <div style="font-size:.75rem;color:var(--texto-suave);margin-top:2px">
                        Sin invitados asignados aún.
                    </div>
                    @endif

                    {{-- Botones de acción --}}
                    <div class="evento-footer">
                        @if($reunion->tieneEnlaceVideo())
                        <a href="{{ $reunion->enlace }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn-videocall">
                            📹 Unirse a videollamada
                        </a>
                        @endif

                        @if($reunion->estado === 'programada')
                        <form method="POST"
                              action="{{ route('admin.reuniones.cancelar', $reunion) }}"
                              onsubmit="return confirm('¿Cancelar la reunión «{{ addslashes($reunion->titulo) }}»? Los invitados serán notificados.')">
                            @csrf
                            <button type="submit" class="btn-cancelar-evt">
                                ✕ Cancelar reunión
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="empty-agenda">
            <div class="ea-icon">📅</div>
            <p>Sin reuniones programadas próximamente.</p>
            <p>Usa el formulario para programar una nueva.</p>
        </div>
        @endforelse

        {{-- Reuniones pasadas --}}
        @if($pasadas->isNotEmpty())
        <div class="pasadas-header">Historial reciente</div>

        @foreach($pasadas as $reunion)
        <div class="evento-card" style="opacity:.65">
            <div class="evento-header">
                <div class="evento-fecha-col" style="background:#94a3b8">
                    <div class="ef-dia">{{ $reunion->fecha_hora->format('d') }}</div>
                    <div class="ef-mes">{{ $reunion->fecha_hora->isoFormat('MMM') }}</div>
                    <div class="ef-hora">{{ $reunion->fecha_hora->format('H:i') }}</div>
                </div>
                <div class="evento-body">
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="evento-titulo">{{ $reunion->titulo }}</div>
                        <span class="badge badge-{{ $reunion->estado }}">
                            {{ ucfirst(str_replace('_', ' ', $reunion->estado)) }}
                        </span>
                    </div>
                    <div class="evento-meta">
                        <span>{{ $reunion->etiquetaTipo() }}</span>
                        <span>👤 {{ $reunion->creador->name ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        @endif

    </div>

    {{-- ── Columna derecha: Formulario nueva reunión ───────────────────────── --}}
    <div>
        <div class="form-card">
            <div class="form-card-header">
                <h3>➕ Nueva reunión</h3>
                <p>Programa capacitaciones y asambleas</p>
            </div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('admin.reuniones.store') }}" id="formReunion">
                    @csrf

                    <div class="form-group" style="margin-bottom:14px">
                        <label for="titulo">Título *</label>
                        <input type="text" id="titulo" name="titulo"
                               maxlength="200" required
                               value="{{ old('titulo') }}"
                               class="{{ $errors->has('titulo') ? 'invalid' : '' }}"
                               placeholder="Ej: Capacitación lactoscan">
                        @error('titulo')
                            <span class="error-msg">{{ $message }}</span>
                        @enderror
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                        <div class="form-group">
                            <label for="tipo">Tipo *</label>
                            <select id="tipo" name="tipo" required
                                    class="{{ $errors->has('tipo') ? 'invalid' : '' }}">
                                <option value="capacitacion" {{ old('tipo','capacitacion')==='capacitacion' ? 'selected' : '' }}>
                                    📚 Capacitación
                                </option>
                                <option value="asamblea" {{ old('tipo')==='asamblea' ? 'selected' : '' }}>
                                    🏛️ Asamblea
                                </option>
                                <option value="otro" {{ old('tipo')==='otro' ? 'selected' : '' }}>
                                    📅 Otro
                                </option>
                            </select>
                            @error('tipo') <span class="error-msg">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="fecha_hora">Fecha y hora *</label>
                            <input type="datetime-local" id="fecha_hora" name="fecha_hora"
                                   required
                                   value="{{ old('fecha_hora') }}"
                                   min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                                   class="{{ $errors->has('fecha_hora') ? 'invalid' : '' }}">
                            @error('fecha_hora') <span class="error-msg">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:14px">
                        <label for="enlace">Enlace videollamada</label>
                        <input type="url" id="enlace" name="enlace"
                               value="{{ old('enlace') }}"
                               class="{{ $errors->has('enlace') ? 'invalid' : '' }}"
                               placeholder="https://meet.google.com/xyz">
                        @error('enlace') <span class="error-msg">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group" style="margin-bottom:14px">
                        <label for="lugar">Lugar (si es presencial)</label>
                        <input type="text" id="lugar" name="lugar"
                               maxlength="200"
                               value="{{ old('lugar') }}"
                               placeholder="Ej: Planta ProLech, Sala de reuniones">
                    </div>

                    <div class="form-group" style="margin-bottom:16px">
                        <label for="descripcion">Descripción / Agenda</label>
                        <textarea id="descripcion" name="descripcion"
                                  rows="3" maxlength="2000"
                                  placeholder="Temas a tratar, materiales requeridos…">{{ old('descripcion') }}</textarea>
                    </div>

                    {{-- Selector de invitados --}}
                    <div class="form-group" style="margin-bottom:18px">
                        <label>Invitados</label>
                        <div style="display:flex;gap:8px;margin-bottom:6px">
                            <button type="button" class="btn btn-sm"
                                    style="background:#eff6ff;color:var(--azul);padding:4px 10px;font-size:.75rem"
                                    onclick="seleccionarTodos()">Todos</button>
                            <button type="button" class="btn btn-sm"
                                    style="background:#f8fafc;color:var(--texto-suave);padding:4px 10px;font-size:.75rem"
                                    onclick="deseleccionarTodos()">Ninguno</button>
                            <button type="button" class="btn btn-sm"
                                    style="background:#eff6ff;color:var(--azul);padding:4px 10px;font-size:.75rem"
                                    onclick="soloChoferes()">Solo choferes</button>
                        </div>
                        <div class="invitados-selector">
                            @forelse($usuariosInvitables as $u)
                            <label class="inv-item">
                                <input type="checkbox"
                                       name="invitados[]"
                                       value="{{ $u->id }}"
                                       {{ in_array($u->id, old('invitados', [])) ? 'checked' : '' }}>
                                <span style="flex:1">{{ $u->name }}</span>
                                <span class="inv-rol-badge {{ $u->rol }}">{{ ucfirst($u->rol) }}</span>
                            </label>
                            @empty
                            <p style="font-size:.8rem;color:var(--texto-suave);padding:8px">
                                Sin usuarios disponibles.
                            </p>
                            @endforelse
                        </div>
                        <div style="font-size:.72rem;color:var(--texto-suave);margin-top:4px">
                            Se enviará notificación a cada invitado seleccionado.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        📅 Programar reunión
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    function getCheckboxes() {
        return document.querySelectorAll('.invitados-selector input[type="checkbox"]');
    }

    window.seleccionarTodos = function () {
        getCheckboxes().forEach(function (cb) { cb.checked = true; });
    };

    window.deseleccionarTodos = function () {
        getCheckboxes().forEach(function (cb) { cb.checked = false; });
    };

    window.soloChoferes = function () {
        getCheckboxes().forEach(function (cb) {
            var badge = cb.closest('.inv-item').querySelector('.inv-rol-badge');
            cb.checked = badge && badge.classList.contains('chofer');
        });
    };
}());
</script>

@endsection
