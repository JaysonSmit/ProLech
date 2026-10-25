@extends('layouts.admin')

@section('title', 'Auditoría y Trazabilidad')
@section('page-title', '🛡️ Trazabilidad y Auditoría')

@section('content')

{{-- ── Métricas y Filtros ─────────────────────────────────────────────────────── --}}
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card azul">
        <div class="label">Total Eventos Registrados</div>
        <div class="value">{{ $auditorias->total() }}</div>
        <div class="sub">Trazabilidad completa de operaciones</div>
    </div>
    <div class="stat-card verde">
        <div class="label">Módulos Auditados</div>
        <div class="value">{{ $entidades->count() }}</div>
        <div class="sub">Ventas, Despachos, Calidad, etc.</div>
    </div>
    <div class="stat-card naranja">
        <div class="label">Tipos de Acciones</div>
        <div class="value">{{ $acciones->count() }}</div>
        <div class="sub">Modificaciones y transiciones</div>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" action="{{ route('admin.auditorias.index') }}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center">
            <div style="font-weight:600;font-size:.85rem;color:var(--text-muted)">Filtrar registros:</div>

            <select name="entidad" style="padding:6px 12px;border:1px solid var(--border);border-radius:6px;font-size:.85rem" onchange="this.form.submit()">
                <option value="">— Toda entidad —</option>
                @foreach($entidades as $ent)
                    <option value="{{ $ent }}" {{ $entidadFiltro === $ent ? 'selected' : '' }}>{{ $ent }}</option>
                @endforeach
            </select>

            <select name="accion" style="padding:6px 12px;border:1px solid var(--border);border-radius:6px;font-size:.85rem" onchange="this.form.submit()">
                <option value="">— Toda acción —</option>
                @foreach($acciones as $acc)
                    <option value="{{ $acc }}" {{ $accionFiltro === $acc ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($acc)) }}</option>
                @endforeach
            </select>

            @if($entidadFiltro || $accionFiltro)
                <a href="{{ route('admin.auditorias.index') }}" class="btn btn-sm" style="background:var(--border);color:var(--text)">Limpiar filtros</a>
            @endif
        </form>
    </div>
</div>

{{-- ── Tabla de Auditorías ──────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <h3>📋 Registro Inmutable de Auditoría</h3>
        <span class="card-header-meta">{{ $auditorias->total() }} eventos registrados</span>
    </div>
    <div class="card-body" style="padding:0">
        @if($auditorias->isEmpty())
            <div style="text-align:center;padding:40px 20px;color:var(--text-muted)">
                <div style="font-size:2.2rem;margin-bottom:8px">🛡️</div>
                <div style="font-weight:600">No se encontraron eventos de auditoría</div>
                <div style="font-size:.85rem;margin-top:4px">Las acciones del sistema se registrarán automáticamente aquí.</div>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Usuario</th>
                            <th>Módulo / Entidad</th>
                            <th>Acción</th>
                            <th>Cambio Realizado</th>
                            <th>Motivo / Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auditorias as $auditoria)
                        <tr>
                            <td style="white-space:nowrap;font-size:.82rem">
                                <strong>{{ $auditoria->created_at->format('d/m/Y') }}</strong>
                                <span style="color:var(--text-muted);display:block;font-size:.75rem">{{ $auditoria->created_at->format('H:i:s') }}</span>
                            </td>
                            <td>
                                <strong>{{ $auditoria->usuario->name ?? 'Sistema' }}</strong>
                                @if($auditoria->usuario)
                                    <span style="display:block;font-size:.72rem;color:var(--text-muted)">
                                        Rol: {{ \App\Support\RolWeb::etiquetas()[$auditoria->usuario->rol] ?? $auditoria->usuario->rol }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span style="font-family:monospace;background:var(--bg);padding:3px 8px;border-radius:4px;border:1px solid var(--border);font-size:.82rem">
                                    {{ $auditoria->entidad_tipo }} #{{ $auditoria->entidad_id }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $badgeStyle = match($auditoria->accion) {
                                        'creacion'                => 'background:#dcfce7;color:#166534',
                                        'cambio_estado_despacho' => 'background:#e0f2fe;color:#0369a1',
                                        'modificacion', 'edicion' => 'background:#fef3c7;color:#92400e',
                                        'eliminacion'             => 'background:#fee2e2;color:#991b1b',
                                        default                   => 'background:var(--bg);color:var(--text)',
                                    };
                                @endphp
                                <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;{{ $badgeStyle }}">
                                    {{ str_replace('_', ' ', ucfirst($auditoria->accion)) }}
                                </span>
                            </td>
                            <td style="font-size:.82rem">
                                @if($auditoria->campo_modificado)
                                    <div><strong>Campo:</strong> <code>{{ $auditoria->campo_modificado }}</code></div>
                                    @if($auditoria->valor_anterior)
                                        <div style="color:var(--red);text-decoration:line-through;font-size:.75rem">
                                            Ant: {{ $auditoria->valor_anterior }}
                                        </div>
                                    @endif
                                    @if($auditoria->valor_nuevo)
                                        <div style="color:var(--green);font-weight:600;font-size:.78rem">
                                            Nuevo: {{ $auditoria->valor_nuevo }}
                                        </div>
                                    @endif
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td style="font-size:.85rem;max-width:280px">
                                {{ $auditoria->motivo ?? '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding:16px 20px;border-top:1px solid var(--border)">
                {{ $auditorias->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
