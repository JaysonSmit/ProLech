<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota de venta {{ $venta->numero_comprobante }} · ProLech</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f7; font-family: "Segoe UI", system-ui, sans-serif; color: #0f172a; }
        .acciones { max-width: 720px; margin: 20px auto 0; padding: 0 16px; display: flex; gap: 8px; justify-content: flex-end; }
        .acciones button, .acciones a { padding: 9px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff;
            font: inherit; font-size: .88rem; font-weight: 600; cursor: pointer; color: #0f172a; text-decoration: none; }
        .acciones .imprimir { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .hoja { position: relative; max-width: 720px; margin: 12px auto 30px; background: #fff; padding: 36px 40px;
            border-radius: 10px; box-shadow: 0 4px 18px rgba(15, 23, 42, .08); overflow: hidden; }
        .cabecera { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid #0f172a; padding-bottom: 16px; }
        .marca { font-size: 1.5rem; font-weight: 800; }
        .marca small { display: block; font-size: .8rem; font-weight: 500; color: #475569; margin-top: 2px; }
        .numero { text-align: right; border: 2px solid #0f172a; border-radius: 8px; padding: 8px 14px; align-self: flex-start; }
        .numero div:first-child { font-size: .72rem; text-transform: uppercase; letter-spacing: .6px; color: #475569; }
        .numero div:last-child { font-size: 1.25rem; font-weight: 800; }
        .datos { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 24px; margin: 18px 0; font-size: .9rem; }
        .datos span { color: #64748b; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        th { text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #475569;
            border-bottom: 1px solid #cbd5e1; padding: 8px 6px; }
        td { padding: 9px 6px; border-bottom: 1px solid #eef2f7; }
        .num { text-align: right; white-space: nowrap; }
        .total td { border: none; font-size: 1.15rem; font-weight: 800; padding-top: 14px; }
        .pie { margin-top: 26px; font-size: .75rem; color: #64748b; display: flex; justify-content: space-between; gap: 16px; }
        .firma { margin-top: 46px; display: flex; justify-content: space-around; gap: 30px; font-size: .78rem; color: #475569; text-align: center; }
        .firma div { border-top: 1px solid #94a3b8; padding-top: 6px; min-width: 180px; }
        .anulada-sello { position: absolute; top: 42%; left: 50%; transform: translate(-50%, -50%) rotate(-18deg);
            font-size: 5rem; font-weight: 900; color: rgba(220, 38, 38, .16); border: 8px solid rgba(220, 38, 38, .16);
            padding: 4px 30px; border-radius: 14px; pointer-events: none; letter-spacing: 6px; }
        .anulada-nota { margin-top: 16px; padding: 10px 14px; border: 1px solid #fca5a5; background: #fef2f2; color: #b91c1c;
            border-radius: 8px; font-size: .85rem; }
        @media (max-width: 600px) {
            .hoja { padding: 22px 16px; border-radius: 0; }
            .cabecera { flex-direction: column; }
            .numero { text-align: left; }
            .datos { grid-template-columns: 1fr; }
            .anulada-sello { font-size: 3rem; }
        }
        @media print {
            body { background: #fff; }
            .acciones { display: none; }
            .hoja { box-shadow: none; margin: 0; max-width: none; padding: 0; }
            @page { margin: 14mm; }
        }
    </style>
</head>
<body>
    <div class="acciones">
        <a href="{{ route('admin.ventas') }}">← Volver a ventas</a>
        <button type="button" class="imprimir" onclick="window.print()">🖨️ Imprimir</button>
    </div>

    <div class="hoja">
        @if($venta->anulada)
            <div class="anulada-sello">ANULADA</div>
        @endif

        <div class="cabecera">
            <div class="marca">🧀 ProLech
                <small>Planta quesera · Huata, Puno</small>
            </div>
            <div class="numero">
                <div>Nota de venta</div>
                <div>{{ $venta->numero_comprobante }}</div>
            </div>
        </div>

        <div class="datos">
            <div><span>Cliente:</span> <strong>{{ $venta->cliente }}</strong></div>
            <div><span>Fecha:</span> {{ $venta->fecha_venta->format('d/m/Y') }}</div>
            <div><span>Tipo:</span> {{ $venta->tipo_cliente === 'mayorista' ? 'Mayorista' : 'Público' }}</div>
            <div><span>Destino:</span> {{ $venta->destino ?: 'Planta / Local' }}</div>
            @if($venta->guia_remision)
                <div><span>Guía de remisión:</span> {{ $venta->guia_remision }}</div>
            @endif
            <div><span>Atendido por:</span> {{ $venta->registrador?->name ?? '—' }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Cant.</th>
                    <th>Descripción</th>
                    <th class="num">P. unit.</th>
                    <th class="num">Importe</th>
                </tr>
            </thead>
            <tbody>
                @forelse($venta->detalles as $d)
                    <tr>
                        <td style="white-space:nowrap">{{ rtrim(rtrim(number_format($d->cantidad, 3), '0'), '.') }} {{ $d->unidad }}</td>
                        <td>{{ $d->descripcion }}</td>
                        <td class="num">S/ {{ number_format($d->precio_unitario, 2) }}</td>
                        <td class="num">S/ {{ number_format($d->subtotal, 2) }}</td>
                    </tr>
                @empty
                    {{-- Ventas anteriores al carrito: solo queso de planta --}}
                    <tr>
                        <td style="white-space:nowrap">{{ number_format($venta->cantidad_kg, 2) }} kg</td>
                        <td>Queso de planta</td>
                        <td class="num">S/ {{ number_format($venta->precio_unitario, 2) }}</td>
                        <td class="num">S/ {{ number_format($venta->total, 2) }}</td>
                    </tr>
                @endforelse
                <tr class="total">
                    <td colspan="3" class="num">TOTAL</td>
                    <td class="num">S/ {{ number_format($venta->total, 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if($venta->notas_despacho)
            <p style="font-size:.85rem;color:#475569;margin-top:14px"><strong>Notas:</strong> {{ $venta->notas_despacho }}</p>
        @endif

        @if($venta->anulada)
            <div class="anulada-nota">
                <strong>Venta anulada</strong> el {{ $venta->anulada_at->timezone('America/Lima')->format('d/m/Y H:i') }}
                por {{ $venta->anulador?->name ?? '—' }}. Motivo: {{ $venta->motivo_anulacion }}
            </div>
        @else
            <div class="firma">
                <div>Entregué conforme</div>
                <div>Recibí conforme</div>
            </div>
        @endif

        <div class="pie">
            <div>Documento interno de control. No es un comprobante de pago electrónico (SUNAT).</div>
            <div>Emitido {{ now('America/Lima')->format('d/m/Y H:i') }}</div>
        </div>
    </div>
</body>
</html>
