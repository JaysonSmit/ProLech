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
            ->when($rol !== 'todos', fn ($q) => $q->conRol($rol))
            ->orderBy('rol')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        // Cuenta a cada usuario en todos sus roles (principal y accesos adicionales)
        $contadores = collect(['admin', 'chofer', 'calidad', 'asistente', 'jefe_produccion', 'despacho'])
            ->mapWithKeys(fn ($r) => [$r => User::conRol($r)->count()])
            ->put('total', User::count())
            ->all();

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
            'rol'      => ['required', Rule::in(self::rolesInternos())],
            'roles_extra'   => ['nullable', 'array'],
            'roles_extra.*' => [Rule::in(self::rolesInternos())],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $usuario = User::create([
            'name'        => self::nombrePropio($validated['name']),
            'email'       => mb_strtolower(trim($validated['email'])),
            'rol'         => $validated['rol'],
            'roles_extra' => self::extras($validated['rol'], $validated['roles_extra'] ?? []),
            'password'    => Hash::make($validated['password']),
            'activo'      => true,
        ]);

        return back()->with('success', "✅ Usuario \"{$usuario->name}\" creado con acceso de: " . self::describir($usuario) . '.');
    }

    // POST /admin/usuarios/{usuario}/accesos — nombre, correo, rol principal y accesos adicionales
    public function accesos(Request $request, User $usuario): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['sometimes', 'required', 'string', 'max:150'],
            'email'         => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($usuario->id)],
            'rol'           => ['required', Rule::in(self::rolesInternos())],
            'roles_extra'   => ['nullable', 'array'],
            'roles_extra.*' => [Rule::in(self::rolesInternos())],
        ]);

        $extras = self::extras($validated['rol'], $validated['roles_extra'] ?? []);
        // Nadie se quita a sí mismo el acceso de administrador (se quedaría fuera del panel)
        if ($usuario->id === auth()->id() && ! in_array('admin', [$validated['rol'], ...($extras ?? [])], true)) {
            return back()->with('error', 'No puedes quitarte tu propio acceso de administrador.');
        }

        $usuario->update(array_filter([
            'name'  => isset($validated['name']) ? self::nombrePropio($validated['name']) : null,
            'email' => isset($validated['email']) ? mb_strtolower(trim($validated['email'])) : null,
        ]) + ['rol' => $validated['rol'], 'roles_extra' => $extras]);

        return back()->with('success', "✅ Usuario \"{$usuario->name}\" actualizado: " . self::describir($usuario) . '.');
    }

    /** «jhon fredy quilla  LARICO» → «Jhon Fredy Quilla Larico» (de, del, la, y… en minúscula). */
    public static function nombrePropio(string $nombre): string
    {
        $palabras = explode(' ', mb_convert_case(preg_replace('/\s+/u', ' ', trim($nombre)), MB_CASE_TITLE, 'UTF-8'));
        foreach ($palabras as $i => $p) {
            if ($i > 0 && in_array(mb_strtolower($p), ['de', 'del', 'la', 'las', 'los', 'y', 'e'], true)) {
                $palabras[$i] = mb_strtolower($p);
            }
        }
        return implode(' ', $palabras);
    }

    /** Roles que se pueden asignar desde el panel (el de proveedor tiene su propio portal). */
    private static function rolesInternos(): array
    {
        return array_keys(array_diff_key(RolWeb::etiquetas(), ['proveedor' => '']));
    }

    /** Accesos adicionales sin repetir el principal; null si no hay. */
    private static function extras(string $principal, array $extras): ?array
    {
        $limpios = array_values(array_unique(array_diff($extras, [$principal])));
        return $limpios ?: null;
    }

    private static function describir(User $u): string
    {
        $etq = RolWeb::etiquetas();
        return implode(' + ', array_map(fn ($r) => $etq[$r] ?? $r, $u->roles()));
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
