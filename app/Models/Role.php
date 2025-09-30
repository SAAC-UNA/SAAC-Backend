<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo Role que extiende la implementación de Spatie.
 *
 * Representa un rol dentro del sistema con sus atributos básicos:
 * - name: nombre único del rol (obligatorio por Spatie).
 * - description: descripción breve del rol (campo adicional).
 * - guard_name: especifica el guard de autenticación (por defecto "api").
 *
 * Este modelo mantiene los nombres de atributos en inglés para
 * asegurar compatibilidad total con el paquete spatie/laravel-permission.
 */
class Role extends SpatieRole
{
    use HasFactory;

    // Clave primaria
    protected $primaryKey = 'id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'name',
        'description', // campo adicional para describir el rol
        'guard_name',
    ];
}
