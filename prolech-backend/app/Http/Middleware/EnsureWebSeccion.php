<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe rutas del panel web según RolWeb (admin ve todo).
 */
class EnsureWebSeccion
{
    public function handle(Request $request, Closure $next, string $seccion): Response
    {
        $user = $request->user();

        if (! $user || ! $user->seccionWebPermitida($seccion)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
