<?php

namespace App\Services;

use App\Models\Acopio;
use App\Models\Notificacion;
use App\Models\User;

/**
 * Regla de 2 strikes: 1 adulteración (observado o rechazado) en la semana de liquidación
 * → aviso al administrador; 2 o más → el ganadero queda suspendido y se avisa.
 * La usan la app clásica (AcopioApiController) y la app de campo (AcopioCampoEnlace).
 */
class AdulteracionService
{
    private static bool $pausada = false;

    /** Ejecuta $callback sin aplicar la regla (datos de demostración: no suspender a nadie). */
    public static function sinAplicar(callable $callback): mixed
    {
        self::$pausada = true;
        try {
            return $callback();
        } finally {
            self::$pausada = false;
        }
    }

    /** @return string|null  mensaje para el chofer, o null si no aplica */
    public function evaluar(Acopio $acopio): ?string
    {
        if (self::$pausada || ! in_array($acopio->estado, ['rechazado', 'observado'], true)) {
            return null;
        }

        $ganadero = $acopio->ganadero;
        [$inicioCiclo, $finCiclo] = Acopio::rangoSemanaLiquidacion();

        // Un acopio de otra semana (p. ej. sincronizado tarde) no cuenta para la semana activa;
        // un ganadero ya suspendido no se vuelve a suspender ni a notificar.
        $fecha = $acopio->fecha->toDateString();
        if (! $ganadero || $ganadero->estaSuspendido() || $fecha < $inicioCiclo || $fecha > $finCiclo) {
            return null;
        }

        $strikesEnSemana = Acopio::where('ganadero_id', $ganadero->id)
                                 ->whereIn('estado', ['rechazado', 'observado'])
                                 ->whereDate('fecha', '>=', $inicioCiclo)->whereDate('fecha', '<=', $finCiclo)
                                 ->count();

        if ($strikesEnSemana >= 2) {
            $ganadero->suspender();

            $this->notificar(
                titulo:   "🚫 Proveedor suspendido: {$ganadero->nombre}",
                mensaje:  "El proveedor {$ganadero->nombre} (DNI: {$ganadero->dni}) "
                        . "alcanzó 2 adulteraciones en la semana {$inicioCiclo} → {$finCiclo}. "
                        . "Ha sido suspendido automáticamente. Revise en el panel.",
                tipo:     'suspension',
                ganaderoId: $ganadero->id,
            );

            return "⚠️ SUSPENDIDO: {$ganadero->nombre} acumuló {$strikesEnSemana} "
                . "adulteraciones esta semana y ha sido bloqueado automáticamente.";
        }

        if ($strikesEnSemana === 1) {
            $this->notificar(
                titulo:   "⚠️ Strike 1/2: {$ganadero->nombre}",
                mensaje:  "El proveedor {$ganadero->nombre} tiene su primer strike por adulteración "
                        . "esta semana. Una adulteración más resultará en suspensión automática.",
                tipo:     'adulteracion_exceso',
                ganaderoId: $ganadero->id,
            );

            return "⚠️ Primer strike: {$ganadero->nombre} tiene 1 adulteración esta semana. "
                . "Una más y será suspendido.";
        }

        return null;
    }

    private function notificar(string $titulo, string $mensaje, string $tipo, int $ganaderoId): void
    {
        try {
            // Administrador activo (user id 1 por convención si no hay)
            $adminId = User::conRol('admin')->where('activo', true)->orderBy('id')->value('id') ?? 1;

            Notificacion::enviar(
                userId:       $adminId,
                titulo:       $titulo,
                mensaje:      $mensaje,
                tipo:         $tipo,
                referenciaId: $ganaderoId,
            );
        } catch (\Throwable) {
            // No interrumpir el flujo si falla la notificación
        }
    }
}
