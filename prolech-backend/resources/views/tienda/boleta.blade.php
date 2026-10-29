@php
    $d = $pedido->desglose_igv;

    // Importe en letras (formato de comprobantes peruanos): "CINCUENTA Y 00/100 SOLES"
    $enLetras = function (float $monto): string {
        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
                     'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE',
                     'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIUNO', 'VEINTIDÓS', 'VEINTITRÉS',
                     'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];
        $decenas  = ['', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
                     'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $menorMil = function (int $n) use ($unidades, $decenas, $centenas): string {
            if ($n === 0)   return '';
            if ($n === 100) return 'CIEN';
            $c = intdiv($n, 100); $r = $n % 100;
            $txt = $centenas[$c];
            if ($r > 0) {
                $parte = $r < 30
                    ? $unidades[$r]
                    : $decenas[intdiv($r, 10)] . ($r % 10 ? ' Y ' . $unidades[$r % 10] : '');
                $txt = trim($txt . ' ' . $parte);
            }
            return $txt;
        };

        $entero    = (int) floor($monto);
        $centimos  = (int) round(($monto - $entero) * 100);
        $miles     = intdiv($entero, 1000);
        $resto     = $entero % 1000;

        $texto = match (true) {
            $entero === 0 => 'CERO',
            $miles === 0  => $menorMil($resto),
            $miles === 1  => trim('MIL ' . $menorMil($resto)),
            default       => trim($menorMil($miles) . ' MIL ' . $menorMil($resto)),
        };
        $texto = preg_replace('/UNO MIL/', 'UN MIL', $texto);

        return $texto . ' Y ' . str_pad((string) $centimos, 2, '0', STR_PAD_LEFT) . '/100 SOLES';
    };

    $metodo = $pedido->metodo_badge;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta {{ $pedido->numero_comprobante ?? $pedido->codigo_pedido }} · ProLech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --brand: #1f6feb; --brand-dark: #0b1f44; --sky: #38bdf8;
            --grad: linear-gradient(135deg, #1f6feb 0%, #38bdf8 100%);
            --border: #e3e9f3; --muted: #64748b; --text: #0f172a; --bg: #eef3fb;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text); padding: 28px 16px 60px; -webkit-font-smoothing: antialiased; }

        .toolbar { max-width: 820px; margin: 0 auto 16px; display: flex; gap: 10px; justify-content: space-between; flex-wrap: wrap; align-items: center; }
        .toolbar a, .toolbar button {
            font: inherit; font-weight: 700; font-size: .88rem; cursor: pointer; text-decoration: none;
            padding: 11px 20px; border-radius: 999px; border: 1.5px solid var(--border); background: #fff; color: var(--brand-dark);
        }
        .toolbar .primary { background: var(--grad); color: #fff; border: none; box-shadow: 0 12px 24px -12px rgba(31,111,235,.85); }
        .toolbar .actions { display: flex; gap: 10px; flex-wrap: wrap; }

        .doc {
            max-width: 820px; margin: 0 auto; background: #fff; border-radius: 22px;
            box-shadow: 0 30px 60px -30px rgba(11,31,68,.35); overflow: hidden; position: relative;
        }
        .doc-band { height: 8px; background: var(--grad); }
        .doc-inner { padding: 36px 40px 30px; }

        .head { display: grid; grid-template-columns: 1fr 260px; gap: 24px; align-items: start; }
        .brand { display: flex; gap: 14px; align-items: center; }
        .logo { width: 56px; height: 56px; border-radius: 16px; background: var(--grad); display: inline-flex; align-items: center; justify-content: center; font-size: 1.7rem; }
        .brand h1 { font-size: 1.35rem; font-weight: 800; color: var(--brand-dark); letter-spacing: -.4px; }
        .brand p { font-size: .8rem; color: var(--muted); line-height: 1.5; }

        .ruc-box { border: 2px solid var(--brand-dark); border-radius: 16px; text-align: center; overflow: hidden; }
        .ruc-box div { padding: 8px 10px; font-weight: 700; font-size: .85rem; }
        .ruc-box .tipo { background: var(--brand-dark); color: #fff; font-size: .82rem; letter-spacing: .5px; }
        .ruc-box .num { font-size: 1.15rem; font-weight: 800; color: var(--brand); letter-spacing: 1px; }

        .info { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin: 28px 0 22px; padding: 18px 20px; background: #f6f9fe; border-radius: 16px; }
        .info dt { font-size: .68rem; text-transform: uppercase; letter-spacing: .8px; color: var(--muted); font-weight: 700; }
        .info dd { font-size: .88rem; font-weight: 600; margin-top: 3px; word-break: break-word; }
        .info .wide { grid-column: span 2; }

        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        thead th { text-align: left; font-size: .7rem; text-transform: uppercase; letter-spacing: .8px; color: var(--muted); padding: 10px 12px; border-bottom: 2px solid var(--brand-dark); }
        tbody td { padding: 12px; border-bottom: 1px solid var(--border); }
        .r { text-align: right; } .c { text-align: center; }

        .bottom { display: grid; grid-template-columns: 1fr 280px; gap: 24px; margin-top: 22px; align-items: start; }
        .letras { font-size: .8rem; background: #f6f9fe; border-radius: 12px; padding: 12px 14px; }
        .letras b { display: block; font-size: .66rem; text-transform: uppercase; letter-spacing: .8px; color: var(--muted); margin-bottom: 4px; }
        .pago { margin-top: 12px; font-size: .82rem; color: var(--text); line-height: 1.6; }
        .tot div { display: flex; justify-content: space-between; padding: 7px 0; font-size: .88rem; }
        .tot .grand { margin-top: 6px; padding: 12px 14px; border-radius: 12px; background: var(--brand-dark); color: #fff; font-weight: 800; font-size: 1.05rem; }

        .foot { margin-top: 30px; padding-top: 18px; border-top: 1px dashed var(--border); display: flex; gap: 18px; align-items: center; justify-content: space-between; }
        .foot p { font-size: .74rem; color: var(--muted); line-height: 1.6; }
        .qr { width: 86px; height: 86px; display: grid; grid-template-columns: repeat(9, 1fr); gap: 2px; padding: 6px; border: 1px solid var(--border); border-radius: 10px; flex-shrink: 0; }
        .qr i { background: var(--brand-dark); border-radius: 1px; }
        .qr i.w { background: transparent; }
        .gracias { text-align: center; font-weight: 800; color: var(--brand); margin-top: 18px; }

        .estado { position: absolute; top: 26px; right: -42px; transform: rotate(35deg); background: #f2a541; color: #0b1f44; font-weight: 800; font-size: .7rem; letter-spacing: 1px; padding: 6px 50px; }

        @media (max-width: 640px) {
            .doc-inner { padding: 26px 18px; }
            .head, .bottom { grid-template-columns: 1fr; }
            .info { grid-template-columns: 1fr 1fr; }
            .info .wide { grid-column: 1 / -1; }
            thead th:nth-child(3), tbody td:nth-child(3) { display: none; }
        }

        @media print {
            @page { size: A4; margin: 12mm; }
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .doc { box-shadow: none; border-radius: 0; max-width: none; }
            .doc-band, .logo, .ruc-box .tipo, .tot .grand, .info, .letras { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <a href="{{ route('tienda.index') }}">← Volver a la tienda</a>
    <div class="actions">
        <button type="button" onclick="window.print()">🖨️ Imprimir</button>
        <button type="button" class="primary" onclick="window.print()">⬇️ Descargar PDF</button>
    </div>
</div>

<article class="doc">
    <div class="doc-band"></div>
    @if($pedido->estado_pedido === 'Pendiente' && $pedido->metodo_pago !== 'Tarjeta')
        <div class="estado">{{ $pedido->metodo_pago === 'Yape' ? 'PAGO POR VERIFICAR' : 'PAGO CONTRA ENTREGA' }}</div>
    @endif

    <div class="doc-inner">
        <header class="head">
            <div class="brand">
                <span class="logo">🧀</span>
                <div>
                    <h1>{{ $empresa['nombre'] }}</h1>
                    <p>{{ $empresa['direccion'] }}@if($empresa['telefono'])<br>Tel. {{ $empresa['telefono'] }}@endif</p>
                </div>
            </div>
            <div class="ruc-box">
                <div>RUC {{ $empresa['ruc'] ?? '— (configurar)' }}</div>
                <div class="tipo">BOLETA DE VENTA ELECTRÓNICA</div>
                <div class="num">{{ $pedido->numero_comprobante ?? 'B001-' . str_pad($pedido->id, 8, '0', STR_PAD_LEFT) }}</div>
            </div>
        </header>

        <dl class="info">
            <div><dt>Fecha de emisión</dt><dd>{{ $pedido->created_at->format('d/m/Y H:i') }}</dd></div>
            <div><dt>Pedido</dt><dd>{{ $pedido->codigo_pedido }}</dd></div>
            <div><dt>Moneda</dt><dd>Soles (PEN)</dd></div>
            <div class="wide"><dt>Cliente</dt><dd>{{ $pedido->cliente_nombre }}</dd></div>
            <div><dt>DNI</dt><dd>{{ $pedido->cliente_documento ?: 'Cliente varios' }}</dd></div>
            <div class="wide"><dt>Dirección</dt><dd>{{ $pedido->cliente_direccion }}</dd></div>
            <div><dt>Teléfono</dt><dd>{{ $pedido->cliente_telefono }}</dd></div>
        </dl>

        <table>
            <thead>
                <tr>
                    <th class="c" style="width:60px">Cant.</th>
                    <th>Descripción</th>
                    <th class="r" style="width:110px">P. unit.</th>
                    <th class="r" style="width:110px">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pedido->detalles as $item)
                <tr>
                    <td class="c">{{ $item->cantidad }}</td>
                    <td>{{ $item->producto?->nombre ?? 'Producto' }} <span style="color:var(--muted); font-size:.78rem;">· UND</span></td>
                    <td class="r">S/ {{ number_format($item->precio_unitario, 2) }}</td>
                    <td class="r"><b>S/ {{ number_format($item->subtotal, 2) }}</b></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="bottom">
            <div>
                <div class="letras"><b>Son</b>{{ $enLetras($d['total']) }}</div>
                <div class="pago">
                    <b>Forma de pago:</b> {{ $metodo['icon'] }} {{ $metodo['label'] }}
                    @if($pedido->pago_referencia) · {{ $pedido->pago_referencia }}@endif
                    <br><b>Condición:</b> Contado
                    @if($pedido->notas)<br><b>Observaciones:</b> {{ $pedido->notas }}@endif
                </div>
            </div>
            <div class="tot">
                <div><span>Op. gravada</span><span>S/ {{ number_format($d['gravada'], 2) }}</span></div>
                <div><span>Op. exonerada</span><span>S/ 0.00</span></div>
                <div><span>IGV (18 %)</span><span>S/ {{ number_format($d['igv'], 2) }}</span></div>
                <div class="grand"><span>Importe total</span><span>S/ {{ number_format($d['total'], 2) }}</span></div>
            </div>
        </div>

        <footer class="foot">
            <p>
                Representación impresa de la boleta de venta electrónica generada por el sistema ProLech.
                @if($pedido->sunat_estado === 'aceptada') <b>Aceptada por SUNAT.</b>
                @elseif($pedido->sunat_estado !== 'enviada') <b>Comprobante interno: aún no enviado a SUNAT.</b>@endif
                @if($pedido->sunat_pdf_url) <a href="{{ $pedido->sunat_pdf_url }}">Ver PDF oficial</a>@endif<br>
                Consérvala para cualquier reclamo o cambio. Código de pedido: <b>{{ $pedido->codigo_pedido }}</b>.
            </p>
            <div class="qr" id="qr" aria-hidden="true"></div>
        </footer>
        <p class="gracias">¡Gracias por elegir lácteos de Huata! 🥛</p>
    </div>
</article>

<script>
    // Patrón decorativo determinístico a partir del número de boleta
    (() => {
        const seed = @json(($pedido->numero_comprobante ?? '') . $pedido->codigo_pedido . $pedido->total);
        const qr = document.getElementById('qr');
        let h = 0;
        for (let k = 0; k < 81; k++) {
            h = (h * 31 + seed.charCodeAt(k % seed.length) + k) % 9973;
            const r = Math.floor(k / 9), c = k % 9;
            const esquina = (r < 3 && c < 3) || (r < 3 && c > 5) || (r > 5 && c < 3);
            qr.insertAdjacentHTML('beforeend', `<i class="${esquina || h % 3 ? '' : 'w'}"></i>`);
        }
    })();
</script>
</body>
</html>
