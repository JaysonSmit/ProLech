<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AsignacionZona;
use App\Models\Notificacion;
use App\Models\SolicitudRuta;
use App\Models\Zona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RutasController extends Controller
{
    /**
     * GET /admin/rutas/solicitudes
     * Lista todas las solicitudes de cambio de ruta con filtros por estado.
     */
    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'pendiente');

        $solicitudes = SolicitudRuta::with(['chofer:id,name,email', 'revisor:id,name'])
            ->when($estado !== 'todas', fn ($q) => $q->where('estado', $estado))
            ->whereNotNull('chofer_id')        // Solo las de choferes (app móvil)
            // CASE en vez de FIELD(): FIELD solo existe en MySQL
            ->orderByRaw("CASE estado WHEN 'pendiente' THEN 0 WHEN 'aprobado' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $contadores = [
            'pendiente' => SolicitudRuta::whereNotNull('chofer_id')->where('estado', 'pendiente')->count(),
            'aprobado'  => SolicitudRuta::whereNotNull('chofer_id')->where('estado', 'aprobado')->count(),
            'rechazado' => SolicitudRuta::whereNotNull('chofer_id')->where('estado', 'rechazado')->count(),
        ];

        // Ruta que cada chofer tiene HOY (para las pendientes: la guardada pudo quedar desactualizada)
        $rutasDelMes = AsignacionZona::with('zona:id,nombre')->where('mes', now()->month)->where('anio', now()->year)
            ->get()->mapWithKeys(fn ($a) => [$a->chofer_id => $a->zona?->nombre ?? 'Volante']);

        return view('admin.rutas_solicitudes', compact('solicitudes', 'contadores', 'estado', 'rutasDelMes'));
    }

    /**
     * POST /admin/rutas/solicitudes/{solicitud}/responder
     * El admin aprueba o rechaza la solicitud y genera una notificación al chofer.
     */
    public function responderSolicitud(Request $request, SolicitudRuta $solicitud): RedirectResponse
    {
        $validated = $request->validate([
            'accion'         => ['required', Rule::in(['aprobado', 'rechazado'])],
            'nota_respuesta' => ['nullable', 'string', 'max:300'],
        ]);

        if (! $solicitud->esPendiente()) {
            return back()->with('error', 'Esta solicitud ya fue procesada anteriormente.');
        }

        $nuevoEstado = $validated['accion'];
        $nota        = $validated['nota_respuesta'] ?? '';

        // Actualizar la solicitud
        $solicitud->update([
            'estado'      => $nuevoEstado,
            'revisado_por'=> $request->user()->id,
            'revisado_at' => now(),
        ]);

        // Aprobada y la ruta pedida existe: el chofer pasa a esa ruta este mes (intercambia si estaba ocupada)
        $cambio = '';
        if ($nuevoEstado === 'aprobado' && $solicitud->chofer_id) {
            $pedida = mb_strtolower(trim((string) $solicitud->ruta_solicitada));
            $zona = Zona::where('activa', true)->get()->first(fn (Zona $z) =>
                mb_strtolower($z->nombre) === $pedida || mb_strtolower($z->codigo) === $pedida);
            if ($zona) {
                $intercambio = AsignacionZona::asignar($solicitud->chofer_id, $zona->id, now()->month, now()->year);
                $cambio = " Ya quedó asignado a {$zona->nombre} este mes." . ($intercambio ? " Intercambio: {$intercambio}." : '');
            } else {
                $cambio = " La ruta \"{$solicitud->ruta_solicitada}\" no existe en el sistema: asígnala a mano en «Rutas y choferes».";
            }
        }

        // Generar notificación push para el chofer
        if ($solicitud->chofer_id) {
            $esAprobado = $nuevoEstado === 'aprobado';

            $titulo  = $esAprobado
                ? '✅ Cambio de ruta aprobado'
                : '❌ Cambio de ruta rechazado';

            $mensaje = $esAprobado
                ? "Tu solicitud para pasar a la ruta \"{$solicitud->ruta_solicitada}\" fue APROBADA."
                    . ($nota ? " Nota: {$nota}" : '')
                : "Tu solicitud para la ruta \"{$solicitud->ruta_solicitada}\" fue RECHAZADA."
                    . ($nota ? " Motivo: {$nota}" : ' Contacta al administrador para más detalles.');

            Notificacion::enviar(
                userId:       $solicitud->chofer_id,
                titulo:       $titulo,
                mensaje:      $mensaje,
                tipo:         $nuevoEstado === 'aprobado' ? 'ruta_aprobada' : 'ruta_rechazada',
                referenciaId: $solicitud->id,
            );
        }

        $etiqueta = $nuevoEstado === 'aprobado' ? 'aprobada' : 'rechazada';
        return redirect()
            ->route('admin.rutas.solicitudes')
            ->with('success', "Solicitud {$etiqueta} correctamente. El chofer fue notificado.{$cambio}");
    }
}
