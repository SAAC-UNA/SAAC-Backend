<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
<<<<<<< HEAD
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    protected $table = 'USUARIO';
    protected $primaryKey = 'usuario_id';
    #'rol_id',
    protected $fillable = ['nombre', 'cedula', 'email'];

    public function auditLog()
    {
        return $this->hasMany(AuditLog::class, 'usuario_id', 'usuario_id');
=======
    use HasFactory;

    protected $table = 'USUARIO';
    protected $primaryKey = 'usuario_id';
    protected $fillable = ['cedula', 'nombre', 'email'];

    // A user has many comments
    public function comments()
    {
        return $this->hasMany(Comment::class, 'usuario_id', 'usuario_id');
>>>>>>> 02_API_de_Endpoints_de_Estructura
    }
}