<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class AccesosController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $usuarios = User::with('role')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('role', function ($roleQuery) use ($search) {
                            $roleQuery->where('nombre', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'estado' => $user->estado,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'nombre' => $user->role->nombre,
                    ] : null,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            });

        $roles = Roles::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'descripcion']);

        return Inertia::render('Accesos', [
            'usuarios' => $usuarios->values(),
            'roles' => $roles,
            'filters' => [
                'search' => $search, // ← Agregar esto
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role_id.required' => 'Debes seleccionar un rol.',
            'role_id.exists' => 'El rol seleccionado no existe.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        return redirect()->route('accesos')->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role_id.required' => 'Debes seleccionar un rol.',
            'role_id.exists' => 'El rol seleccionado no existe.',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ]);

        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleStatus(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes cambiar el estado de tu propia cuenta.');
        }

        $nuevoEstado = $user->estado == 1 ? 0 : 1;
        $user->update(['estado' => $nuevoEstado]);

        $usuarios = User::with('role')->get();
        $roles = Roles::all();

        return back()->with([
            'usuarios' => $usuarios,
            'roles' => $roles,
            'success' => $nuevoEstado == 1 ? 'Usuario activado.' : 'Usuario desactivado.',
        ]);
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $nombreUsuario = $user->name;
        $user->delete();

        $usuarios = User::with('role')->get();
        $roles = Roles::all();

        return back()->with([
            'usuarios' => $usuarios,
            'roles' => $roles,
            'success' => "Usuario {$nombreUsuario} eliminado correctamente.",
        ]);
    }
}
