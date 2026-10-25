<?php

namespace App\Services;

use App\Models\Acopio;
use App\Models\AcopioCampo;
use App\Models\AsignacionZona;
use App\Models\Configuracion;
use App\Models\Ganadero;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Decide qué ruta recorre cada chofer y en qué orden visita a los ganaderos.
 *
 * Prioridad para elegir la ruta:
 *   1. ?ruta=NOMBRE explícito (pruebas / cobertura puntual).
 *   2. Zona asignada al chofer este mes (rotación mensual en "Rotación Zonas").
 *   3. Respaldo: ruta del último ganadero que acopió.
 *
 * El orden de las paradas se calcula con "vecino más cercano" partiendo de la
 * planta, que da un recorrido corto sin depender de servicios externos.
 */
class RutaChoferService
{
    public function rutaDe(User $chofer, ?string $rutaManual = null): array
    {
        $hoy = now();

        $asignacion = AsignacionZona::with('zona')
            ->where('chofer_id', $chofer->id)
            ->where('mes', $hoy->month)
            ->where('anio', $hoy->year)
            ->first();

        $query  = Ganadero::where('activo', true);
        $zona   = null;
        $nombre = null;
        $origen = null;

        if ($rutaManual) {
            $query->where('ruta_asignada', $rutaManual);
            $nombre = $rutaManual;
            $origen = 'manual';
        } elseif ($asignacion?->zona) {
            $zona   = $asignacion->zona;
            $query->where('zona_id', $zona->id);
            $nombre = $zona->nombre;
            $origen = 'zona';
        } else {
            // Sin zona este mes: ruta del último acopio, o la primera ruta con ganaderos activos
            $nombre = $this->rutaPorHistorial($chofer->id)
                ?? Ganadero::where('activo', true)->whereNotNull('ruta_asignada')->value('ruta_asignada');
            if (! $nombre) {
                return ['error' => 'Aún no tienes una zona asignada este mes. Pide al administrador que la asigne en "Rotación Zonas".'];
            }
            $query->where('ruta_asignada', $nombre);
            $origen = 'historial';
        }

        $ganaderos = $query
            ->select('id', 'dni', 'nombre', 'direccion', 'ruta_asignada', 'estado', 'geo_lat', 'geo_lon', 'zona_id')
            ->orderBy('nombre')
            ->get();

        $atendidos = $this->atendidosHoy($chofer->id);
        $planta    = $this->planta();
        $ordenados = $this->ordenarPorCercania($ganaderos, $planta);

        $paradas = $ordenados->values()->map(fn (Ganadero $g, int $i) => [
            'id'              => $g->id,
            'orden'           => $i + 1,
            'ganadero_id'     => $g->id,
            'ganadero_nombre' => $g->nombre,
            'ganadero_dni'    => (string) $g->dni,
            'direccion'       => $g->direccion,
            'geo_lat'         => $g->geo_lat !== null ? (float) $g->geo_lat : null,
            'geo_lon'         => $g->geo_lon !== null ? (float) $g->geo_lon : null,
            'atendido'        => $atendidos->contains($g->id),
            'suspendido'      => strcasecmp((string) $g->estado, 'Suspendido') === 0,
        ]);

        return [
            'asignacion_id' => $asignacion?->id ?? 0,
            'ruta_codigo'   => $zona?->codigo ?? $nombre,
            'ruta_nombre'   => $nombre,
            'ruta'          => $nombre,
            'origen_ruta'   => $origen,
            'rol_mes'       => $asignacion?->rol_mes ?? 'sin_asignar',
            'fecha'         => $hoy->toDateString(),
            'planta'        => $planta,
            // Capacidad del camión (litros) para la barra de carga de la app
            'capacidad_litros' => (float) Configuracion::get('capacidad_camion_litros', 1200),
            'total'         => $paradas->count(),
            'atendidos'     => $paradas->where('atendido', true)->count(),
            'paradas'       => $paradas->all(),
            // Formato anterior: se mantiene para versiones antiguas de la app
            'ganaderos'     => $ganaderos,
        ];
    }

    /** Coordenadas de la planta (configurables en la tabla `configuracion`). */
    public function planta(): array
    {
        return [
            'nombre' => Configuracion::get('planta_nombre', 'Planta ProLech Huata'),
            'lat'    => (float) Configuracion::get('planta_lat', -15.6167),
            'lon'    => (float) Configuracion::get('planta_lon', -69.9708),
        ];
    }

    private function rutaPorHistorial(int $choferId): ?string
    {
        $ultimoGanadero = Acopio::where('chofer_id', $choferId)
            ->orderByDesc('fecha')->orderByDesc('created_at')
            ->value('ganadero_id');

        return $ultimoGanadero
            ? Ganadero::where('id', $ultimoGanadero)->value('ruta_asignada')
            : null;
    }

    /** Ganaderos que ya entregaron hoy (app antigua o acopio offline-first). */
    private function atendidosHoy(int $choferId): Collection
    {
        $hoy = now()->toDateString();

        $clasicos = Acopio::where('chofer_id', $choferId)->whereDate('fecha', $hoy)->pluck('ganadero_id');
        $campo    = AcopioCampo::where('chofer_id', $choferId)->whereDate('created_at', $hoy)->pluck('ganadero_id');

        return $clasicos->merge($campo)->unique()->values();
    }

    /** Vecino más cercano desde la planta; los que no tienen GPS van al final. */
    private function ordenarPorCercania(Collection $ganaderos, array $inicio): Collection
    {
        [$conGps, $sinGps] = $ganaderos->partition(fn ($g) => $g->geo_lat !== null && $g->geo_lon !== null);

        $pendientes = $conGps->values()->all();
        $orden      = [];
        $lat = $inicio['lat'];
        $lon = $inicio['lon'];

        while ($pendientes) {
            $mejor = 0;
            $mejorDist = INF;
            foreach ($pendientes as $i => $g) {
                $d = self::distanciaKm($lat, $lon, (float) $g->geo_lat, (float) $g->geo_lon);
                if ($d < $mejorDist) { $mejorDist = $d; $mejor = $i; }
            }
            $siguiente = $pendientes[$mejor];
            array_splice($pendientes, $mejor, 1);
            $orden[] = $siguiente;
            $lat = (float) $siguiente->geo_lat;
            $lon = (float) $siguiente->geo_lon;
        }

        return collect($orden)->merge($sinGps->values());
    }

    public static function distanciaKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($a)));
    }
}
