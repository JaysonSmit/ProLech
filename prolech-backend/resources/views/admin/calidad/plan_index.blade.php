@extends('layouts.admin')

@section('title', 'Plan de Calidad — CONFIDENCIAL')
@section('page-title', '🔒 Plan de Control de Calidad — CONFIDENCIAL')

@section('content')

{{-- Aviso confidencial --}}
<div style="background:#fff3cd;border:1.5px solid #ffc107;border-radius:8px;
            padding:12px 18px;margin-bottom:22px;display:flex;align-items:center;gap:10px">
    <span style="font-size:1.4rem">🔒</span>
    <div>
        <strong style="color:#664d03">Información estrictamente confidencial</strong>
        <div style="font-size:.82rem;color:#664d03;margin-top:2px">
            Este plan NO debe comunicarse a acopiadores ni proveedores.
            El control de calidad es <strong>inopinado</strong> para garantizar resultados reales.
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:18px">{{ session('success') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:start">

    {{-- ── Lista de planes ──────────────────────────────────────────────────── --}}
    <div>
        {{-- Próximos operativos --}}
        @if($proximos->isNotEmpty())
        <div class="card" style="margin-bottom:18px;border:1.5px solid #ffc107">
            <div class="card-header" style="background:#fff8e1">
                <h3 style="color:#b45309">📅 Próximos operativos programados</h3>
                <span style="font-size:.8rem;color:#b45309">{{ $proximos->count() }} pendiente(s)</span>
            </div>
            <div class="card-body" style="padding:0">
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:#fff8e1">
                            <th style="padding:8px 14px;font-size:.78rem;color:#b45309;font-weight:700;text-align:left">FECHA</th>
                            <th style="padding:8px 14px;font-size:.78rem;color:#b45309;font-weight:700;text-align:left">RUTA OBJETIVO</th>
                            <th style="padding:8px 14px;font-size:.78rem;color:#b45309;font-weight:700;text-align:left">RESPONSABLE</th>
                            <th style="padding:8px 14px;font-size:.78rem;color:#b45309;font-weight:700;text-align:center">EQUIPOS</th>
                            <th style="padding:8px 14px;font-size:.78rem;color:#b45309;font-weight:700">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proximos as $p)
                        <tr style="border-bottom:1px solid #fde68a">
                            <td style="padding:9px 14px;font-weight:700;font-size:.88rem">
                                {{ $p->fecha->isoFormat('ddd D MMM YYYY') }}
                            </td>
                            <td style="padding:9px 14px;font-size:.88rem">{{ $p->ruta_objetivo }}</td>
                            <td style="padding:9px 14px;font-size:.88rem;color:var(--text-muted)">
                                {{ $p->responsable ?? '—' }}
                            </td>
                            <td style="padding:9px 14px;text-align:center">
                                @for($i=0;$i<$p->num_equipos_lactoscan;$i++) 🔬 @endfor
                            </td>
                            <td style="padding:9px 14px">
                                <form method="POST"
                                      action="{{ route('admin.calidad.plan.ejecutado', $p) }}"
                                      style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm"
                                            style="background:var(--green-bg);color:var(--green);
                                                   border:1px solid #bbf7d0">
                                        ✓ Marcar ejecutado
                                    </button>
                                </form>
                                <form method="POST"
                                      action="{{ route('admin.calidad.plan.destroy', $p) }}"
                                      style="display:inline"
                                      onsubmit="return confirm('¿Eliminar este plan?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger-ghost">✕</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Historial --}}
        <div class="card">
            <div class="card-header">
                <h3>📋 Historial de operativos</h3>
                <span class="card-header-meta">{{ $planes->total() }} registro(s)</span>
            </div>
            <div class="card-body" style="padding:0">
                @if($planes->isEmpty())
                <p style="padding:24px;text-align:center;color:var(--text-muted)">
                    Sin planes registrados aún.
                </p>
                @else
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:var(--bg)">
                            <th style="padding:8px 14px;font-size:.78rem;color:var(--text-muted);font-weight:600;text-align:left">FECHA</th>
                            <th style="padding:8px 14px;font-size:.78rem;color:var(--text-muted);font-weight:600;text-align:left">RUTA</th>
                            <th style="padding:8px 14px;font-size:.78rem;color:var(--text-muted);font-weight:600;text-align:left">RESPONSABLE</th>
                            <th style="padding:8px 14px;font-size:.78rem;color:var(--text-muted);font-weight:600;text-align:center">ESTADO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($planes as $p)
                        <tr style="border-bottom:1px solid var(--border)">
                            <td style="padding:9px 14px;font-size:.88rem;font-weight:600">
                                {{ $p->fecha->format('d/m/Y') }}
                            </td>
                            <td style="padding:9px 14px;font-size:.88rem">{{ $p->ruta_objetivo }}</td>
                            <td style="padding:9px 14px;font-size:.85rem;color:var(--text-muted)">
                                {{ $p->responsable ?? '—' }}
                            </td>
                            <td style="padding:9px 14px;text-align:center">
                                @if($p->ejecutado)
                                    <span class="badge badge-aprobado">Ejecutado</span>
                                @else
                                    <span class="badge" style="background:var(--amber-bg);color:#b45309">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                        @if($p->resultado_resumen)
                        <tr style="background:var(--bg)">
                            <td colspan="4" style="padding:6px 14px 10px;font-size:.8rem;color:var(--text-muted)">
                                📝 {{ $p->resultado_resumen }}
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
                <div style="padding:12px 16px">{{ $planes->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Formulario nuevo plan ─────────────────────────────────────────────── --}}
    <div class="card" style="border:1.5px solid #ffc107">
        <div class="card-header" style="background:#fff8e1">
            <h3 style="color:#b45309">🔒 Programar nuevo operativo</h3>
        </div>
        <div class="card-body" style="padding:18px">
            <form method="POST" action="{{ route('admin.calidad.plan.store') }}">
                @csrf
                <div style="display:flex;flex-direction:column;gap:14px">

                    <div class="form-group">
                        <label class="form-label">Fecha del operativo *</label>
                        <input type="date" name="fecha" class="form-control"
                               min="{{ now()->toDateString() }}" required>
                        <small style="color:#b45309">No comunicar esta fecha a acopiadores ni proveedores.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ruta / Zona objetivo *</label>
                        <input type="text" name="ruta_objetivo" class="form-control"
                               placeholder="Ej: Ruta Norte — Sector Huata" required maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Personal de calidad responsable</label>
                        <input type="text" name="responsable" class="form-control"
                               placeholder="Nombre del responsable" maxlength="150">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Equipos LactoScan a llevar (1–3)</label>
                        <select name="num_equipos_lactoscan" class="form-control">
                            <option value="1">1 equipo</option>
                            <option value="2">2 equipos</option>
                            <option value="3">3 equipos (todos)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Observaciones internas</label>
                        <textarea name="observaciones" class="form-control" rows="3"
                                  placeholder="Notas para el equipo de calidad..." maxlength="500"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%">
                        🔒 Programar operativo confidencial
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
