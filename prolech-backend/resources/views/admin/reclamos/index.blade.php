@extends('layouts.admin')

@section('title', 'Reclamos de litraje')
@section('page-title', '📢 Reclamos de litraje (mismo día)')

@section('content')

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif

<div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
    @foreach(['pendiente' => 'Pendientes', 'atendido' => 'Atendidos', 'rechazado' => 'Rechazados', 'todos' => 'Todos'] as $val => $label)
    <a href="{{ route('admin.reclamos.index', ['estado' => $val]) }}"
       class="btn btn-sm {{ $estado === $val ? 'btn-primary' : 'btn-ghost' }}">
        {{ $label }}
        @if($val !== 'todos' && isset($conteo[$val]))
            ({{ $conteo[$val] }})
        @endif
    </a>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <h3>Reclamos de proveedores</h3>
        <span class="card-header-meta">{{ $reclamos->total() }} registro(s)</span>
    </div>

    @if($reclamos->isEmpty())
    <div style="padding:40px;text-align:center;color:var(--text-muted)">
        No hay reclamos en este filtro.
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Registrado</th>
                    <th>Reclamado</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reclamos as $r)
                <tr>
                    <td>{{ $r->fecha_reclamo->format('d/m/Y H:i') }}<br>
                        <span style="font-size:.72rem;color:var(--text-muted)">{{ $r->created_at->format('H:i') }}</span>
                    </td>
                    <td><strong>{{ $r->ganadero->nombre }}</strong><br>
                        <span class="mono" style="font-size:.78rem">{{ $r->ganadero->dni }}</span>
                    </td>
                    <td>{{ number_format($r->litros_registrados, 1) }} L</td>
                    <td style="font-weight:700;color:var(--brand-light)">{{ number_format($r->litros_correctos, 1) }} L</td>
                    <td style="max-width:220px;font-size:.82rem">{{ Str::limit($r->motivo, 120) }}</td>
                    <td>
                        @if($r->estado === 'pendiente')
                            <span class="badge badge-observado">Pendiente</span>
                        @elseif($r->estado === 'atendido')
                            <span class="badge badge-aprobado">Atendido</span>
                        @else
                            <span class="badge badge-rechazado">Rechazado</span>
                        @endif
                    </td>
                    <td>
                        @if($r->estado === 'pendiente')
                        <details>
                            <summary class="btn btn-sm btn-primary" style="cursor:pointer;list-style:none">Responder</summary>
                            <form method="POST" action="{{ route('admin.reclamos.responder', $r) }}"
                                  style="margin-top:10px;padding:12px;background:var(--bg-soft);border-radius:8px;min-width:260px">
                                @csrf
                                <div class="form-group" style="margin-bottom:10px">
                                    <label class="form-label">Decisión</label>
                                    <select name="estado" class="form-control" required>
                                        <option value="atendido">Atendido — corregir registro</option>
                                        <option value="rechazado">Rechazar reclamo</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom:10px">
                                    <label class="form-label">Respuesta al proveedor</label>
                                    <textarea name="respuesta_admin" rows="3" class="form-control" required
                                              placeholder="Indica qué se hará con el acopio #{{ $r->acopio_id }}"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Guardar respuesta</button>
                            </form>
                        </details>
                        @else
                            <span style="font-size:.78rem;color:var(--text-muted)">
                                {{ $r->atendidoPor->name ?? '—' }}<br>
                                {{ $r->atendido_at?->format('d/m H:i') }}
                            </span>
                        @endif
                    </td>
                </tr>
                @if($r->respuesta_admin && $r->estado !== 'pendiente')
                <tr>
                    <td colspan="7" style="font-size:.78rem;background:var(--bg-soft);padding:8px 14px;color:var(--text-muted)">
                        💬 {{ $r->respuesta_admin }}
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:14px 22px">{{ $reclamos->links() }}</div>
    @endif
</div>

@endsection
