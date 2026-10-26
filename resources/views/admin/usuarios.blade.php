@extends('layouts.admin')

@section('title', 'Usuarios')
@section('page-title', '👥 Gestión de usuarios del sistema')

@section('content')

<div style="display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start">

    {{-- ── Lista de usuarios ───────────────────────────────────────────────── --}}
    <div>

        {{-- Tabs de rol --}}
        <div style="display:flex;gap:4px;background:var(--bg);padding:4px;border-radius:10px;
                    width:fit-content;margin-bottom:18px">
            @foreach(['todos' => '📋 Todos', 'chofer' => '🚛 Choferes', 'admin' => '⚙️ Admin', 'calidad' => '🔬 Calidad', 'asistente' => '📋 Asistente', 'jefe_produccion' => '🏭 Jefe prod.', 'despacho' => '📦 Despacho'] as $r => $label)
            <a href="{{ route('admin.usuarios.index', ['rol' => $r]) }}"
               style="padding:7px 16px;border-radius:7px;font-size:.83rem;font-weight:600;
                      text-decoration:none;transition:all .15s;
                      {{ $rol === $r
                          ? 'background:var(--bg-card);color:var(--brand-light);box-shadow:0 1px 4px rgba(0,0,0,.08)'
                          : 'color:var(--text-muted)' }}">
                {{ $label }}
                <span style="margin-left:4px;background:{{ $rol === $r ? 'var(--green-bg)' : 'var(--border)' }};
                             color:{{ $rol === $r ? 'var(--green)' : 'var(--text-muted)' }};
                             border-radius:10px;padding:1px 7px;font-size:.7rem">
                    @if($r === 'todos') {{ $contadores['total'] }}
                    @else {{ $contadores[$r] ?? 0 }}
                    @endif
                </span>
            </a>
            @endforeach
        </div>

        <div class="card">
            <div class="card-header">
                <h3>👥 Usuarios del sistema</h3>
                <span class="card-header-meta">{{ $usuarios->total() }} usuario(s)</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuarios as $u)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:34px;height:34px;border-radius:50%;
                                                background:{{ $u->rol === 'admin' ? 'var(--brand)' : 'var(--blue-bg)' }};
                                                display:flex;align-items:center;justify-content:center;
                                                font-weight:700;font-size:.85rem;
                                                color:{{ $u->rol === 'admin' ? '#fff' : 'var(--blue)' }};
                                                flex-shrink:0">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <strong>{{ $u->name }}</strong>
                                        @if($u->id === auth()->id())
                                        <span class="tag" style="margin-left:4px;font-size:.65rem">Tú</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted" style="font-size:.83rem">{{ $u->email }}</td>
                            <td>
                                @if($u->rol === 'admin')
                                    <span class="badge" style="background:var(--brand);color:#fff">⚙️ Admin</span>
                                @elseif($u->rol === 'chofer')
                                    <span class="badge badge-mayorista">🚛 Chofer</span>
                                @else
                                    <span class="badge badge-observado">{{ $rolesDisponibles[$u->rol] ?? $u->rol }}</span>
                                @endif
                                @foreach($u->roles_extra ?? [] as $extra)
                                    <span class="badge" style="background:var(--purple-bg);color:var(--purple);margin-top:3px"
                                          title="Acceso adicional">+ {{ $rolesDisponibles[$extra] ?? $extra }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($u->activo)
                                    <span class="badge badge-aprobado">● Activo</span>
                                @else
                                    <span class="badge badge-rechazado">● Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    {{-- Rol principal y accesos adicionales --}}
                                    <button class="btn btn-ghost btn-sm"
                                            onclick='abrirAccesos({{ $u->id }}, @json($u->name), @json($u->rol), @json($u->roles_extra ?? []))'>
                                        🛡️ Accesos
                                    </button>

                                    {{-- Cambiar contraseña --}}
                                    <button class="btn btn-ghost btn-sm"
                                            onclick="abrirCambioPass({{ $u->id }}, '{{ addslashes($u->name) }}')">
                                        🔑 Contraseña
                                    </button>

                                    {{-- Toggle activo --}}
                                    @if($u->id !== auth()->id())
                                    <form method="POST"
                                          action="{{ route('admin.usuarios.toggle', $u) }}"
                                          onsubmit="return confirm('¿{{ $u->activo ? 'Desactivar' : 'Activar' }} a {{ addslashes($u->name) }}?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm"
                                                style="{{ $u->activo
                                                    ? 'background:var(--red-bg);color:var(--red);border:1px solid #fecaca'
                                                    : 'background:var(--green-bg);color:var(--green);border:1px solid #bbf7d0' }}">
                                            {{ $u->activo ? '🚫 Desactivar' : '✓ Activar' }}
                                        </button>
                                    </form>
                                    @endif

                                    {{-- Resetear 2FA si está activo --}}
                                    @if($u->otp_activo)
                                    <form method="POST"
                                          action="{{ route('2fa.desactivar') }}"
                                          onsubmit="return confirm('¿Resetear la verificación 2FA de {{ addslashes($u->name) }}? Podrá configurar un número nuevo la próxima vez que ingrese.')">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $u->id }}">
                                        <button type="submit" class="btn btn-sm"
                                                style="background:#fef3c7;color:#b45309;border:1px solid #fde68a"
                                                title="Resetear verificación WhatsApp para configurar nuevo número">
                                            📱 Reset 2FA
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted)">
                                Sin usuarios con este filtro
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 22px">{{ $usuarios->links() }}</div>
        </div>
    </div>

    {{-- ── Panel derecho ───────────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px;position:sticky;top:80px">

        {{-- Crear usuario --}}
        <div class="card">
            <div class="card-header" style="background:var(--brand);border-radius:var(--radius) var(--radius) 0 0">
                <h3 style="color:#fff">➕ Nuevo usuario</h3>
            </div>
            <div class="card-body" style="padding:18px">
                <form method="POST" action="{{ route('admin.usuarios.store') }}">
                    @csrf

                    <div class="form-group" style="margin-bottom:12px">
                        <label>Nombre completo <span class="req">*</span></label>
                        <input type="text" name="name" required maxlength="150"
                               value="{{ old('name') }}"
                               placeholder="Ej: Carlos Ramos Quispe"
                               class="{{ $errors->has('name') ? 'invalid' : '' }}">
                        @error('name')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:12px">
                        <label>Email <span class="req">*</span></label>
                        <input type="email" name="email" required
                               value="{{ old('email') }}"
                               placeholder="carlos@prolech.com"
                               class="{{ $errors->has('email') ? 'invalid' : '' }}">
                        @error('email')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:12px">
                        <label>Rol <span class="req">*</span></label>
                        <select name="rol" required class="{{ $errors->has('rol') ? 'invalid' : '' }}">
                            @foreach($rolesDisponibles as $valor => $etiqueta)
                            <option value="{{ $valor }}" {{ old('rol','chofer') === $valor ? 'selected' : '' }}>
                                {{ $etiqueta }}
                            </option>
                            @endforeach
                        </select>
                        @error('rol')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:12px">
                        <label>Accesos adicionales <span style="font-weight:400;color:var(--text-muted)">(opcional)</span></label>
                        <div class="casillas-roles" data-principal="rol">
                            @foreach($rolesDisponibles as $valor => $etiqueta)
                            <label class="casilla-rol">
                                <input type="checkbox" name="roles_extra[]" value="{{ $valor }}"
                                       {{ in_array($valor, old('roles_extra', []), true) ? 'checked' : '' }}>
                                {{ $etiqueta }}
                            </label>
                            @endforeach
                        </div>
                        <span style="font-size:.74rem;color:var(--text-muted)">Ej.: un despachador que también sale a recoger leche: rol «Personal de despacho» + acceso «Chofer».</span>
                    </div>

                    <div class="form-group" style="margin-bottom:12px">
                        <label>Contraseña <span class="req">*</span></label>
                        <input type="password" name="password" required minlength="6"
                               placeholder="Mínimo 6 caracteres"
                               class="{{ $errors->has('password') ? 'invalid' : '' }}">
                        @error('password')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:18px">
                        <label>Confirmar contraseña <span class="req">*</span></label>
                        <input type="password" name="password_confirmation" required minlength="6"
                               placeholder="Repetir contraseña">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        💾 Crear usuario
                    </button>
                </form>
            </div>
        </div>

        {{-- Editar accesos --}}
        <div class="card" id="panelAccesos" style="display:none">
            <div class="card-header" style="background:var(--purple);border-radius:var(--radius) var(--radius) 0 0">
                <h3 style="color:#fff">🛡️ Accesos del usuario</h3>
                <button onclick="cerrarAccesos()" style="background:none;border:none;color:#fff;cursor:pointer;font-size:1.2rem">✕</button>
            </div>
            <div class="card-body" style="padding:18px">
                <div id="acc-nombre" style="font-weight:700;margin-bottom:14px;color:var(--text)"></div>
                <form method="POST" id="formAccesos" action="">
                    @csrf
                    <div class="form-group" style="margin-bottom:12px">
                        <label>Rol principal <span class="req">*</span></label>
                        <select name="rol" id="acc-rol" required>
                            @foreach($rolesDisponibles as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:16px">
                        <label>Accesos adicionales</label>
                        <div class="casillas-roles" id="acc-extras" data-principal="acc-rol">
                            @foreach($rolesDisponibles as $valor => $etiqueta)
                            <label class="casilla-rol">
                                <input type="checkbox" name="roles_extra[]" value="{{ $valor }}"> {{ $etiqueta }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div style="display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">💾 Guardar accesos</button>
                        <button type="button" class="btn btn-ghost" onclick="cerrarAccesos()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal cambio de contraseña --}}
        <div class="card" id="panelPass" style="display:none">
            <div class="card-header" style="background:var(--amber);border-radius:var(--radius) var(--radius) 0 0">
                <h3 style="color:#fff">🔑 Cambiar contraseña</h3>
                <button onclick="cerrarPass()" style="background:none;border:none;color:#fff;cursor:pointer;font-size:1.2rem">✕</button>
            </div>
            <div class="card-body" style="padding:18px">
                <div id="pass-nombre" style="font-weight:700;margin-bottom:14px;color:var(--text)"></div>
                <form method="POST" id="formPass" action="">
                    @csrf
                    <div class="form-group" style="margin-bottom:12px">
                        <label>Nueva contraseña <span class="req">*</span></label>
                        <input type="password" name="nueva_password" required minlength="6"
                               placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group" style="margin-bottom:16px">
                        <label>Confirmar contraseña <span class="req">*</span></label>
                        <input type="password" name="nueva_password_confirmation" required minlength="6"
                               placeholder="Repetir">
                    </div>
                    <div style="display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">
                            💾 Actualizar
                        </button>
                        <button type="button" class="btn btn-ghost" onclick="cerrarPass()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Info --}}
        <div class="card">
            <div class="card-header"><h3>ℹ️ Información</h3></div>
            <div class="card-body" style="padding:14px 16px;font-size:.83rem;color:var(--text-muted);line-height:1.7">
                <p>• Los <strong>choferes</strong> acceden solo desde la app Android.</p>
                <p>• Los <strong>admin</strong> acceden al panel web con su email/contraseña.</p>
                <p>• Un usuario puede tener <strong>varios accesos</strong>: su rol principal y otros adicionales (botón 🛡️ Accesos). Ve las secciones de todos ellos.</p>
                <p>• <strong>Desactivar</strong> bloquea el acceso sin borrar el historial.</p>
                <p>• La contraseña inicial recomendada: <strong>prolech + año</strong>.</p>
            </div>
        </div>

    </div>
</div>

<style>
    .casillas-roles { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 10px; margin-top: 4px; }
    .casilla-rol { display: flex; gap: 6px; align-items: center; font-size: .8rem; font-weight: 400; text-transform: none; letter-spacing: 0; cursor: pointer; }
    .casilla-rol input { width: auto; margin: 0; }
</style>
<script>
(function () {
    'use strict';
    var BASE = "{{ url('/admin/usuarios') }}";

    window.abrirCambioPass = function (id, nombre) {
        document.getElementById('formPass').action = BASE + '/' + id + '/cambiar-password';
        document.getElementById('pass-nombre').textContent = 'Cambiar contraseña de: ' + nombre;
        document.getElementById('panelPass').style.display = 'block';
        document.getElementById('panelPass').scrollIntoView({ behavior: 'smooth' });
    };

    window.cerrarPass = function () {
        document.getElementById('panelPass').style.display = 'none';
    };

    // La casilla del rol principal se desactiva: ya lo tiene
    function principalDe(grupo) {
        return document.getElementById(grupo.dataset.principal) || document.querySelector('[name="' + grupo.dataset.principal + '"]');
    }
    function sincronizarCasillas(grupo) {
        var principal = principalDe(grupo);
        grupo.querySelectorAll('input[type=checkbox]').forEach(function (c) {
            var esPrincipal = c.value === principal.value;
            c.disabled = esPrincipal;
            if (esPrincipal) c.checked = false;
            c.parentElement.style.opacity = esPrincipal ? '.45' : '1';
        });
    }
    document.querySelectorAll('.casillas-roles').forEach(function (g) {
        principalDe(g).addEventListener('change', function () { sincronizarCasillas(g); });
        sincronizarCasillas(g);
    });

    window.abrirAccesos = function (id, nombre, rol, extras) {
        document.getElementById('formAccesos').action = BASE + '/' + id + '/accesos';
        document.getElementById('acc-nombre').textContent = 'Usuario: ' + nombre;
        document.getElementById('acc-rol').value = rol;
        document.querySelectorAll('#acc-extras input').forEach(function (c) { c.checked = extras.indexOf(c.value) >= 0; });
        sincronizarCasillas(document.getElementById('acc-extras'));
        document.getElementById('panelAccesos').style.display = 'block';
        document.getElementById('panelAccesos').scrollIntoView({ behavior: 'smooth' });
    };
    window.cerrarAccesos = function () {
        document.getElementById('panelAccesos').style.display = 'none';
    };
}());
</script>

@endsection
