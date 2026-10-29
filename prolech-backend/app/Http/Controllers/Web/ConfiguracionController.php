<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ConfiguracionController
 *
 * Permite al administrador cambiar precios por temporada y parámetros
 * del sistema sin tocar el código (RF-PAG-02).
 */
class ConfiguracionController extends Controller
{
    public function index(): View
    {
        $config = Configuracion::all()->keyBy('clave');
        return view('admin.configuracion.index', compact('config'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'precio_litro_normal'       => ['required', 'numeric', 'min:0.01', 'max:99.99'],
            'precio_litro_lluvias'      => ['required', 'numeric', 'min:0.01', 'max:99.99'],
            'precio_litro_penalidad'    => ['required', 'numeric', 'min:0.01', 'max:99.99'],
            'precio_queso_mayorista'    => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'precio_queso_proveedor'    => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'precio_queso_publico'      => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'margen_ganancia_pct'       => ['nullable', 'numeric', 'min:0', 'max:300'],
            'controles_por_dia'         => ['nullable', 'integer', 'min:0', 'max:50'],
            'tarifa_chofer_dia'         => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'bono_chofer_litro'         => ['nullable', 'numeric', 'min:0', 'max:5'],
            'pension_chofer_pct'        => ['nullable', 'numeric', 'min:0', 'max:30'],
            'costo_combustible_km'      => ['nullable', 'numeric', 'min:0', 'max:20'],
            'km_ruta_default'           => ['nullable', 'numeric', 'min:0', 'max:500'],
            'gastos_generales_pct'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'temporada_activa'          => ['required', 'in:normal,lluvias'],
            'agua_limite_retiro'        => ['required', 'numeric', 'min:1', 'max:50'],
            'descuento_litro_leve'      => ['required', 'numeric', 'min:0.01', 'max:5'],
            'rendimiento_quesos_100L'   => ['required', 'numeric', 'min:1', 'max:50'],
            'stock_minimo_venta_directa'=> ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        $validated['margen_ganancia_pct'] = $validated['margen_ganancia_pct'] ?? 30;
        $validated['controles_por_dia'] = $validated['controles_por_dia'] ?? 3;
        $validated = array_filter($validated, fn ($v) => $v !== null);
        foreach ($validated as $clave => $valor) {
            Configuracion::set($clave, $valor);
        }

        app(\App\Services\CostoLecheService::class)->aplicarAInsumo();

        return back()->with('success', '✅ Configuración actualizada correctamente.');
    }
}
