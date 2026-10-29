@extends('layouts.admin')

@section('title', 'Comunicados Masivos')
@section('page-title', '📢 Comunicados Digitales a Proveedores')

@section('content')

{{-- ── Métricas de Difusión ────────────────────────────────────────────────── --}}
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 22px;">
    <div class="stat-card verde">
        <div class="label">Proveedores Destinatarios</div>
        <div class="value">{{ $stats['total_ganaderos'] }}</div>
        <div class="unit">en padrón activo</div>
    </div>
    <div class="stat-card azul">
        <div class="label">Con Teléfono / WhatsApp</div>
        <div class="value">{{ $stats['con_telefono'] }}</div>
        <div class="unit">reciben mensaje directo</div>
    </div>
    <div class="stat-card amber">
        <div class="label">Comunicados Emitidos</div>
        <div class="value">{{ $stats['total_comunicados'] }}</div>
        <div class="unit">en el historial</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 24px; align-items: start;">

    {{-- ── Formulario: Redactar Comunicado ────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h3>✍️ Emitir Nuevo Comunicado</h3>
        </div>
        <div class="card-body" style="padding: 20px;">
            <form method="POST" action="{{ route('admin.comunicados.store') }}">
                @csrf

                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="titulo">Título del Comunicado <span class="req">*</span></label>
                    <input type="text" id="titulo" name="titulo" class="form-control"
                           placeholder="Ej. Aviso de lluvias: nuevo horario de acopio ruta norte"
                           value="{{ old('titulo') }}" required maxlength="150">
                    @error('titulo') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="tipo">Tipo / Categoría <span class="req">*</span></label>
                    <select id="tipo" name="tipo" class="form-control" required>
                        <option value="general" {{ old('tipo') === 'general' ? 'selected' : '' }}>📢 General (Avisos de operación o ruta)</option>
                        <option value="urgente" {{ old('tipo') === 'urgente' ? 'selected' : '' }}>🚨 Urgente (Corte de vía, clima extremo, emergencia)</option>
                        <option value="calidad" {{ old('tipo') === 'calidad' ? 'selected' : '' }}>🔬 Calidad de Leche (Recomendaciones de higiene o acidez)</option>
                        <option value="precio"  {{ old('tipo') === 'precio'  ? 'selected' : '' }}>💵 Precios y Pagos (Ajuste de temporada o liquidaciones)</option>
                        <option value="capacitacion" {{ old('tipo') === 'capacitacion' ? 'selected' : '' }}>📚 Capacitación / Visita ATOS (Charlas técnicas)</option>
                    </select>
                    @error('tipo') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="mensaje">Mensaje / Contenido <span class="req">*</span></label>
                    <textarea id="mensaje" name="mensaje" class="form-control" rows="5"
                              placeholder="Escribe el mensaje claro y detallado para los productores..."
                              required maxlength="2000">{{ old('mensaje') }}</textarea>
                    <small style="color: var(--text-muted); font-size: 0.72rem;">Máx. 2000 caracteres. Se mostrará en el portal web y por WhatsApp.</small>
                    @error('mensaje') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div style="background: var(--bg); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; text-transform: none; font-size: 0.85rem; font-weight: 600; color: var(--text);">
                        <input type="checkbox" name="enviar_whatsapp" value="1" {{ old('enviar_whatsapp', true) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--brand-light);">
                        <span>📲 Difundir masivamente por WhatsApp</span>
                    </label>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px; margin-left: 28px;">
                        Envía el mensaje instantáneamente a los {{ $stats['con_telefono'] }} productores que tienen número registrado.
                    </p>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    🚀 Publicar Comunicado
                </button>
            </form>
        </div>
    </div>

    {{-- ── Historial de Comunicados ────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h3>📋 Historial de Comunicados Emitidos</h3>
            <span style="font-size: 0.8rem; color: var(--text-muted);">{{ $comunicados->total() }} registro(s)</span>
        </div>
        <div class="card-body" style="padding: 0;">
            @if($comunicados->isEmpty())
                <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                    <div style="font-size: 2.2rem; margin-bottom: 8px;">📢</div>
                    <div style="font-weight: 600;">No hay comunicados registrados aún</div>
                    <p style="font-size: 0.8rem; margin-top: 4px;">Usa el formulario para redactar el primer aviso a los productores.</p>
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Título y Contenido</th>
                                <th>Canales</th>
                                <th>Autor</th>
                                <th style="text-align: right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comunicados as $c)
                            @php $badge = $c->tipo_badge; @endphp
                            <tr>
                                <td style="white-space: nowrap; font-size: 0.8rem; color: var(--text-muted);">
                                    {{ $c->fecha_publicacion->format('d/m/Y') }}
                                </td>
                                <td>
                                    <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                </td>
                                <td style="max-width: 260px;">
                                    <strong style="display: block; color: var(--text); font-size: 0.86rem; margin-bottom: 3px;">
                                        {{ $c->titulo }}
                                    </strong>
                                    <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.3; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                        {{ $c->mensaje }}
                                    </p>
                                </td>
                                <td style="white-space: nowrap; font-size: 0.75rem;">
                                    <span class="tag" style="background: var(--green-bg); color: var(--green);">🌐 Portal ({{ $c->destinatarios_count }})</span>
                                    @if($c->enviar_whatsapp)
                                        <span class="tag" style="background: var(--blue-bg); color: var(--blue); margin-top: 3px; display: inline-flex;">📲 WhatsApp</span>
                                    @endif
                                </td>
                                <td style="font-size: 0.8rem; color: var(--text-muted);">
                                    {{ $c->autor->name ?? 'Sistema' }}
                                </td>
                                <td style="text-align: right;">
                                    <form method="POST" action="{{ route('admin.comunicados.destroy', $c) }}"
                                          onsubmit="return confirm('¿Seguro de eliminar este comunicado del portal?');"
                                          style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-sm" style="color: var(--red); padding: 4px 8px;" title="Eliminar">
                                            🗑️
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="padding: 12px 18px;">
                    {{ $comunicados->links() }}
                </div>
            @endif
        </div>
    </div>

</div>

@endsection
