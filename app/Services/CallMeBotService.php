<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * CallMeBotService
 *
 * Envía mensajes de WhatsApp gratis usando la API de CallMeBot.
 * Cada usuario debe activar su número UNA SOLA VEZ:
 *
 *   1. Agregar el número +34 644 59 21 97 a contactos de WhatsApp
 *   2. Enviarle el mensaje: "I allow callmebot to send me messages"
 *   3. Recibirá su API key personal (ej: 1234567)
 *   4. El admin registra esa API key en su perfil dentro del sistema
 *
 * Documentación: https://www.callmebot.com/blog/free-api-whatsapp-messages/
 */
class CallMeBotService
{
    private const API_URL = 'https://api.callmebot.com/whatsapp.php';

    /**
     * Envía un mensaje WhatsApp al usuario.
     *
     * @param  string $telefono   Número con código de país SIN + (ej: 51901344157)
     * @param  string $apikey     API key personal del usuario en CallMeBot
     * @param  string $mensaje    Texto del mensaje
     * @return bool               true = enviado, false = error
     */
    public static function enviar(string $telefono, string $apikey, string $mensaje): bool
    {
        // En dev/test sin apikey → simular envío en logs
        if (empty($apikey) || empty($telefono)) {
            Log::info('[CallMeBot] Simulación — sin credenciales.', [
                'telefono' => $telefono,
                'mensaje'  => $mensaje,
            ]);
            return true; // No bloquear el login en desarrollo
        }

        // Normalizar número: asegurarse de que empiece con código de país
        $numero = preg_replace('/\D/', '', $telefono);
        if (strlen($numero) === 9) {
            $numero = '51' . $numero; // Perú por defecto
        }

        $url = self::API_URL . '?' . http_build_query([
            'phone'  => $numero,
            'text'   => $mensaje,
            'apikey' => $apikey,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'ProLech-ERP/1.0',
        ]);

        $respuesta  = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('[CallMeBot] Error cURL.', [
                'numero' => $numero,
                'error'  => $curlError,
            ]);
            if (config('app.debug')) {
                Log::info("[CallMeBot DEV] Error cURL simulado exitoso para desarrollo.");
                return true;
            }
            return false;
        }

        // CallMeBot retorna 200 con "Message queued." o similar en éxito
        if ($httpStatus === 200) {
            Log::info('[CallMeBot] Mensaje enviado.', ['numero' => $numero]);
            return true;
        }

        Log::warning('[CallMeBot] Respuesta inesperada.', [
            'http_status' => $httpStatus,
            'respuesta'   => $respuesta,
        ]);

        if (config('app.debug')) {
            Log::info("[CallMeBot DEV] Permitiendo simulación en entorno local/debug.");
            return true;
        }

        return false;
    }

    /**
     * Envía el código OTP al usuario con el formato estándar ProLech.
     */
    public static function enviarOtp(string $telefono, string $apikey, string $codigo): bool
    {
        Log::info("🔐 [CallMeBot OTP DEV] Código generado para {$telefono}: {$codigo}");
        $mensaje = "🔐 *ProLech — Código de verificación*\n\n"
                 . "Tu código de acceso es:\n\n"
                 . "*{$codigo}*\n\n"
                 . "⏱ Válido por 10 minutos.\n"
                 . "No compartas este código con nadie.";

        return self::enviar($telefono, $apikey, $mensaje);
    }
}
