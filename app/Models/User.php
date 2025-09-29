<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'USUARIO';

    // Clave primaria
    protected $primaryKey = 'usuario_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['cedula', 'nombre', 'email'];

    /**
     * Relación: Un usuario tiene muchos comentarios.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'usuario_id', 'usuario_id');
    }
}
