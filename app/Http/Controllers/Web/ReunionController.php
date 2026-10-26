<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\Reunion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReunionController extends Controller
{
    /**
     * GET /admin/reuniones
     * Vista de agenda con próximas y pasadas reuniones.
     */
    public function index(): View
    {
        $proximas = Reunion::with(['creador:id,name', 'invitados:id,name,rol'])
            ->proximas()
            ->get();

        $pasadas = Reunion::with(['creador:id,name'])
            ->pasadas()
            ->limit(10)
            ->get();

        // Usuarios disponibles para invitar (choferes y ganaderos activos)
        $usuariosInvitables = User::conRol('chofer', 'ganadero')
            ->where('activo', true)
            ->orderBy('name')
            ->get(['id', 'name', 'rol']);

        return view('admin.reuniones', compact('proximas', 'pasadas', 'usuariosInvitables'));
    }

    /**
     * POST /admin/reuniones
     *
     * FASE 4 — Genera notificaciones para:
     *   1. Todos los choferes activos (siempre)
     *   2. Todos los ganaderos que tengan user_id (si acceden a la app)
     *   3. Los invitados específicos seleccionados manualmente
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'titulo'       => ['required', 'string', 'max:200'],
            'tipo'         => ['required', Rule::in(['capacitacion', 'asamblea', 'otro'])],
            'fecha_hora'   => ['required', 'date', 'after:now'],
            'enlace'       => ['nullable', 'url', 'max:500'],
            'lugar'        => ['nullable', 'string', 'max:200'],
            'descripcion'  => ['nullable', 'string', 'max:2000'],
            'invitados'    => ['nullable', 'array'],
            'invitados.*'  => ['integer', 'exists:users,id'],
        ]);

        $reunion = Reunion::create([
            'titulo'      => $validated['titulo'],
            'tipo'        => $validated['tipo'],
            'fecha_hora'  => $validated['fecha_hora'],
            'enlace'      => $validated['enlace'] ?? null,
            'lugar'       => $validated['lugar'] ?? null,
            'descripcion' => $validated['descripcion'] ?? null,
            'estado'      => 'programada',
            'creado_por'  => $request->user()->id,
        ]);

        // ── Construir el mensaje de notificación ──────────────────────────────
        $fechaFormato = \Carbon\Carbon::parse($validated['fecha_hora'])
            ->isoFormat('dddd D [de] MMMM [a las] HH:mm');

        $tipoLabel = match($validated['tipo']) {
            'capacitacion' => 'capacitación',
            'asamblea'     => 'asamblea',
            default        => 'reunión',
        };

        $mensajeBase = "Nueva reunión programada: *{$validated['titulo']}* para el {$fechaFormato}.";
        if (! empty($validated['lugar'])) {
            $mensajeBase .= " Lugar: {$validated['lugar']}.";
        }
        if (! empty($validated['enlace'])) {
            $mensajeBase .= " Se realizará por videollamada.";
        }

        // ── Recopilar destinatarios únicos ────────────────────────────────────
        $invitadosExplicitos = collect($validated['invitados'] ?? []);

        // Todos los choferes activos
        $chofereIds = User::conRol('chofer')
                          ->where('activo', true)
                          ->pluck('id');

        // Ganaderos con cuenta en la app (user_id no nulo)
        $ganaderoUserIds = \App\Models\Ganadero::whereNotNull('user_id')
                                               ->where('activo', true)
                                               ->pluck('user_id');

        // Unión sin duplicados
        $todosIds = $invitadosExplicitos
            ->merge($chofereIds)
            ->merge($ganaderoUserIds)
            ->unique()
            ->filter() // quitar nulls
            ->values();

        // ── Attach al pivot solo los explícitos ───────────────────────────────
        if ($invitadosExplicitos->isNotEmpty()) {
            $reunion->invitados()->attach(
                $invitadosExplicitos->mapWithKeys(fn ($id) => [
                    $id => ['notificado' => true, 'confirmado' => false]
                ])->all()
            );
        }

        // ── Crear una Notificacion por cada destinatario ──────────────────────
        $notificados = 0;
        foreach ($todosIds as $userId) {
            try {
                Notificacion::enviar(
                    userId:       (int) $userId,
                    titulo:       "📅 Nueva {$tipoLabel}: {$validated['titulo']}",
                    mensaje:      $mensajeBase,
                    tipo:         'reunion',
                    referenciaId: $reunion->id,
                );
                $notificados++;
            } catch (\Throwable) {
                // Continuar aunque falle una notificación individual
            }
        }

        return redirect()
            ->route('admin.reuniones')
            ->with('success', "Reunión \"{$reunion->titulo}\" creada. {$notificados} usuario(s) notificados (choferes + ganaderos con app).");
    }

    /**
     * POST /admin/reuniones/{reunion}/cancelar
     * Cancela una reunión y notifica a los invitados.
     */
    public function cancelar(Request $request, Reunion $reunion): RedirectResponse
    {
        if ($reunion->estado === 'finalizada') {
            return back()->with('error', 'No se puede cancelar una reunión ya finalizada.');
        }

        $reunion->update(['estado' => 'cancelada']);

        // Notificar a todos los invitados
        foreach ($reunion->invitados as $invitado) {
            Notificacion::enviar(
                userId:       $invitado->id,
                titulo:       "❌ Reunión cancelada: {$reunion->titulo}",
                mensaje:      "La {$reunion->tipo} del {$reunion->fecha_hora->isoFormat('D [de] MMMM')} ha sido cancelada.",
                tipo:         'reunion',
                referenciaId: $reunion->id,
            );
        }

        return redirect()
            ->route('admin.reuniones')
            ->with('success', "Reunión cancelada. Invitados notificados.");
    }
}
