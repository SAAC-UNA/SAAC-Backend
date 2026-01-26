<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Comment extends Model // falta que exienda de lo nuevo este cmentario 
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'COMENTARIO';

    // Clave primaria
    protected $primaryKey = 'comentario_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['usuario_id', 'commentable_type', 'commentable_id', 'texto'];

    /**
     * Relación: Un comentario pertenece a un usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Relación polimórfica: Un comentario puede pertenecer a cualquier entidad.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function commentable()
    {
        return $this->morphTo();
    }
}
