<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // necesario para Auth
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens; // Sanctum para autenticación API
/**
 * Modelo de Usuario del sistema.
 *
 * Este modelo extiende de Authenticatable para permitir la autenticación
 * mediante LDAP. Los usuarios se sincronizan automáticamente desde LDAP
 * a la base de datos local para gestión de roles y permisos.
 *
 * - Autenticación: Se valida contra servidor LDAP
 * - Sincronización: Al primer login se crea/actualiza desde LDAP
 * - BD Local: Almacena roles, permisos y relaciones (Spatie)
 * - Password: Campo requerido por Authenticatable, siempre NULL
 */
use Spatie\Permission\Traits\HasRoles; //  importa el trait correcto

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens; //  incluye los traits necesarios

    protected $table = 'USUARIO';
    protected $primaryKey = 'usuario_id';
    public $timestamps = true;

    // Spatie usará este guard (coincide con lo que estás usando)
    protected $guard_name = 'api';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'cedula',    // uid de LDAP
        'nombre',    // cn de LDAP
        'email',     // mail de LDAP
        'status'     // Estado local del usuario
    ];

    // Campos ocultos en serialización (seguridad)
    protected $hidden = [
        // password no existe en esta tabla (solo autenticación LDAP)
    ];

    // Estados del usuario
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
