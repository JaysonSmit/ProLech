<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RolWeb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    // GET /admin/usuarios
    public function index(Request $request): View
    {
        $rol = $request->input('rol', 'todos');

        $usuarios = User::query()
            ->when($rol !== 'todos', fn ($q) => $q->where('rol', $rol))
            ->orderBy('rol')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $contadores = [
            'admin'           => User::where('rol', 'admin')->count(),
            'chofer'          => User::where('rol', 'chofer')->count(),
            'calidad'         => User::where('rol', 'calidad')->count(),
            'asistente'       => User::where('rol', 'asistente')->count(),
            'jefe_produccion' => User::where('rol', 'jefe_produccion')->count(),
            'despacho'        => User::where('rol', 'despacho')->count(),
            'total'           => User::count(),
        ];

        $rolesDisponibles = RolWeb::etiquetas();
        unset($rolesDisponibles['proveedor']);

        return view('admin.usuarios', compact('usuarios', 'contadores', 'rol', 'rolesDisponibles'));
    }

    // POST /admin/usuarios
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:150'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'rol'      => ['required', Rule::in(array_keys(array_diff_key(RolWeb::etiquetas(), ['proveedor' => ''])))],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'rol'      => $validated['rol'],
            'password' => Hash::make($validated['password']),
            'activo'   => true,
        ]);

        return back()->with('success', "✅ Usuario \"{$validated['name']}\" creado con rol {$validated['rol']}.");
    }

    // POST /admin/usuarios/{usuario}/toggle
    public function toggle(User $usuario): RedirectResponse
    {
        // No permitir desactivarse a sí mismo
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $usuario->update(['activo' => ! $usuario->activo]);
        $estado = $usuario->activo ? 'activado' : 'desactivado';
        return back()->with('success', "✅ Usuario \"{$usuario->name}\" {$estado}.");
    }

    // POST /admin/usuarios/{usuario}/cambiar-password
    public function cambiarPassword(Request $request, User $usuario): RedirectResponse
    {
        $request->validate([
            'nueva_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $usuario->update(['password' => Hash::make($request->nueva_password)]);
        return back()->with('success', "✅ Contraseña de \"{$usuario->name}\" actualizada.");
    }
}
