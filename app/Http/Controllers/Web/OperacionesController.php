<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Acopio;
use App\Models\AcopioCampo;
use App\Models\AsignacionZona;
use App\Models\Ganadero;
use App\Models\Liquidacion;
use App\Models\TrackingGps;
use App\Models\TrackingGpsPunto;
use App\Models\User;
use App\Models\Zona;
use App\Services\RutaChoferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use App\Support\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Herramientas de operación del panel:
 *  - Panel en vivo (litros por zona, choferes en ruta, alertas de calidad).
 *  - Repetición del recorrido de un chofer y km recorridos.
 *  - Asignar ganaderos a zonas y ubicarlos desde el mapa.
 *  - Exportar acopios, liquidaciones y rutas a Excel (.xlsx / .csv) y PDF.
 */
class OperacionesController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    // 11. PANEL EN VIVO
    // ══════════════════════════════════════════════════════════════════════

    public function enVivo(): View
    {
        return view('admin.operaciones.en-vivo', ['datos' => $this->datosEnVivo()]);
    }

    public function enVivoDatos(): JsonResponse
    {
        return response()->json($this->datosEnVivo());
    }

    /** Une los acopios de la app antigua y los de la app offline-first del día. */
    private function acopiosDelDia(Carbon $dia): Collection
    {
        $clasicos = Acopio::with('ganadero:id,nombre,zona_id')
            ->whereNull('acopio_campo_id')   // los de campo se suman abajo
            ->whereDate('fecha', $dia->toDateString())
            ->get()
            ->map(fn ($a) => [
                'ganadero' => $a->ganadero?->nombre ?? 'Ganadero',
                'zona_id'  => $a->ganadero?->zona_id,
                'litros'   => (float) $a->litros,
                'agua'     => (float) ($a->porcentaje_agua ?? 0),
                'calidad'  => strtoupper((string) $a->estado),
                'hora'     => $a->created_at,
                'chofer_id' => $a->chofer_id,
            ]);

        $campo = AcopioCampo::whereDate('created_at', $dia->toDateString())->get();
        $zonasGanadero = Ganadero::whereIn('id', $campo->pluck('ganadero_id'))->pluck('zona_id', 'id');
        $campo = $campo->map(fn ($a) => [
            'ganadero' => $a->ganadero_nombre,
            'zona_id'  => $zonasGanadero[$a->ganadero_id] ?? null,
            'litros'   => (float) $a->litros,
            'agua'     => (float) $a->porcentaje_agua,
            'calidad'  => $a->estado_calidad,
            'hora'     => $a->created_at,
            'chofer_id' => $a->chofer_id,
        ]);

        return $clasicos->concat($campo);
    }

    private function datosEnVivo(): array
    {
        $hoy     = now();
        $acopios = $this->acopiosDelDia($hoy);
        $zonas   = Zona::where('activa', true)->orderBy('orden')->get();
        $choferes = User::whereIn('id', $acopios->pluck('chofer_id')->filter()->unique())->pluck('name', 'id');

        $porZona = $zonas->map(fn ($z) => [
            'zona'     => $z->nombre,
            'litros'   => round($acopios->where('zona_id', $z->id)->sum('litros'), 1),
            'entregas' => $acopios->where('zona_id', $z->id)->count(),
            'chofer'   => $z->choferActual()?->name,
        ])->values();
        $sinZona = $acopios->whereNull('zona_id');
        if ($sinZona->isNotEmpty()) {
            $porZona->push(['zona' => 'Sin zona', 'litros' => round($sinZona->sum('litros'), 1),
                'entregas' => $sinZona->count(), 'chofer' => null]);
        }

        $enRuta = TrackingGps::with('chofer:id,name')->activos(30)->get()->map(fn ($t) => [
            'chofer'  => $t->chofer?->name,
            'minutos' => (int) $t->ultima_actualizacion->diffInMinutes(now()),
            'km_hoy'  => round(self::kmDe(TrackingGpsPunto::recorridoDeHoy($t->chofer_id)), 1),
        ])->values();

        $alertas = $acopios->whereIn('calidad', ['OBSERVADO', 'RECHAZADO', 'OBSERVADA', 'RECHAZADA'])
            ->sortByDesc('hora')->take(10)
            ->map(fn ($a) => [
                'ganadero' => $a['ganadero'],
                'calidad'  => str_starts_with($a['calidad'], 'RECHAZ') ? 'RECHAZADO' : 'OBSERVADO',
                'agua'     => $a['agua'],
                'litros'   => $a['litros'],
                'hora'     => optional($a['hora'])->format('H:i'),
                'chofer'   => $choferes[$a['chofer_id']] ?? null,
            ])->values();

        $aprobados = $acopios->filter(fn ($a) => str_starts_with($a['calidad'], 'APROB'))->count();

        return [
            'actualizado'  => $hoy->format('H:i:s'),
            'litros_hoy'   => round($acopios->sum('litros'), 1),
            'entregas_hoy' => $acopios->count(),
            'pct_aprobado' => $acopios->count() ? (int) round($aprobados * 100 / $acopios->count()) : null,
            'en_ruta'      => $enRuta,
            'por_zona'     => $porZona,
            'alertas'      => $alertas,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // 12. REPETICIÓN DEL RECORRIDO + KM POR CHOFER
    // ══════════════════════════════════════════════════════════════════════

    public function recorrido(Request $request): View
    {
        $fecha = Carbon::parse($request->query('fecha', now()->toDateString()));
        $choferes = User::where('rol', 'chofer')->orderBy('name')->get(['id', 'name']);

        // Km del día por chofer (todos los que enviaron puntos)
        $kmPorChofer = TrackingGpsPunto::whereDate('registrado_en', $fecha->toDateString())
            ->orderBy('registrado_en')
            ->get(['chofer_id', 'latitud', 'longitud', 'registrado_en'])
            ->groupBy('chofer_id')
            ->map(fn ($puntos, $id) => [
                'chofer_id' => $id,
                'chofer'    => $choferes->firstWhere('id', $id)?->name ?? "Chofer #$id",
                'km'        => round(self::kmDe($puntos->map(fn ($p) => [$p->latitud, $p->longitud])->all()), 1),
                'puntos'    => $puntos->count(),
                'inicio'    => $puntos->first()->registrado_en->format('H:i'),
                'fin'       => $puntos->last()->registrado_en->format('H:i'),
            ])->sortByDesc('km')->values();

        return view('admin.operaciones.recorrido', [
            'fecha'       => $fecha->toDateString(),
            'choferes'    => $choferes,
            'kmPorChofer' => $kmPorChofer,
            'choferId'    => (int) $request->query('chofer', $kmPorChofer->first()['chofer_id'] ?? 0),
            'planta'      => app(RutaChoferService::class)->planta(),
        ]);
    }

    public function recorridoDatos(Request $request, User $chofer): JsonResponse
    {
        $fecha = Carbon::parse($request->query('fecha', now()->toDateString()));
        $puntos = TrackingGpsPunto::where('chofer_id', $chofer->id)
            ->whereDate('registrado_en', $fecha->toDateString())
            ->orderBy('registrado_en')
            ->get(['latitud', 'longitud', 'velocidad_kmh', 'registrado_en']);

        // Paradas de acopio del día (para marcarlas en la línea de tiempo)
        $acopios = AcopioCampo::where('chofer_id', $chofer->id)
            ->whereDate('created_at', $fecha->toDateString())
            ->get(['ganadero_id', 'ganadero_nombre', 'litros', 'estado_calidad', 'created_at']);
        $coords = Ganadero::whereIn('id', $acopios->pluck('ganadero_id'))->get(['id', 'geo_lat', 'geo_lon'])->keyBy('id');

        return response()->json([
            'chofer' => $chofer->name,
            'km'     => round(self::kmDe($puntos->map(fn ($p) => [$p->latitud, $p->longitud])->all()), 2),
            'puntos' => $puntos->map(fn ($p) => [
                'lat' => $p->latitud, 'lon' => $p->longitud,
                't'   => $p->registrado_en->getTimestampMs(),
                'v'   => $p->velocidad_kmh,
            ]),
            'acopios' => $acopios->map(fn ($a) => [
                'ganadero' => $a->ganadero_nombre,
                'litros'   => (float) $a->litros,
                'calidad'  => $a->estado_calidad,
                't'        => $a->created_at->getTimestampMs(),
                'lat'      => $coords[$a->ganadero_id]->geo_lat ?? null,
                'lon'      => $coords[$a->ganadero_id]->geo_lon ?? null,
            ]),
        ]);
    }

    /** Distancia total de una lista de [lat, lon]. */
    public static function kmDe(array $puntos): float
    {
        $km = 0.0;
        for ($i = 1, $n = count($puntos); $i < $n; $i++) {
            $km += RutaChoferService::distanciaKm(
                (float) $puntos[$i - 1][0], (float) $puntos[$i - 1][1],
                (float) $puntos[$i][0], (float) $puntos[$i][1]
            );
        }
        return $km;
    }

    // ══════════════════════════════════════════════════════════════════════
    // 13. ZONAS EN EL MAPA
    // ══════════════════════════════════════════════════════════════════════

    public function zonasMapa(): View
    {
        $zonas = Zona::where('activa', true)->orderBy('orden')->get(['id', 'nombre', 'codigo', 'poligono', 'color']);
        $ganaderos = Ganadero::where('activo', true)->orderBy('nombre')
            ->get(['id', 'nombre', 'dni', 'direccion', 'zona_id', 'geo_lat', 'geo_lon', 'estado']);

        return view('admin.operaciones.zonas-mapa', [
            'zonas'     => $zonas,
            'ganaderos' => $ganaderos,
            'planta'    => app(RutaChoferService::class)->planta(),
        ]);
    }

    /** POST /admin/zonas/mapa/{ganadero}: cambia la zona y/o la ubicación desde el mapa. */
    public function zonasMapaGuardar(Request $request, Ganadero $ganadero): JsonResponse
    {
        $validated = $request->validate([
            'zona_id' => ['nullable', 'exists:zonas,id'],
            'geo_lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:geo_lon'],
            'geo_lon' => ['nullable', 'numeric', 'between:-180,180', 'required_with:geo_lat'],
        ]);

        $cambios = [];
        if ($request->has('zona_id')) $cambios['zona_id'] = $validated['zona_id'];
        if ($request->filled('geo_lat')) {
            $cambios['geo_lat'] = round((float) $validated['geo_lat'], 7);
            $cambios['geo_lon'] = round((float) $validated['geo_lon'], 7);
            // Ubicado dentro del polígono de una zona y sin zona elegida a mano → se asigna esa zona
            if (! $request->has('zona_id')) {
                $zona = Zona::deUbicacion($cambios['geo_lat'], $cambios['geo_lon']);
                if ($zona) $cambios['zona_id'] = $zona->id;
            }
        }
        $ganadero->update($cambios);

        return response()->json([
            'message'  => "Ganadero {$ganadero->nombre} actualizado.",
            'ganadero' => $ganadero->only(['id', 'nombre', 'zona_id', 'geo_lat', 'geo_lon']),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // 13b. RUTAS: crear rutas, elegir sus proveedores y su chofer, trazar el camino
    // ══════════════════════════════════════════════════════════════════════

    public function trazarRutas(): View
    {
        $zonas = Zona::where('activa', true)->orderBy('orden')
            ->get(['id', 'nombre', 'codigo', 'poligono', 'color', 'trazado', 'trazado_km']);
        $ganaderos = Ganadero::where('activo', true)->orderBy('nombre')
            ->get(['id', 'nombre', 'dni', 'zona_id', 'geo_lat', 'geo_lon', 'estado']);

        return view('admin.operaciones.trazar-rutas', [
            'zonas'     => $zonas,
            'ganaderos' => $ganaderos,
            'paradas'   => $zonas->mapWithKeys(fn (Zona $z) => [$z->id => $this->paradasDeZona($z)]),
            'choferes'  => User::where('rol', 'chofer')->where('activo', true)->orderBy('name')->get(['id', 'name']),
            'titulares' => $this->titularesDelMes(),
            'mes'       => now()->month,
            'anio'      => now()->year,
            'planta'    => app(RutaChoferService::class)->planta(),
        ]);
    }

    /** zona_id → chofer titular del mes actual. */
    private function titularesDelMes(): array
    {
        return AsignacionZona::with('chofer:id,name')
            ->where('mes', now()->month)->where('anio', now()->year)->whereNotNull('zona_id')->get()
            ->mapWithKeys(fn ($a) => [$a->zona_id => ['id' => $a->chofer_id, 'nombre' => $a->chofer?->name]])
            ->all();
    }

    /** POST /admin/rutas: crea una ruta nueva (zona) vacía. */
    public function rutaCrear(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:zonas,nombre'],
            'color'  => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], ['nombre.unique' => 'Ya existe una ruta con ese nombre.']);

        // Código único derivado del nombre: "Collana 5" → COLLANA-5
        $base = substr(strtoupper(\Illuminate\Support\Str::slug($validated['nombre'])), 0, 16) ?: 'RUTA';
        $codigo = $base;
        for ($i = 2; Zona::where('codigo', $codigo)->exists(); $i++) {
            $codigo = substr($base, 0, 16) . '-' . $i;
        }

        $zona = Zona::create([
            'codigo' => $codigo,
            'nombre' => $validated['nombre'],
            'color'  => $validated['color'] ?? null,
            'orden'  => (int) Zona::max('orden') + 1,
            'activa' => true,
        ]);

        return response()->json([
            'message' => "Ruta {$zona->nombre} creada. Ahora agrega sus proveedores y su chofer.",
            'zona'    => $zona->only(['id', 'nombre', 'codigo', 'poligono', 'color', 'trazado', 'trazado_km']),
        ], 201);
    }

    /** PUT /admin/rutas/{zona}: renombrar, cambiar color o desactivar la ruta. */
    public function rutaActualizar(Request $request, Zona $zona): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['sometimes', 'required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('zonas', 'nombre')->ignore($zona->id)],
            'color'  => ['sometimes', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'activa' => ['sometimes', 'boolean'],
        ], ['nombre.unique' => 'Ya existe una ruta con ese nombre.']);

        if (($validated['activa'] ?? true) === false) {
            $proveedores = Ganadero::where('zona_id', $zona->id)->where('activo', true)->count();
            if ($proveedores > 0) {
                return response()->json(['message' => "La ruta aún tiene {$proveedores} proveedor(es). Muévelos a otra ruta antes de eliminarla."], 422);
            }
            // Su chofer de este mes en adelante queda como volante
            AsignacionZona::where('zona_id', $zona->id)
                ->where(fn ($q) => $q->where('anio', '>', now()->year)
                    ->orWhere(fn ($q2) => $q2->where('anio', now()->year)->where('mes', '>=', now()->month)))
                ->update(['zona_id' => null, 'rol_mes' => 'volante']);
        }

        $zona->update($validated);

        return response()->json([
            'message' => $zona->activa ? "Ruta {$zona->nombre} actualizada." : "Ruta {$zona->nombre} eliminada.",
            'zona'    => $zona->only(['id', 'nombre', 'codigo', 'color', 'activa']),
        ]);
    }

    /** POST /admin/rutas/{zona}/proveedores: agrega o quita proveedores de la ruta. */
    public function rutaProveedores(Request $request, Zona $zona): JsonResponse
    {
        $validated = $request->validate([
            'ganadero_ids'   => ['required', 'array', 'min:1'],
            'ganadero_ids.*' => ['integer', 'exists:ganaderos,id'],
            'accion'         => ['required', \Illuminate\Validation\Rule::in(['agregar', 'quitar'])],
        ]);

        $query = Ganadero::whereIn('id', $validated['ganadero_ids']);
        if ($validated['accion'] === 'quitar') {
            $query->where('zona_id', $zona->id)->update(['zona_id' => null]);
        } else {
            $query->update(['zona_id' => $zona->id]);
        }

        $n = count($validated['ganadero_ids']);
        return response()->json([
            'message'   => $validated['accion'] === 'agregar'
                ? "{$n} proveedor(es) agregado(s) a {$zona->nombre}."
                : "{$n} proveedor(es) quitado(s) de {$zona->nombre}.",
            'paradas'   => $this->paradasDeZona($zona),
            'ganaderos' => Ganadero::whereIn('id', $validated['ganadero_ids'])->get(['id', 'zona_id'])->all(),
        ]);
    }

    /** POST /admin/rutas/{zona}/chofer: chofer titular de la ruta este mes (null = sin chofer). */
    public function rutaChofer(Request $request, Zona $zona): JsonResponse
    {
        $validated = $request->validate([
            'chofer_id' => ['nullable', \Illuminate\Validation\Rule::exists('users', 'id')->where('rol', 'chofer')],
        ]);
        $mes = now()->month;
        $anio = now()->year;

        if ($validated['chofer_id']) {
            $intercambio = AsignacionZona::asignar((int) $validated['chofer_id'], $zona->id, $mes, $anio);
            $nombre = User::find($validated['chofer_id'])->name;
            $mensaje = "{$nombre} recorrerá {$zona->nombre} este mes." . ($intercambio ? " Intercambio: {$intercambio}." : '');
        } else {
            AsignacionZona::where('zona_id', $zona->id)->where('mes', $mes)->where('anio', $anio)
                ->update(['zona_id' => null, 'rol_mes' => 'volante']);
            $mensaje = "{$zona->nombre} quedó sin chofer este mes; el anterior pasa a volante.";
        }

        return response()->json(['message' => $mensaje, 'titulares' => $this->titularesDelMes()]);
    }

    /** POST /admin/rutas/trazar/{zona}: guarda (o borra, con trazado vacío) el camino de la zona. */
    public function trazarRutasGuardar(Request $request, Zona $zona): JsonResponse
    {
        $validated = $request->validate([
            'trazado'     => ['present', 'array', 'max:5000'],
            'trazado.*'   => ['array', 'size:2'],
            'trazado.*.0' => ['numeric', 'between:-90,90'],
            'trazado.*.1' => ['numeric', 'between:-180,180'],
        ]);

        $puntos = array_map(fn ($p) => [round((float) $p[0], 6), round((float) $p[1], 6)], $validated['trazado']);
        if (count($puntos) === 1) {
            return response()->json(['message' => 'El trazado necesita al menos dos puntos.'], 422);
        }

        $zona->update([
            'trazado'    => $puntos ?: null,
            'trazado_km' => $puntos ? Zona::largoKm($puntos) : null,
        ]);

        return response()->json([
            'message'    => $puntos
                ? "Ruta de {$zona->nombre} guardada ({$zona->trazado_km} km). La app del chofer la usará al actualizar su ruta."
                : "Se borró el trazado de {$zona->nombre}: la app volverá a sugerir el camino automáticamente.",
            'trazado_km' => $zona->trazado_km,
            'paradas'    => $this->paradasDeZona($zona->fresh()),
        ]);
    }

    /** Ganaderos de la zona en el orden en que el chofer los visitará, con su distancia al trazado. */
    private function paradasDeZona(Zona $zona): array
    {
        $ganaderos = Ganadero::where('activo', true)->where('zona_id', $zona->id)
            ->get(['id', 'nombre', 'geo_lat', 'geo_lon', 'estado']);
        $ordenados = $zona->tieneTrazado() ? RutaChoferService::ordenarPorTrazado($ganaderos, $zona) : $ganaderos->sortBy('nombre');

        return $ordenados->values()->map(function (Ganadero $g, int $i) use ($zona) {
            $pos = $g->geo_lat !== null ? $zona->posicionEnTrazado((float) $g->geo_lat, (float) $g->geo_lon) : null;
            return [
                'id'      => $g->id,
                'orden'   => $i + 1,
                'nombre'  => $g->nombre,
                'sin_gps' => $g->geo_lat === null,
                'km'      => $pos[0] ?? null,
                'lejos_m' => $pos[1] ?? null,
            ];
        })->all();
    }

    // ══════════════════════════════════════════════════════════════════════
    // 14. EXPORTAR
    // ══════════════════════════════════════════════════════════════════════

    public function exportar(Request $request): View
    {
        return view('admin.operaciones.exportar', [
            'desde' => $request->query('desde', now()->startOfMonth()->toDateString()),
            'hasta' => $request->query('hasta', now()->toDateString()),
        ]);
    }

    private function rango(Request $request): array
    {
        $desde = Carbon::parse($request->query('desde', now()->startOfMonth()->toDateString()))->startOfDay();
        $hasta = Carbon::parse($request->query('hasta', now()->toDateString()))->endOfDay();
        return [$desde, $hasta];
    }

    private function filasAcopios(Request $request): Collection
    {
        [$desde, $hasta] = $this->rango($request);
        $zonas = Zona::pluck('nombre', 'id');
        $choferes = User::pluck('name', 'id');

        $clasicos = Acopio::with('ganadero:id,nombre,dni,zona_id')
            ->whereNull('acopio_campo_id')   // los de campo se suman abajo
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->get()
            ->map(fn ($a) => [
                'Fecha'      => Carbon::parse($a->fecha)->format('d/m/Y'),
                'Hora'       => optional($a->created_at)->format('H:i'),
                'Ganadero'   => $a->ganadero?->nombre,
                'DNI'        => $a->ganadero?->dni,
                'Zona'       => $zonas[$a->ganadero?->zona_id] ?? '',
                'Chofer'     => $choferes[$a->chofer_id] ?? '',
                'Litros'     => (float) $a->litros,
                'Agua %'     => (float) ($a->porcentaje_agua ?? 0),
                'Densidad'   => (float) ($a->densidad ?? 0),
                'Grasa %'    => $a->grasa !== null ? (float) $a->grasa : '',
                'Calidad'    => strtoupper((string) $a->estado),
                'Origen'     => 'App clásica',
            ]);

        $campo = AcopioCampo::whereBetween('created_at', [$desde, $hasta])->get();
        $ganaderos = Ganadero::whereIn('id', $campo->pluck('ganadero_id'))->get(['id', 'dni', 'zona_id'])->keyBy('id');
        $campo = $campo->map(fn ($a) => [
            'Fecha'    => $a->created_at->format('d/m/Y'),
            'Hora'     => $a->created_at->format('H:i'),
            'Ganadero' => $a->ganadero_nombre,
            'DNI'      => $ganaderos[$a->ganadero_id]->dni ?? '',
            'Zona'     => $zonas[$ganaderos[$a->ganadero_id]->zona_id ?? 0] ?? '',
            'Chofer'   => $choferes[$a->chofer_id] ?? '',
            'Litros'   => (float) $a->litros,
            'Agua %'   => (float) $a->porcentaje_agua,
            'Densidad' => (float) $a->densidad,
            'Grasa %'  => $a->grasa !== null ? (float) $a->grasa : '',
            'Calidad'  => $a->estado_calidad,
            'Origen'   => 'App offline',
        ]);

        return $clasicos->concat($campo)->sortBy([['Fecha', 'asc'], ['Hora', 'asc']])->values();
    }

    public function exportarAcopiosCsv(Request $request): Response
    {
        [$desde, $hasta] = $this->rango($request);
        return $this->descargar($request, 'acopios', 'Acopios', $this->filasAcopios($request),
            "Acopios del {$desde->format('d/m/Y')} al {$hasta->format('d/m/Y')}", ['Litros']);
    }

    public function exportarAcopiosImprimir(Request $request): View
    {
        [$desde, $hasta] = $this->rango($request);
        $filas = $this->filasAcopios($request);
        return view('admin.operaciones.acopios-imprimir', [
            'filas' => $filas, 'desde' => $desde, 'hasta' => $hasta,
            'totalLitros' => $filas->sum('Litros'),
        ]);
    }

    public function exportarLiquidacionesCsv(Request $request): Response
    {
        [$desde, $hasta] = $this->rango($request);
        $filas = Liquidacion::with('ganadero:id,nombre,dni')
            ->where('semana_fin', '>=', $desde->toDateString())
            ->where('semana_inicio', '<=', $hasta->toDateString())
            ->orderBy('semana_inicio')
            ->get()
            ->map(fn ($l) => [
                'Ganadero'           => $l->ganadero?->nombre,
                'DNI'                => $l->ganadero?->dni,
                'Semana del'         => Carbon::parse($l->semana_inicio)->format('d/m/Y'),
                'al'                 => Carbon::parse($l->semana_fin)->format('d/m/Y'),
                'Fecha de pago'      => Carbon::parse($l->fecha_pago)->format('d/m/Y'),
                'Litros aprobados'   => (float) $l->litros_aprobados,
                'Precio S/ L'        => (float) $l->precio_litro,
                'Total bruto S/'     => (float) $l->total_bruto,
                'Descuentos S/'      => (float) $l->total_descuentos,
                'Total neto S/'      => (float) $l->total_neto,
                'Estado'             => $l->estado ?? '',
            ]);

        return $this->descargar($request, 'liquidaciones', 'Liquidaciones', $filas,
            "Planilla de liquidaciones del {$desde->format('d/m/Y')} al {$hasta->format('d/m/Y')}",
            ['Litros aprobados', 'Total bruto S/', 'Descuentos S/', 'Total neto S/']);
    }

    public function exportarRutasCsv(Request $request): Response
    {
        $mes  = (int) $request->query('mes', now()->month);
        $anio = (int) $request->query('anio', now()->year);
        $asignaciones = AsignacionZona::with(['chofer:id,name', 'zona:id,nombre'])
            ->where('mes', $mes)->where('anio', $anio)->get()->keyBy('zona_id');

        $filas = Ganadero::where('activo', true)->with('zona:id,nombre')->orderBy('zona_id')->orderBy('nombre')->get()
            ->map(fn ($g) => [
                'Zona'       => $g->zona?->nombre ?? 'Sin zona',
                'Chofer del mes' => $asignaciones[$g->zona_id]->chofer->name ?? '',
                'Ganadero'   => $g->nombre,
                'DNI'        => $g->dni,
                'Dirección'  => $g->direccion,
                'Latitud'    => $g->geo_lat,
                'Longitud'   => $g->geo_lon,
                'Estado'     => $g->estado,
            ]);

        return $this->descargar($request, "rutas_{$anio}_{$mes}", 'Hoja de ruta', $filas,
            'Hoja de ruta — ' . Carbon::create($anio, $mes)->locale('es')->translatedFormat('F Y'), [], true);
    }

    /**
     * Descarga en el formato pedido (?formato=csv|xlsx|pdf). El PDF agrega totales de las
     * columnas indicadas y, en la hoja de ruta, agrupa por zona con casilla para marcar.
     */
    private function descargar(Request $request, string $nombre, string $hoja, Collection $filas,
                               string $titulo, array $sumar = [], bool $porZona = false): Response
    {
        $archivo = "prolech_{$nombre}_" . now()->format('Ymd_His');

        return match ($request->query('formato', 'csv')) {
            'xlsx' => response(
                Xlsx::generar($hoja, $filas->isEmpty() ? ['Sin datos en el rango seleccionado'] : array_keys($filas->first()), $filas),
                200,
                [
                    'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => "attachment; filename=\"{$archivo}.xlsx\"",
                ]
            ),
            'pdf' => Pdf::loadView('admin.operaciones.tabla-pdf', [
                'titulo'  => $titulo,
                'filas'   => $filas,
                'totales' => collect($sumar)->mapWithKeys(fn ($c) => [$c => $filas->sum($c)])->all(),
                'grupos'  => $porZona ? $filas->groupBy('Zona') : null,
            ])->setPaper('a4', 'landscape')->download("{$archivo}.pdf"),
            default => $this->csv($nombre, $filas),
        };
    }

    /**
     * CSV que Excel en español abre bien: BOM UTF-8 (tildes) y ";" como separador.
     */
    private function csv(string $nombre, Collection $filas): StreamedResponse
    {
        $archivo = "prolech_{$nombre}_" . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($filas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            if ($filas->isNotEmpty()) {
                fputcsv($out, array_keys($filas->first()), ';');
                foreach ($filas as $fila) {
                    fputcsv($out, array_map(
                        fn ($v) => is_float($v) ? str_replace('.', ',', (string) $v) : $v,
                        $fila
                    ), ';');
                }
            } else {
                fputcsv($out, ['Sin datos en el rango seleccionado'], ';');
            }
            fclose($out);
        }, $archivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
