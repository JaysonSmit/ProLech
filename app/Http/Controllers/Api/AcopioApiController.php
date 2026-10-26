<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Acopio;
use App\Models\Ganadero;
use App\Services\RutaChoferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AcopioApiController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/mi-ruta
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Ruta del día del chofer: zona asignada este mes (rotación), paradas
     * ordenadas por cercanía desde la planta, ganaderos ya atendidos y la
     * ubicación de la planta para trazar el recorrido en el mapa de la app.
     */
    public function miRuta(Request $request, RutaChoferService $rutas): JsonResponse
    {
        $chofer = $request->user();
        $datos  = $rutas->rutaDe($chofer, $request->query('ruta'));

        if (isset($datos['error'])) {
            return response()->json(['message' => $datos['error']], 422);
        }

        $hoy = now()->toDateString();
        $datos['acopios_hoy'] = Acopio::where('chofer_id', $chofer->id)
                                      ->where('fecha', $hoy)
                                      ->orderByDesc('created_at')
                                      ->get(['id', 'ganadero_id', 'litros', 'porcentaje_agua',
                                             'acidez_ph', 'densidad', 'tiene_descuento',
                                             'estado', 'fecha', 'created_at']);
        $datos['fecha_hoy'] = $hoy;

        return response()->json($datos);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/acopios
    //
    // Reglas de negocio implementadas:
    //   1. Bloqueo: ganadero con estado 'Suspendido' → 403
    //   2. Duplicado: mismo ganadero no puede entregar más de 1 vez/día → 422
    //   3. 2-strikes: >= 2 adulteraciones en la semana → suspender ganadero
    //   4. Notificación al admin cuando se ejecuta la suspensión
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ganadero_id'       => ['required', 'integer', 'exists:ganaderos,id'],
            'litros'                      => ['required', 'numeric', 'min:0.1', 'max:9999.99'],
            'medicion_balde_confirmada'   => ['required', 'boolean', 'accepted'],
            'equipo_lactoscan'            => ['nullable', 'integer', 'between:1,3'],
            'capacidad_tacho_litros'      => ['nullable', 'numeric', 'min:1', 'max:500'],
            'porcentaje_agua'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'acidez_ph'         => ['nullable', 'numeric', 'min:0', 'max:14'],
            'densidad'          => ['nullable', 'numeric', 'min:0.900', 'max:1.100'],
            // ── Campos LACTOMAT completos ─────────────────────────────────────
            'grasa'             => ['nullable', 'numeric', 'min:0', 'max:100'],
            'proteina'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lactosa'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sng'               => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sales'             => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total_solidos'     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'punto_congelacion' => ['nullable', 'numeric', 'min:-2', 'max:0'],
            // ─────────────────────────────────────────────────────────────────
            'fecha'             => ['required', 'date', 'before_or_equal:today'],
            'observaciones'     => ['nullable', 'string', 'max:500'],
            // Foto de evidencia JPEG en Base64 (máx. ~4 MB de imagen)
            'foto_evidencia'    => ['nullable', 'string', 'max:5600000'],
            // UUID generado en el celular: los reintentos offline no duplican el acopio
            'client_uuid'       => ['nullable', 'uuid'],
        ]);

        // ── Idempotencia: este acopio ya llegó en un envío anterior ──────────
        if (! empty($validated['client_uuid'])) {
            $existente = Acopio::where('client_uuid', $validated['client_uuid'])->first();

            if ($existente) {
                if ($existente->chofer_id !== $request->user()->id) {
                    return response()->json(['message' => 'No tienes permisos sobre este acopio.'], 403);
                }

                return response()->json([
                    'message'    => 'Acopio ya registrado anteriormente.',
                    'duplicado'  => true,
                    'acopio'     => $this->formatearAcopio($existente),
                ], 200);
            }
        }

        // ── Decodificar la foto de evidencia antes de tocar la BD ─────────────
        $fotoBytes = null;
        if (! empty($validated['foto_evidencia'])) {
            $fotoBytes = base64_decode($validated['foto_evidencia'], true);
            if ($fotoBytes === false || ! str_starts_with($fotoBytes, "\xFF\xD8")) {
                return response()->json([
                    'message' => 'La foto de evidencia no es una imagen JPEG válida.',
                    'errors'  => ['foto_evidencia' => ['Formato inválido.']],
                ], 422);
            }
        }
        unset($validated['foto_evidencia']);

        // ── REGLA 1: Verificar que el ganadero NO esté suspendido ─────────────
        $ganadero = Ganadero::findOrFail($validated['ganadero_id']);

        if ($ganadero->estaSuspendido()) {
            return response()->json([
                'message' => 'Ganadero suspendido por reincidencia en adulteración de leche. Contacte al administrador.',
                'codigo'  => 'GANADERO_SUSPENDIDO',
                'ganadero'=> $ganadero->nombre,
            ], 403);
        }

        // ── REGLA 2: Sin duplicado en el mismo día ────────────────────────────
        $yaEntrego = Acopio::where('ganadero_id', $validated['ganadero_id'])
                           ->where('fecha', $validated['fecha'])
                           ->exists();

        if ($yaEntrego) {
            return response()->json([
                'message' => 'Este productor ya entregó leche hoy. Solo se permite una entrega por día.',
                'errors'  => ['ganadero_id' => ['Duplicado: ya existe un acopio para este productor en la fecha indicada.']],
            ], 422);
        }

        // ── Calcular estado y descuento ───────────────────────────────────────
        $estado         = 'aprobado';
        $tieneDescuento = false;

        $agua     = isset($validated['porcentaje_agua']) ? (float) $validated['porcentaje_agua'] : 0.0;
        $ph       = isset($validated['acidez_ph'])       ? (float) $validated['acidez_ph']       : 0.0;
        $densidad = isset($validated['densidad'])        ? (float) $validated['densidad']         : null;

        $estado         = Acopio::calcularEstado($agua, $ph, $densidad);
        $tieneDescuento = Acopio::calcularDescuento($agua);

        $tieneParametrosCalidad = $agua > 0 || $ph > 0 || $densidad !== null
            || ! empty($validated['grasa']) || ! empty($validated['proteina']);

        if ($tieneParametrosCalidad && empty($validated['equipo_lactoscan'])) {
            return response()->json([
                'message' => 'Indique qué equipo LactoScan (1, 2 o 3) se usó para esta medición.',
                'errors'  => ['equipo_lactoscan' => ['Requerido cuando hay parámetros de calidad.']],
            ], 422);
        }

        if (! empty($validated['capacidad_tacho_litros'])
            && (float) $validated['litros'] > (float) $validated['capacidad_tacho_litros']) {
            return response()->json([
                'message' => 'Los litros superan la capacidad del tacho indicada.',
                'errors'  => ['litros' => ['RF-ACO-03: verifique la medición con balde.']],
            ], 422);
        }

        // ── Guardar el acopio ─────────────────────────────────────────────────
        $acopio = Acopio::create([
            ...$validated,
            'chofer_id'       => $request->user()->id,
            'estado'          => $estado,
            'tiene_descuento' => $tieneDescuento,
        ]);

        if ($fotoBytes !== null) {
            $ruta = "evidencias/acopio_{$acopio->id}.jpg";
            Storage::disk('public')->put($ruta, $fotoBytes);
            $acopio->update(['foto_evidencia' => $ruta]);
        }

        // ── REGLA 3: Sistema de 2-strikes (aviso y suspensión) ────────────────
        $mensaje2Strikes = app(\App\Services\AdulteracionService::class)->evaluar($acopio);

        // ── Construir respuesta ───────────────────────────────────────────────
        $responseData = [
            'message'         => 'Acopio registrado correctamente.',
            'acopio'          => $this->formatearAcopio($acopio),
        ];

        if ($mensaje2Strikes) {
            $responseData['alerta_calidad'] = $mensaje2Strikes;
            // Si el ganadero quedó suspendido, informar a la app para bloquear el dropdown
            $responseData['ganadero_suspendido'] = $ganadero->fresh()->estaSuspendido();
        }

        return response()->json($responseData, 201);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/acopios/historial
    // ──────────────────────────────────────────────────────────────────────────

    public function historial(Request $request): JsonResponse
    {
        $request->validate([
            'dias'  => ['sometimes', 'integer', 'min:1', 'max:365'],
            'fecha' => ['sometimes', 'date'],
        ]);

        $query = Acopio::with('ganadero:id,nombre,dni')
            ->where('chofer_id', $request->user()->id);

        if ($request->filled('fecha')) {
            $query->where('fecha', $request->query('fecha'));
        } else {
            $dias = (int) $request->query('dias', 90);
            $query->where('fecha', '>=', now()->subDays($dias)->toDateString());
        }

        $acopios = $query
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->get([
                'id', 'ganadero_id', 'litros', 'porcentaje_agua',
                'acidez_ph', 'densidad',
                'grasa', 'proteina', 'lactosa', 'sng',
                'sales', 'total_solidos', 'punto_congelacion',
                'tiene_descuento', 'estado', 'fecha', 'created_at', 'observaciones',
            ]);

        $stats = [
            'total_litros'  => (float) $acopios->sum('litros'),
            'total_acopios' => $acopios->count(),
            'aprobados'     => $acopios->where('estado', 'aprobado')->count(),
            'rechazados'    => $acopios->where('estado', 'rechazado')->count(),
        ];

        return response()->json(['acopios' => $acopios, 'stats' => $stats]);
    }

    // ── Formato JSON de un acopio (contrato con la App Kotlin) ────────────────
    private function formatearAcopio(Acopio $acopio): array
    {
        $num = fn ($v) => $v !== null ? (float) $v : null;

        return [
            'id'                => $acopio->id,
            'ganadero_id'       => $acopio->ganadero_id,
            'litros'            => (float) $acopio->litros,
            'porcentaje_agua'   => $num($acopio->porcentaje_agua),
            'acidez_ph'         => $num($acopio->acidez_ph),
            'densidad'          => $num($acopio->densidad),
            // ── Campos LACTOMAT ───────────────────────────────────────────────
            'grasa'             => $num($acopio->grasa),
            'proteina'          => $num($acopio->proteina),
            'lactosa'           => $num($acopio->lactosa),
            'sng'               => $num($acopio->sng),
            'sales'             => $num($acopio->sales),
            'total_solidos'     => $num($acopio->total_solidos),
            'punto_congelacion' => $num($acopio->punto_congelacion),
            // ─────────────────────────────────────────────────────────────────
            'tiene_descuento'   => $acopio->tiene_descuento,
            'estado'            => $acopio->estado,
            'fecha'             => $acopio->fecha->toDateString(),
            'created_at'        => $acopio->created_at->toIso8601String(),
        ];
    }
}
