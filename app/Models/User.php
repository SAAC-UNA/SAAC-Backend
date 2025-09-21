<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    protected $table = 'USUARIO';
    protected $primaryKey = 'usuario_id';
    #'rol_id',
    protected $fillable = ['nombre', 'cedula', 'email'];

    public function auditLog()
    {
        return $this->hasMany(AuditLog::class, 'usuario_id', 'usuario_id');
    }
}