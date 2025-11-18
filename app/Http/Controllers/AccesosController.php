<?php

namespace App\Http\Controllers;

use App\Models\Role;
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

        $usuarios = User::withoutGlobalScope(\App\Scopes\ActiveScope::class)
        ->with('roles')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('roles', function ($roleQuery) use ($search) {
                            $roleQuery->where('display_name', 'like', "%{$search}%");
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
                    'status' => $user->status,
                    'estado' => $user->status === 'active' ? 1 : 0,
                    'role' => $user->roles->first() ? [
                        'id' => $user->roles->first()->id,
                        'nombre' => $user->roles->first()->display_name,
                        'descripcion' => $user->roles->first()->description,
                    ] : null,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                        ];
                    }),
                    'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : null,
                ];
            });

        $roles = Role::orderBy('display_name')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'nombre' => $role->display_name,
                'name' => $role->name,
                'descripcion' => $role->description,
            ];
        });

        return Inertia::render('Accesos', [
            'usuarios' => $usuarios->values(),
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        try {
            \Log::info(' Datos recibidos para crear usuario:', $request->all());

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'role_id' => ['required', 'exists:roles,id'],
                'company_id' => ['nullable', 'integer', 'exists:companies,id'],
                'gender' => ['nullable', 'in:male,female,other'],
            ]);

            \Log::info(' Validación exitosa:', $validated);

            $user = new User();
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->password = Hash::make($validated['password']);
            $user->status = 'active'; //  Correcto según tu ENUM
            $user->company_id = $validated['company_id'] ?? 1;
            $user->dark_theme = 0;
            $user->rtl = 0;
            $user->email_notifications = 1;
            $user->gender = $validated['gender'] ?? 'male';
            $user->locale = 'es';
            $user->login = 'enable';
            $user->save();

            \Log::info('Usuario creado con ID: ' . $user->id);

            $user->roles()->attach($validated['role_id']);

            \Log::info(' Rol asignado correctamente');

            return redirect()->route('accesos')->with('success', 'Usuario creado correctamente.');
        } catch (\Exception $e) {
            \Log::error(' Error al crear usuario:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return back()->with('error', 'Error al crear el usuario: ' . $e->getMessage())->withInput();
        }
    }
    public function update(Request $request, int $id)
    {
        try {
            $user = User::findOrFail($id);

            \Log::info(' Actualizando usuario ID: ' . $id, $request->all());

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
                'role_id' => ['required', 'exists:roles,id'],
                'company_id' => ['nullable', 'integer', 'exists:companies,id'],
                'gender' => ['nullable', 'in:male,female,other'],
                'status' => ['nullable', 'in:active,deactive'], // Validación corregida
            ]);

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->company_id = $validated['company_id'] ?? $user->company_id;
            $user->gender = $validated['gender'] ?? $user->gender;
            $user->status = $validated['status'] ?? $user->status;

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            $user->roles()->sync([$validated['role_id']]);

            \Log::info(' Usuario actualizado correctamente');

            return back()->with('success', 'Usuario actualizado correctamente.');
        } catch (\Exception $e) {
            \Log::error('💥 Error al actualizar usuario:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
            return back()->with('error', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    public function toggleStatus(int $id)
{
    try {
        $user = User::withoutGlobalScope(\App\Scopes\ActiveScope::class)
            ->findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes cambiar el estado de tu propia cuenta.');
        }

        $nuevoEstado = $user->status === 'active' ? 'deactive' : 'active';
        $user->status = $nuevoEstado;
        $user->save();

        return back()->with('success', $nuevoEstado === 'active' ? 'Usuario activado.' : 'Usuario desactivado.');
        
    } catch (\Exception $e) {
        \Log::error('Error al cambiar estado:', [
            'user_id' => $id,
            'error' => $e->getMessage()
        ]);
        
        return back()->with('error', 'Error al cambiar el estado del usuario.');
    }
}

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $nombreUsuario = $user->name;
        $user->roles()->detach();
        $user->delete();

        return back()->with('success', "Usuario {$nombreUsuario} eliminado correctamente.");
    }
}