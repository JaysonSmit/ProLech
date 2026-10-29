<?php

namespace App\Services;

use App\Models\PedidoWeb;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Emite la boleta electrónica ante SUNAT a través de Nubefact (OSE/PSE).
 *
 * Configuración (.env):
 *   NUBEFACT_URL=https://api.nubefact.com/api/v1/<tu-ruta>
 *   NUBEFACT_TOKEN=<token de tu cuenta>
 *
 * Sin credenciales queda en modo "simulada": la tienda funciona igual y la boleta
 * se muestra como representación interna (no válida para SUNAT).
 */
class FacturacionElectronicaService
{
    public function configurado(): bool
    {
        return filled(config('services.nubefact.url')) && filled(config('services.nubefact.token'));
    }

    /** Datos en el formato JSON de Nubefact para una boleta (tipo 2). */
    public function payloadBoleta(PedidoWeb $pedido): array
    {
        $pedido->loadMissing('detalles.producto');
        [$serie, $numero] = explode('-', $pedido->numero_comprobante);
        $d = $pedido->desglose_igv;
        $conDni = filled($pedido->cliente_documento) && preg_match('/^\d{8}$/', $pedido->cliente_documento);

        return [
            'operacion'                   => 'generar_comprobante',
            'tipo_de_comprobante'         => 2,           // 2 = boleta
            'serie'                       => $serie,      // B001
            'numero'                      => (int) $numero,
            'sunat_transaction'           => 1,           // venta interna
            'cliente_tipo_de_documento'   => $conDni ? 1 : '-',
            'cliente_numero_de_documento' => $conDni ? $pedido->cliente_documento : '-',
            'cliente_denominacion'        => $pedido->cliente_nombre,
            'cliente_direccion'           => $pedido->cliente_direccion,
            'cliente_email'               => $pedido->cliente_email ?? '',
            'fecha_de_emision'            => $pedido->created_at->format('d-m-Y'),
            'moneda'                      => 1,           // soles
            'porcentaje_de_igv'           => 18.00,
            'total_gravada'               => $d['gravada'],
            'total_igv'                   => $d['igv'],
            'total'                       => $d['total'],
            'enviar_automaticamente_a_la_sunat'  => true,
            'enviar_automaticamente_al_cliente'  => filled($pedido->cliente_email),
            'observaciones'               => "Pedido web {$pedido->codigo_pedido}",
            'items' => $pedido->detalles->map(function ($det) {
                $precio = (float) $det->precio_unitario;           // con IGV
                $valor  = round($precio / 1.18, 10);               // sin IGV
                $total  = round($precio * $det->cantidad, 2);
                $subtotal = round($valor * $det->cantidad, 2);
                return [
                    'unidad_de_medida'        => 'NIU',
                    'codigo'                  => (string) $det->producto_tienda_id,
                    'descripcion'             => $det->producto?->nombre ?? 'Producto lácteo',
                    'cantidad'                => $det->cantidad,
                    'valor_unitario'          => $valor,
                    'precio_unitario'         => $precio,
                    'subtotal'                => $subtotal,
                    'tipo_de_igv'             => 1,                // gravado - operación onerosa
                    'igv'                     => round($total - $subtotal, 2),
                    'total'                   => $total,
                    'anticipo_regularizacion' => false,
                ];
            })->values()->all(),
        ];
    }

    /** Envía la boleta y guarda el resultado en el pedido. Nunca lanza excepción. */
    public function emitirBoleta(PedidoWeb $pedido): PedidoWeb
    {
        if (! $this->configurado()) {
            $pedido->update([
                'sunat_estado'  => 'simulada',
                'sunat_mensaje' => 'Nubefact no configurado: representación interna, sin validez ante SUNAT.',
            ]);
            return $pedido;
        }

        try {
            $respuesta = Http::withToken(config('services.nubefact.token'))
                ->acceptJson()
                ->timeout(20)
                ->post(config('services.nubefact.url'), $this->payloadBoleta($pedido));

            $json = $respuesta->json() ?? [];
            if ($respuesta->successful() && empty($json['errors'])) {
                $pedido->update([
                    'sunat_estado'  => ($json['aceptada_por_sunat'] ?? false) ? 'aceptada' : 'enviada',
                    'sunat_pdf_url' => $json['enlace_del_pdf'] ?? null,
                    'sunat_mensaje' => $json['sunat_description'] ?? 'Comprobante generado en Nubefact.',
                ]);
            } else {
                $pedido->update([
                    'sunat_estado'  => 'rechazada',
                    'sunat_mensaje' => is_string($json['errors'] ?? null) ? $json['errors'] : $respuesta->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[Nubefact] No se pudo emitir la boleta', ['pedido' => $pedido->codigo_pedido, 'error' => $e->getMessage()]);
            $pedido->update(['sunat_estado' => 'error', 'sunat_mensaje' => 'Sin conexión con Nubefact: ' . $e->getMessage()]);
        }

        return $pedido;
    }
}
