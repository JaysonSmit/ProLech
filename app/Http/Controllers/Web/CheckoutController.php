<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProductoTienda;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkoutService)
    {
    }

    // ──────────────────────────────────────────────────────────────────────────
    // VISTA DE CHECKOUT
    // ──────────────────────────────────────────────────────────────────────────

    /** GET /tienda/checkout */
    public function index(): View
    {
        return view('tienda.checkout', [
            'yapeNumero'  => \App\Models\Configuracion::get('yape_numero', '987 654 321'),
            'yapeTitular' => \App\Models\Configuracion::get('yape_titular', 'ProLech Huata'),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PROCESAR PEDIDO
    // ──────────────────────────────────────────────────────────────────────────

    /** POST /tienda/checkout */
    public function store(Request $request): RedirectResponse
    {
        // ── 1. Validar datos del cliente y método de pago ─────────────────────
        $validated = $request->validate([
            'cliente_nombre'    => ['required', 'string', 'max:150'],
            'cliente_telefono'  => ['required', 'string', 'max:20'],
            'cliente_direccion' => ['required', 'string', 'max:300'],
            'cliente_email'     => ['nullable', 'email', 'max:150'],
            // DNI de 8 dígitos o carné de extranjería; SUNAT lo exige en boletas desde S/ 700
            'cliente_documento' => ['nullable', 'regex:/^(\d{8}|[A-Za-z0-9]{9,12})$/'],
            // Nº de operación Yape o "VISA •••• 4242": nunca el número completo de tarjeta
            'pago_referencia'   => ['nullable', 'string', 'max:60'],
            'metodo_pago'       => ['required', 'in:Yape,Efectivo,Tarjeta'],
            'notas'             => ['nullable', 'string', 'max:500'],
            // Comprobante Yape solo requerido si el método es Yape
            'comprobante_yape'  => [
                'nullable',
                'required_if:metodo_pago,Yape',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
            // El carrito viene como JSON desde el frontend
            'carrito'           => ['required', 'json'],
        ], [
            'comprobante_yape.required_if' => 'Debes subir la captura de pantalla del pago Yape.',
            'carrito.required'             => 'Tu carrito está vacío.',
            'cliente_documento.regex'      => 'Ingresa un DNI de 8 dígitos o un carné de extranjería válido.',
        ]);

        // Por seguridad solo se guardan los últimos 4 dígitos de la tarjeta
        if (($validated['metodo_pago'] ?? null) === 'Tarjeta' && ! empty($validated['pago_referencia'])
            && ! preg_match('/^[A-Z ]{0,12}•{4} \d{4}$/u', $validated['pago_referencia'])) {
            $validated['pago_referencia'] = null;
        }

        // ── 2. Decodificar y validar items del carrito ────────────────────────
        $items = json_decode($validated['carrito'], true);

        // SUNAT: boletas de S/ 700 o más deben identificar al comprador
        $totalEstimado = collect(is_array($items) ? $items : [])->sum(function ($i) {
            $producto = ProductoTienda::find($i['id'] ?? 0);
            return $producto ? (float) $producto->precio_venta * (int) ($i['cantidad'] ?? 0) : 0;
        });
        if ($totalEstimado >= 700 && empty($validated['cliente_documento'])) {
            return back()->withInput()->withErrors([
                'cliente_documento' => 'Para compras desde S/ 700 debes ingresar tu DNI.',
            ]);
        }

        if (empty($items) || ! is_array($items)) {
            return back()
                ->withInput()
                ->withErrors(['carrito' => 'El carrito está vacío o tiene un formato inválido.']);
        }

        // Validación básica de estructura de cada ítem
        foreach ($items as $item) {
            if (empty($item['id']) || empty($item['cantidad']) || (int) $item['cantidad'] < 1) {
                return back()
                    ->withInput()
                    ->withErrors(['carrito' => 'El carrito contiene ítems inválidos.']);
            }
        }

        // ── 3. Subir comprobante Yape si viene ───────────────────────────────
        $comprobanteYapePath = null;
        if ($request->hasFile('comprobante_yape')) {
            $comprobanteYapePath = $request->file('comprobante_yape')
                ->store('tienda/comprobantes', 'public');
        }

        // ── 4. Delegar al servicio (DB::transaction dentro del service) ───────
        try {
            $pedido = $this->checkoutService->procesarPedido(
                datosCliente:        $validated,
                items:               $items,
                comprobanteYapePath: $comprobanteYapePath,
            );
        } catch (\RuntimeException $e) {
            // Error de negocio (stock insuficiente, producto inactivo…)
            return back()
                ->withInput()
                ->withErrors(['checkout' => $e->getMessage()]);
        } catch (\Throwable $e) {
            // Error inesperado de BD u otro
            return back()
                ->withInput()
                ->withErrors(['checkout' => 'Ocurrió un error al procesar tu pedido. Intenta de nuevo.']);
        }

        // ── 5. Redirigir a la confirmación (el JS vaciará el localStorage) ────
        return redirect()
            ->route('tienda.confirmacion', ['codigo' => $pedido->codigo_pedido])
            ->with('pedido_id', $pedido->id);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CONFIRMACIÓN
    // ──────────────────────────────────────────────────────────────────────────

    /** GET /tienda/confirmacion/{codigo} */
    public function confirmacion(string $codigo): View
    {
        // La clave de sesión 'pedido_id' garantiza que solo quien acaba de
        // hacer el pedido pueda ver esta página con datos completos.
        $pedidoId = session('pedido_id');

        if (! $pedidoId) {
            // Acceso directo sin sesión: mostrar solo el código
            return view('tienda.confirmacion', ['pedido' => null, 'codigo' => $codigo]);
        }

        $pedido = \App\Models\PedidoWeb::with('detalles.producto')
            ->where('id', $pedidoId)
            ->where('codigo_pedido', $codigo)
            ->firstOrFail();

        $urlBoleta = URL::signedRoute('tienda.boleta', ['codigo' => $pedido->codigo_pedido]);

        return view('tienda.confirmacion', compact('pedido', 'codigo', 'urlBoleta'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // BOLETA
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * GET /tienda/boleta/{codigo}?signature=…
     * Enlace firmado: solo quien recibió el link (cliente o admin) puede abrirla.
     */
    public function boleta(string $codigo): View
    {
        $pedido = \App\Models\PedidoWeb::with('detalles.producto')
            ->where('codigo_pedido', $codigo)
            ->firstOrFail();

        $empresa = [
            'nombre'    => \App\Models\Configuracion::get('empresa_razon_social', 'ProLech — Planta de Lácteos Huata'),
            'ruc'       => \App\Models\Configuracion::get('empresa_ruc'),
            'direccion' => \App\Models\Configuracion::get('empresa_direccion', 'Huata, Puno — Perú'),
            'telefono'  => \App\Models\Configuracion::get('empresa_telefono'),
        ];

        return view('tienda.boleta', compact('pedido', 'empresa'));
    }
}
