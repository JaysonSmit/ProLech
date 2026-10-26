@extends('layouts.admin')

@section('title', 'Solicitudes de Ruta')
@section('page-title', '🛣️ Gestión de Solicitudes de Ruta')

@section('content')
<style>
    /* ── Modal de respuesta ────────────────────────────────────────────────── */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,.5);
        backdrop-filter: blur(3px);
        z-index: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity .2s;
    }

    .modal-overlay.abierto {
        opacity: 1;
        pointer-events: all;
    }

    .modal {
        background: var(--blanco);
        border-radius: 14px;
        width: 100%;
        max-width: 480px;
        padding: 28px;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
        transform: translateY(16px);
        transition: transform .2s;
    }

    .modal-overlay.abierto .modal { transform: translateY(0); }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .modal-header h3 { font-size: 1.05rem; font-weight: 700; }

    .btn-close-modal {
        background: none;
        border: none;
        font-size: 1.3rem;
        cursor: pointer;
        color: var(--texto-suave);
        padding: 4px;
        line-height: 1;
    }

    /* ── Filtros de tab ────────────────────────────────────────────────────── */
    .tabs {
        display: flex;
        gap: 4px;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        margin-bottom: 20px;
        width: fit-content;
    }

    .tab-btn {
        padding: 8px 18px;
        border: none;
        border-radius: 7px;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        background: transparent;
        color: var(--texto-suave);
        text-decoration: none;
        transition: background .15s, color .15s;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .tab-btn:hover { background: rgba(255,255,255,.6); color: var(--texto); }

    .tab-btn.activo {
        background: var(--blanco);
        color: var(--azul);
        box-shadow: 0 1px 4px rgba(0,0,0,.1);
    }

    .badge-count {
        background: var(--azul);
        color: #fff;
        border-radius: 12px;
        font-size: .68rem;
        padding: 1px 7px;
        min-width: 20px;
        text-align: center;
    }

    .badge-count.naranja { background: var(--naranja); }
    .badge-count.rojo    { background: var(--rojo); }
    .badge-count.verde   { background: var(--verde); }

    /* ── Tabla de solicitudes ──────────────────────────────────────────────── */
    .ruta-arrow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: .88rem;
    }

    .ruta-arrow .from { color: var(--texto-suave); }
    .ruta-arrow .arrow { color: var(--azul); font-weight: 700; }
    .ruta-arrow .to { color: var(--azul); font-weight: 600; }

    .motivo-cell {
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--texto-suave);
        font-size: .82rem;
    }

    .btn-aprobar {
        background: var(--verde);
        color: #fff;
        padding: 6px 14px;
        border-radius: 7px;
        font-size: .8rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-aprobar:hover { background: #15803d; }

    .btn-rechazar {
        background: var(--rojo);
        color: #fff;
        padding: 6px 14px;
        border-radius: 7px;
        font-size: .8rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-rechazar:hover { background: #b91c1c; }

    .acciones { display: flex; gap: 6px; }
</style>

{{-- ── Tabs de filtro por estado ──────────────────────────────────────────── --}}
<div class="tabs">
    <a href="{{ route('admin.rutas.solicitudes', ['estado' => 'pendiente']) }}"
       class="tab-btn {{ $estado === 'pendiente' ? 'activo' : '' }}">
        ⏳ Pendientes
        <span class="badge-count naranja">{{ $contadores['pendiente'] }}</span>
    </a>
    <a href="{{ route('admin.rutas.solicitudes', ['estado' => 'aprobado']) }}"
       class="tab-btn {{ $estado === 'aprobado' ? 'activo' : '' }}">
        ✅ Aprobadas
        <span class="badge-count verde">{{ $contadores['aprobado'] }}</span>
    </a>
    <a href="{{ route('admin.rutas.solicitudes', ['estado' => 'rechazado']) }}"
       class="tab-btn {{ $estado === 'rechazado' ? 'activo' : '' }}">
        ❌ Rechazadas
        <span class="badge-count rojo">{{ $contadores['rechazado'] }}</span>
    </a>
    <a href="{{ route('admin.rutas.solicitudes', ['estado' => 'todas']) }}"
       class="tab-btn {{ $estado === 'todas' ? 'activo' : '' }}">
        📋 Todas
    </a>
</div>

{{-- ── Tabla principal ─────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <h3>
            @if($estado === 'pendiente') ⏳ Solicitudes pendientes de revisión
            @elseif($estado === 'aprobado') ✅ Solicitudes aprobadas
            @elseif($estado === 'rechazado') ❌ Solicitudes rechazadas
            @else 📋 Todas las solicitudes
            @endif
        </h3>
        <span style="font-size:.8rem;color:var(--texto-suave)">
            {{ $solicitudes->total() }} registro(s)
        </span>
    </div>
    <div class="card-body" style="padding:0">
        @if($solicitudes->isEmpty())
            <div style="padding:40px;text-align:center;color:var(--texto-suave)">
                @if($estado === 'pendiente')
                    ✅ Sin solicitudes pendientes. Todo al día.
                @else
                    Sin registros para este filtro.
                @endif
            </div>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Chofer</th>
                        <th>Cambio de ruta</th>
                        <th>Motivo</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Revisado por</th>
                        @if($estado === 'pendiente' || $estado === 'todas')
                        <th>Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($solicitudes as $s)
                    <tr>
                        <td style="color:var(--texto-suave);font-size:.8rem">{{ $s->id }}</td>

                        <td>
                            <div style="font-weight:600">{{ $s->chofer->name ?? '—' }}</div>
                            <div style="font-size:.75rem;color:var(--texto-suave)">
                                {{ $s->chofer->email ?? '' }}
                            </div>
                        </td>

                        <td>
                            <div class="ruta-arrow">
                                <span class="from">{{ $s->estado === 'pendiente' ? ($rutasDelMes[$s->chofer_id] ?? $s->ruta_actual ?? 'Sin asignar') : ($s->ruta_actual ?? 'Sin asignar') }}</span>
                                <span class="arrow">→</span>
                                <span class="to">{{ $s->ruta_solicitada }}</span>
                            </div>
                        </td>

                        <td>
                            <div class="motivo-cell" title="{{ $s->motivo }}">
                                {{ $s->motivo ?? '—' }}
                            </div>
                        </td>

                        <td style="white-space:nowrap;font-size:.82rem">
                            {{ $s->fecha_solicitud ? $s->fecha_solicitud->format('d/m/Y') : $s->created_at->format('d/m/Y') }}
                        </td>

                        <td>
                            @if($s->estado === 'pendiente')
                                <span class="badge badge-observado">Pendiente</span>
                            @elseif($s->estado === 'aprobado')
                                <span class="badge badge-aprobado">Aprobada</span>
                            @else
                                <span class="badge badge-rechazado">Rechazada</span>
                            @endif
                        </td>

                        <td style="font-size:.82rem;color:var(--texto-suave)">
                            @if($s->revisor)
                                {{ $s->revisor->name }}
                                <div style="font-size:.72rem">{{ $s->revisado_at?->format('d/m/Y H:i') }}</div>
                            @else
                                —
                            @endif
                        </td>

                        @if($estado === 'pendiente' || $estado === 'todas')
                        <td>
                            @if($s->esPendiente())
                            <div class="acciones">
                                <button class="btn-aprobar"
                                        onclick="abrirModal({{ $s->id }}, '{{ addslashes($s->chofer->name ?? '') }}', '{{ addslashes($s->ruta_solicitada) }}', 'aprobado')">
                                    ✅ Aprobar
                                </button>
                                <button class="btn-rechazar"
                                        onclick="abrirModal({{ $s->id }}, '{{ addslashes($s->chofer->name ?? '') }}', '{{ addslashes($s->ruta_solicitada) }}', 'rechazado')">
                                    ❌ Rechazar
                                </button>
                            </div>
                            @else
                                <span style="font-size:.78rem;color:var(--texto-suave)">Procesada</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding:16px 22px">
            {{ $solicitudes->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ── Modal de confirmación de respuesta ─────────────────────────────────── --}}
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitulo">Confirmar acción</h3>
            <button class="btn-close-modal" onclick="cerrarModal()">✕</button>
        </div>

        <div style="margin-bottom:18px;font-size:.9rem;color:var(--texto)">
            <div id="modalDesc" style="margin-bottom:12px"></div>
        </div>

        <form method="POST" id="formRespuesta">
            @csrf
            <input type="hidden" name="accion" id="inputAccion">

            <div class="form-group" style="margin-bottom:18px">
                <label for="nota_respuesta">Nota para el chofer (opcional)</label>
                <textarea id="nota_respuesta" name="nota_respuesta"
                          rows="3" maxlength="300"
                          placeholder="Ej: Se reasigna por mayor volumen en esa zona…"></textarea>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn btn-sm"
                        style="background:var(--gris-borde);color:var(--texto)"
                        onclick="cerrarModal()">
                    Cancelar
                </button>
                <button type="submit" class="btn" id="btnConfirmar">
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    'use strict';

    var BASE_URL = "{{ route('admin.rutas.responder', ['solicitud' => '__ID__']) }}";

    window.abrirModal = function (id, chofer, rutaSolicitada, accion) {
        var esAprobado = accion === 'aprobado';

        document.getElementById('modalTitulo').textContent =
            esAprobado ? '✅ Aprobar solicitud' : '❌ Rechazar solicitud';

        document.getElementById('modalDesc').innerHTML =
            '<strong>' + chofer + '</strong> solicita cambio a la ruta ' +
            '<strong>"' + rutaSolicitada + '"</strong>.<br>' +
            '<span style="color:' + (esAprobado ? 'var(--verde)' : 'var(--rojo)') + ';font-weight:600">' +
            (esAprobado ? 'Se notificará al chofer que su solicitud fue APROBADA.' :
                          'Se notificará al chofer que su solicitud fue RECHAZADA.') +
            '</span>';

        document.getElementById('inputAccion').value = accion;

        var btnConfirmar = document.getElementById('btnConfirmar');
        if (esAprobado) {
            btnConfirmar.textContent = '✅ Confirmar aprobación';
            btnConfirmar.style.background = 'var(--verde)';
            btnConfirmar.style.color = '#fff';
        } else {
            btnConfirmar.textContent = '❌ Confirmar rechazo';
            btnConfirmar.style.background = 'var(--rojo)';
            btnConfirmar.style.color = '#fff';
        }

        // Actualizar la action del form con el ID real
        var url = BASE_URL.replace('__ID__', id);
        document.getElementById('formRespuesta').setAttribute('action', url);
        document.getElementById('nota_respuesta').value = '';

        document.getElementById('modalOverlay').classList.add('abierto');
    };

    window.cerrarModal = function () {
        document.getElementById('modalOverlay').classList.remove('abierto');
    };

    // Cerrar con Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') window.cerrarModal();
    });

    // Cerrar al hacer click fuera del modal
    document.getElementById('modalOverlay').addEventListener('click', function (e) {
        if (e.target === this) window.cerrarModal();
    });
}());
</script>

@endsection
