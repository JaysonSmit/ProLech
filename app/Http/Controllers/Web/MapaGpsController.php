<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TrackingGps;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapaGpsController extends Controller
{
    public function index(): View
    {
        $centroHuata = ['lat' => -15.8403, 'lng' => -70.0219];

        // Choferes activos (últimos 30 min, en_camino)
        $choforesActivos = TrackingGps::with('chofer:id,name,email')
            ->activos(30)
            ->get()
            ->map(fn (TrackingGps $t) => $this->mapearChofer($t, true));

        // Todos los choferes con algún tracking (para el panel lateral completo)
        $todosConTracking = TrackingGps::with('chofer:id,name,email')
            ->todosConTracking()
            ->get()
            ->map(fn (TrackingGps $t) => $this->mapearChofer(
                $t,
                $t->ultima_actualizacion >= now()->subMinutes(30) && $t->estado_ruta === 'en_camino'
            ));

        $totalChoferes = User::conRol('chofer')->where('activo', true)->count();

        $sinSenial = TrackingGps::with('chofer:id,name')
            ->where('ultima_actualizacion', '<', now()->subMinutes(30))
            ->where('estado_ruta', 'en_camino')
            ->get()
            ->map(fn (TrackingGps $t) => [
                'nombre'              => $t->chofer->name ?? 'Chofer #'.$t->chofer_id,
                'ultima_actualizacion'=> $t->ultima_actualizacion->diffForHumans(),
            ]);

        return view('admin.mapa', [
            'centroHuata'      => $centroHuata,
            'choferes'         => $choforesActivos,
            'todosConTracking' => $todosConTracking,
            'choforesJson'     => $todosConTracking->toJson(),   // todos para el mapa
            'totalChoferes'    => $totalChoferes,
            'sinSenial'        => $sinSenial,
        ]);
    }

    public function ubicaciones(): JsonResponse
    {
        $todos = TrackingGps::with('chofer:id,name')
            ->todosConTracking()
            ->get()
            ->map(fn (TrackingGps $t) => array_merge(
                $this->mapearChofer($t,
                    $t->ultima_actualizacion >= now()->subMinutes(30) && $t->estado_ruta === 'en_camino'
                ),
                ['ts' => $t->ultima_actualizacion->timestamp]
            ));

        $activos = $todos->where('activo', true)->count();

        return response()->json([
            'choferes'      => $todos->values(),
            'total_activos' => $activos,
            'server_time'   => now()->toIso8601String(),
        ]);
    }

    private function mapearChofer(TrackingGps $t, bool $activo): array
    {
        return [
            'id'                   => $t->chofer_id,
            'nombre'               => $t->chofer->name ?? 'Chofer #'.$t->chofer_id,
            'email'                => $t->chofer->email ?? '',
            'latitud'              => $t->latitud,
            'longitud'             => $t->longitud,
            'estado_ruta'          => $t->estado_ruta,
            'activo'               => $activo,
            'ultima_actualizacion' => $t->ultima_actualizacion->diffForHumans(),
            'minutos_desde'        => (int) $t->ultima_actualizacion->diffInMinutes(now()),
            // Trazo real recorrido hoy (puntos que envía la app)
            'recorrido'            => \App\Models\TrackingGpsPunto::recorridoDeHoy($t->chofer_id),
            'zona'                 => \App\Models\AsignacionZona::with('zona:id,nombre')
                                        ->where('chofer_id', $t->chofer_id)
                                        ->where('mes', now()->month)->where('anio', now()->year)
                                        ->first()?->zona?->nombre,
        ];
    }
}
