@extends('layouts.admin')

@section('title', 'Proveedores')
@section('page-title', '🐄 Gestión de Proveedores')

@section('content')

{{-- ── Stats ──────────────────────────────────────────────────────────────── --}}
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
    <div class="stat-card verde">
        <div class="label">Activos</div>
        <div class="value">{{ $totales['activos'] }}</div>
        <div class="unit">en padrón activo</div>
    </div>
    <div class="stat-card rojo">
        <div class="label">Inactivos</div>
        <div class="value">{{ $totales['inactivos'] }}</div>
        <div class="unit">dados de baja</div>
    </div>
    <div class="stat-card azul">
        <div class="label">Total registrados</div>
        <div class="value">{{ $totales['total'] }}</div>
        <div class="unit">en el sistema</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:start">

    {{-- ── Tabla de ganaderos ──────────────────────────────────────────────── --}}
    <div>
        {{-- Filtros --}}
        <form method="GET" action="{{ route('admin.ganaderos.index') }}"
              style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;align-items:center">

            <input type="text" name="q" value="{{ $query }}"
                   placeholder="Buscar por nombre o DNI…"
                   style="flex:1;min-width:200px;padding:8px 12px;border:1.5px solid var(--border);
                          border-radius:var(--radius-sm);font-family:inherit;font-size:.85rem">

            <select name="ruta" style="padding:8px 12px;border:1.5px solid var(--border);
                                       border-radius:var(--radius-sm);font-family:inherit;font-size:.85rem">
                <option value="">Todas las rutas</option>
                @foreach($rutas as $r)
                <option value="{{ $r }}" {{ $ruta === $r ? 'selected' : '' }}>{{ $r }}</option>
                @endforeach
            </select>

            <select name="estado" style="padding:8px 12px;border:1.5px solid var(--border);
                                         border-radius:var(--radius-sm);font-family:inherit;font-size:.85rem">
                <option value="activo"   {{ $estado === 'activo'   ? 'selected' : '' }}>Solo activos</option>
                <option value="inactivo" {{ $estado === 'inactivo' ? 'selected' : '' }}>Solo inactivos</option>
                <option value="todos"    {{ $estado === 'todos'    ? 'selected' : '' }}>Todos</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">🔍 Filtrar</button>
            <a href="{{ route('admin.ganaderos.index') }}" class="btn btn-ghost btn-sm">Limpiar</a>
        </form>

        <div class="card">
            <div class="card-header">
                <h3>📋 Proveedores registrados</h3>
                <span class="card-header-meta">{{ $ganaderos->total() }} resultado(s)</span>
            </div>

            @if($ganaderos->isEmpty())
                <div style="padding:48px;text-align:center;color:var(--text-muted)">
                    <div style="font-size:2.5rem;margin-bottom:10px">🐄</div>
                    <div style="font-weight:600">Sin proveedores con estos filtros</div>
                </div>
            @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>DNI</th>
                            <th>Ruta asignada</th>
                            <th>Portal</th>
                            <th>Dirección</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ganaderos as $g)
                        <tr id="fila-{{ $g->id }}">
                            <td>
                                <strong>{{ $g->nombre }}</strong>
                            </td>
                            <td class="mono">{{ $g->dni }}</td>
                            <td>
                                <span class="tag">{{ $g->ruta_asignada }}</span>
                            </td>
                            <td style="font-size:.78rem;color:var(--text-muted);max-width:140px">
                                @if($g->user)
                                    <span class="mono" title="{{ $g->user->email }}">{{ $g->user->email }}</span>
                                @else
                                    <span style="color:var(--text-muted)">Sin acceso</span>
                                @endif
                            </td>
                            <td style="font-size:.82rem;color:var(--text-muted)">
                                {{ $g->direccion ?? '—' }}
                            </td>
                            <td>
                                @if($g->activo)
                                    <span class="badge badge-aprobado">● Activo</span>
                                @else
                                    <span class="badge badge-rechazado">● Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    {{-- Editar inline --}}
                                    <button class="btn btn-ghost btn-sm"
                                            onclick="abrirEditar({{ $g->id }}, '{{ addslashes($g->nombre) }}', '{{ $g->dni }}', '{{ addslashes($g->ruta_asignada) }}', '{{ addslashes($g->direccion ?? '') }}', {{ $g->zona_id ?? 'null' }}, {{ $g->geo_lat ?? 'null' }}, {{ $g->geo_lon ?? 'null' }})">
                                        ✏️ Editar
                                    </button>

                                    {{-- Acceso portal proveedor --}}
                                    <button class="btn btn-ghost btn-sm"
                                            style="color:var(--blue)"
                                            onclick="abrirAcceso({{ $g->id }}, '{{ addslashes($g->nombre) }}', {{ $g->user_id ? 'true' : 'false' }})">
                                        {{ $g->user_id ? '🔑 Clave' : '🌐 Portal' }}
                                    </button>

                                    {{-- Toggle activo/inactivo --}}
                                    <form method="POST"
                                          action="{{ route('admin.ganaderos.toggle', $g) }}"
                                          onsubmit="return confirm('¿{{ $g->activo ? 'Desactivar' : 'Activar' }} a {{ addslashes($g->nombre) }}?')">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm"
                                                style="{{ $g->activo
                                                    ? 'background:var(--red-bg);color:var(--red);border:1px solid #fecaca'
                                                    : 'background:var(--green-bg);color:var(--green);border:1px solid #bbf7d0' }}">
                                            {{ $g->activo ? '🚫 Desactivar' : '✓ Activar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 22px">{{ $ganaderos->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ── Panel derecho: Formulario nuevo + Editar ────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px;position:sticky;top:80px">

        {{-- Formulario nuevo proveedor --}}
        <div class="card" id="panelNuevo">
            <div class="card-header" style="background:var(--brand);border-radius:var(--radius) var(--radius) 0 0">
                <h3 style="color:#fff">➕ Nuevo proveedor</h3>
            </div>
            <div class="card-body" style="padding:18px">
                <form method="POST" action="{{ route('admin.ganaderos.store') }}">
                    @csrf
                    <div class="form-group" style="margin-bottom:12px">
                        <label for="nombre">Nombre completo <span class="req">*</span></label>
                        <input type="text" id="nombre" name="nombre"
                               required maxlength="150"
                               value="{{ old('nombre') }}"
                               placeholder="Ej: Juan Quispe Condori"
                               class="{{ $errors->has('nombre') ? 'invalid' : '' }}">
                        @error('nombre')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:12px">
                        <label for="dni">DNI <span class="req">*</span> <span class="hint">— 8 dígitos</span></label>
                        <input type="text" id="dni" name="dni"
                               required maxlength="8" pattern="\d{8}"
                               value="{{ old('dni') }}"
                               placeholder="12345678"
                               class="{{ $errors->has('dni') ? 'invalid' : '' }}">
                        @error('dni')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:12px">
                        <label for="ruta_asignada">Ruta asignada <span class="req">*</span></label>
                        <input type="text" id="ruta_asignada" name="ruta_asignada"
                               required maxlength="100"
                               value="{{ old('ruta_asignada') }}"
                               placeholder="Ej: Ruta Norte, Collana 1…"
                               list="rutas-list"
                               class="{{ $errors->has('ruta_asignada') ? 'invalid' : '' }}">
                        <datalist id="rutas-list">
                            @foreach($rutas as $r)
                            <option value="{{ $r }}">
                            @endforeach
                        </datalist>
                        @error('ruta_asignada')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:12px">
                        <label for="zona_id">Zona (Collana)</label>
                        <select id="zona_id" name="zona_id">
                            <option value="">Sin zona asignada</option>
                            @foreach($zonas as $z)
                            <option value="{{ $z->id }}" {{ old('zona_id') == $z->id ? 'selected' : '' }}>
                                {{ $z->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:12px">
                        <label>Ubicación GPS del predio <span class="hint">(opcional — el chofer también puede fijarla desde la app)</span></label>
                        <div style="display:flex;gap:8px">
                            <input type="number" step="any" name="geo_lat" value="{{ old('geo_lat') }}" placeholder="Latitud  -15.7000">
                            <input type="number" step="any" name="geo_lon" value="{{ old('geo_lon') }}" placeholder="Longitud -69.9900">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:16px">
                        <label for="direccion">Dirección / Referencia</label>
                        <input type="text" id="direccion" name="direccion"
                               maxlength="200"
                               value="{{ old('direccion') }}"
                               placeholder="Ej: Jr. Los Pinos 123">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        💾 Registrar proveedor
                    </button>
                </form>
            </div>
        </div>

        {{-- Panel de edición (oculto por defecto) --}}
        <div class="card" id="panelEditar" style="display:none">
            <div class="card-header" style="background:var(--blue);border-radius:var(--radius) var(--radius) 0 0">
                <h3 style="color:#fff">✏️ Editar proveedor</h3>
                <button onclick="cerrarEditar()" style="background:none;border:none;color:#fff;cursor:pointer;font-size:1.2rem">✕</button>
            </div>
            <div class="card-body" style="padding:18px">
                <form method="POST" id="formEditar" action="">
                    @csrf
                    <div class="form-group" style="margin-bottom:12px">
                        <label>Nombre completo <span class="req">*</span></label>
                        <input type="text" name="nombre" id="edit-nombre" required maxlength="150">
                    </div>
                    <div class="form-group" style="margin-bottom:12px">
                        <label>DNI <span class="req">*</span></label>
                        <input type="text" name="dni" id="edit-dni" required maxlength="8" pattern="\d{8}">
                    </div>
                    <div class="form-group" style="margin-bottom:12px">
                        <label>Ruta asignada <span class="req">*</span></label>
                        <input type="text" name="ruta_asignada" id="edit-ruta" required maxlength="100" list="rutas-list">
                    </div>
                    <div class="form-group" style="margin-bottom:12px">
                        <label>Zona</label>
                        <select name="zona_id" id="edit-zona">
                            <option value="">Sin zona</option>
                            @foreach($zonas as $z)
                            <option value="{{ $z->id }}">{{ $z->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:12px">
                        <label>Ubicación GPS del predio</label>
                        <div style="display:flex;gap:8px">
                            <input type="number" step="any" name="geo_lat" id="edit-lat" placeholder="Latitud">
                            <input type="number" step="any" name="geo_lon" id="edit-lon" placeholder="Longitud">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:16px">
                        <label>Dirección</label>
                        <input type="text" name="direccion" id="edit-direccion" maxlength="200">
                    </div>
                    <div style="display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">
                            💾 Guardar cambios
                        </button>
                        <button type="button" class="btn btn-ghost" onclick="cerrarEditar()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Info operativa --}}
        <div class="card">
            <div class="card-header"><h3>ℹ️ Guía rápida</h3></div>
            <div class="card-body" style="padding:14px 16px;font-size:.83rem;color:var(--text-muted);line-height:1.7">
                <p>• El <strong>nombre y ruta</strong> son los datos que verá el chofer en la app.</p>
                <p>• Usa <strong>Desactivar</strong> para dar de baja sin borrar el historial.</p>
                <p>• La <strong>zona (Collana)</strong> es para el sistema de rotación mensual.</p>
                <p>• El <strong>DNI</strong> debe ser único — identifica al proveedor en la boleta.</p>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    'use strict';

    var BASE_URL = "{{ url('/admin/ganaderos') }}";

    window.abrirEditar = function (id, nombre, dni, ruta, dir, zonaId, lat, lon) {
        document.getElementById('edit-lat').value = lat !== null && lat !== undefined ? lat : '';
        document.getElementById('edit-lon').value = lon !== null && lon !== undefined ? lon : '';
        document.getElementById('formEditar').action = BASE_URL + '/' + id + '/actualizar';
        document.getElementById('edit-nombre').value = nombre;
        document.getElementById('edit-dni').value    = dni;
        document.getElementById('edit-ruta').value   = ruta;
        document.getElementById('edit-direccion').value = dir;
        var sel = document.getElementById('edit-zona');
        sel.value = zonaId !== null ? String(zonaId) : '';
        document.getElementById('panelEditar').style.display = 'block';
        document.getElementById('panelNuevo').style.display  = 'none';
        document.getElementById('panelEditar').scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    window.cerrarEditar = function () {
        document.getElementById('panelEditar').style.display = 'none';
        document.getElementById('panelNuevo').style.display  = 'block';
    };

    // ── Modal acceso portal proveedor ─────────────────────────────────────────
    window.abrirAcceso = function (id, nombre, tieneAcceso) {
        document.getElementById('acceso-nombre').textContent = nombre;
        document.getElementById('acceso-titulo').textContent = tieneAcceso
            ? '🔑 Cambiar contraseña del portal'
            : '🌐 Crear acceso al portal';
        document.getElementById('formAcceso').action = BASE_URL + '/' + id + '/crear-acceso';
        document.getElementById('modalAcceso').style.display = 'flex';
    };
    window.cerrarAcceso = function () {
        document.getElementById('modalAcceso').style.display = 'none';
        document.getElementById('acceso-password').value = '';
    };
}());
</script>

{{-- Modal acceso portal --}}
<div id="modalAcceso"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
            z-index:1000;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:24px;width:360px;box-shadow:0 8px 32px rgba(0,0,0,.2)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 id="acceso-titulo" style="font-size:1rem;color:#0f3d1a">🌐 Crear acceso al portal</h3>
            <button onclick="cerrarAcceso()" style="background:none;border:none;cursor:pointer;font-size:1.2rem">✕</button>
        </div>
        <p style="font-size:.85rem;color:#6b8f6b;margin-bottom:14px">
            Proveedor: <strong id="acceso-nombre"></strong>
        </p>
        <p style="font-size:.82rem;color:#6b8f6b;margin-bottom:16px;background:#e8f5e9;
                  padding:8px 12px;border-radius:6px">
            El proveedor podrá ver su litraje, calidad y pagos en
            <strong>{{ url('/proveedor/dashboard') }}</strong>
        </p>
        <form method="POST" id="formAcceso" action="">
            @csrf
            <div class="form-group" style="margin-bottom:14px">
                <label class="form-label">Contraseña de acceso *</label>
                <input type="text" id="acceso-password" name="password"
                       class="form-control" required minlength="6"
                       placeholder="Mínimo 6 caracteres">
                <small style="color:#6b8f6b">Comparte esta contraseña con el proveedor.</small>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary" style="flex:1">Guardar</button>
                <button type="button" class="btn btn-ghost" onclick="cerrarAcceso()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

@endsection
