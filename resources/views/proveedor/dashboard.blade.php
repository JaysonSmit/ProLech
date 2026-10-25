<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal ProLech — {{ auth()->user()->name }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; background:#f4f6f4; color:#1a2e1a; min-height:100vh; }

        /* Header */
        .header { background:#1a5c2a; color:#fff; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; }
        .header-logo { font-size:1.3rem; font-weight:800; }
        .header-sub  { font-size:.8rem; opacity:.7; margin-top:2px; }
        .header-user { text-align:right; font-size:.82rem; }
        .header-user a { color:#a7f3c8; text-decoration:none; font-size:.78rem; }

        /* Nav tabs */
        .nav-tabs { background:#fff; border-bottom:2px solid #e0e8e0; display:flex; gap:0; }
        .nav-tab { padding:12px 20px; font-size:.88rem; font-weight:600; color:#6b8f6b;
                   text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-2px; }
        .nav-tab.active { color:#1a5c2a; border-bottom-color:#1a5c2a; }
        .nav-tab:hover  { color:#1a5c2a; }

        /* Container */
        .container { max-width:900px; margin:24px auto; padding:0 16px; }

        /* Cards */
        .card { background:#fff; border-radius:10px; border:1px solid #e0e8e0; margin-bottom:18px; overflow:hidden; }
        .card-header { padding:14px 18px; border-bottom:1px solid #e0e8e0; display:flex; justify-content:space-between; align-items:center; }
        .card-header h3 { font-size:.95rem; font-weight:700; color:#0f3d1a; }
        .card-body { padding:16px 18px; }

        /* Stats */
        .stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:20px; }
        .stat-card { background:#fff; border-radius:10px; border:1px solid #e0e8e0; padding:16px 18px; text-align:center; }
        .stat-label { font-size:.75rem; color:#6b8f6b; text-transform:uppercase; font-weight:600; margin-bottom:6px; }
        .stat-value { font-size:1.8rem; font-weight:900; color:#1a5c2a; }
        .stat-unit  { font-size:.8rem; color:#6b8f6b; margin-top:2px; }

        /* Tabla */
        table { width:100%; border-collapse:collapse; }
        th { padding:9px 14px; font-size:.78rem; color:#6b8f6b; font-weight:600; text-align:left; background:#f4f6f4; }
        td { padding:10px 14px; font-size:.88rem; border-bottom:1px solid #e0e8e0; }
        tr:last-child td { border-bottom:none; }

        /* Badges */
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:700; }
        .badge-aprobado  { background:#e8f5e9; color:#1a5c2a; }
        .badge-observado { background:#fff8e1; color:#b45309; }
        .badge-rechazado { background:#ffebee; color:#b71c1c; }

        /* Alerta calidad */
        .alerta { padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:.88rem; }
        .alerta-warn { background:#fff8e1; border:1px solid #ffc107; color:#664d03; }
        .alerta-ok   { background:#e8f5e9; border:1px solid #a7f3c8; color:#0f3d1a; }
        .alerta-err  { background:#ffebee; border:1px solid #f87171; color:#7f1d1d; }

        /* Footer */
        .footer { text-align:center; padding:20px; font-size:.75rem; color:#6b8f6b; margin-top:12px; }

        @media(max-width:600px) {
            .stats-row { grid-template-columns:1fr 1fr; }
            .nav-tab   { padding:10px 12px; font-size:.8rem; }
        }
    </style>
</head>
<body>

<div class="header">
    <div>
        <div class="header-logo">🐄 ProLech Huata</div>
        <div class="header-sub">Portal del Proveedor · Distrito de Guata, Puno</div>
    </div>
    <div class="header-user">
        <div><strong>{{ $ganadero->nombre }}</strong></div>
        <div>DNI: {{ $ganadero->dni }}</div>
        <form method="POST" action="{{ route('logout') }}" style="margin-top:4px">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer">
                <a href="#" style="color:#a7f3c8">Cerrar sesión</a>
            </button>
        </form>
    </div>
</div>

<div class="nav-tabs">
    <a href="{{ route('proveedor.dashboard') }}"
       class="nav-tab {{ request()->routeIs('proveedor.dashboard') ? 'active' : '' }}">
        🏠 Inicio
    </a>
    <a href="{{ route('proveedor.historial') }}"
       class="nav-tab {{ request()->routeIs('proveedor.historial') ? 'active' : '' }}">
        📋 Mis Entregas
    </a>
    <a href="{{ route('proveedor.pagos') }}"
       class="nav-tab {{ request()->routeIs('proveedor.pagos') ? 'active' : '' }}">
        💰 Mis Pagos
    </a>
</div>

<div class="container">

    @if(session('success'))
    <div class="alerta alerta-ok">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alerta alerta-err">{{ session('error') }}</div>
    @endif

    {{-- Alerta estado del proveedor --}}
    @if($ganadero->estado === 'Suspendido')
    <div class="alerta alerta-err">
        🚫 <strong>Tu cuenta está suspendida</strong> por reincidencia en adulteración de leche.
        Contacta al administrador de la planta para más información.
    </div>
    @endif

    {{-- Stats del día --}}
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Litros hoy</div>
            <div class="stat-value">{{ number_format($litrosHoy, 1) }}</div>
            <div class="stat-unit">litros registrados · {{ now()->format('d/m/Y') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Litros esta semana</div>
            <div class="stat-value">{{ number_format($litrosSemana, 1) }}</div>
            <div class="stat-unit">
                {{ $semanaInicio->format('d/m') }} → {{ $semanaFin->format('d/m/Y') }}
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pago estimado semana</div>
            @if($liquidacionSemana)
                <div class="stat-value" style="font-size:1.4rem">
                    S/ {{ number_format($liquidacionSemana->total_neto, 2) }}
                </div>
                <div class="stat-unit">
                    <span class="badge badge-{{ $liquidacionSemana->estado }}">
                        {{ ucfirst($liquidacionSemana->estado) }}
                    </span>
                </div>
            @else
                <div class="stat-value" style="font-size:1.1rem;color:#6b8f6b">
                    Pendiente
                </div>
                <div class="stat-unit">Se genera cada jueves</div>
            @endif
        </div>
    </div>

    {{-- Comunicados Oficiales a Productores --}}
    @if(isset($comunicadosRecientes) && $comunicadosRecientes->isNotEmpty())
    <div class="card" style="border-left: 4px solid #1a5c2a; margin-bottom: 20px;">
        <div class="card-header" style="background:#f9fbf9">
            <h3>📢 Comunicados Oficiales ProLech</h3>
            <span style="font-size:.78rem;color:#6b8f6b">{{ $comunicadosRecientes->count() }} comunicado(s) reciente(s)</span>
        </div>
        <div class="card-body" style="padding:14px 18px">
            @foreach($comunicadosRecientes as $com)
            @php $badge = $com->tipo_badge; @endphp
            <div style="padding: 12px 0; {{ !$loop->last ? 'border-bottom: 1px solid #e0e8e0;' : '' }}">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                    <span style="font-size:0.75rem;color:#6b8f6b">{{ $com->fecha_publicacion->format('d/m/Y') }}</span>
                </div>
                <h4 style="font-size:0.95rem;color:#0f3d1a;margin-bottom:4px">{{ $com->titulo }}</h4>
                <p style="font-size:0.85rem;color:#334d33;line-height:1.4">{{ $com->mensaje }}</p>
                <div style="font-size:0.72rem;color:#85a385;margin-top:4px">
                    Publicado por: {{ $com->autor->name ?? 'Administración ProLech' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Acopios del día --}}
    <div class="card">
        <div class="card-header">
            <h3>📦 Mis entregas de hoy — {{ now()->isoFormat('dddd D [de] MMMM') }}</h3>
            <span style="font-size:.8rem;color:#6b8f6b">{{ $acopiosHoy->count() }} registro(s)</span>
        </div>
        @if($acopiosHoy->isEmpty())
        <div class="card-body" style="text-align:center;color:#6b8f6b;padding:32px">
            <div style="font-size:2rem;margin-bottom:8px">🥛</div>
            Sin entregas registradas hoy.
            <div style="font-size:.8rem;margin-top:6px">
                Si entregaste leche y no aparece aquí, reclama hoy mismo en la planta.
            </div>
        </div>
        @else
        <table>
            <thead>
                <tr>
                    <th>Hora</th>
                    <th style="text-align:right">Litros</th>
                    <th style="text-align:center">% Agua</th>
                    <th style="text-align:center">pH</th>
                    <th style="text-align:center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($acopiosHoy as $a)
                <tr>
                    <td style="color:#6b8f6b">
                        {{ \Carbon\Carbon::parse($a->created_at)->format('H:i') }}
                    </td>
                    <td style="text-align:right;font-weight:700;color:#1a5c2a">
                        {{ number_format($a->litros, 1) }} L
                    </td>
                    <td style="text-align:center">
                        @if($a->porcentaje_agua !== null)
                            <span style="color:{{ $a->porcentaje_agua > 5 ? '#b71c1c' : '#1a5c2a' }};font-weight:600">
                                {{ number_format($a->porcentaje_agua, 2) }}%
                            </span>
                        @else — @endif
                    </td>
                    <td style="text-align:center;color:#6b8f6b">
                        {{ $a->acidez_ph !== null ? number_format($a->acidez_ph, 2) : '—' }}
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-{{ $a->estado }}">{{ ucfirst($a->estado) }}</span>
                    </td>
                </tr>
                @if($a->tiene_descuento)
                <tr>
                    <td colspan="5" style="padding:4px 14px 8px;font-size:.78rem;color:#b45309;background:#fff8e1">
                        ⚠️ Este acopio tiene descuento por % agua elevado. Se refleja en tu pago semanal.
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f4f6f4">
                    <td style="padding:10px 14px;font-weight:700">Total hoy</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:900;color:#1a5c2a;font-size:1rem">
                        {{ number_format($acopiosHoy->sum('litros'), 1) }} L
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>

    {{-- RF-PRO-06 — Reclamo de litraje (solo hoy) --}}
    @if($acopiosHoy->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h3>📢 Reclamar error de litraje (solo hoy)</h3>
        </div>
        <div class="card-body">
            <p style="font-size:.82rem;color:#6b8f6b;margin-bottom:14px">
                Si la cantidad registrada no coincide con lo que entregaste, envía el reclamo antes de medianoche.
            </p>
            <form method="POST" action="{{ route('proveedor.reclamo.store') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                    <div>
                        <label style="font-size:.78rem;font-weight:600;color:#6b8f6b">Entrega</label>
                        <select name="acopio_id" required
                                style="width:100%;padding:10px;border:1px solid #e0e8e0;border-radius:8px;margin-top:4px">
                            @foreach($acopiosHoy as $a)
                            <option value="{{ $a->id }}">
                                {{ \Carbon\Carbon::parse($a->created_at)->format('H:i') }}
                                — {{ number_format($a->litros, 1) }} L registrados
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.78rem;font-weight:600;color:#6b8f6b">Litros correctos</label>
                        <input type="number" name="litros_correctos" step="0.1" min="0.1" max="500" required
                               placeholder="Ej: 28.5"
                               style="width:100%;padding:10px;border:1px solid #e0e8e0;border-radius:8px;margin-top:4px">
                    </div>
                </div>
                <div style="margin-bottom:12px">
                    <label style="font-size:.78rem;font-weight:600;color:#6b8f6b">Motivo del reclamo</label>
                    <textarea name="motivo" rows="3" required minlength="10" maxlength="1000"
                              placeholder="Describe qué ocurrió con la medición..."
                              style="width:100%;padding:10px;border:1px solid #e0e8e0;border-radius:8px;margin-top:4px;font-family:inherit"></textarea>
                </div>
                <button type="submit"
                        style="background:#1a5c2a;color:#fff;border:none;padding:12px 20px;border-radius:8px;font-weight:700;cursor:pointer">
                    Enviar reclamo
                </button>
            </form>

            @if($reclamosHoy->isNotEmpty())
            <div style="margin-top:16px;padding-top:14px;border-top:1px solid #e0e8e0">
                <div style="font-size:.78rem;font-weight:700;color:#6b8f6b;margin-bottom:8px">Tus reclamos de hoy</div>
                @foreach($reclamosHoy as $rec)
                <div style="font-size:.82rem;padding:8px 10px;background:#f4f6f4;border-radius:6px;margin-bottom:6px">
                    {{ number_format($rec->litros_registrados,1) }} L → {{ number_format($rec->litros_correctos,1) }} L
                    · <strong>{{ ucfirst($rec->estado) }}</strong>
                    @if($rec->respuesta_admin)
                        <div style="color:#6b8f6b;margin-top:4px">{{ $rec->respuesta_admin }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Último pago --}}
    @if($ultimoPago)
    <div class="card">
        <div class="card-header">
            <h3>💰 Último pago recibido</h3>
        </div>
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
                <div>
                    <div style="font-size:.82rem;color:#6b8f6b">Semana</div>
                    <div style="font-weight:700">
                        {{ $ultimoPago->semana_inicio->format('d/m/Y') }}
                        → {{ $ultimoPago->semana_fin->format('d/m/Y') }}
                    </div>
                    <div style="font-size:.82rem;color:#6b8f6b;margin-top:4px">
                        {{ number_format($ultimoPago->litros_aprobados, 1) }} L ×
                        S/ {{ number_format($ultimoPago->precio_litro, 2) }}
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:1.6rem;font-weight:900;color:#1a5c2a">
                        S/ {{ number_format($ultimoPago->total_neto, 2) }}
                    </div>
                    <div style="font-size:.8rem;color:#6b8f6b">
                        Pagado {{ $ultimoPago->pagada_at?->format('d/m/Y') ?? '—' }}
                        @if($ultimoPago->metodo_pago)
                            · {{ ucfirst($ultimoPago->metodo_pago) }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

<div class="footer">ProLech Huata · Planta Municipal de Lácteos · Guata, Puno</div>

</body>
</html>
