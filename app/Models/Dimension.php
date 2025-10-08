<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dimension extends Model
{
    /** @use HasFactory<\Database\Factories\DimensionFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'DIMENSION';

    // Clave primaria
    protected $primaryKey = 'dimension_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['comentario_id', 'nombre', 'nomenclatura' , 'activo'];
    
    //public function career()
    //{
      //  return $this->belongsTo(Career::class, 'carrera_id', 'carrera_id');
    //}
    /**
     * Relación: Una dimensión tiene muchos componentes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */

    public function components()
    {
        return $this->hasMany(Component::class, 'dimension_id', 'dimension_id');
    }
     /**
     * Relación: Una dimensión puede tener un comentario.
     */
    //public function comment()
    //{
       // return $this->belongsTo(Comment::class, 'comentario_id', 'comentario_id');
    //} dudosa procedencia de chat nose si ya estaba esto si no sirve fijo ay que descomentarlo
}
