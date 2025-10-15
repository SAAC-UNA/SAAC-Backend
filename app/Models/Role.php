<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo Role que extiende la implementación de Spatie.
 *
 * Representa un rol dentro del sistema con nombre, descripción
 * y relación con permisos. Mantiene el nombre en inglés para 
 * compatibilidad con el paquete spatie/laravel-permission.
 */
class Role extends SpatieRole
{
    use HasFactory;

    // Clave primaria
    protected $primaryKey = 'id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'name',
        'description', // se mantiene en inglés por compatibilidad con Spatie
        'guard_name',
    ];
}
