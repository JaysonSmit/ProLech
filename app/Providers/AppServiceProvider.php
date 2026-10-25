<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Login: máximo 5 intentos por minuto por correo + IP (frena ataques de fuerza bruta)
        // y 30 por IP para que no se puedan probar muchos correos distintos.
        $porCorreo = fn (Request $r) => strtolower((string) $r->input('email')) . '|' . $r->ip();

        RateLimiter::for('login-api', fn (Request $r) => [
            Limit::perMinute(5)->by($porCorreo($r))->response(fn () => response()->json(
                ['message' => 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.'], 429
            )),
            Limit::perMinute(30)->by('ip:' . $r->ip()),
        ]);

        RateLimiter::for('login-web', fn (Request $r) => [
            Limit::perMinute(5)->by($porCorreo($r))->response(fn () => back()
                ->withInput($r->only('email'))
                ->withErrors(['email' => 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.'])),
            Limit::perMinute(30)->by('ip:' . $r->ip()),
        ]);
    }
}
