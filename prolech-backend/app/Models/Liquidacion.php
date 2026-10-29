<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Liquidacion extends Model
{
    use HasFactory;

    protected $table = 'liquidaciones';

    protected $fillable = [
        'ganadero_id',
        'semana_inicio',
        'semana_fin',
        'fecha_pago',
        'litros_aprobados',
        'precio_litro',
        'subtotal_normal',
        'litros_adulterados',
        'precio_penalidad',
        'subtotal_penalidad',
        'num_adulteraciones',
        'quesos_solicitados',
        'precio_queso_proveedor',
        'descuento_quesos',
        'otros_descuentos',
        'detalle_descuentos',
        'total_bruto',
        'total_descuentos',
        'total_neto',
        'estado',
        'generada_por',
        'pagada_at',
        'metodo_pago',
        'referencia_pago',
    ];

    protected $casts = [
        'semana_inicio'  => 'date',
        'semana_fin'     => 'date',
        'fecha_pago'     => 'date',
        'pagada_at'      => 'datetime',
    ];

    // Precio base S/ por litro (se puede mover a config)
    const PRECIO_LITRO_BASE   = 1.80;
    const PRECIO_PENALIDAD    = 1.50;   // S/ por litro adulterado
    const PRECIO_QUESO_PROV   = 17.00;  // S/ para proveedores
    const MAX_ADULTERACIONES  = 2;      // 3ª adulteración afecta semana siguiente

    public function ganadero()
    {
        return $this->belongsTo(Ganadero::class);
    }

    public function generador()
    {
        return $this->belongsTo(User::class, 'generada_por');
    }

    // ── Helpers de ciclo de pago ──────────────────────────────────────────────

    /**
     * Devuelve el jueves de inicio de la semana de liquidación que contiene $fecha.
     * Ciclo: jueves → miércoles.
     */
    public static function inicioSemana(Carbon $fecha): Carbon
    {
        // Carbon: 4 = jueves
        $diaSemana = $fecha->dayOfWeek;
        if ($diaSemana === Carbon::THURSDAY) {
            return $fecha->copy()->startOfDay();
        }
        // Si estamos antes del jueves (lun/mar/mié), retroceder al jueves anterior
        if ($diaSemana < Carbon::THURSDAY) {
            return $fecha->copy()->previous(Carbon::THURSDAY)->startOfDay();
        }
        // Si estamos después (vie/sáb/dom), avanzar al último jueves
        return $fecha->copy()->previous(Carbon::THURSDAY)->startOfDay();
    }

    /**
     * Devuelve el miércoles de cierre (6 días después del jueves de inicio).
     */
    public static function finSemana(Carbon $inicio): Carbon
    {
        return $inicio->copy()->addDays(6)->endOfDay();
    }

    /**
     * Devuelve el viernes de pago (día siguiente al miércoles de cierre).
     */
    public static function fechaPago(Carbon $fin): Carbon
    {
        return $fin->copy()->next(Carbon::FRIDAY)->startOfDay();
    }
}
