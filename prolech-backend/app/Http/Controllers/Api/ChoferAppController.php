<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\Reunion;
use App\Models\SolicitudRuta;
use App\Models\TrackingGps;
use App\Models\TrackingGpsPunto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ChoferAppController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/ganaderos/{ganadero}/ubicacion
    // El chofer, parado en el predio, guarda su ubicación GPS desde la app.
    // Solo para ganaderos de su ruta del día (no puede tocar otras zonas).
    // ──────────────────────────────────────────────────────────────────────────

    public function fijarUbicacionGanadero(Request $request, \App\Models\Ganadero $ganadero,
                                           \App\Services\RutaChoferService $rutas): JsonResponse
    {
        $validated = $request->validate([
            'latitud'     => ['required', 'numeric', 'between:-90,90'],
            'longitud'    => ['required', 'numeric', 'between:-180,180'],
            'precision_m' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'precision_m.max' => 'La señal GPS es muy imprecisa (más de 100 m). Espera unos segundos e inténtalo de nuevo.',
        ]);

        $usuario = $request->user();
        if ($usuario->rol !== 'admin') {
            $ruta = $rutas->rutaDe($usuario);
            $idsRuta = collect($ruta['paradas'] ?? [])->pluck('ganadero_id');
            if (! $idsRuta->contains($ganadero->id)) {
                return response()->json(['message' => 'Este ganadero no pertenece a tu ruta.'], 403);
            }
        }

        $ganadero->update([
            'geo_lat' => round((float) $validated['latitud'], 7),
            'geo_lon' => round((float) $validated['longitud'], 7),
        ]);

        return response()->json([
            'message' => "Ubicación de {$ganadero->nombre} guardada.",
            'geo_lat' => $ganadero->geo_lat,
            'geo_lon' => $ganadero->geo_lon,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/tracking/lote
    // La app guarda los puntos GPS en SQLDelight y los sube en lotes cuando
    // hay señal. Idempotente: un reintento no duplica puntos (unique chofer+hora).
    // ──────────────────────────────────────────────────────────────────────────

    public function trackingLote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'puntos'                   => ['required', 'array', 'min:1', 'max:500'],
            'puntos.*.latitud'         => ['required', 'numeric', 'between:-90,90'],
            'puntos.*.longitud'        => ['required', 'numeric', 'between:-180,180'],
            'puntos.*.registrado_en'   => ['required', 'integer', 'min:0'],
            'puntos.*.precision_m'     => ['nullable', 'numeric', 'min:0'],
            'puntos.*.velocidad_kmh'   => ['nullable', 'numeric', 'min:0'],
            'estado_ruta'              => ['sometimes', 'in:en_camino,finalizado'],
        ]);

        $choferId = $request->user()->id;
        $ahora    = now();

        $filas = collect($validated['puntos'])->map(fn ($p) => [
            'chofer_id'     => $choferId,
            'latitud'       => $p['latitud'],
            'longitud'      => $p['longitud'],
            'precision_m'   => $p['precision_m'] ?? null,
            'velocidad_kmh' => $p['velocidad_kmh'] ?? null,
            'registrado_en' => Carbon::createFromTimestampMs($p['registrado_en'])->setTimezone(config('app.timezone')),
            'created_at'    => $ahora,
            'updated_at'    => $ahora,
        ]);

        // insertOrIgnore: los puntos ya recibidos se descartan sin error
        $nuevos = TrackingGpsPunto::insertOrIgnore($filas->all());

        // La posición "en vivo" del panel es el punto más reciente del lote
        $ultimo = collect($validated['puntos'])->sortByDesc('registrado_en')->first();
        TrackingGps::actualizarUbicacion(
            choferId:   $choferId,
            latitud:    (float) $ultimo['latitud'],
            longitud:   (float) $ultimo['longitud'],
            estadoRuta: $validated['estado_ruta'] ?? 'en_camino',
        );

        return response()->json([
            'recibidos' => count($validated['puntos']),
            'nuevos'    => $nuevos,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/tracking
    // Background Service de Kotlin envía lat/lng periódicamente.
    // Usa UPSERT: un solo registro por chofer en tracking_gps.
    // ──────────────────────────────────────────────────────────────────────────

    public function tracking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitud'     => ['required', 'numeric', 'between:-90,90'],
            'longitud'    => ['required', 'numeric', 'between:-180,180'],
            'estado_ruta' => ['sometimes', 'in:en_camino,finalizado'],
        ]);

        /** @var \App\Models\User $chofer */
        $chofer = $request->user();

        $tracking = TrackingGps::actualizarUbicacion(
            choferId:   $chofer->id,
            latitud:    (float) $validated['latitud'],
            longitud:   (float) $validated['longitud'],
            estadoRuta: $validated['estado_ruta'] ?? 'en_camino',
        );

        return response()->json([
            'message'             => 'Ubicación actualizada.',
            'ultima_actualizacion' => $tracking->ultima_actualizacion->toIso8601String(),
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/estado-solicitud-ruta
    // El chofer consulta el estado de su última solicitud de cambio de ruta.
    // Útil para que la app muestre si está pendiente, aprobada o rechazada,
    // y para recibir la notificación de resolución sin depender del push.
    // ──────────────────────────────────────────────────────────────────────────

    public function estadoSolicitudRuta(Request $request): JsonResponse
    {
        /** @var \App\Models\User $chofer */
        $chofer = $request->user();

        $solicitud = SolicitudRuta::where('chofer_id', $chofer->id)
                                  ->orderByDesc('created_at')
                                  ->first();

        if (! $solicitud) {
            return response()->json([
                'tiene_solicitud' => false,
                'solicitud'       => null,
            ]);
        }

        return response()->json([
            'tiene_solicitud' => true,
            'solicitud'       => [
                'id'              => $solicitud->id,
                'ruta_actual'     => $solicitud->ruta_actual,
                'ruta_solicitada' => $solicitud->ruta_solicitada,
                'motivo'          => $solicitud->motivo,
                'estado'          => $solicitud->estado,
                'fecha_solicitud' => $solicitud->fecha_solicitud?->toDateString(),
                'revisado_at'     => $solicitud->revisado_at?->toIso8601String(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/solicitar-ruta
    // El chofer solicita un cambio de ruta desde la app.
    // ──────────────────────────────────────────────────────────────────────────

    public function solicitarRuta(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ruta_solicitada' => ['required', 'string', 'max:100'],
            'motivo'          => ['required', 'string', 'max:500'],
        ]);

        /** @var \App\Models\User $chofer */
        $chofer = $request->user();

        // Bloquear si ya tiene una solicitud pendiente sin resolver
        $pendiente = SolicitudRuta::where('chofer_id', $chofer->id)
                                  ->where('estado', 'pendiente')
                                  ->exists();

        if ($pendiente) {
            return response()->json([
                'message' => 'Ya tienes una solicitud de cambio de ruta pendiente de revisión.',
            ], 422);
        }

        // Obtener la ruta actual del chofer desde su último acopio real
        // (orden por fecha de acopio + hora de registro, no por updated_at del ganadero)
        $ultimoAcopio = \App\Models\Acopio::where('chofer_id', $chofer->id)
                                          ->orderByDesc('fecha')
                                          ->orderByDesc('created_at')
                                          ->first(['ganadero_id']);

        $rutaActual = $ultimoAcopio
            ? \App\Models\Ganadero::where('id', $ultimoAcopio->ganadero_id)->value('ruta_asignada') ?? 'Sin asignar'
            : 'Sin asignar';

        $solicitud = SolicitudRuta::create([
            'chofer_id'       => $chofer->id,
            'ruta_actual'     => $rutaActual,
            'ruta_solicitada' => $validated['ruta_solicitada'],
            'motivo'          => $validated['motivo'],
            'estado'          => 'pendiente',
            'fecha_solicitud' => now()->toDateString(),
        ]);

        return response()->json([
            'message'   => 'Solicitud enviada. El administrador la revisará pronto.',
            'solicitud' => [
                'id'              => $solicitud->id,
                'ruta_actual'     => $solicitud->ruta_actual,
                'ruta_solicitada' => $solicitud->ruta_solicitada,
                'estado'          => $solicitud->estado,
                'fecha_solicitud' => $solicitud->fecha_solicitud->toDateString(),
            ],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/reuniones
    // Reuniones a las que el usuario autenticado fue invitado.
    // ──────────────────────────────────────────────────────────────────────────

    public function reuniones(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $reuniones = $user->reuniones()
                          ->where('fecha_hora', '>=', now()->subDays(1))
                          ->orderBy('fecha_hora')
                          ->get()
                          ->map(fn (Reunion $r) => [
                              'id'           => $r->id,
                              'titulo'       => $r->titulo,
                              'tipo'         => $r->tipo,
                              'tipo_label'   => $r->etiquetaTipo(),
                              'fecha_hora'   => $r->fecha_hora->toIso8601String(),
                              'fecha_human'  => $r->fecha_hora->isoFormat('dddd D [de] MMMM, HH:mm'),
                              'enlace'       => $r->enlace,
                              'tiene_video'  => $r->tieneEnlaceVideo(),
                              'lugar'        => $r->lugar,
                              'descripcion'  => $r->descripcion,
                              'estado'       => $r->estado,
                              'confirmado'   => (bool) $r->pivot->confirmado,
                          ]);

        return response()->json([
            'reuniones' => $reuniones,
            'total'     => $reuniones->count(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/notificaciones
    // Notificaciones no leídas del usuario autenticado.
    // ──────────────────────────────────────────────────────────────────────────

    public function notificaciones(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $notificaciones = $user->notificaciones()
                               ->noLeidas()
                               ->orderByDesc('created_at')
                               ->limit(50)
                               ->get(['id', 'titulo', 'mensaje', 'tipo', 'referencia_id', 'created_at']);

        return response()->json([
            'notificaciones' => $notificaciones,
            'total_no_leidas' => $notificaciones->count(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/notificaciones/{id}/leer
    // Marca una notificación específica como leída.
    // ──────────────────────────────────────────────────────────────────────────

    public function marcarLeida(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $notificacion = $user->notificaciones()->findOrFail($id);
        $notificacion->update(['leida' => true]);

        return response()->json(['message' => 'Notificación marcada como leída.']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/notificaciones/leer-todas
    // Marca todas las notificaciones del usuario como leídas.
    // ──────────────────────────────────────────────────────────────────────────

    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $count = $user->notificaciones()->noLeidas()->update(['leida' => true]);

        return response()->json([
            'message'    => 'Notificaciones marcadas como leídas.',
            'marcadas'   => $count,
        ]);
    }
}
