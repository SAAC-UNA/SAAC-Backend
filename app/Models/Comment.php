<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory;

    protected $table = 'COMENTARIO';
    protected $primaryKey = 'comentario_id';
    protected $fillable = ['usuario_id', 'texto', 'fecha_creacion'];

    // A comment belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }
}
