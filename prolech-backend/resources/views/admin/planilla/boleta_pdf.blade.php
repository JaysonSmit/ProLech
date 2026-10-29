<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Boleta {{ $p->codigo() }}</title>
<style>
    @page { margin: 22px 26px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #0f2a4d; }
    .cab { border-bottom: 3px solid #1f6feb; padding-bottom: 6px; margin-bottom: 10px; }
    .cab h1 { font-size: 15px; margin: 0; }
    .cab small { color: #5b6b82; }
    .datos td { padding: 2px 4px; }
    table { width: 100%; border-collapse: collapse; }
    .det th { background: #1f6feb; color: #fff; text-align: left; padding: 4px 6px; font-size: 9px; }
    .det td { padding: 4px 6px; border-bottom: 1px solid #e3e9f3; }
    .num { text-align: right; }
    .total td { font-weight: bold; font-size: 11px; background: #eef5ff; border-top: 2px solid #1f6feb; }
    .estado { display: inline-block; padding: 2px 8px; border-radius: 8px; font-weight: bold; }
    .pagada { background: #dcfce7; color: #15803d; } .borrador { background: #fef3c7; color: #92400e; }
    .firmas { margin-top: 44px; width: 100%; }
    .firmas td { text-align: center; }
    .firmas span { border-top: 1px solid #0f2a4d; padding: 3px 26px 0; }
    .nota { margin-top: 10px; color: #5b6b82; font-size: 8px; }
</style>
</head>
<body>
<div class="cab">
    <h1>ProLech — Boleta de pago al chofer</h1>
    <small>Planta de lácteos Huata, Puno · {{ $p->codigo() }} · Emitida el {{ now()->format('d/m/Y H:i') }}</small>
</div>

<table class="datos">
    <tr><td><strong>Chofer:</strong> {{ $p->chofer->name }}</td><td><strong>Ruta:</strong> {{ $p->ruta ?? '—' }}</td></tr>
    <tr><td><strong>Periodo ({{ $p->tipo }}):</strong> {{ $p->periodo_inicio->format('d/m/Y') }} al {{ $p->periodo_fin->format('d/m/Y') }}</td>
        <td><strong>Estado:</strong> <span class="estado {{ $p->estado }}">{{ $p->estaPagada() ? 'PAGADA' : 'POR PAGAR' }}</span></td></tr>
    @if($p->estaPagada())
    <tr><td><strong>Pagado:</strong> {{ $p->pagada_at->format('d/m/Y H:i') }} · {{ \App\Models\PlanillaChofer::METODOS[$p->metodo_pago] ?? $p->metodo_pago }}</td><td><strong>Ref.:</strong> {{ $p->referencia_pago ?? '—' }}</td></tr>
    @endif
</table>

<br>
<table class="det">
    <thead><tr><th>Concepto</th><th class="num">Cantidad</th><th class="num">Tarifa</th><th class="num">Importe</th></tr></thead>
    <tbody>
        <tr><td>Jornal por día con ruta</td><td class="num">{{ $p->dias_trabajados }} días</td><td class="num">S/ {{ number_format($p->tarifa_base_dia, 2) }}</td><td class="num">S/ {{ number_format($p->monto_base, 2) }}</td></tr>
        <tr><td>Bono por litro acopiado</td><td class="num">{{ number_format($p->litros_recolectados, 0) }} L</td><td class="num">S/ {{ number_format($p->bono_por_litro, 3) }}</td><td class="num">S/ {{ number_format($p->monto_bono, 2) }}</td></tr>
        <tr><td><strong>Total bruto</strong></td><td></td><td></td><td class="num"><strong>S/ {{ number_format($p->total_bruto, 2) }}</strong></td></tr>
        <tr><td>(−) Aporte de pensión</td><td></td><td></td><td class="num">S/ {{ number_format($p->descuento_pension, 2) }}</td></tr>
        <tr><td>(−) Adelantos</td><td></td><td></td><td class="num">S/ {{ number_format($p->adelantos, 2) }}</td></tr>
    </tbody>
    <tfoot><tr class="total"><td colspan="3">NETO A PAGAR</td><td class="num">S/ {{ number_format($p->total_neto, 2) }}</td></tr></tfoot>
</table>

@if(! empty($p->detalle_dias))
<br>
<table class="det">
    <thead><tr><th>Día</th><th class="num">Entregas</th><th class="num">Litros</th><th class="num">Km</th><th>Fuente km</th></tr></thead>
    <tbody>
    @foreach($p->detalle_dias as $d)
        <tr><td>{{ \Carbon\Carbon::parse($d['fecha'])->format('d/m/Y') }}</td><td class="num">{{ $d['acopios'] }}</td>
            <td class="num">{{ number_format($d['litros'], 0) }}</td><td class="num">{{ number_format($d['km'], 1) }}</td>
            <td>{{ ['gps' => 'GPS', 'ruta' => 'Ruta trazada', 'estimado' => 'Estimado'][$d['fuente_km']] ?? '' }}</td></tr>
    @endforeach
    </tbody>
</table>
@endif

<p class="nota">Km recorridos: {{ number_format($p->km_recorridos, 1) }} · Combustible a cargo de la planta: S/ {{ number_format($p->costo_combustible, 2) }} (no se descuenta al chofer).</p>

<table class="firmas"><tr>
    <td><span>Administración ProLech</span></td>
    <td><span>{{ $p->chofer->name }}</span></td>
</tr></table>
</body>
</html>
