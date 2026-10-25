<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Envía un mensaje individual por WhatsApp vía Ultramsg API.
     * Si no hay credenciales en .env, registra en logs sin lanzar excepción.
     */
    public static function enviar(string $telefono, string $mensaje): bool
    {
        $instanceId = config('services.ultramsg.instance_id') ?? env('ULTRAMSG_INSTANCE_ID');
        $token      = config('services.ultramsg.token')       ?? env('ULTRAMSG_TOKEN');

        if (empty($instanceId) || empty($token)) {
            Log::info('[WhatsApp] Simulación de envío (credenciales no configuradas).', [
                'telefono' => $telefono,
                'mensaje'  => substr($mensaje, 0, 120) . '...',
            ]);
            return true; // Retorna true en modo dev/simulación para no bloquear la operación
        }

        $numero = preg_replace('/\D/', '', $telefono);
        if (! str_starts_with($numero, '51') && strlen($numero) === 9) {
            $numero = '51' . $numero;
        }

        $url  = "https://api.ultramsg.com/{$instanceId}/messages/chat";
        $body = http_build_query([
            'token' => $token,
            'to'    => $numero,
            'body'  => $mensaje,
        ]);

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

        $respuesta  = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('[WhatsApp] cURL error.', [
                'numero' => $numero,
                'error'  => $curlError,
            ]);
            return false;
        }

        $data = json_decode($respuesta, true);

        if ($httpStatus === 200 && isset($data['sent']) && $data['sent'] === 'true') {
            Log::info('[WhatsApp] Mensaje enviado con éxito.', ['numero' => $numero]);
            return true;
        }

        Log::warning('[WhatsApp] Respuesta inesperada del proveedor.', [
            'http_status' => $httpStatus,
            'respuesta'   => $respuesta,
        ]);

        return false;
    }

    /**
     * Envía un mensaje masivo a una lista de números telefónicos.
     * Retorna la cantidad de envíos exitosos.
     */
    public static function enviarMasivo(iterable $telefonos, string $mensaje): int
    {
        $exitosos = 0;
        foreach ($telefonos as $tel) {
            if (! empty($tel) && self::enviar($tel, $mensaje)) {
                $exitosos++;
            }
        }
        return $exitosos;
    }
}
