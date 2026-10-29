<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcopioCampo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// ══════════════════════════════════════════════════════════════════════════════
//  AcopioCampoController — Sincronización offline-first de la App Kotlin
//
//  El celular genera el UUID y guarda primero en SQLDelight; el SyncManager
//  sube los pendientes cuando hay red. Por eso:
//   • POST y PUT son idempotentes (UPSERT por uuid): un reintento no duplica.
//   • Conflictos se resuelven con Last-Write-Wins sobre `actualizado_en` (ms).
//   • Cada chofer solo ve y modifica sus propios registros.
// ══════════════════════════════════════════════════════════════════════════════

class AcopioCampoController extends Controller
{
    // GET /api/acopios-campo
    public function index(Request $request): JsonResponse
    {
        $acopios = AcopioCampo::where('chofer_id', $request->user()->id)
                              ->orderByDesc('actualizado_en')
                              ->get();

        return response()->json([
            'data' => $acopios->map->toApi()->values(),
        ]);
    }

    // POST /api/acopios-campo
    public function store(Request $request): JsonResponse
    {
        $datos = $this->validar($request, conUuid: true);

        return $this->guardar($request, $datos['uuid'], $datos);
    }

    // PUT /api/acopios-campo/{uuid}
    public function update(Request $request, string $uuid): JsonResponse
    {
        $datos = $this->validar($request, conUuid: false);

        return $this->guardar($request, $uuid, $datos);
    }

    // DELETE /api/acopios-campo/{uuid}
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $acopio = AcopioCampo::where('uuid', $uuid)
                             ->where('chofer_id', $request->user()->id)
                             ->first();

        if (! $acopio) {
            return response()->json(['message' => 'Acopio no encontrado.'], 404);
        }

        $acopio->delete();

        return response()->json(['message' => 'Acopio eliminado.']);
    }

    // POST /api/acopios-campo/{uuid}/foto  (multipart: foto)
    // Evidencia del acopio: pantalla del Lactoscan, balde o leche rechazada.
    public function foto(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
        ], [
            'foto.max' => 'La foto pesa más de 6 MB.',
        ]);

        $acopio = AcopioCampo::where('uuid', $uuid)
                             ->where('chofer_id', $request->user()->id)
                             ->first();

        if (! $acopio) {
            return response()->json(['message' => 'Acopio no encontrado.'], 404);
        }

        if ($acopio->foto_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($acopio->foto_path);
        }
        $acopio->update([
            'foto_path' => $request->file('foto')->store('acopios-campo/fotos', 'public'),
        ]);

        return response()->json(['data' => $acopio->toApi()]);
    }

    // ── Privados ───────────────────────────────────────────────────────────────

    private function validar(Request $request, bool $conUuid): array
    {
        $reglas = [
            'ganadero_id'     => ['required', 'integer', 'min:1'],
            'ganadero_nombre' => ['required', 'string', 'max:255'],
            'litros'          => ['required', 'numeric', 'min:0', 'max:99999'],
            // El chofer ya no mide la calidad: estos campos solo llegan de versiones antiguas de la app
            'porcentaje_agua' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'densidad'        => ['nullable', 'numeric', 'min:0', 'max:99'],
            'estado_calidad'  => ['nullable', 'in:APROBADO,OBSERVADO,RECHAZADO'],
            'actualizado_en'  => ['required', 'integer', 'min:0'],
            'grasa'           => ['nullable', 'numeric', 'min:0', 'max:15'],
            // Lo que ve a simple vista y el frasco si la app se lo pidió
            'observacion'      => ['nullable', 'in:' . implode(',', array_keys(\App\Services\ControlCalidadService::OBSERVACIONES))],
            'observacion_nota' => ['nullable', 'string', 'max:200'],
            'muestra_codigo'   => ['nullable', 'string', 'max:20'],
        ];

        if ($conUuid) {
            $reglas['uuid'] = ['required', 'uuid'];
        }

        $datos = $request->validate($reglas);
        $datos['porcentaje_agua'] ??= 0;
        $datos['densidad'] ??= 0;
        $datos['estado_calidad'] ??= 'APROBADO';
        return $datos;
    }

    /** UPSERT por uuid aplicando Last-Write-Wins. */
    private function guardar(Request $request, string $uuid, array $datos): JsonResponse
    {
        $choferId = $request->user()->id;
        $existente = AcopioCampo::where('uuid', $uuid)->first();

        if ($existente && $existente->chofer_id !== $choferId) {
            return response()->json(['message' => 'No tienes permisos sobre este acopio.'], 403);
        }

        // El servidor tiene una versión más reciente: se conserva y se devuelve
        if ($existente && $existente->actualizado_en > (int) $datos['actualizado_en']) {
            return response()->json(['data' => $existente->toApi()]);
        }

        $campos = collect($datos)->except('uuid')->all();

        if ($existente) {
            $existente->update($campos);
            return response()->json(['data' => $existente->toApi()]);
        }

        $acopio = AcopioCampo::create($campos + [
            'uuid'      => $uuid,
            'chofer_id' => $choferId,
        ]);

        return response()->json(['data' => $acopio->toApi()], 201);
    }
}
