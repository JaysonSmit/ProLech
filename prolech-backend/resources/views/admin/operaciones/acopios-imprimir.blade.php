<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acopios {{ $desde->format('d/m/Y') }} – {{ $hasta->format('d/m/Y') }} · ProLech</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; padding: 28px; font-size: 12px; }
        .barra { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        h1 { font-size: 20px; color: #0b1f44; } .sub { color: #64748b; margin-top: 4px; }
        button { font: inherit; font-weight: 700; padding: 9px 18px; border-radius: 99px; border: none; background: linear-gradient(135deg,#1f6feb,#38bdf8); color: #fff; cursor: pointer; }
        .kpis { display: flex; gap: 12px; margin-bottom: 16px; }
        .kpis div { background: #eef5ff; border-radius: 10px; padding: 10px 14px; } .kpis b { display: block; font-size: 16px; color: #0b1f44; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0b1f44; color: #fff; text-align: left; padding: 7px 8px; font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; }
        td { padding: 6px 8px; border-bottom: 1px solid #e3e9f3; } tr:nth-child(even) td { background: #f8faff; }
        .r { text-align: right; } .RECHAZADO, .RECHAZADA, .rechazado { color: #dc2626; font-weight: 700; } .OBSERVADO, .observado { color: #b45309; font-weight: 700; }
        @media print { button { display: none; } body { padding: 0; } @page { size: A4 landscape; margin: 10mm; } th, tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="barra">
        <div>
            <h1>🧀 ProLech — Reporte de acopios</h1>
            <div class="sub">Del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }} · generado {{ now()->format('d/m/Y H:i') }}</div>
        </div>
        <button onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
    </div>
    <div class="kpis">
        <div>Entregas<b>{{ $filas->count() }}</b></div>
        <div>Litros<b>{{ number_format($totalLitros, 1) }}</b></div>
        <div>Ganaderos<b>{{ $filas->pluck('DNI')->filter()->unique()->count() }}</b></div>
    </div>
    <table>
        <thead><tr>
            <th>Fecha</th><th>Hora</th><th>Ganadero</th><th>Zona</th><th>Chofer</th>
            <th class="r">Litros</th><th class="r">Agua %</th><th class="r">Densidad</th><th class="r">Grasa %</th><th>Calidad</th>
        </tr></thead>
        <tbody>
        @forelse($filas as $f)
            <tr>
                <td>{{ $f['Fecha'] }}</td><td>{{ $f['Hora'] }}</td><td>{{ $f['Ganadero'] }}</td><td>{{ $f['Zona'] }}</td><td>{{ $f['Chofer'] }}</td>
                <td class="r">{{ number_format($f['Litros'], 2) }}</td><td class="r">{{ number_format($f['Agua %'], 1) }}</td>
                <td class="r">{{ number_format($f['Densidad'], 3) }}</td><td class="r">{{ $f['Grasa %'] === '' ? '—' : number_format($f['Grasa %'], 1) }}</td>
                <td class="{{ $f['Calidad'] }}">{{ $f['Calidad'] }}</td>
            </tr>
        @empty
            <tr><td colspan="10" style="text-align:center; padding:30px; color:#64748b">Sin acopios en el rango.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
