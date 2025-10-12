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
    /**
     * Mostrar la vista principal de Inertia
     * Esta ruta solo renderiza la página, NO devuelve datos
     */
    public function index()
    {
        return Inertia::render('Accesos');
    }

    /**
     * Obtener todos los usuarios (para cargar en la tabla)
     * Esta es la ruta que llama axios desde Vue
     */
    public function listar()
    {
        $usuarios = User::with('role')
            ->orderBy('created_at', 'desc')
            ->get()
            // ->where('estado', 1)
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'estado' => $user->estado,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'nombre' => $user->role->nombre,
                        'activo' => $user->role->activo ?? true,
                    ] : null,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            });

        $roles = Roles::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'descripcion']);

        return response()->json([
            'success' => true,
            'usuarios' => $usuarios->values(),
            'roles' => $roles,
        ]);
    }

    /**
     * Crear un nuevo usuario
     */
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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente.',
            'user' => $user->load('role'),
        ], 201);
    }

    /**
     * Actualizar un usuario existente
     */
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

        // Solo actualizar contraseña si se proporciona
        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente.',
            'user' => $user->fresh()->load('role'),
        ]);
    }

    /**
     * Eliminar un usuario
     */
    public function destroy(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propia cuenta.',
            ], 403);
        }

        // ✅ CAMBIO: En lugar de delete(), cambiar estado
        $user->update(['estado' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario desactivado exitosamente.',
        ]);
    }

    /**
     * Cambiar estado del usuario (activar/desactivar)
     */
    public function toggleStatus(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes cambiar el estado de tu propia cuenta.',
            ], 403);
        }

        
        $nuevoEstado = $user->estado == 1 ? 0 : 1;
        $user->update(['estado' => $nuevoEstado]);

        return response()->json([
            'success' => true,
            'message' => $nuevoEstado == 1
                ? 'Usuario activado exitosamente.'
                : 'Usuario desactivado exitosamente.',
            'user' => $user->fresh()->load('role'),
        ]);
    }
}
