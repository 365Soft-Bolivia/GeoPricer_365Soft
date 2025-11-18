<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $roles = Role::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('display_name', 'like', "%{$search}%") // ✅ Cambiado de 'nombre'
                        ->orWhere('description', 'like', "%{$search}%"); // ✅ Cambiado de 'descripcion'
                });
            })
            ->withCount('users') // ✅ Simplificado
            ->orderBy('display_name') // ✅ Cambiado de 'nombre'
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'nombre' => $role->display_name, // ✅ Mapear para el frontend
                    'name' => $role->name, // ✅ Nombre interno
                    'descripcion' => $role->description, // ✅ Mapear para el frontend
                    'activo' => $role->status === 'enabled' ? 1 : 0, // ✅ Ajustar según tu campo
                    'usuarios_count' => $role->users_count,
                    'created_at' => $role->created_at ? $role->created_at->format('Y-m-d H:i:s') : null,
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
            'nombre' => ['required', 'string', 'max:255', 'unique:roles,display_name'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['boolean'],
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.unique' => 'Ya existe un rol con este nombre.',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
        ]);

        // ✅ Asignación directa
        $role = new Role();
        $role->name = strtolower(str_replace(' ', '_', $validated['nombre'])); // nombre interno
        $role->display_name = $validated['nombre'];
        $role->description = $validated['descripcion'] ?? null;
        $role->status = ($validated['activo'] ?? true) ? 'enabled' : 'disabled';
        $role->save();

        return redirect()->route('roles')->with('success', 'Rol creado correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:roles,display_name,' . $role->id],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['boolean'],
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.unique' => 'Ya existe un rol con este nombre.',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
        ]);

        // ✅ Asignación directa
        $role->display_name = $validated['nombre'];
        $role->description = $validated['descripcion'] ?? null;
        $role->status = ($validated['activo'] ?? $role->status === 'enabled') ? 'enabled' : 'disabled';
        $role->save();

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    public function toggleStatus(int $id)
    {
        $role = Role::findOrFail($id);

        // Verificar si el rol tiene usuarios asignados antes de desactivarlo
        $isEnabled = $role->status === 'enabled';
        
        if ($isEnabled && $role->users()->count() > 0) {
            return back()->with('error', 'No puedes desactivar un rol que tiene usuarios asignados.');
        }

        $role->status = $isEnabled ? 'disabled' : 'enabled';
        $role->save();

        return back()->with('success', $role->status === 'enabled' ? 'Rol activado correctamente.' : 'Rol desactivado correctamente.');
    }

    public function destroy(int $id)
    {
        $role = Role::findOrFail($id);

        // Verificar si el rol tiene usuarios asignados
        if ($role->users()->count() > 0) {
            return back()->with('error', 'No puedes eliminar un rol que tiene usuarios asignados.');
        }

        $nombreRole = $role->display_name;
        $role->delete();

        return back()->with('success', "Rol '{$nombreRole}' eliminado correctamente.");
    }
}