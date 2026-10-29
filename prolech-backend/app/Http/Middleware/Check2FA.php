<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check2FA — Middleware de verificación OTP por WhatsApp.
 *
 * Roles obligatorios: admin, chofer, jefe_produccion.
 *
 * Flujo por sesión:
 *  - Usuario se loguea → sesión auth OK.
 *  - Si su rol requiere 2FA y tiene OTP activo → pide código WhatsApp.
 *  - Si su rol requiere 2FA pero no configuró aún → lleva al setup.
 *  - Tras verificar → session('2fa_verified') = true → pasa libre todo el día.
 *  - Al cerrar sesión → session se destruye → próximo login pide código de nuevo.
 */
class Check2FA
{
    private const ROLES_OBLIGATORIOS = ['admin', 'chofer', 'jefe_produccion'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Solo aplica a roles obligatorios
        if (! $user->tieneRol(...self::ROLES_OBLIGATORIOS)) {
            return $next($request);
        }

        // Rutas 2FA y logout nunca redirigen (evita loop)
        if ($request->routeIs('2fa.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        // ── Ya verificó en esta sesión → libre ─────────────────────────────
        if ($request->session()->get('2fa_verified')) {
            return $next($request);
        }

        // ── Tiene OTP activo → pedir código ────────────────────────────────
        if ($user->otp_activo && $user->telefono) {
            return redirect()->route('2fa.verificar');
        }

        // ── No configuró aún → forzar setup ───────────────────────────────
        return redirect()->route('2fa.setup');
    }
}
