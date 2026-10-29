<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Acopio;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Listado de entregas de leche (acopios) con filtros y paginación. */
class AcopioWebController extends Controller
{
    public function index(Request $request): View
    {
        $f = $request->validate([
            'desde'  => ['nullable', 'date'],
            'hasta'  => ['nullable', 'date'],
            'zona'   => ['nullable', 'integer'],
            'chofer' => ['nullable', 'integer'],
            'estado' => ['nullable', 'in:aprobado,observado,rechazado'],
            'buscar' => ['nullable', 'string', 'max:80'],
        ]);

        $query = Acopio::with(['ganadero:id,nombre,dni,zona_id', 'ganadero.zona:id,nombre', 'chofer:id,name'])
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->whereDate('fecha', '>=', $v))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->whereDate('fecha', '<=', $v))
            ->when($f['zona'] ?? null, fn ($q, $v) => $q->whereHas('ganadero', fn ($g) => $g->where('zona_id', $v)))
            ->when($f['chofer'] ?? null, fn ($q, $v) => $q->where('chofer_id', $v))
            ->when($f['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))
            ->when($f['buscar'] ?? null, fn ($q, $v) => $q->whereHas('ganadero', fn ($g) => $g->where('nombre', 'like', "%{$v}%")->orWhere('dni', 'like', "%{$v}%")));

        $totales = (clone $query)->selectRaw("COUNT(*) n, COALESCE(SUM(litros),0) litros,
            SUM(CASE WHEN estado = 'aprobado' THEN 1 ELSE 0 END) aprobados,
            SUM(CASE WHEN estado = 'observado' THEN 1 ELSE 0 END) observados,
            SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) rechazados")->first();

        $acopios = $query->orderByDesc('fecha')->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('admin.acopios.index', [
            'acopios'  => $acopios,
            'totales'  => $totales,
            'filtros'  => $f,
            'zonas'    => Zona::where('activa', true)->orderBy('orden')->get(['id', 'nombre']),
            'choferes' => User::conRol('chofer')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
