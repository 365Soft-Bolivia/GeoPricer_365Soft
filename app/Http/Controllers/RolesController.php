<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $roles = Roles::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            })
            ->withCount(['users' => function ($query) {
                // Asegurarse de usar la foreign key correcta
                $query->whereColumn('users.role_id', 'roles.id');
            }])
            ->orderBy('nombre')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'nombre' => $role->nombre,
                    'descripcion' => $role->descripcion,
                    'activo' => $role->activo,
                    'usuarios_count' => $role->users_count,
                    'created_at' => $role->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('Roles', [
            'roles' => $roles->values(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:roles'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['boolean'],
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.unique' => 'Ya existe un rol con este nombre.',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
        ]);

        Roles::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'activo' => $validated['activo'] ?? true,
        ]);

        return redirect()->route('roles')->with('success', 'Rol creado correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $role = Roles::findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:roles,nombre,' . $role->id],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['boolean'],
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.unique' => 'Ya existe un rol con este nombre.',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
        ]);

        $role->update([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'activo' => $validated['activo'] ?? $role->activo,
        ]);

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    public function toggleStatus(int $id)
    {
        $role = Roles::findOrFail($id);

        // Verificar si el rol tiene usuarios asignados antes de desactivarlo
        if ($role->activo && $role->users()->count() > 0) {
            return back()->with('error', 'No puedes desactivar un rol que tiene usuarios asignados.');
        }

        $nuevoEstado = !$role->activo;
        $role->update(['activo' => $nuevoEstado]);

        return back()->with([
            'success' => $nuevoEstado ? 'Rol activado correctamente.' : 'Rol desactivado correctamente.',
        ]);
    }

    public function destroy(int $id)
    {
        $role = Roles::findOrFail($id);

        // Verificar si el rol tiene usuarios asignados
        if ($role->users()->count() > 0) {
            return back()->with('error', 'No puedes eliminar un rol que tiene usuarios asignados.');
        }

        $nombreRole = $role->nombre;
        $role->delete();

        return back()->with([
            'success' => "Rol '{$nombreRole}' eliminado correctamente.",
        ]);
    }
}