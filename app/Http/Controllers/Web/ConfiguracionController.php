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
            'temporada_activa'          => ['required', 'in:normal,lluvias'],
            'agua_limite_retiro'        => ['required', 'numeric', 'min:1', 'max:50'],
            'descuento_litro_leve'      => ['required', 'numeric', 'min:0.01', 'max:5'],
            'rendimiento_quesos_100L'   => ['required', 'numeric', 'min:1', 'max:50'],
            'stock_minimo_venta_directa'=> ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        foreach ($validated as $clave => $valor) {
            Configuracion::set($clave, $valor);
        }

        return back()->with('success', '✅ Configuración actualizada correctamente.');
    }
}
