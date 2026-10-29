<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Acopio;
use App\Models\Ganadero;
use App\Models\Liquidacion;
use App\Models\ReclamoLitraje;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PortalProveedorController — RF-USU-05 / RF-PRO-06
 *
 * Portal de consulta para proveedores (ganaderos).
 * Acceso: usuarios con rol 'proveedor' vinculados a un ganadero via user_id.
 *
 * El proveedor puede ver:
 *   - Su litraje del día actual (para reclamar el mismo día si hay error)
 *   - Historial de entregas de la semana
 *   - Resultados de calidad de sus acopios
 *   - Sus pagos/liquidaciones
 */
class PortalProveedorController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // GET /proveedor/dashboard
    // ──────────────────────────────────────────────────────────────────────────

    public function dashboard(): View
    {
        $user     = auth()->user();
        $ganadero = Ganadero::where('user_id', $user->id)->first();

        if (! $ganadero) {
            abort(403, 'Tu cuenta no está vinculada a ningún proveedor. Contacta al administrador.');
        }

        // ── Acopios del día ───────────────────────────────────────────────────
        $hoy        = now()->toDateString();
        $acopiosHoy = Acopio::where('ganadero_id', $ganadero->id)
            ->where('fecha', $hoy)
            ->orderByDesc('created_at')
            ->get();

        // ── Semana actual ─────────────────────────────────────────────────────
        $semanaInicio = Liquidacion::inicioSemana(now());
        $semanaFin    = Liquidacion::finSemana($semanaInicio);

        $acopiosSemana = Acopio::where('ganadero_id', $ganadero->id)
            ->whereBetween('fecha', [$semanaInicio->toDateString(), $semanaFin->toDateString()])
            ->orderBy('fecha')
            ->get();

        $litrosSemana  = $acopiosSemana->sum('litros');
        $litrosHoy     = $acopiosHoy->sum('litros');

        // ── Liquidación de la semana si ya fue generada ────────────────────────
        $liquidacionSemana = Liquidacion::where('ganadero_id', $ganadero->id)
            ->where('semana_inicio', $semanaInicio->toDateString())
            ->first();

        // ── Última liquidación pagada ─────────────────────────────────────────
        $ultimoPago = Liquidacion::where('ganadero_id', $ganadero->id)
            ->where('estado', 'pagada')
            ->orderByDesc('semana_inicio')
            ->first();

        $reclamosHoy = ReclamoLitraje::where('ganadero_id', $ganadero->id)
            ->where('fecha_reclamo', $hoy)
            ->orderByDesc('created_at')
            ->get();

        // RF-COM-01 — Comunicados masivos recientes para el proveedor
        $comunicadosRecientes = \App\Models\Comunicado::with('autor:id,name')
            ->recientes()
            ->limit(5)
            ->get();

        return view('proveedor.dashboard', compact(
            'ganadero', 'acopiosHoy', 'acopiosSemana',
            'litrosSemana', 'litrosHoy',
            'liquidacionSemana', 'ultimoPago',
            'semanaInicio', 'semanaFin',
            'reclamosHoy', 'comunicadosRecientes'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /proveedor/historial
    // ──────────────────────────────────────────────────────────────────────────

    public function historial(Request $request): View
    {
        $user     = auth()->user();
        $ganadero = Ganadero::where('user_id', $user->id)->firstOrFail();

        $dias = (int) $request->query('dias', 30);

        $desde = now()->subDays($dias)->toDateString();

        $acopios = Acopio::where('ganadero_id', $ganadero->id)
            ->where('fecha', '>=', $desde)
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->get();

        $reclamos = ReclamoLitraje::where('ganadero_id', $ganadero->id)
            ->where('fecha_reclamo', '>=', $desde)
            ->orderByDesc('fecha_reclamo')
            ->get();

        $liquidaciones = Liquidacion::where('ganadero_id', $ganadero->id)
            ->where('semana_inicio', '>=', $desde)
            ->orderByDesc('semana_inicio')
            ->get();

        $sancionesCount = $acopios->whereIn('estado', ['rechazado', 'observado'])->count()
            + $acopios->where('tiene_descuento', true)->count();

        return view('proveedor.historial', compact(
            'ganadero', 'acopios', 'dias', 'reclamos', 'liquidaciones', 'sancionesCount'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /proveedor/pagos
    // ──────────────────────────────────────────────────────────────────────────

    public function pagos(): View
    {
        $user     = auth()->user();
        $ganadero = Ganadero::where('user_id', $user->id)->firstOrFail();

        $liquidaciones = Liquidacion::where('ganadero_id', $ganadero->id)
            ->orderByDesc('semana_inicio')
            ->paginate(12);

        $totalPagado = Liquidacion::where('ganadero_id', $ganadero->id)
            ->where('estado', 'pagada')
            ->sum('total_neto');

        return view('proveedor.pagos', compact('ganadero', 'liquidaciones', 'totalPagado'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /proveedor/reclamo — RF-PRO-06 (solo el mismo día del acopio)
    // ──────────────────────────────────────────────────────────────────────────

    public function storeReclamo(Request $request): RedirectResponse
    {
        $user     = auth()->user();
        $ganadero = Ganadero::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'acopio_id'        => ['required', 'integer', 'exists:acopios,id'],
            'litros_correctos' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'motivo'           => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $acopio = Acopio::where('id', $validated['acopio_id'])
            ->where('ganadero_id', $ganadero->id)
            ->firstOrFail();

        $hoy = now()->toDateString();
        if ($acopio->fecha->toDateString() !== $hoy) {
            return back()->with('error', 'Solo puedes reclamar litraje de entregas registradas hoy.');
        }

        $yaExiste = ReclamoLitraje::where('ganadero_id', $ganadero->id)
            ->where('acopio_id', $acopio->id)
            ->where('estado', 'pendiente')
            ->exists();

        if ($yaExiste) {
            return back()->with('error', 'Ya tienes un reclamo pendiente para esta entrega.');
        }

        ReclamoLitraje::create([
            'ganadero_id'        => $ganadero->id,
            'acopio_id'          => $acopio->id,
            'fecha_reclamo'      => $hoy,
            'litros_registrados' => $acopio->litros,
            'litros_correctos'   => $validated['litros_correctos'],
            'motivo'             => $validated['motivo'],
            'estado'             => 'pendiente',
        ]);

        return back()->with('success', '✅ Reclamo enviado. El administrador lo revisará hoy.');
    }
}
