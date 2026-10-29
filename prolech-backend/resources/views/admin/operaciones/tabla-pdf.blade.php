{{-- Reporte PDF genérico (dompdf): acopios, planilla de liquidaciones y hoja de ruta --}}
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $titulo }}</title>
<style>
    @page { margin: 22px 26px 34px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #0f2a4d; }
    .cab { border-bottom: 3px solid #1f6feb; padding-bottom: 6px; margin-bottom: 10px; }
    .cab h1 { font-size: 15px; margin: 0; }
    .cab small { color: #5b6b82; }
    h2 { font-size: 11px; margin: 12px 0 4px; color: #1f6feb; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #1f6feb; color: #fff; text-align: left; padding: 4px 5px; font-size: 8.5px; }
    td { padding: 3px 5px; border-bottom: 1px solid #e3e9f3; }
    tr:nth-child(even) td { background: #f6f9fe; }
    td.num, th.num { text-align: right; }
    tfoot td { font-weight: bold; background: #eef5ff; border-top: 2px solid #1f6feb; }
    .marca { width: 14px; height: 14px; border: 1px solid #5b6b82; display: inline-block; }
    .pie { position: fixed; bottom: -20px; left: 0; right: 0; font-size: 8px; color: #94a3b8; text-align: center; }
    .firmas { margin-top: 40px; width: 100%; }
    .firmas td { border: 0; text-align: center; padding-top: 30px; }
    .firmas span { border-top: 1px solid #0f2a4d; padding: 3px 30px 0; }
</style>
</head>
<body>
<div class="cab">
    <h1>ProLech — {{ $titulo }}</h1>
    <small>Planta de lácteos Huata, Puno · Generado el {{ now()->format('d/m/Y H:i') }} · {{ $filas->count() }} registros</small>
</div>

@php
    $columnas = $filas->isEmpty() ? [] : array_keys($filas->first());
    $esNumero = fn ($v) => is_int($v) || is_float($v);
    // Hasta 4 decimales (densidad 1,0305) sin ceros de más, mínimo 2 (32,70)
    $fmt = fn ($v) => is_float($v) ? preg_replace('/(,\d\d\d*?)0+$/', '$1', number_format($v, 4, ',', '.')) : $v;
@endphp

@if ($filas->isEmpty())
    <p>Sin datos en el rango seleccionado.</p>
@else
    @foreach (($grupos ?? collect(['' => $filas])) as $grupo => $lista)
        @if ($grupo !== '')<h2>{{ $grupo }}</h2>@endif
        <table>
            <thead><tr>
                @if ($grupos)<th style="width:18px">✓</th>@endif
                @foreach ($columnas as $c)
                    <th class="{{ $esNumero($lista->first()[$c]) ? 'num' : '' }}">{{ $c }}</th>
                @endforeach
            </tr></thead>
            <tbody>
            @foreach ($lista as $fila)
                <tr>
                    @if ($grupos)<td><span class="marca"></span></td>@endif
                    @foreach ($columnas as $c)
                        <td class="{{ $esNumero($fila[$c]) ? 'num' : '' }}">{{ $fmt($fila[$c]) }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
            @if (! empty($totales) && ! $grupos)
                <tfoot><tr>
                    @foreach ($columnas as $i => $c)
                        <td class="num">{{ array_key_exists($c, $totales) ? $fmt((float) $totales[$c]) : ($i === 0 ? 'TOTAL' : '') }}</td>
                    @endforeach
                </tr></tfoot>
            @endif
        </table>
    @endforeach

    @if (! empty($totales))
        <table class="firmas"><tr>
            <td><span>Responsable de acopio</span></td>
            <td><span>Administración</span></td>
        </tr></table>
    @endif
@endif

<div class="pie">ProLech · Documento generado por el sistema de acopio</div>
</body>
</html>
