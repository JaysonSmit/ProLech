@extends('layouts.admin')

@section('title', 'Capacitaciones ATOS')
@section('page-title', '📚 Capacitaciones y visitas ATOS lecheros')

@section('content')

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

<div class="stats-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:20px;max-width:480px">
    <div class="stat-card azul">
        <div class="label">Capacitaciones este mes</div>
        <div class="value">{{ $stats['mes_capacitaciones'] }}</div>
    </div>
    <div class="stat-card verde">
        <div class="label">Visitas a ATOS este mes</div>
        <div class="value">{{ $stats['mes_visitas'] }}</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:start">

    <div class="card">
        <div class="card-header"><h3>Registro histórico</h3></div>
        @if($registros->isEmpty())
        <div style="padding:32px;text-align:center;color:var(--text-muted)">Sin registros.</div>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Proveedor / ATOS</th>
                        <th>Tema</th>
                        <th>Asistentes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registros as $r)
                    <tr>
                        <td>{{ $r->fecha->format('d/m/Y') }}</td>
                        <td>{{ $r->tipo === 'visita' ? '🏠 Visita' : '📖 Capacitación' }}</td>
                        <td>{{ $r->ganadero?->nombre ?? 'General / varios' }}</td>
                        <td>{{ $r->tema }}</td>
                        <td>{{ $r->asistentes }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:14px">{{ $registros->links() }}</div>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><h3>➕ Nuevo registro</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.calidad.capacitaciones.store') }}">
                @csrf
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-control" required>
                        <option value="capacitacion">Capacitación grupal</option>
                        <option value="visita">Visita a ATOS lechero</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Proveedor (opcional)</label>
                    <select name="ganadero_id" class="form-control">
                        <option value="">— General / varios —</option>
                        @foreach($ganaderos as $g)
                        <option value="{{ $g->id }}">{{ $g->nombre }} ({{ $g->dni }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Tema</label>
                    <input type="text" name="tema" class="form-control" required maxlength="200"
                           placeholder="Ej: Higiene del ordeño, uso del lactoscan">
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">N° asistentes</label>
                    <input type="number" name="asistentes" class="form-control" min="1" value="1" required>
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Guardar</button>
            </form>
        </div>
    </div>
</div>

@endsection
