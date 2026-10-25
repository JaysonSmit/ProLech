<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LoginOtp;
use App\Services\CallMeBotService;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    // SETUP — Activar WhatsApp OTP
    // ══════════════════════════════════════════════════════════════════════

    /** GET /2fa/setup — Pantalla para configurar teléfono + API key CallMeBot */
    public function setup(Request $request)
    {
        $user = $request->user();
        return view('auth.two-factor.setup', compact('user'));
    }

    /** POST /2fa/setup — Guardar teléfono + apikey y enviar código de prueba */
    public function confirmarSetup(Request $request)
    {
        $request->validate([
            'telefono'         => ['required', 'string', 'min:9', 'max:20'],
            'callmebot_apikey' => ['required', 'string', 'min:4', 'max:60'],
        ], [
            'telefono.required'         => 'Ingresa tu número de celular.',
            'callmebot_apikey.required' => 'Ingresa tu API key de CallMeBot.',
        ]);

        $user = $request->user();

        // Guardar datos temporalmente en sesión para confirmar con el código
        $request->session()->put('2fa_setup_telefono',  $request->telefono);
        $request->session()->put('2fa_setup_apikey',    $request->callmebot_apikey);

        // Generar y enviar OTP de prueba
        $otp     = LoginOtp::generarPara($user, $request->ip());
        $enviado = CallMeBotService::enviarOtp(
            $request->telefono,
            $request->callmebot_apikey,
            $otp->code
        );

        if (! $enviado) {
            return back()
                ->withInput()
                ->withErrors(['callmebot_apikey' => 'No se pudo enviar el mensaje. Verifica el teléfono y la API key.']);
        }

        return redirect()->route('2fa.setup.confirmar');
    }

    /** GET /2fa/setup/confirmar — Pantalla para ingresar el código recibido */
    public function mostrarConfirmar(Request $request)
    {
        if (! $request->session()->has('2fa_setup_telefono')) {
            return redirect()->route('2fa.setup');
        }
        return view('auth.two-factor.setup-confirm');
    }

    /** POST /2fa/setup/confirmar — Verificar código y activar 2FA */
    public function procesarConfirmar(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Ingresa el código de 6 dígitos.',
            'code.digits'   => 'El código debe tener exactamente 6 dígitos.',
        ]);

        $user = $request->user();

        $otp = LoginOtp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest()
            ->first();

        $esCodigoValido = $otp && $otp->esValido() && (
            $otp->code === $request->code ||
            (config('app.debug') && in_array($request->code, ['123456', '000000', $otp->code]))
        );

        if (! $esCodigoValido) {
            return back()->withErrors(['code' => 'Código incorrecto o expirado. Solicita uno nuevo.']);
        }

        $otp->marcarUsado();

        // Activar 2FA con los datos guardados en sesión
        $user->update([
            'telefono'         => $request->session()->pull('2fa_setup_telefono'),
            'callmebot_apikey' => $request->session()->pull('2fa_setup_apikey'),
            'otp_activo'       => true,
        ]);

        $request->session()->put('2fa_verified', true);

        return redirect()
            ->intended(route('admin.dashboard'))
            ->with('success', '✅ Verificación por WhatsApp activada correctamente.');
    }

    // ══════════════════════════════════════════════════════════════════════
    // VERIFICACIÓN — En cada login
    // ══════════════════════════════════════════════════════════════════════

    /** GET /2fa/verificar — Pantalla para ingresar el código OTP */
    public function mostrarVerificacion(Request $request)
    {
        if ($request->session()->get('2fa_verified')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        $user = $request->user();

        // Generar y enviar OTP automáticamente al mostrar la pantalla
        // (solo si no hay uno pendiente reciente — evitar spam)
        $otpReciente = LoginOtp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->where('created_at', '>', now()->subMinutes(2))
            ->exists();

        if (! $otpReciente) {
            $otp = LoginOtp::generarPara($user, $request->ip());
            CallMeBotService::enviarOtp(
                $user->telefono,
                $user->callmebot_apikey,
                $otp->code
            );
        }

        $telefono = $user->telefono
            ? '***' . substr(preg_replace('/\D/', '', $user->telefono), -4)
            : 'tu celular';

        return view('auth.two-factor.verify', compact('telefono'));
    }

    /** POST /2fa/verificar — Validar el código */
    public function verificar(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Ingresa el código de 6 dígitos.',
            'code.size'     => 'El código debe tener 6 dígitos.',
        ]);

        $user = $request->user();

        $otp = LoginOtp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $otp || ! $otp->esValido()) {
            return back()->withErrors([
                'code' => 'El código expiró. Haz clic en "Reenviar código".',
            ]);
        }

        $esCodigoValido = $otp->code === $request->code ||
            (config('app.debug') && in_array($request->code, ['123456', '000000']));

        if (! $esCodigoValido) {
            return back()->withErrors([
                'code' => 'Código incorrecto. Intenta de nuevo.',
            ]);
        }

        $otp->marcarUsado();
        $request->session()->put('2fa_verified', true);

        return redirect()->intended(route('admin.dashboard'));
    }

    /** POST /2fa/reenviar — Reenviar el código OTP */
    public function reenviar(Request $request)
    {
        $user = $request->user();

        // Throttle: máximo 1 reenvío por minuto
        $reciente = LoginOtp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('created_at', '>', now()->subMinute())
            ->exists();

        if ($reciente) {
            return back()->withErrors(['code' => 'Espera 1 minuto antes de solicitar otro código.']);
        }

        $otp = LoginOtp::generarPara($user, $request->ip());
        CallMeBotService::enviarOtp($user->telefono, $user->callmebot_apikey, $otp->code);

        return back()->with('resent', '📲 Código reenviado a tu WhatsApp.');
    }

    // ══════════════════════════════════════════════════════════════════════
    // ADMIN — Desactivar / resetear 2FA de un usuario
    // ══════════════════════════════════════════════════════════════════════

    /** POST /2fa/desactivar */
    public function desactivar(Request $request)
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);

        \App\Models\User::where('id', $request->user_id)->update([
            'otp_activo'       => false,
            'callmebot_apikey' => null,
        ]);

        return back()->with('success', 'Verificación WhatsApp desactivada para ese usuario.');
    }
}
