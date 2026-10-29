<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ganadero;
use App\Models\Zona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GanaderoController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // GET /admin/ganaderos
    // ──────────────────────────────────────────────────────────────────────────

    /** Ficha de seguimiento del ganadero: semáforo, entregas, pagos y controles de calidad. */
    public function show(Ganadero $ganadero): View
    {
        $ganadero->load('zona:id,nombre');
        $riesgo = app(\App\Services\ControlCalidadService::class)->riesgo($ganadero);
        $acopios = \App\Models\Acopio::with('chofer:id,name')->where('ganadero_id', $ganadero->id)
            ->orderByDesc('fecha')->orderByDesc('id')->paginate(10);
        $resumen = \App\Models\Acopio::where('ganadero_id', $ganadero->id)->whereDate('fecha', '>=', today()->subDays(30))
            ->selectRaw("COUNT(*) n, COALESCE(SUM(litros),0) litros, AVG(porcentaje_agua) agua,
                SUM(CASE WHEN estado IN ('observado','rechazado') THEN 1 ELSE 0 END) malas")->first();
        $liquidaciones = \App\Models\Liquidacion::where('ganadero_id', $ganadero->id)->latest('semana_inicio')->take(6)->get();
        $controles = \App\Models\ControlCalidad::where('ganadero_id', $ganadero->id)->latest('fecha')->take(6)->get();
        // Litros por día de los últimos 30 días para el gráfico
        $serie = \App\Models\Acopio::where('ganadero_id', $ganadero->id)->whereDate('fecha', '>=', today()->subDays(29))
            ->selectRaw('DATE(fecha) dia, SUM(litros) litros')->groupByRaw('DATE(fecha)')->pluck('litros', 'dia');

        return view('admin.ganaderos.show', compact('ganadero', 'riesgo', 'acopios', 'resumen', 'liquidaciones', 'controles', 'serie'));
    }

    public function index(Request $request): View
    {
        $query  = $request->input('q');
        $ruta   = $request->input('ruta');
        $estado = $request->input('estado', 'activo');  // activo | inactivo | todos

        $ganaderos = Ganadero::query()
            ->with('user:id,email')
            ->when($query, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('nombre', 'like', "%{$query}%")
                   ->orWhere('dni',   'like', "%{$query}%")
                   ->orWhere('direccion', 'like', "%{$query}%")
            ))
            ->when($ruta, fn ($q) => $q->where('ruta_asignada', $ruta))
            ->when($estado === 'activo',   fn ($q) => $q->where('activo', true))
            ->when($estado === 'inactivo', fn ($q) => $q->where('activo', false))
            ->orderBy('ruta_asignada')
            ->orderBy('nombre')
            ->paginate(25)
            ->withQueryString();

        $rutas  = Ganadero::select('ruta_asignada')->distinct()->pluck('ruta_asignada')->sort()->values();
        $zonas  = Zona::where('activa', true)->orderBy('orden')->get();
        $totales = [
            'activos'   => Ganadero::where('activo', true)->count(),
            'inactivos' => Ganadero::where('activo', false)->count(),
            'total'     => Ganadero::count(),
        ];

        return view('admin.ganaderos', compact('ganaderos', 'rutas', 'zonas', 'totales', 'query', 'ruta', 'estado'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /admin/ganaderos
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dni'           => ['required', 'digits:8', 'unique:ganaderos,dni'],
            'nombre'        => ['required', 'string', 'max:150'],
            'direccion'     => ['nullable', 'string', 'max:200'],
            'ruta_asignada' => ['required', 'string', 'max:100'],
            'zona_id'       => ['nullable', 'exists:zonas,id'],
            // Ubicación del predio: la usa la app para el mapa y el orden de la ruta
            'geo_lat'       => ['nullable', 'numeric', 'between:-90,90', 'required_with:geo_lon'],
            'geo_lon'       => ['nullable', 'numeric', 'between:-180,180', 'required_with:geo_lat'],
        ]);

        Ganadero::create(array_merge($validated, ['activo' => true]));

        return back()->with('success', "✅ Proveedor \"{$validated['nombre']}\" registrado correctamente.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /admin/ganaderos/{ganadero}/actualizar
    // ──────────────────────────────────────────────────────────────────────────

    public function actualizar(Request $request, Ganadero $ganadero): RedirectResponse
    {
        $validated = $request->validate([
            'dni'           => ['required', 'digits:8', "unique:ganaderos,dni,{$ganadero->id}"],
            'nombre'        => ['required', 'string', 'max:150'],
            'direccion'     => ['nullable', 'string', 'max:200'],
            'ruta_asignada' => ['required', 'string', 'max:100'],
            'zona_id'       => ['nullable', 'exists:zonas,id'],
            // Ubicación del predio: la usa la app para el mapa y el orden de la ruta
            'geo_lat'       => ['nullable', 'numeric', 'between:-90,90', 'required_with:geo_lon'],
            'geo_lon'       => ['nullable', 'numeric', 'between:-180,180', 'required_with:geo_lat'],
        ]);

        $ganadero->update($validated);

        return back()->with('success', "✅ Proveedor \"{$ganadero->nombre}\" actualizado.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /admin/ganaderos/{ganadero}/toggle
    // Activa o desactiva al ganadero
    // ──────────────────────────────────────────────────────────────────────────

    public function toggle(Ganadero $ganadero): RedirectResponse
    {
        $ganadero->update(['activo' => ! $ganadero->activo]);

        $estado = $ganadero->activo ? 'activado' : 'desactivado';
        return back()->with('success', "✅ Proveedor \"{$ganadero->nombre}\" {$estado}.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /admin/ganaderos/{ganadero}/crear-acceso
    // Crea o actualiza usuario web para que el proveedor acceda al portal.
    // ──────────────────────────────────────────────────────────────────────────

    public function crearAcceso(Request $request, Ganadero $ganadero): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($ganadero->user_id) {
            // Ya tiene usuario — solo actualizar contraseña
            $ganadero->user->update([
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            ]);
            return back()->with('success', "✅ Contraseña del portal actualizada para {$ganadero->nombre}.");
        }

        // Crear nuevo usuario con rol proveedor
        $email = strtolower(str_replace(' ', '.', $ganadero->dni)) . '@proveedor.prolech';

        $user = \App\Models\User::create([
            'name'     => $ganadero->nombre,
            'email'    => $email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'rol'      => 'proveedor',
            'activo'   => true,
        ]);

        $ganadero->update(['user_id' => $user->id]);

        return back()->with('success',
            "✅ Acceso al portal creado para {$ganadero->nombre}. " .
            "Email: {$email} · Contraseña: {$request->password}"
        );
    }
}
