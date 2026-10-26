<?php

namespace App\Services;

use App\Models\Acopio;
use App\Models\AcopioCampo;
use App\Models\Ganadero;
use App\Models\Liquidacion;

/**
 * Vincula los acopios de la app de campo (offline-first, tabla acopios_campo) con la
 * tabla acopios, que es la que leen el dashboard, liquidaciones, reportes y alertas.
 * Cada registro de campo tiene un acopio espejo (acopios.acopio_campo_id) que se crea,
 * actualiza y elimina junto con él.
 */
class AcopioCampoEnlace
{
    public function sincronizar(AcopioCampo $campo): ?Acopio
    {
        // Ganadero que ya no existe en la planta: no hay a quién liquidarle
        if (! Ganadero::whereKey($campo->ganadero_id)->exists()) {
            return null;
        }

        $agua = (float) $campo->porcentaje_agua;
        $datos = [
            'ganadero_id'     => $campo->ganadero_id,
            'chofer_id'       => $campo->chofer_id,
            'litros'          => $campo->litros,
            'porcentaje_agua' => $agua,
            'densidad'        => $campo->densidad ?: null,
            'grasa'           => $campo->grasa,
            // El estado es el que vio el chofer y quedó en el ticket del ganadero
            'estado'          => strtolower($campo->estado_calidad),
            'tiene_descuento' => Acopio::calcularDescuento($agua),
            'foto_evidencia'  => $campo->foto_path,
        ];

        $acopio = Acopio::where('acopio_campo_id', $campo->id)->first();
        if ($acopio) {
            $acopio->update($datos);
            return $acopio;
        }

        $registrado = $campo->created_at ?? now();
        $acopio = new Acopio($datos + [
            'acopio_campo_id' => $campo->id,
            'fecha'           => $registrado->toDateString(),
            'observaciones'   => 'Registrado en la app de campo',
        ]);
        $acopio->created_at = $registrado;   // la hora real del acopio, no la de la sincronización
        $acopio->save();

        return $acopio;
    }

    /**
     * Si el chofer borra el registro, se borra el acopio espejo, salvo que ya esté
     * en una liquidación confirmada o pagada (ahí se conserva para no descuadrar el pago).
     */
    public function eliminar(AcopioCampo $campo): void
    {
        $acopio = Acopio::where('acopio_campo_id', $campo->id)->first();
        if (! $acopio) {
            return;
        }

        $liquidado = Liquidacion::where('ganadero_id', $acopio->ganadero_id)
            ->whereIn('estado', ['confirmada', 'pagada'])
            ->whereDate('semana_inicio', '<=', $acopio->fecha)
            ->whereDate('semana_fin', '>=', $acopio->fecha)
            ->exists();

        if ($liquidado) {
            $acopio->update(['acopio_campo_id' => null]);
        } else {
            $acopio->delete();
        }
    }
}
