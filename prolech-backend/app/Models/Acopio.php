<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acopio extends Model
{
    use HasFactory;

    protected $fillable = [
        'ganadero_id',
        'chofer_id',
        'litros',
        'medicion_balde_confirmada',
        'equipo_lactoscan',
        'capacidad_tacho_litros',
        'porcentaje_agua',
        'acidez_ph',
        'densidad',
        // ── Campos LACTOMAT completos ─────────────────────────────────────
        'grasa',
        'proteina',
        'lactosa',
        'sng',
        'sales',
        'total_solidos',
        'punto_congelacion',
        // ─────────────────────────────────────────────────────────────────
        'tiene_descuento',
        'estado',
        'fecha',
        'observaciones',
        'foto_evidencia',
        'client_uuid',
        'acopio_campo_id',
        'observacion',
        'observacion_nota',
        'muestra_codigo',
        'control_resultado',
    ];

    protected $casts = [
        'fecha'                     => 'date',
        'tiene_descuento'           => 'boolean',
        'medicion_balde_confirmada' => 'boolean',
        'equipo_lactoscan'          => 'integer',
        'capacidad_tacho_litros'    => 'float',
    ];

    public function ganadero()
    {
        return $this->belongsTo(Ganadero::class);
    }

    public function chofer()
    {
        return $this->belongsTo(User::class, 'chofer_id');
    }

    /** Registro de la app de campo del que viene este acopio (si viene de ahí). */
    public function acopioCampo()
    {
        return $this->belongsTo(AcopioCampo::class);
    }

    // ── Reglas de negocio — Análisis de calidad ───────────────────────────────

    /**
     * Determina el estado automáticamente según los parámetros del Lactoscan.
     *
     * Reglas:
     *   >= 10% agua             → rechazado (adulteración grave)
     *   pH < 5.5 ó > 8.5       → rechazado (imposible en leche real)
     *   > 5% agua               → observado
     *   pH fuera de 6.4–6.8    → observado (rango ideal, no grave)
     *   densidad fuera rango    → observado
     *   resto                   → aprobado
     */
    public static function calcularEstado(float $agua, float $ph, ?float $densidad = null): string
    {
        // pH = 0 significa que el campo no fue ingresado — ignorar en la evaluación
        $phValido = $ph > 0;

        // Rechazo definitivo — solo casos graves
        if ($agua >= 10.0) {
            return 'rechazado';
        }
        // pH fuera del rango biológicamente posible para leche → rechazo
        if ($phValido && ($ph < 5.5 || $ph > 8.5)) {
            return 'rechazado';
        }

        // Observado — fuera del rango ideal pero no grave
        if ($agua > 5.0) {
            return 'observado';
        }
        // pH fuera del rango óptimo (6.4–6.8) → observado, no rechazado
        if ($phValido && ($ph < 6.4 || $ph > 6.8)) {
            return 'observado';
        }

        if ($densidad !== null && $densidad > 0 && ($densidad < 1.028 || $densidad > 1.033)) {
            return 'observado';
        }

        return 'aprobado';
    }

    /**
     * Determina si aplica descuento en la liquidación.
     * Regla: % agua ~1% (entre 0.5% y 1.5%) → descuento menor en liquidación.
     * NO es rechazo, pero afecta el precio pagado al proveedor esa semana.
     */
    public static function calcularDescuento(float $agua): bool
    {
        return $agua >= 0.5 && $agua <= 1.5;
    }

    /**
     * Scope: acopios adulterados de la semana activa de liquidación.
     * La semana cierra el miércoles y abre el jueves.
     *
     * Máximo 2 adulteraciones permitidas por semana. A la 3.ª se notifica al admin.
     */
    public function scopeAdulteracionesSemana($query, int $ganaderoId)
    {
        [$inicio, $fin] = self::rangoSemanaLiquidacion();
        return $query
            ->where('ganadero_id', $ganaderoId)
            ->whereIn('estado', ['rechazado', 'observado'])
            ->whereDate('fecha', '>=', $inicio)->whereDate('fecha', '<=', $fin);
    }

    /**
     * Devuelve el rango [inicio, fin] de la semana de liquidación actual.
     * La semana cierra el miércoles y abre el jueves.
     */
    public static function rangoSemanaLiquidacion(): array
    {
        $hoy = now()->startOfDay();

        // El jueves más reciente (hoy si es jueves). Antes, de domingo a miércoles se restaba
        // una semana de más y la regla de 2 strikes contaba la semana equivocada.
        $inicioSemana = $hoy->copy()->startOfWeek(\Carbon\Carbon::THURSDAY);

        $finSemana = $inicioSemana->copy()->addDays(6); // miércoles siguiente

        return [$inicioSemana->toDateString(), $finSemana->toDateString()];
    }
}
