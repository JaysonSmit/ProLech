<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre de Pago — {{ $liquidacion->ganadero->nombre }}</title>
    <style>
        /* ── Reset ── */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 30px;
        }

        /* ── Sobre ── */
        .sobre {
            background: #fff;
            width: 148mm;       /* A5 landscape aprox */
            min-height: 105mm;
            border: 2px solid #1a5c2a;
            border-radius: 8px;
            padding: 18px 22px;
            box-shadow: 0 4px 20px rgba(0,0,0,.15);
        }

        /* ── Encabezado ── */
        .header {
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 2px solid #1a5c2a;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .logo-circle {
            width: 52px; height: 52px;
            background: #1a5c2a;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; flex-shrink: 0;
        }
        .empresa-nombre {
            font-size: 20px; font-weight: 800; color: #1a5c2a; line-height: 1.1;
        }
        .empresa-sub {
            font-size: 10px; color: #555; margin-top: 2px;
        }
        .periodo-badge {
            margin-left: auto;
            background: #e8f5e9;
            border: 1px solid #1a5c2a;
            border-radius: 6px;
            padding: 6px 12px;
            text-align: center;
        }
        .periodo-badge .label { font-size: 9px; color: #555; text-transform: uppercase; }
        .periodo-badge .valor { font-size: 12px; font-weight: 700; color: #1a5c2a; }

        /* ── Proveedor ── */
        .proveedor-bloque {
            background: #f0f7f1;
            border-left: 4px solid #1a5c2a;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 14px;
        }
        .proveedor-nombre {
            font-size: 16px; font-weight: 800; color: #0f3d1a;
        }
        .proveedor-dni {
            font-size: 10px; color: #555; margin-top: 2px;
        }

        /* ── Tabla de días ── */
        .tabla-dias { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .tabla-dias th {
            background: #1a5c2a; color: #fff;
            font-size: 9px; text-transform: uppercase;
            padding: 5px 8px; text-align: left;
        }
        .tabla-dias td {
            font-size: 11px; padding: 5px 8px;
            border-bottom: 1px solid #e0e0e0;
            color: #333;
        }
        .tabla-dias tr:nth-child(even) td { background: #f9f9f9; }
        .tabla-dias .td-litros { text-align: right; font-weight: 700; color: #1a5c2a; }
        .tabla-dias .tfoot-td {
            font-weight: 800; font-size: 12px;
            border-top: 2px solid #1a5c2a;
            padding-top: 7px;
        }

        /* ── Resumen financiero ── */
        .resumen {
            background: #fff;
            border: 1.5px solid #1a5c2a;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 14px;
        }
        .resumen-fila {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 7px 14px;
            font-size: 11px;
            border-bottom: 1px solid #e8f5e9;
        }
        .resumen-fila:last-child { border-bottom: none; }
        .resumen-fila .lbl { color: #555; }
        .resumen-fila .val { font-weight: 700; color: #222; }
        .resumen-total {
            background: #1a5c2a;
            padding: 10px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .resumen-total .lbl { color: #b8f0c8; font-size: 12px; font-weight: 700; }
        .resumen-total .val { color: #fff; font-size: 20px; font-weight: 900; }

        /* ── Alerta adulteración ── */
        .alerta-adulteracion {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 7px 12px;
            font-size: 10px;
            color: #664d03;
            margin-bottom: 10px;
        }

        /* ── Pie ── */
        .pie {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px dashed #ccc;
        }
        .pie-metodo { font-size: 10px; color: #555; }
        .pie-metodo strong { display: block; font-size: 13px; color: #1a5c2a; }
        .pie-firma {
            text-align: center;
            font-size: 9px;
            color: #888;
        }
        .pie-firma .linea-firma {
            width: 100px;
            border-bottom: 1px solid #999;
            margin: 0 auto 4px;
        }

        /* ── Botón imprimir (no sale en impresión) ── */
        .btn-imprimir {
            display: block;
            margin: 20px auto 0;
            padding: 10px 32px;
            background: #1a5c2a;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        /* ── Media print ── */
        @media print {
            body { background: #fff; padding: 0; }
            .sobre { box-shadow: none; border: 2px solid #1a5c2a; }
            .btn-imprimir { display: none; }
        }
    </style>
</head>
<body>

<div class="sobre">

    {{-- Encabezado --}}
    <div class="header">
        <div class="logo-circle">🐄</div>
        <div>
            <div class="empresa-nombre">ProLech Huata</div>
            <div class="empresa-sub">Planta Lechera Municipal · Distrito de Guata, Puno</div>
        </div>
        <div class="periodo-badge">
            <div class="label">Período de pago</div>
            <div class="valor">
                {{ $liquidacion->semana_inicio->format('d/m') }}
                → {{ $liquidacion->semana_fin->format('d/m/Y') }}
            </div>
            <div class="label" style="margin-top:3px">
                Pago: {{ $liquidacion->fecha_pago->isoFormat('dddd D MMM') }}
            </div>
        </div>
    </div>

    {{-- Proveedor --}}
    <div class="proveedor-bloque">
        <div class="proveedor-nombre">{{ $liquidacion->ganadero->nombre }}</div>
        <div class="proveedor-dni">
            DNI: {{ $liquidacion->ganadero->dni }}
            @if($liquidacion->ganadero->ruta_asignada)
                &nbsp;·&nbsp; Ruta: {{ $liquidacion->ganadero->ruta_asignada }}
            @endif
        </div>
    </div>

    {{-- Tabla de días acopiados --}}
    @if($dias->isNotEmpty())
    <table class="tabla-dias">
        <thead>
            <tr>
                <th>Día</th>
                <th>Fecha</th>
                <th style="text-align:right">Litros</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dias as $i => $dia)
            <tr>
                <td>Día {{ $i + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($dia->fecha)->isoFormat('ddd D MMM') }}</td>
                <td class="td-litros">{{ number_format($dia->litros_dia, 1) }} L</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="tfoot-td">Total litros entregados</td>
                <td class="tfoot-td td-litros">
                    {{ number_format($liquidacion->litros_aprobados, 1) }} L
                </td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- Alerta adulteración --}}
    @if($liquidacion->num_adulteraciones > 0)
    <div class="alerta-adulteracion">
        ⚠️ <strong>{{ $liquidacion->num_adulteraciones }} adulteración(es) detectada(s).</strong>
        @if(($liquidacion->litros_adulterados ?? 0) > 0 && $liquidacion->num_adulteraciones >= 1)
            La leche de esta semana fue pagada a tarifa penalidad (S/ {{ number_format($liquidacion->precio_penalidad, 2) }}/L).
        @else
            Se aplicó descuento en precio por contenido de agua.
        @endif
        {{ $liquidacion->detalle_descuentos }}
    </div>
    @endif

    {{-- Resumen financiero --}}
    <div class="resumen">
        <div class="resumen-fila">
            <span class="lbl">Litros acumulados</span>
            <span class="val">{{ number_format($liquidacion->litros_aprobados, 1) }} L</span>
        </div>
        <div class="resumen-fila">
            <span class="lbl">Precio por litro</span>
            <span class="val">S/ {{ number_format($liquidacion->precio_litro, 2) }}</span>
        </div>
        @if($liquidacion->subtotal_normal != $liquidacion->total_bruto)
        <div class="resumen-fila">
            <span class="lbl">Subtotal leche</span>
            <span class="val">S/ {{ number_format($liquidacion->subtotal_normal, 2) }}</span>
        </div>
        @endif
        @if($liquidacion->total_descuentos > 0)
        <div class="resumen-fila">
            <span class="lbl">Descuentos</span>
            <span class="val" style="color:#c00">− S/ {{ number_format($liquidacion->total_descuentos, 2) }}</span>
        </div>
        @endif
        <div class="resumen-total">
            <span class="lbl">TOTAL A COBRAR</span>
            <span class="val">S/ {{ number_format($liquidacion->total_neto, 2) }}</span>
        </div>
    </div>

    {{-- Pie --}}
    <div class="pie">
        <div class="pie-metodo">
            Método de pago:
            <strong>
                @if($liquidacion->metodo_pago === 'yape') 📱 Yape
                @elseif($liquidacion->metodo_pago === 'plin') 📱 Plin
                @else 💵 Efectivo
                @endif
            </strong>
            @if($liquidacion->referencia_pago)
                <span style="font-size:9px">Ref: {{ $liquidacion->referencia_pago }}</span>
            @endif
        </div>
        <div class="pie-firma">
            <div class="linea-firma"></div>
            Firma del administrador
        </div>
    </div>

</div>

<button class="btn-imprimir" onclick="window.print()">🖨️ Imprimir sobre de pago</button>

<script>
    // Auto-abrir diálogo de impresión al cargar
    window.addEventListener('load', function() {
        setTimeout(function() { window.print(); }, 500);
    });
</script>

</body>
</html>
