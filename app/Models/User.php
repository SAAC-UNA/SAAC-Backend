<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $table = 'USUARIO';
    protected $primaryKey = 'usuario_id';
    protected $fillable = ['cedula', 'nombre', 'email'];

    // A user has many comments
    public function comments()
    {
        return $this->hasMany(Comment::class, 'usuario_id', 'usuario_id');
    }
}
