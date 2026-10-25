<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Acopio;
use App\Models\Configuracion;
use App\Models\Ganadero;
use App\Models\Liquidacion;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiquidacionController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // GET /admin/liquidaciones
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $semanaInicioParam = $request->query('semana_inicio');
        $semanaInicio = $semanaInicioParam
            ? Carbon::parse($semanaInicioParam)
            : Liquidacion::inicioSemana(now());

        $semanaFin = Liquidacion::finSemana($semanaInicio);
        $fechaPago = Liquidacion::fechaPago($semanaFin);

        $liquidaciones = Liquidacion::with('ganadero:id,dni,nombre,ruta_asignada,telefono,metodo_pago_preferido')
            ->where('semana_inicio', $semanaInicio->toDateString())
            ->orderBy('estado')
            ->orderByDesc('total_neto')
            ->paginate(25)
            ->withQueryString();

        $resumen = [
            'total_ganaderos' => $liquidaciones->total(),
            'total_litros'    => Liquidacion::where('semana_inicio', $semanaInicio->toDateString())->sum('litros_aprobados'),
            'total_pagar'     => Liquidacion::where('semana_inicio', $semanaInicio->toDateString())->sum('total_neto'),
            'pendientes_pago' => Liquidacion::where('semana_inicio', $semanaInicio->toDateString())->where('estado', 'confirmada')->count(),
        ];

        return view('admin.liquidaciones.index', compact(
            'liquidaciones', 'semanaInicio', 'semanaFin', 'fechaPago', 'resumen'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /admin/liquidaciones/generar
    //
    // FASE 3 — Después de generar cada liquidación, envía un WhatsApp
    //           al proveedor con su resumen de pago (si tiene teléfono).
    // ──────────────────────────────────────────────────────────────────────────

    public function generar(Request $request): RedirectResponse
    {
        $semanaInicio = Liquidacion::inicioSemana(now());
        $semanaFin    = Liquidacion::finSemana($semanaInicio);
        $fechaPago    = Liquidacion::fechaPago($semanaFin);

        $ganaderos = Ganadero::where('activo', true)->get();
        $generadas = 0;
        $enviadas  = 0;

        foreach ($ganaderos as $ganadero) {
            // Evitar regenerar si ya existe
            if (Liquidacion::where('ganadero_id', $ganadero->id)
                           ->where('semana_inicio', $semanaInicio->toDateString())
                           ->exists()) {
                continue;
            }

            $liquidacion = $this->calcularLiquidacion(
                ganadero:    $ganadero,
                semanaInicio: $semanaInicio,
                semanaFin:   $semanaFin,
                fechaPago:   $fechaPago,
                generadaPor: $request->user()->id
            );

            if ($liquidacion === null) {
                continue;
            }

            $generadas++;

            // ── FASE 3: Enviar WhatsApp al proveedor si tiene teléfono ────────
            if (! empty($ganadero->telefono)) {
                $periodoTexto = $semanaInicio->format('d/m') . ' al ' . $semanaFin->format('d/m/Y');
                $mensaje      = $this->buildMensajeLiquidacion(
                    nombre:       $ganadero->nombre,
                    periodo:      $periodoTexto,
                    litros:       $liquidacion->litros_aprobados,
                    totalNeto:    $liquidacion->total_neto,
                    fechaPago:    $fechaPago,
                    adulteraciones: $liquidacion->num_adulteraciones
                );

                $enviado = $this->enviarWhatsApp($ganadero->telefono, $mensaje);
                if ($enviado) {
                    $enviadas++;
                }
            }
        }

        $msg = "✅ Se generaron {$generadas} liquidaciones para la semana del "
             . "{$semanaInicio->format('d/m/Y')} al {$semanaFin->format('d/m/Y')}.";

        if ($enviadas > 0) {
            $msg .= " Se enviaron {$enviadas} notificaciones por WhatsApp.";
        }

        return redirect()->route('admin.liquidaciones.index')->with('success', $msg);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /admin/liquidaciones/{liquidacion}/confirmar
    // ──────────────────────────────────────────────────────────────────────────

    public function confirmar(Liquidacion $liquidacion): RedirectResponse
    {
        if ($liquidacion->estado !== 'borrador') {
            return back()->with('error', 'Solo se pueden confirmar liquidaciones en estado borrador.');
        }

        $liquidacion->update(['estado' => 'confirmada']);

        return back()->with('success', "✅ Liquidación de {$liquidacion->ganadero->nombre} confirmada.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /admin/liquidaciones/{liquidacion}/marcar-pagada
    // ──────────────────────────────────────────────────────────────────────────

    public function marcarPagada(Request $request, Liquidacion $liquidacion): RedirectResponse
    {
        if ($liquidacion->estado === 'pagada') {
            return back()->with('error', 'Esta liquidación ya fue marcada como pagada.');
        }

        $validated = $request->validate([
            'metodo_pago'     => ['required', 'in:efectivo,yape,plin'],
            'referencia_pago' => ['nullable', 'string', 'max:100'],
        ]);

        $liquidacion->update([
            'estado'          => 'pagada',
            'pagada_at'       => now(),
            'metodo_pago'     => $validated['metodo_pago'],
            'referencia_pago' => $validated['referencia_pago'] ?? null,
        ]);

        // Notificar pago por WhatsApp si el proveedor tiene teléfono
        $ganadero = $liquidacion->ganadero;
        if (! empty($ganadero->telefono)) {
            $metodoLabel = ['efectivo' => 'efectivo', 'yape' => 'Yape', 'plin' => 'Plin'][$validated['metodo_pago']];
            $msgPago = "✅ ProLech: Tu pago de S/ " . number_format($liquidacion->total_neto, 2)
                     . " ha sido procesado por {$metodoLabel}."
                     . ($validated['referencia_pago'] ? " Referencia: {$validated['referencia_pago']}." : "")
                     . " ¡Gracias {$ganadero->nombre}!";
            $this->enviarWhatsApp($ganadero->telefono, $msgPago);
        }

        $metodoLabel = ['efectivo' => '💵 Efectivo', 'yape' => '📱 Yape', 'plin' => '📱 Plin'][$validated['metodo_pago']];
        return back()->with('success', "✅ Pago de {$ganadero->nombre} registrado como {$metodoLabel}. Total: S/ " . number_format($liquidacion->total_neto, 2));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /admin/liquidaciones/{liquidacion}
    // ──────────────────────────────────────────────────────────────────────────

    public function show(Liquidacion $liquidacion): View
    {
        $liquidacion->load('ganadero');

        $acopios = Acopio::with('chofer:id,name')
            ->where('ganadero_id', $liquidacion->ganadero_id)
            ->whereBetween('fecha', [
                $liquidacion->semana_inicio->toDateString(),
                $liquidacion->semana_fin->toDateString(),
            ])
            ->orderBy('fecha')
            ->get();

        return view('admin.liquidaciones.show', compact('liquidacion', 'acopios'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /admin/liquidaciones/{liquidacion}/sobre-pdf
    //
    // Genera el sobre de pago en HTML con estilos de impresión @media print.
    // El navegador lo imprime directamente al abrirlo (sin dependencias externas).
    // ──────────────────────────────────────────────────────────────────────────

    public function sobrePdf(Liquidacion $liquidacion): \Illuminate\Http\Response
    {
        $liquidacion->load('ganadero');

        $dias = Acopio::where('ganadero_id', $liquidacion->ganadero_id)
            ->whereBetween('fecha', [
                $liquidacion->semana_inicio->toDateString(),
                $liquidacion->semana_fin->toDateString(),
            ])
            ->selectRaw('fecha, SUM(litros) as litros_dia')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $html = view('admin.liquidaciones.sobre_pdf', compact('liquidacion', 'dias'))->render();

        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=UTF-8',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // MÉTODOS PRIVADOS
    // ══════════════════════════════════════════════════════════════════════════

    // ──────────────────────────────────────────────────────────────────────────
    // Construye el mensaje de WhatsApp para la liquidación
    // ──────────────────────────────────────────────────────────────────────────

    private function buildMensajeLiquidacion(
        string $nombre,
        string $periodo,
        float  $litros,
        float  $totalNeto,
        Carbon $fechaPago,
        int    $adulteraciones
    ): string {
        $msg = "🐄 *ProLech Huata*\n\n";
        $msg .= "Hola *{$nombre}*, tu liquidación del {$periodo} está lista.\n\n";
        $msg .= "📊 *Resumen de la semana:*\n";
        $msg .= "• Total leche entregada: *{$litros} L*\n";
        $msg .= "• Total a cobrar este viernes: *S/ " . number_format($totalNeto, 2) . "*\n";

        if ($adulteraciones > 0) {
            $msg .= "• ⚠️ Adulteraciones registradas: *{$adulteraciones}*\n";
            if ($adulteraciones >= 2) {
                $msg .= "• 🚫 Su cuenta ha sido *suspendida* por reincidencia.\n";
            }
        }

        $msg .= "\n💵 *Día de pago:* " . $fechaPago->isoFormat('dddd D [de] MMMM') . "\n";
        $msg .= "\nConsultas al administrador: ProLech Huata 🧀";

        return $msg;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FASE 3 — Enviar mensaje por WhatsApp usando cURL (Ultramsg API)
    //
    // Configura en .env:
    //   ULTRAMSG_INSTANCE_ID=your_instance_id
    //   ULTRAMSG_TOKEN=your_token
    //
    // Ultramsg: https://ultramsg.com — API genérica de WhatsApp Business.
    // El número de teléfono debe incluir código de país sin '+': "5199XXXXXXXX"
    // ──────────────────────────────────────────────────────────────────────────

    private function enviarWhatsApp(string $telefono, string $mensaje): bool
    {
        $instanceId = config('services.ultramsg.instance_id') ?? env('ULTRAMSG_INSTANCE_ID');
        $token      = config('services.ultramsg.token')       ?? env('ULTRAMSG_TOKEN');

        // Si no hay credenciales configuradas, loguear y retornar false sin crashear
        if (empty($instanceId) || empty($token)) {
            \Illuminate\Support\Facades\Log::info('[WhatsApp] Credenciales no configuradas. Mensaje no enviado.', [
                'telefono' => $telefono,
                'preview'  => substr($mensaje, 0, 100),
            ]);
            return false;
        }

        // Normalizar el número: solo dígitos, añadir código de Perú (51) si no lo tiene
        $numero = preg_replace('/\D/', '', $telefono);
        if (! str_starts_with($numero, '51') && strlen($numero) === 9) {
            $numero = '51' . $numero;
        }

        $url  = "https://api.ultramsg.com/{$instanceId}/messages/chat";
        $body = http_build_query([
            'token'  => $token,
            'to'     => $numero,
            'body'   => $mensaje,
        ]);

        // cURL nativo de PHP
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $respuesta   = curl_exec($ch);
        $httpStatus  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError   = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            \Illuminate\Support\Facades\Log::error('[WhatsApp] cURL error.', [
                'numero' => $numero,
                'error'  => $curlError,
            ]);
            return false;
        }

        $data = json_decode($respuesta, true);

        if ($httpStatus === 200 && isset($data['sent']) && $data['sent'] === 'true') {
            \Illuminate\Support\Facades\Log::info('[WhatsApp] Mensaje enviado.', ['numero' => $numero]);
            return true;
        }

        \Illuminate\Support\Facades\Log::warning('[WhatsApp] Respuesta inesperada.', [
            'http_status' => $httpStatus,
            'respuesta'   => $respuesta,
        ]);
        return false;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Lógica central de cálculo de liquidación
    // ──────────────────────────────────────────────────────────────────────────

    private function calcularLiquidacion(
        Ganadero $ganadero,
        Carbon   $semanaInicio,
        Carbon   $semanaFin,
        Carbon   $fechaPago,
        int      $generadaPor
    ): ?Liquidacion {
        $acopios = Acopio::where('ganadero_id', $ganadero->id)
                         ->whereBetween('fecha', [$semanaInicio->toDateString(), $semanaFin->toDateString()])
                         ->get();

        if ($acopios->isEmpty()) {
            return null;
        }

        // ── Precios desde configuración ───────────────────────────────────────
        $precioLitro     = Configuracion::precioLitroActual();
        $precioPenalidad = (float) Configuracion::get('precio_litro_penalidad', 0.65);
        $aguaLimite      = (float) Configuracion::get('agua_limite_retiro', 5.0);

        // ── Clasificar acopios según reglas del documento Ecolactus (RF-PAG-03) ─
        $precioDescuentoLeve = $precioLitro - (float) Configuracion::get('descuento_litro_leve', 0.10);

        $esLeve = fn ($a) => ($a->porcentaje_agua ?? 0) > 0
            && ($a->porcentaje_agua ?? 0) <= $aguaLimite
            && in_array($a->estado, ['observado', 'rechazado'], true);

        $esGrave = fn ($a) => ($a->porcentaje_agua ?? 0) > $aguaLimite
            && in_array($a->estado, ['observado', 'rechazado'], true);

        $acopiosLeves     = $acopios->filter($esLeve);
        $acopiosGraves    = $acopios->filter($esGrave);
        $acopiosDescuento = $acopios->where('tiene_descuento', true);
        $numLeves         = $acopiosLeves->count();
        $tieneGrave       = $acopiosGraves->isNotEmpty();

        $subtotalPenalidad = 0.0;
        $detalle           = [];

        if ($tieneGrave) {
            // ≥5% primera vez → toda la semana a precio penalidad (S/0.60–0.70)
            $litrosTotales     = (float) $acopios->sum('litros');
            $subtotalNormal    = round($litrosTotales * $precioPenalidad, 2);
            $litrosAprobados   = $litrosTotales;
            $litrosAdulterados = (float) $acopiosGraves->sum('litros');
            $detalle[]         = "Adulteración >{$aguaLimite}%: TODA la semana a S/ {$precioPenalidad}/L";
        } elseif ($numLeves >= 2) {
            // <5% segunda vez → retiro de esas entregas + descuento en el resto
            $litrosRetirados   = (float) $acopiosLeves->sum('litros');
            $litrosRestantes   = max(0, (float) $acopios->sum('litros') - $litrosRetirados);
            $subtotalNormal    = round($litrosRestantes * $precioDescuentoLeve, 2);
            $litrosAprobados   = $litrosRestantes;
            $litrosAdulterados = $litrosRetirados;
            $detalle[]         = "2da adulteración leve (<{$aguaLimite}%): retiro {$litrosRetirados} L, resto a S/ {$precioDescuentoLeve}/L";
        } elseif ($numLeves === 1) {
            // <5% primera vez → paga menos TODA la semana, sin retiro
            $litrosTotales   = (float) $acopios->sum('litros');
            $subtotalNormal  = round($litrosTotales * $precioDescuentoLeve, 2);
            $litrosAprobados = $litrosTotales;
            $litrosAdulterados = 0.0;
            $detalle[]       = "1ra adulteración leve: semana completa a S/ {$precioDescuentoLeve}/L";
        } else {
            // Sin adulteración leve/grave — descuento puntual por ~1% agua si aplica
            $litrosAprobados    = (float) $acopios->whereNotIn('estado', ['rechazado'])->sum('litros');
            $litrosAdulterados  = 0.0;
            $litrosConDescuento = (float) $acopiosDescuento->sum('litros');
            $litrosLimpios      = max(0, $litrosAprobados - $litrosConDescuento);

            $subtotalNormal = round($litrosLimpios * $precioLitro, 2)
                            + round($litrosConDescuento * $precioDescuentoLeve, 2);

            if ($litrosConDescuento > 0) {
                $detalle[] = "Descuento por agua ~1% en {$litrosConDescuento} L";
            }
        }

        $numAdulteraciones = $acopios->whereIn('estado', ['rechazado', 'observado'])->count();
        $totalBruto        = $subtotalNormal + $subtotalPenalidad;
        $totalNeto         = max(0, $totalBruto);

        if ($numAdulteraciones > 0 && empty($detalle)) {
            $detalle[] = "{$numAdulteraciones} incidencia(s) de calidad en la semana";
        }

        return Liquidacion::create([
            'ganadero_id'            => $ganadero->id,
            'semana_inicio'          => $semanaInicio->toDateString(),
            'semana_fin'             => $semanaFin->toDateString(),
            'fecha_pago'             => $fechaPago->toDateString(),
            'litros_aprobados'       => $litrosAprobados,
            'precio_litro'           => $precioLitro,
            'subtotal_normal'        => $subtotalNormal,
            'litros_adulterados'     => $litrosAdulterados,
            'precio_penalidad'       => $precioPenalidad,
            'subtotal_penalidad'     => $subtotalPenalidad,
            'num_adulteraciones'     => $numAdulteraciones,
            'quesos_solicitados'     => 0,
            'precio_queso_proveedor' => (float) Configuracion::get('precio_queso_proveedor', 18.00),
            'descuento_quesos'       => 0,
            'otros_descuentos'       => 0,
            'detalle_descuentos'     => $detalle ? implode('; ', $detalle) : null,
            'total_bruto'            => $totalBruto,
            'total_descuentos'       => 0,
            'total_neto'             => $totalNeto,
            'estado'                 => 'borrador',
            'generada_por'           => $generadaPor,
        ]);
    }
}
