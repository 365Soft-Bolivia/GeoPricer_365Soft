<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con usuarios
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id'); // ← Agregar 'role_id' aquí
    }

    /**
     * Accessor para obtener el nombre del rol
     * (para mantener compatibilidad con el middleware que busca 'name')
     */
    public function getNameAttribute()
    {
        return $this->nombre;
    }
}