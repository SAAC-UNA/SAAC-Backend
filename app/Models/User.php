<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // necesario para Auth
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
use Spatie\Permission\Traits\HasRoles; //  importa el trait correcto

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; //  incluye el trait aquí

    protected $table = 'USUARIO';
    protected $primaryKey = 'usuario_id';
    public $timestamps = true;

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


     public function comment()
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

    public function careers()
    {
        return $this->belongsToMany(Career::class, 'CARRERA_USUARIO', 'usuario_id', 'carrera_id');

    }
}
