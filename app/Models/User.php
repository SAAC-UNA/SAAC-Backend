<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // necesario para Auth
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles; //  importa el trait correcto

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; //  incluye el trait aquí

    protected $table = 'USUARIO';
    protected $primaryKey = 'usuario_id';
    public $timestamps = true;

    protected $fillable = [
        'cedula',
        'nombre',
        'email',
    
    ];

     public function comment()
    {
        return $this->hasMany(Comment::class, 'usuario_id', 'usuario_id');
    }

    public function careers()
    {
        return $this->belongsToMany(Career::class, 'CARRERA_USUARIO', 'usuario_id', 'carrera_id');
    }
}
