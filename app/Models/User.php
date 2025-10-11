<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Para autenticación
use Spatie\Permission\Traits\HasRoles; // Para roles y permisos spatie
//use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Usuario del sistema.
 *
 * Este modelo extiende de Authenticatable para permitir la autenticación
 * mediante Laravel (local) y, en el futuro, integración con LDAP.
 *
 * - Actualmente, se utiliza para usuarios locales durante el desarrollo.
 * - En el Sprint 3 se conectará con el servicio LDAP institucional
 *   a través de un adaptador (sin cambiar esta clase).
 *
 * También implementa HasRoles (Spatie) para la gestión de roles y permisos,
 * y define el campo "status" para activar o desactivar usuarios.
 */

class User extends Authenticatable // Cambiado a Authenticatable en models
{
    use HasFactory, HasRoles; // Usar los traits
    // Nombre de la tabla en la base de datos
    protected $table = 'USUARIO';
    // Clave primaria
    protected $primaryKey = 'usuario_id';

    // Spatie usará este guard (coincide con lo que estás usando)
    protected $guard_name = 'api';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['cedula', 'nombre', 'email', 'status'];

     // Estados
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    // Helpers de dominio
    public function isActive(): bool      { return $this->status === self::STATUS_ACTIVE; }
    public function activate(): void      { $this->update(['status' => self::STATUS_ACTIVE]); }
    public function deactivate(): void    { $this->update(['status' => self::STATUS_INACTIVE]); }
    public function scopeActive($q)       { return $q->where('status', self::STATUS_ACTIVE); }

    /**
     * Relación: Un usuario tiene muchos comentarios.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'usuario_id', 'usuario_id');
    }
    /**
    * Indica a Laravel que use 'usuario_id' para el Route Model Binding.
    *
    * Esto permite que en las rutas con {user}, Laravel busque por usuario_id
    * en lugar de por 'id'.
    */
    public function getRouteKeyName(): string
    {
    return 'usuario_id';
    }
}
