@extends('layouts.admin')

@section('title', 'Calidad en planta')
@section('page-title', '🏭 Control de calidad en planta — por lote de tachos')

@section('content')

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

@php $cmp = $comparacionCampo; @endphp
<div class="card" style="margin-bottom:18px;border-left:4px solid {{ abs($cmp['diferencia']) > max(1, $cmp['litros_campo'] * 0.05) ? 'var(--amber)' : 'var(--green)' }}">
    <div class="card-header">
        <h3>⚖️ Campo vs planta (hoy)</h3>
    </div>
    <div class="card-body" style="display:flex;gap:24px;flex-wrap:wrap;font-size:.88rem">
        <div><span style="color:var(--text-muted)">Litros acopiados en ruta</span><br><strong>{{ number_format($cmp['litros_campo'], 1) }} L</strong></div>
        <div><span style="color:var(--text-muted)">Litros controlados en planta</span><br><strong>{{ number_format($cmp['litros_planta'], 1) }} L</strong></div>
        <div><span style="color:var(--text-muted)">Diferencia</span><br><strong style="color:{{ $cmp['diferencia'] >= 0 ? 'var(--brand-light)' : 'var(--red)' }}">{{ number_format($cmp['diferencia'], 1) }} L</strong></div>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
    <div class="stat-card azul">
        <div class="label">Lotes hoy</div>
        <div class="value">{{ $resumenHoy['lotes'] }}</div>
    </div>
    <div class="stat-card verde">
        <div class="label">Tachos revisados hoy</div>
        <div class="value">{{ $resumenHoy['tachos'] }}</div>
    </div>
    <div class="stat-card naranja">
        <div class="label">Litros controlados hoy</div>
        <div class="value">{{ number_format($resumenHoy['litros'], 0) }}</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:start">

    <div class="card">
        <div class="card-header">
            <h3>Historial de lotes</h3>
        </div>
        @if($lotes->isEmpty())
        <div style="padding:32px;text-align:center;color:var(--text-muted)">Sin registros aún.</div>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Lote</th>
                        <th>Tachos</th>
                        <th>Litros</th>
                        <th>Equipo</th>
                        <th>% Agua</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lotes as $l)
                    <tr>
                        <td>{{ $l->fecha->format('d/m/Y') }}</td>
                        <td class="mono"><strong>{{ $l->codigo_lote }}</strong></td>
                        <td>{{ $l->cantidad_tachos }}</td>
                        <td>{{ number_format($l->litros_total, 1) }} L</td>
                        <td>{{ $l->equipo_lactoscan ? '🔬 '.$l->equipo_lactoscan : '—' }}</td>
                        <td>{{ $l->agua_promedio !== null ? number_format($l->agua_promedio, 2).'%' : '—' }}</td>
                        <td><span class="badge badge-{{ $l->resultado === 'aprobado' ? 'aprobado' : ($l->resultado === 'observado' ? 'observado' : 'rechazado') }}">{{ ucfirst($l->resultado) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:14px">{{ $lotes->links() }}</div>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><h3>➕ Nuevo control por lote</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.calidad.planta.store') }}">
                @csrf
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" class="form-control" value="{{ old('fecha', now()->toDateString()) }}" required>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Código de lote</label>
                    <input type="text" name="codigo_lote" class="form-control" placeholder="Ej: LOTE-20260914-A" required maxlength="40">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                    <div class="form-group">
                        <label class="form-label">Cant. tachos</label>
                        <input type="number" name="cantidad_tachos" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Litros total</label>
                        <input type="number" name="litros_total" class="form-control" step="0.1" min="0.1" required>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Equipo LactoScan</label>
                    <select name="equipo_lactoscan" class="form-control">
                        <option value="">—</option>
                        @for($i=1;$i<=3;$i++)<option value="{{ $i }}">Equipo {{ $i }}</option>@endfor
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                    <div class="form-group">
                        <label class="form-label">% agua prom.</label>
                        <input type="number" name="agua_promedio" class="form-control" step="0.01" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">pH prom.</label>
                        <input type="number" name="ph_promedio" class="form-control" step="0.01" min="0" max="14">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Resultado</label>
                    <select name="resultado" class="form-control" required>
                        <option value="aprobado">Aprobado</option>
                        <option value="observado">Observado</option>
                        <option value="rechazado">Rechazado</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Guardar control</button>
            </form>
        </div>
    </div>
</div>

@endsection
