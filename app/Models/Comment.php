<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory;
<<<<<<< HEAD
=======

>>>>>>> 02_API_de_Endpoints_de_Estructura
    protected $table = 'COMENTARIO';
    protected $primaryKey = 'comentario_id';
    protected $fillable = ['usuario_id', 'texto', 'fecha_creacion'];

<<<<<<< HEAD
=======
    // A comment belongs to a user
>>>>>>> 02_API_de_Endpoints_de_Estructura
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }
}
