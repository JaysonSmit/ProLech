<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pagos — ProLech</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; background:#f4f6f4; color:#1a2e1a; }
        .header { background:#1a5c2a; color:#fff; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; }
        .header-logo { font-size:1.2rem; font-weight:800; }
        .nav-tabs { background:#fff; border-bottom:2px solid #e0e8e0; display:flex; }
        .nav-tab { padding:12px 20px; font-size:.88rem; font-weight:600; color:#6b8f6b; text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-2px; }
        .nav-tab.active { color:#1a5c2a; border-bottom-color:#1a5c2a; }
        .container { max-width:900px; margin:24px auto; padding:0 16px; }
        .card { background:#fff; border-radius:10px; border:1px solid #e0e8e0; margin-bottom:18px; overflow:hidden; }
        .card-header { padding:14px 18px; border-bottom:1px solid #e0e8e0; display:flex; justify-content:space-between; align-items:center; }
        .card-header h3 { font-size:.95rem; font-weight:700; color:#0f3d1a; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:700; }
        .badge-borrador   { background:#f1f5f9; color:#64748b; }
        .badge-confirmada { background:#fff8e1; color:#b45309; }
        .badge-pagada     { background:#e8f5e9; color:#1a5c2a; }
        .total-banner { background:#1a5c2a; color:#fff; border-radius:10px; padding:20px 24px;
                        display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .total-banner .lbl { font-size:.85rem; opacity:.8; }
        .total-banner .val { font-size:2rem; font-weight:900; }
        table { width:100%; border-collapse:collapse; }
        th { padding:9px 14px; font-size:.78rem; color:#6b8f6b; font-weight:600; text-align:left; background:#f4f6f4; }
        td { padding:10px 14px; font-size:.88rem; border-bottom:1px solid #e0e8e0; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-logo">🐄 ProLech · Mis Pagos</div>
    <div style="font-size:.82rem;color:#a7f3c8">{{ $ganadero->nombre }}</div>
</div>

<div class="nav-tabs">
    <a href="{{ route('proveedor.dashboard') }}" class="nav-tab">🏠 Inicio</a>
    <a href="{{ route('proveedor.historial') }}" class="nav-tab">📋 Mis Entregas</a>
    <a href="{{ route('proveedor.pagos') }}" class="nav-tab active">💰 Mis Pagos</a>
</div>

<div class="container">

    {{-- Banner total pagado --}}
    <div class="total-banner">
        <div>
            <div class="lbl">Total acumulado pagado</div>
            <div class="val">S/ {{ number_format($totalPagado, 2) }}</div>
        </div>
        <div style="font-size:2.5rem">💰</div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>📑 Historial de liquidaciones semanales</h3>
            <span style="font-size:.8rem;color:#6b8f6b">{{ $liquidaciones->total() }} semana(s)</span>
        </div>

        @if($liquidaciones->isEmpty())
        <div style="padding:32px;text-align:center;color:#6b8f6b">
            <div style="font-size:2rem;margin-bottom:8px">📑</div>
            Sin liquidaciones registradas aún.
        </div>
        @else
        <table>
            <thead>
                <tr>
                    <th>Período</th>
                    <th style="text-align:right">Litros</th>
                    <th style="text-align:right">Precio/L</th>
                    <th style="text-align:right">Total neto</th>
                    <th style="text-align:center">Estado</th>
                    <th>Fecha pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach($liquidaciones as $liq)
                <tr>
                    <td>
                        <div style="font-weight:600">
                            {{ $liq->semana_inicio->format('d/m') }}
                            → {{ $liq->semana_fin->format('d/m/Y') }}
                        </div>
                    </td>
                    <td style="text-align:right;color:#1a5c2a;font-weight:700">
                        {{ number_format($liq->litros_aprobados, 1) }} L
                    </td>
                    <td style="text-align:right;color:#6b8f6b">
                        S/ {{ number_format($liq->precio_litro, 2) }}
                    </td>
                    <td style="text-align:right;font-weight:900;font-size:1rem;color:#1a5c2a">
                        S/ {{ number_format($liq->total_neto, 2) }}
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-{{ $liq->estado }}">
                            {{ ucfirst($liq->estado) }}
                        </span>
                    </td>
                    <td style="color:#6b8f6b;font-size:.82rem">
                        {{ $liq->estado === 'pagada' && $liq->pagada_at
                            ? $liq->pagada_at->format('d/m/Y')
                            : $liq->fecha_pago->format('d/m/Y') }}
                        @if($liq->metodo_pago)
                            <div style="font-size:.75rem">{{ ucfirst($liq->metodo_pago) }}</div>
                        @endif
                    </td>
                </tr>
                @if($liq->detalle_descuentos)
                <tr style="background:#fff8e1">
                    <td colspan="6" style="padding:5px 14px;font-size:.78rem;color:#b45309">
                        ⚠️ {{ $liq->detalle_descuentos }}
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        <div style="padding:12px 16px">{{ $liquidaciones->links() }}</div>
        @endif
    </div>

</div>
</body>
</html>
