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
        'zona_extra_id',
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

    /** Segunda ruta que el chofer cubre este mes (hay más rutas que choferes). */
    public function zonaExtra()
    {
        return $this->belongsTo(Zona::class, 'zona_extra_id');
    }

    /** Asignaciones que recorren la zona, como ruta principal o como segunda ruta. */
    public function scopeCubreZona($query, int $zonaId)
    {
        return $query->where(fn ($q) => $q->where('zona_id', $zonaId)->orWhere('zona_extra_id', $zonaId));
    }

    /** Ids de las zonas que recorre (1 o 2). */
    public function zonaIds(): array
    {
        return array_values(array_filter([$this->zona_id, $this->zona_extra_id]));
    }

    /** «Collana 1», «Collana 1 + HUATA1», «Volante + PUNO» o «Volante». */
    public function nombreRutas(): string
    {
        $principal = $this->zona?->nombre ?? 'Volante';
        return $this->zonaExtra ? "{$principal} + {$this->zonaExtra->nombre}" : $principal;
    }

    /** Chofer que recorre la zona en el mes (titular o con ella como segunda ruta). */
    public static function choferDeZona(int $zonaId, ?int $mes = null, ?int $anio = null): ?int
    {
        return self::cubreZona($zonaId)->where('mes', $mes ?? now()->month)->where('anio', $anio ?? now()->year)
            ->orderByRaw('CASE WHEN zona_id = ? THEN 0 ELSE 1 END', [$zonaId])->value('chofer_id');
    }

    /** Nombre de la ruta del chofer en el mes actual: la zona, «Volante» o null si no tiene asignación. */
    public static function rutaDelMes(int $choferId): ?string
    {
        $a = self::with('zona:id,nombre', 'zonaExtra:id,nombre')->where('chofer_id', $choferId)
            ->where('mes', now()->month)->where('anio', now()->year)->first();
        return $a?->nombreRutas();
    }

    // ── Segunda ruta y rutas libres ───────────────────────────────────────────

    /** Litros por día que suele dar una ruta (promedio de los últimos 14 días con acopio). */
    public static function litrosDiariosZona(int $zonaId): float
    {
        $porDia = Acopio::whereHas('ganadero', fn ($q) => $q->where('zona_id', $zonaId))
            ->whereDate('fecha', '>=', today()->subDays(14))->where('estado', '!=', 'rechazado')
            ->get(['fecha', 'litros'])->groupBy(fn ($a) => Carbon::parse($a->fecha)->toDateString())
            ->map(fn ($dia) => (float) $dia->sum('litros'));
        return round((float) $porDia->avg(), 1);
    }

    /** Carga diaria estimada del chofer (sus 1 o 2 rutas) frente a la capacidad del camión. */
    public function cargaEstimada(): array
    {
        $litros = array_sum(array_map(fn ($id) => self::litrosDiariosZona($id), $this->zonaIds()));
        $capacidad = (float) Configuracion::get('capacidad_camion_litros', 1200);
        return [
            'litros'    => round($litros, 1),
            'capacidad' => $capacidad,
            'excede'    => $capacidad > 0 && $litros > $capacidad,
        ];
    }

    /** Zonas activas que nadie recorre en el mes. */
    public static function zonasSinChofer(int $mes, int $anio)
    {
        $cubiertas = self::where('mes', $mes)->where('anio', $anio)->get(['zona_id', 'zona_extra_id'])
            ->flatMap(fn ($a) => [$a->zona_id, $a->zona_extra_id])->filter()->unique();
        return Zona::where('activa', true)->whereNotIn('id', $cubiertas)->orderBy('orden')->get();
    }

    /**
     * Da una segunda ruta al chofer (o se la quita con $zonaId = null).
     * La ruta no puede tener titular; si otro chofer la tenía como segunda ruta, pasa a este.
     */
    public static function asignarSegundaRuta(int $choferId, ?int $zonaId, int $mes, int $anio): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($choferId, $zonaId, $mes, $anio) {
            $asig = self::where('chofer_id', $choferId)->where('mes', $mes)->where('anio', $anio)->lockForUpdate()->first();
            if (! $asig) {
                throw new \InvalidArgumentException('Primero asigna a este chofer una ruta o el rol de volante en el mes.');
            }
            if ($zonaId === null) {
                $asig->update(['zona_extra_id' => null]);
                return 'Se quitó la segunda ruta.';
            }
            if ($asig->zona_id === $zonaId) {
                throw new \InvalidArgumentException('Esa ya es su ruta principal.');
            }
            $titular = self::with('chofer:id,name')->where('zona_id', $zonaId)->where('mes', $mes)->where('anio', $anio)->first();
            if ($titular) {
                throw new \InvalidArgumentException("Esa ruta ya la recorre {$titular->chofer?->name} como titular.");
            }
            $previo = self::with('chofer:id,name')->where('zona_extra_id', $zonaId)->where('mes', $mes)->where('anio', $anio)
                ->where('chofer_id', '!=', $choferId)->first();
            $previo?->update(['zona_extra_id' => null]);
            $asig->update(['zona_extra_id' => $zonaId]);
            return $previo ? "Antes la cubría {$previo->chofer?->name}." : '';
        });
    }

    /**
     * Reparte las rutas sin chofer como segunda ruta: primero al volante y después a los
     * titulares cuya ruta da menos litros (para no pasarse de la capacidad del camión).
     *
     * @return array<int, string> descripción de cada asignación hecha
     */
    public static function cubrirRutasLibres(int $mes, int $anio): array
    {
        $hechas = [];
        foreach (self::zonasSinChofer($mes, $anio) as $zona) {
            $libres = self::with('chofer:id,name', 'zona:id,nombre')->where('mes', $mes)->where('anio', $anio)
                ->whereNull('zona_extra_id')->get();
            if ($libres->isEmpty()) break;
            $elegido = $libres->firstWhere('rol_mes', 'volante')
                ?? $libres->sortBy(fn ($a) => $a->zona_id ? self::litrosDiariosZona($a->zona_id) : 0)->first();
            $elegido->update(['zona_extra_id' => $zona->id]);
            $hechas[] = "{$zona->nombre} → {$elegido->chofer?->name}";
        }
        return $hechas;
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
            if ($zonaId) {
                // La zona pasa a tener titular: deja de ser la segunda ruta de quien la cubría
                self::where('zona_extra_id', $zonaId)->where('mes', $mes)->where('anio', $anio)->update(['zona_extra_id' => null]);
            }

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
     * Genera las asignaciones del mes rotando las rutas.
     *
     *   - Orden de rotación: [volante del mes anterior, titulares por orden de ruta, choferes nuevos].
     *   - El volante entra a la primera ruta y cada titular avanza una; el último queda de volante.
     *   - Si hay más rutas que choferes, las que sobran se reparten como segunda ruta
     *     (primero al volante; ver cubrirRutasLibres).
     *
     * @param int[] $chofereIds  IDs de los choferes activos
     */
    public static function generarRotacion(array $chofereIds, int $mes, int $anio): array
    {
        $zonas = Zona::where('activa', true)->orderBy('orden')->get();
        if (count($chofereIds) < 2) {
            throw new \InvalidArgumentException('Se necesitan al menos 2 choferes activos (un titular y un volante).');
        }

        $mesAnterior  = $mes === 1 ? 12 : $mes - 1;
        $anioAnterior = $mes === 1 ? $anio - 1 : $anio;
        $previas = self::with('zona')->where('mes', $mesAnterior)->where('anio', $anioAnterior)
            ->whereIn('chofer_id', $chofereIds)->get();

        if ($previas->isEmpty()) {
            $orden = array_values($chofereIds);
        } else {
            $volante    = $previas->firstWhere('rol_mes', 'volante')?->chofer_id;
            $titulares  = $previas->where('rol_mes', 'titular')->sortBy(fn ($a) => $a->zona?->orden ?? 99)->pluck('chofer_id')->all();
            $nuevos     = array_values(array_diff($chofereIds, $previas->pluck('chofer_id')->all()));
            $otrosVol   = $previas->where('rol_mes', 'volante')->pluck('chofer_id')->reject(fn ($id) => $id === $volante)->all();
            $orden = array_values(array_unique(array_merge($volante ? [$volante] : [], $titulares, $otrosVol, $nuevos)));
        }

        $numTitulares = min($zonas->count(), count($orden) - 1);   // siempre queda al menos un volante
        $inicio = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $base = ['mes' => $mes, 'anio' => $anio, 'fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $inicio->copy()->endOfMonth()->toDateString()];

        $creadas = [];
        foreach ($orden as $i => $choferId) {
            $esTitular = $i < $numTitulares;
            $creadas[] = self::create($base + [
                'chofer_id' => $choferId,
                'zona_id'   => $esTitular ? $zonas[$i]->id : null,
                'rol_mes'   => $esTitular ? 'titular' : 'volante',
            ]);
        }
        self::cubrirRutasLibres($mes, $anio);

        return $creadas;
    }
}
