<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Comunicado;
use App\Models\Ganadero;
use App\Models\Notificacion;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComunicadoController extends Controller
{
    /**
     * GET /admin/comunicados
     */
    public function index(): View
    {
        $comunicados = Comunicado::with('autor:id,name,rol')
            ->recientes()
            ->paginate(12);

        $totalGanaderosActivos = Ganadero::where('activo', true)->count();
        $ganaderosConTelefono  = Ganadero::where('activo', true)
            ->whereNotNull('telefono')
            ->where('telefono', '!=', '')
            ->count();

        $stats = [
            'total_comunicados' => Comunicado::count(),
            'total_ganaderos'   => $totalGanaderosActivos,
            'con_telefono'      => $ganaderosConTelefono,
            'ultimo_comunicado' => Comunicado::latest('created_at')->value('fecha_publicacion'),
        ];

        return view('admin.comunicados.index', compact('comunicados', 'stats'));
    }

    /**
     * POST /admin/comunicados
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'titulo'          => ['required', 'string', 'max:150'],
            'tipo'            => ['required', 'in:general,urgente,calidad,precio,capacitacion'],
            'mensaje'         => ['required', 'string', 'max:2000'],
            'enviar_whatsapp' => ['nullable', 'boolean'],
        ]);

        $enviarWhatsApp = $request->boolean('enviar_whatsapp');
        $ganaderos      = Ganadero::where('activo', true)->get();
        $telefonos      = $ganaderos->pluck('telefono')->filter()->unique();

        $destinatariosCount = $ganaderos->count();
        $enviadosWhatsApp   = 0;

        if ($enviarWhatsApp && $telefonos->isNotEmpty()) {
            $tipoLabel = match ($validated['tipo']) {
                'urgente'      => '🚨 URGENTE',
                'calidad'      => '🔬 CALIDAD DE LECHE',
                'precio'       => '💵 PRECIOS Y LIQUIDACIONES',
                'capacitacion' => '📚 CAPACITACIÓN / VISITA',
                default        => '📢 COMUNICADO OFICIAL',
            };

            $textoWa  = "*{$tipoLabel} — PROLECH HUATA*\n\n";
            $textoWa .= "*{$validated['titulo']}*\n\n";
            $textoWa .= "{$validated['mensaje']}\n\n";
            $textoWa .= "📅 _" . now()->isoFormat('D [de] MMMM YYYY') . "_\n";
            $textoWa .= "🏢 _Administración ProLech_";

            $enviadosWhatsApp = WhatsAppService::enviarMasivo($telefonos, $textoWa);
        }

        $comunicado = Comunicado::create([
            'titulo'              => $validated['titulo'],
            'tipo'                => $validated['tipo'],
            'mensaje'             => $validated['mensaje'],
            'enviar_whatsapp'     => $enviarWhatsApp,
            'destinatarios_count' => $destinatariosCount,
            'publicado_por'       => $request->user()->id,
            'fecha_publicacion'   => now()->toDateString(),
        ]);

        // Notificar internamente a choferes y personal de planta
        $usuariosInternos = User::where('activo', true)
            ->whereIn('rol', ['admin', 'chofer', 'calidad', 'asistente', 'jefe_produccion'])
            ->where('id', '!=', $request->user()->id)
            ->get();

        foreach ($usuariosInternos as $u) {
            Notificacion::enviar(
                userId:       $u->id,
                titulo:       "📢 Nuevo comunicado: {$comunicado->titulo}",
                mensaje:      substr($comunicado->mensaje, 0, 140) . '...',
                tipo:         'general',
                referenciaId: $comunicado->id
            );
        }

        $msg = "✅ Comunicado publicado exitosamente para {$destinatariosCount} proveedores en el portal.";
        if ($enviarWhatsApp) {
            $msg .= " Se despachó la difusión por WhatsApp a {$enviadosWhatsApp} productores.";
        }

        return redirect()->route('admin.comunicados.index')->with('success', $msg);
    }

    /**
     * DELETE /admin/comunicados/{comunicado}
     */
    public function destroy(Comunicado $comunicado): RedirectResponse
    {
        $comunicado->delete();
        return back()->with('success', '✅ Comunicado eliminado del portal y del historial.');
    }
}
