<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionZona extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_zona';

    protected $fillable = [
        'chofer_id',
        'zona_id',
        'mes',
        'anio',
        'rol_mes',
        'fecha_inicio',
        'fecha_fin',
        'notas',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function chofer()
    {
        return $this->belongsTo(User::class, 'chofer_id');
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    /** Nombre de la ruta del chofer en el mes actual: la zona, «Volante» o null si no tiene asignación. */
    public static function rutaDelMes(int $choferId): ?string
    {
        $a = self::with('zona:id,nombre')->where('chofer_id', $choferId)
            ->where('mes', now()->month)->where('anio', now()->year)->first();
        return $a ? ($a->zona?->nombre ?? 'Volante') : null;
    }

    /**
     * Asigna un chofer a una zona (o como volante si $zonaId es null) en un mes.
     * Cada zona tiene un solo titular por mes: si ya la ocupa otro chofer, intercambian
     * (el otro pasa a la zona que tenía este chofer, o queda como volante).
     *
     * @return string|null descripción del intercambio, o null si no hubo
     */
    public static function asignar(int $choferId, ?int $zonaId, int $mes, int $anio, ?string $notas = null): ?string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($choferId, $zonaId, $mes, $anio, $notas) {
            $anterior = self::where('chofer_id', $choferId)->where('mes', $mes)->where('anio', $anio)->lockForUpdate()->first();
            $ocupante = $zonaId
                ? self::where('zona_id', $zonaId)->where('mes', $mes)->where('anio', $anio)
                    ->where('chofer_id', '!=', $choferId)->lockForUpdate()->first()
                : null;

            $ocupante?->update(['zona_id' => null]);   // libera la zona antes de reasignarla

            $inicio = Carbon::createFromDate($anio, $mes, 1);
            self::updateOrCreate(
                ['chofer_id' => $choferId, 'mes' => $mes, 'anio' => $anio],
                [
                    'zona_id'      => $zonaId,
                    'rol_mes'      => $zonaId ? 'titular' : 'volante',
                    'fecha_inicio' => $inicio->toDateString(),
                    'fecha_fin'    => $inicio->copy()->endOfMonth()->toDateString(),
                    'notas'        => $notas ?? $anterior?->notas,
                ]
            );

            if (! $ocupante) {
                return null;
            }
            $zonaPrevia = $anterior?->zona_id;
            $ocupante->update(['zona_id' => $zonaPrevia, 'rol_mes' => $zonaPrevia ? 'titular' : 'volante']);

            return $ocupante->chofer?->name . ' pasa a ' . ($zonaPrevia ? Zona::find($zonaPrevia)?->nombre : 'volante');
        });
    }

    // ── Lógica de rotación ────────────────────────────────────────────────────

    /**
     * Genera las asignaciones del próximo mes rotando las zonas.
     *
     * Algoritmo de rotación:
     *   - Se obtienen las asignaciones del mes anterior.
     *   - Cada chofer titular pasa a la zona del chofer siguiente (circular).
     *   - El volante del mes anterior se convierte en titular en alguna zona.
     *   - El chofer que era titular en la última zona se convierte en volante.
     *
     * @param int[] $chofereIds  IDs de los 5 choferes (en orden de rotación)
     * @param int   $mes         Mes destino
     * @param int   $anio        Año destino
     */
    public static function generarRotacion(array $chofereIds, int $mes, int $anio): array
    {
        $zonas     = Zona::where('activa', true)->orderBy('orden')->get();
        $numZonas  = $zonas->count();  // 4
        $numCh     = count($chofereIds); // 5

        if ($numCh < $numZonas + 1) {
            throw new \InvalidArgumentException("Se necesitan al menos " . ($numZonas + 1) . " choferes ({$numZonas} titulares + 1 volante).");
        }

        // Obtener asignaciones del mes anterior para determinar el orden actual
        $mesAnterior = $mes === 1 ? 12 : $mes - 1;
        $anioAnterior = $mes === 1 ? $anio - 1 : $anio;

        $asigPrevias = self::where('mes', $mesAnterior)
                          ->where('anio', $anioAnterior)
                          ->whereIn('chofer_id', $chofereIds)
                          ->with('zona')
                          ->get()
                          ->keyBy('chofer_id');

        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $fechaFin    = $fechaInicio->copy()->endOfMonth();

        $creadas = [];

        // Si no hay asignaciones previas, asignar en el orden proporcionado
        if ($asigPrevias->isEmpty()) {
            foreach ($zonas as $index => $zona) {
                $creadas[] = self::create([
                    'chofer_id'    => $chofereIds[$index],
                    'zona_id'      => $zona->id,
                    'mes'          => $mes,
                    'anio'         => $anio,
                    'rol_mes'      => 'titular',
                    'fecha_inicio' => $fechaInicio->toDateString(),
                    'fecha_fin'    => $fechaFin->toDateString(),
                ]);
            }
            // El chofer siguiente al último titular es el volante
            $creadas[] = self::create([
                'chofer_id'    => $chofereIds[$numZonas],
                'zona_id'      => null,
                'mes'          => $mes,
                'anio'         => $anio,
                'rol_mes'      => 'volante',
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin'    => $fechaFin->toDateString(),
            ]);
            return $creadas;
        }

        // Rotación: el volante entra a la zona 1, todos avanzan una zona,
        // el que estaba en la zona 4 pasa a ser volante.
        $volantePrevio = $asigPrevias->firstWhere('rol_mes', 'volante');
        $titularesPrevios = $asigPrevias->where('rol_mes', 'titular')
                                        ->sortBy(fn ($a) => $a->zona?->orden ?? 99);

        // Nuevo orden: [volante, titular_zona1, titular_zona2, titular_zona3] → 4 titulares
        // El titular_zona4 se convierte en volante
        $nuevosChoferesOrden = array_merge(
            $volantePrevio ? [$volantePrevio->chofer_id] : [],
            $titularesPrevios->pluck('chofer_id')->take($numZonas - 1)->toArray()
        );

        $nuevoVolante = $titularesPrevios->last()?->chofer_id;

        foreach ($zonas as $index => $zona) {
            if (!isset($nuevosChoferesOrden[$index])) continue;
            $creadas[] = self::create([
                'chofer_id'    => $nuevosChoferesOrden[$index],
                'zona_id'      => $zona->id,
                'mes'          => $mes,
                'anio'         => $anio,
                'rol_mes'      => 'titular',
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin'    => $fechaFin->toDateString(),
            ]);
        }

        if ($nuevoVolante) {
            $creadas[] = self::create([
                'chofer_id'    => $nuevoVolante,
                'zona_id'      => null,
                'mes'          => $mes,
                'anio'         => $anio,
                'rol_mes'      => 'volante',
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin'    => $fechaFin->toDateString(),
            ]);
        }

        return $creadas;
    }
}
