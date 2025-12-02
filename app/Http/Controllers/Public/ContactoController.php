<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Lead;

class ContactoController extends Controller
{
    /**
     * Mostrar la vista del formulario de contacto
     */
public function index()
{
    return Inertia::render('Public/Contacto');
}

public function store(Request $request)
{
    $validated = $request->validate([
        'nombre' => 'required|string|max:200',
        'apellido' => 'required|string|max:200',
        'carnet' => 'required|string|max:50',
        'email' => 'required|email|max:200',
        'telefono' => 'required|string|max:30',
        'mensaje' => 'required|string|max:2000'
    ]);

    Lead::create([
        'client_name' => $validated['nombre'] . ' ' . $validated['apellido'],
        'client_email' => $validated['email'],
        'mobile' => $validated['telefono'],
        'note' => $validated['mensaje'],
        'carnet' => $validated['carnet'], // si tienes campo carnet real en DB cámbialo
        'source_id' => 1,     // Web formulario (depende de tu DB)
        'status_id' => 1,     // Status inicial
        'column_priority' => 1,
        'added_by' => null,   // Nadie logueado
        'company_id' => 1
    ]);

    return back()->with('success', 'Tu solicitud fue enviada correctamente ✔️');
}
}
