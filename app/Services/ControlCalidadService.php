<?php

namespace App\Services;

use App\Models\Acopio;
use App\Models\AsignacionZona;
use App\Models\Configuracion;
use App\Models\ControlCalidad;
use App\Models\Ganadero;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Control de calidad sorpresa.
 *
 *  - Semáforo: cada ganadero suma puntos con señales pequeñas de los últimos 21 días
 *    (observación del chofer +2, muestra leve +3, litros que suben de golpe +2) que pesan
 *    la mitad pasada la semana 2. Verde 0–2 · amarillo 3–5 · rojo 6+.
 *  - Sorteo diario: N controles (Configuración, 3 por defecto) eligiendo con más peso a los
 *    de más riesgo y a los que llevan más días sin control; 1 de cada 3 es en el predio y el
 *    resto en frasco que trae el chofer. Además un control del tanque por ruta.
 *  - Resultado: normal · leve (advertencia + descuento en esa entrega) · grave (esa leche no
 *    se paga y bloqueo de 3 entregas; 6 si reincide en 30 días; a la 3.ª, suspensión).
 *  - Siempre se avisa: al administrador, al personal de calidad y al chofer de la ruta.
 */
class ControlCalidadService
{
    public const OBSERVACIONES = [
        'normal'   => '✓ Normal',
        'olor'     => '👃 Olor raro',
        'color'    => '🎨 Color raro',
        'grumos'   => '🧀 Grumos o cortada',
        'caliente' => '🌡️ Muy caliente',
        'otro'     => '✏️ Otro',
    ];

    // ── Semáforo ─────────────────────────────────────────────────────────────

    /** @return array{puntaje: float, nivel: string, motivos: list<string>, ultimo_control: ?Carbon} */
    public function riesgo(Ganadero $g): array
    {
        $desde = today()->subDays(21);
        $peso = fn (Carbon $fecha) => $fecha->diffInDays(today()) > 14 ? 0.5 : 1.0;
        $puntaje = 0.0;
        $motivos = [];

        $obs = Acopio::where('ganadero_id', $g->id)->whereDate('fecha', '>=', $desde)
            ->whereNotNull('observacion')->where('observacion', '!=', 'normal')->get(['fecha', 'observacion']);
        foreach ($obs as $o) $puntaje += 2 * $peso($o->fecha);
        if ($obs->count()) $motivos[] = $obs->count() . ' observación(es) del chofer';

        // Lecturas del Lactoscan en la planta: observado +3, rechazado +4
        $malas = Acopio::where('ganadero_id', $g->id)->whereDate('fecha', '>=', $desde)
            ->whereIn('estado', ['observado', 'rechazado'])->get(['fecha', 'estado', 'porcentaje_agua']);
        foreach ($malas as $m) $puntaje += ($m->estado === 'rechazado' ? 4 : 3) * $peso($m->fecha);
        if ($malas->count()) {
            $motivos[] = $malas->count() . ' acopio(s) ' . ($malas->contains('estado', 'rechazado') ? 'observado(s)/rechazado(s)' : 'observado(s)')
                . ' · máx. ' . number_format((float) $malas->max('porcentaje_agua'), 1) . ' % agua';
        }

        $controles = ControlCalidad::where('ganadero_id', $g->id)->whereIn('resultado', ['leve', 'grave'])->whereDate('fecha', '>=', $desde)->get(['fecha', 'resultado']);
        foreach ($controles as $c) $puntaje += ($c->resultado === 'grave' ? 6 : 3) * $peso($c->fecha);
        if ($n = $controles->where('resultado', 'leve')->count()) $motivos[] = "{$n} muestra(s) leve(s)";
        if ($n = $controles->where('resultado', 'grave')->count()) $motivos[] = "{$n} muestra(s) grave(s)";

        // Litros de la última semana contra el promedio de las dos anteriores
        $reciente = (float) Acopio::where('ganadero_id', $g->id)->whereDate('fecha', '>=', today()->subDays(6))->avg('litros');
        $antes = (float) Acopio::where('ganadero_id', $g->id)->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<', today()->subDays(6))->avg('litros');
        if ($antes > 0 && $reciente > $antes * 1.3) {
            $puntaje += 2;
            $motivos[] = 'litros +' . round(($reciente / $antes - 1) * 100) . ' %';
        }

        $puntaje = round($puntaje, 1);
        $ultima = Acopio::where('ganadero_id', $g->id)->latest('fecha')->latest('id')->first(['fecha', 'litros', 'estado', 'porcentaje_agua']);
        return [
            'ultima_entrega' => $ultima,
            'puntaje'        => $puntaje,
            'nivel'          => $puntaje >= 6 ? 'rojo' : ($puntaje >= 3 ? 'amarillo' : 'verde'),
            'motivos'        => $motivos,
            'ultimo_control' => ControlCalidad::where('ganadero_id', $g->id)->where('estado', 'analizado')->max('fecha')
                ? Carbon::parse(ControlCalidad::where('ganadero_id', $g->id)->where('estado', 'analizado')->max('fecha')) : null,
        ];
    }

    /** Todos los ganaderos activos con su semáforo, de mayor a menor riesgo. */
    public function semaforo(): Collection
    {
        return Ganadero::with('zona:id,nombre')->where('activo', true)->orderBy('nombre')->get()
            ->map(fn (Ganadero $g) => ['ganadero' => $g] + $this->riesgo($g))
            ->sortByDesc('puntaje')->values();
    }

    // ── Programación ─────────────────────────────────────────────────────────

    public function programar(Ganadero $g, string $tipo, string $motivo, ?Carbon $fecha = null): ControlCalidad
    {
        $fecha ??= today();
        $control = ControlCalidad::create([
            'fecha'       => $fecha->toDateString(),
            'tipo'        => $tipo,
            'motivo'      => $motivo,
            'ganadero_id' => $g->id,
            'zona_id'     => $g->zona_id,
        ]);
        if ($tipo === 'muestra') {
            $control->update(['frasco_codigo' => 'F-' . $fecha->format('md') . '-' . $control->id]);
        }
        return $control;
    }

    /** Seguimiento: frasco en las próximas 3 entregas para ver cómo está dando siempre la leche. */
    public function seguimiento(Ganadero $g): Collection
    {
        return collect([0, 1, 2])->map(fn ($d) => $this->programar($g, 'muestra', 'Seguimiento (3 entregas)', today()->addDays($d)));
    }

    /**
     * Sorteo del día (lo corre el scheduler cada madrugada). No repite si ya hay sorteo hoy.
     *
     * @return Collection<int, ControlCalidad>
     */
    public function sortear(?Carbon $fecha = null): Collection
    {
        $fecha ??= today();
        if (ControlCalidad::whereDate('fecha', $fecha)->where('motivo', 'like', 'Sorteo%')->exists()) {
            return collect();
        }

        $zonasConChofer = AsignacionZona::where('mes', $fecha->month)->where('anio', $fecha->year)->get(['zona_id', 'zona_extra_id'])
            ->flatMap(fn ($a) => [$a->zona_id, $a->zona_extra_id])->filter()->unique()->values();
        $candidatos = Ganadero::where('activo', true)->where('estado', '!=', Ganadero::ESTADO_SUSPENDIDO)
            ->where('entregas_bloqueadas', 0)->whereIn('zona_id', $zonasConChofer)->get()
            ->map(function (Ganadero $g) {
                $r = $this->riesgo($g);
                $diasSinControl = $r['ultimo_control'] ? $r['ultimo_control']->diffInDays(today()) : 30;
                return ['g' => $g, 'peso' => 1 + $r['puntaje'] * 2 + min(30, $diasSinControl) / 5, 'nivel' => $r['nivel']];
            });

        $n = max(0, (int) Configuracion::get('controles_por_dia', 3));
        $elegidos = collect();
        while ($elegidos->count() < $n && $candidatos->isNotEmpty()) {
            $total = $candidatos->sum('peso');
            $tiro = mt_rand() / mt_getrandmax() * $total;
            foreach ($candidatos as $i => $c) {
                $tiro -= $c['peso'];
                if ($tiro <= 0) {
                    $elegidos->push($c);
                    $candidatos->forget($i);
                    break;
                }
            }
        }

        $controles = $elegidos->values()->map(fn ($c, $i) => $this->programar(
            $c['g'],
            $i % 3 === 0 ? 'predio' : 'muestra',
            'Sorteo' . ($c['nivel'] !== 'verde' ? " · semáforo {$c['nivel']}" : ''),
            $fecha,
        ));

        // Un control del tanque de cada camión al llegar a la planta
        foreach ($zonasConChofer->unique() as $zonaId) {
            $controles->push(ControlCalidad::create([
                'fecha' => $fecha->toDateString(), 'tipo' => 'tanque', 'motivo' => 'Sorteo · tanque del camión', 'zona_id' => $zonaId,
            ]));
        }

        return $controles;
    }

    // ── Desde la app del chofer ──────────────────────────────────────────────

    /** El chofer registró un acopio: frasco de muestra y alerta si vio algo raro. */
    public function alRegistrarAcopio(Acopio $acopio): void
    {
        if ($acopio->muestra_codigo) {
            ControlCalidad::where('frasco_codigo', $acopio->muestra_codigo)->where('estado', 'programado')
                ->update(['estado' => 'muestra_tomada', 'acopio_id' => $acopio->id]);
        }

        if ($acopio->observacion && $acopio->observacion !== 'normal') {
            $g = $acopio->ganadero;
            $texto = (self::OBSERVACIONES[$acopio->observacion] ?? $acopio->observacion)
                . ($acopio->observacion_nota ? " — {$acopio->observacion_nota}" : '');
            $this->avisar(
                User::conRol('admin', 'calidad')->where('activo', true)->pluck('id'),
                "👀 Observación del chofer: {$g?->nombre}",
                "En la entrega de {$acopio->litros} L el chofer marcó: {$texto}. El ganadero sube en el semáforo de calidad.",
                'observacion_chofer', $g?->id,
            );
        }
    }

    // ── Resultado ────────────────────────────────────────────────────────────

    /** Resultado sugerido con las reglas de calidad de la planta. */
    public function sugerirResultado(?float $agua, ?float $ph, ?float $densidad): string
    {
        return match (Acopio::calcularEstado((float) $agua, (float) $ph, $densidad)) {
            'rechazado' => 'grave',
            'observado' => 'leve',
            default     => (float) $agua >= 0.5 ? 'leve' : 'normal',
        };
    }

    public function registrarResultado(ControlCalidad $control, array $datos, User $inspector): ControlCalidad
    {
        return DB::transaction(function () use ($control, $datos, $inspector) {
            $resultado = $datos['resultado'] ?? $this->sugerirResultado($datos['agua'] ?? null, $datos['ph'] ?? null, $datos['densidad'] ?? null);

            // Control en el predio: se liga a la entrega de ese día si ya se acopió
            $acopioId = $control->acopio_id ?? ($control->ganadero_id
                ? Acopio::where('ganadero_id', $control->ganadero_id)->whereDate('fecha', $control->fecha)->value('id') : null);

            $control->update(array_merge(
                array_intersect_key($datos, array_flip(['agua', 'densidad', 'grasa', 'ph', 'temperatura', 'notas', 'foto_path', 'lat', 'lon'])),
                ['resultado' => $resultado, 'estado' => 'analizado', 'inspector_id' => $inspector->id, 'analizado_at' => now(), 'acopio_id' => $acopioId],
            ));

            $sancion = match (true) {
                $control->tipo === 'tanque' => $this->resultadoTanque($control, $resultado),
                $resultado === 'grave'      => $this->sancionGrave($control),
                $resultado === 'leve'       => $this->sancionLeve($control),
                default                     => null,
            };
            if ($sancion) $control->update(['sancion' => $sancion]);

            return $control->fresh();
        });
    }

    private function sancionLeve(ControlCalidad $control): string
    {
        $control->acopio?->update(['control_resultado' => 'leve', 'tiene_descuento' => true]);
        $texto = 'Advertencia y descuento en esa entrega';
        $this->avisarTodos($control, "⚠️ Muestra leve: {$control->ganadero->nombre}",
            "La muestra del {$control->fecha->format('d/m')} salió con observaciones leves. {$texto}. Si se repite con algo grave, se bloquean entregas.");
        return $texto;
    }

    private function sancionGrave(ControlCalidad $control): string
    {
        $g = $control->ganadero;
        $control->acopio?->update(['control_resultado' => 'grave']);

        $graves = ControlCalidad::where('ganadero_id', $g->id)->where('resultado', 'grave')
            ->whereDate('fecha', '>=', $control->fecha->copy()->subDays(30))->count();

        if ($graves >= 3) {
            $g->update(['estado' => Ganadero::ESTADO_SUSPENDIDO, 'entregas_bloqueadas' => 0, 'bloqueo_motivo' => '3.ª adulteración grave en 30 días']);
            $texto = 'Leche no pagada · SUSPENDIDO hasta revisión del administrador (3.ª vez en 30 días)';
        } else {
            $entregas = $graves >= 2 ? 6 : 3;
            $g->update([
                'entregas_bloqueadas' => max($g->entregas_bloqueadas, $entregas),
                'bloqueo_motivo'      => "Muestra adulterada del {$control->fecha->format('d/m')}" . ($graves >= 2 ? ' (reincidencia)' : ''),
            ]);
            $texto = "Leche no pagada · bloqueado {$entregas} entregas" . ($graves >= 2 ? ' por reincidencia' : '');
        }

        $this->avisarTodos($control, "🚫 Leche adulterada: {$g->nombre}",
            "La muestra del {$control->fecha->format('d/m')} salió adulterada" . ($control->agua ? " ({$control->agua} % de agua)" : '') . ". {$texto}.");
        return $texto;
    }

    /** Tanque malo: algún ganadero de la ruta mezcló; se programan 2 frascos más mañana en esa ruta. */
    private function resultadoTanque(ControlCalidad $control, string $resultado): ?string
    {
        if ($resultado === 'normal' || ! $control->zona_id) {
            return null;
        }
        $sospechosos = Ganadero::where('zona_id', $control->zona_id)->where('activo', true)->where('entregas_bloqueadas', 0)
            ->where('estado', '!=', Ganadero::ESTADO_SUSPENDIDO)->get()
            ->sortByDesc(fn ($g) => $this->riesgo($g)['puntaje'])->take(2);
        foreach ($sospechosos as $g) {
            $this->programar($g, 'muestra', 'Tanque de la ruta salió ' . $resultado, today()->addDay());
        }
        $this->avisar(User::conRol('admin', 'calidad')->where('activo', true)->pluck('id'),
            "🚛 Tanque {$resultado}: {$control->zona?->nombre}",
            "El tanque del camión salió {$resultado}. Mañana se toman muestras a: " . $sospechosos->pluck('nombre')->implode(', ') . '.',
            'control_tanque', $control->zona_id);
        return 'Más controles en la ruta: ' . $sospechosos->pluck('nombre')->implode(', ');
    }

    // ── Bloqueos por entregas ────────────────────────────────────────────────

    /**
     * Descuenta una entrega a cada ganadero bloqueado cuyo camión pasó hoy por su ruta.
     * Lo corre el scheduler cada noche.
     */
    public function consumirBloqueos(?Carbon $fecha = null): int
    {
        $fecha ??= today();
        $zonasConRecojo = Acopio::whereDate('fecha', $fecha)->join('ganaderos', 'ganaderos.id', '=', 'acopios.ganadero_id')
            ->distinct()->pluck('ganaderos.zona_id')->filter();
        $n = 0;
        foreach (Ganadero::where('entregas_bloqueadas', '>', 0)->whereIn('zona_id', $zonasConRecojo)->get() as $g) {
            $g->decrement('entregas_bloqueadas');
            $n++;
            if ($g->fresh()->entregas_bloqueadas === 0) {
                $g->update(['bloqueo_motivo' => null]);
                $this->avisar(User::conRol('admin', 'calidad')->where('activo', true)->pluck('id'),
                    "✅ {$g->nombre} puede volver a entregar", 'Cumplió su bloqueo por calidad.', 'bloqueo_cumplido', $g->id);
            }
        }
        return $n;
    }

    // ── Avisos ───────────────────────────────────────────────────────────────

    private function avisarTodos(ControlCalidad $control, string $titulo, string $mensaje): void
    {
        $ids = User::conRol('admin', 'calidad')->where('activo', true)->pluck('id');
        $chofer = $control->ganadero?->zona_id
            ? AsignacionZona::choferDeZona($control->ganadero->zona_id)
            : null;
        if ($chofer) $ids->push($chofer);
        if ($control->ganadero?->user_id) $ids->push($control->ganadero->user_id);   // portal del proveedor
        $this->avisar($ids, $titulo, $mensaje, 'control_calidad', $control->ganadero_id);
    }

    private function avisar(Collection $ids, string $titulo, string $mensaje, string $tipo, ?int $ref): void
    {
        foreach ($ids->unique() as $id) {
            Notificacion::enviar(userId: $id, titulo: $titulo, mensaje: $mensaje, tipo: $tipo, referenciaId: $ref);
        }
    }
}
