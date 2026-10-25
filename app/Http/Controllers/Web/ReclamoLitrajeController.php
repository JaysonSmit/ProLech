<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ReclamoLitraje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReclamoLitrajeController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'pendiente');

        $reclamos = ReclamoLitraje::with(['ganadero', 'acopio', 'atendidoPor:id,name'])
            ->when($estado !== 'todos', fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $conteo = [
            'pendiente' => ReclamoLitraje::where('estado', 'pendiente')->count(),
            'atendido'  => ReclamoLitraje::where('estado', 'atendido')->count(),
            'rechazado' => ReclamoLitraje::where('estado', 'rechazado')->count(),
        ];

        return view('admin.reclamos.index', compact('reclamos', 'estado', 'conteo'));
    }

    public function responder(Request $request, ReclamoLitraje $reclamo): RedirectResponse
    {
        if ($reclamo->estado !== 'pendiente') {
            return back()->with('error', 'Este reclamo ya fue atendido.');
        }

        $validated = $request->validate([
            'estado'           => ['required', 'in:atendido,rechazado'],
            'respuesta_admin'  => ['required', 'string', 'max:2000'],
        ]);

        $reclamo->update([
            'estado'          => $validated['estado'],
            'respuesta_admin' => $validated['respuesta_admin'],
            'atendido_por'    => $request->user()->id,
            'atendido_at'     => now(),
        ]);

        $label = $validated['estado'] === 'atendido' ? 'atendido' : 'rechazado';

        return back()->with('success', "✅ Reclamo de {$reclamo->ganadero->nombre} marcado como {$label}.");
    }
}
