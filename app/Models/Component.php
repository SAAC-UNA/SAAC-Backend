<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    /** @use HasFactory<\Database\Factories\ComponentFactory> */
    use HasFactory;
<<<<<<< HEAD
    protected $table = 'COMPONENTE';
    protected $primaryKey = 'componente_id';
    protected $fillable = ['comentario_id', 'nombre', 'nomenclatura'];

    public function dimension()
    {
        return $this->belongsTo(Dimension::class, 'componente_id', 'componente_id');
=======

    // Nombre de la tabla
    protected $table = 'COMPONENTE';

    // Nombre de la clave primaria
    protected $primaryKey = 'componente_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['dimension_id', 'comentario_id', 'nombre', 'nomenclatura'];

    /**
     * Relación: un componente pertenece a una dimensión
     */
    public function dimension()
    {
        return $this->belongsTo(Dimension::class, 'dimension_id', 'dimension_id');
    }

    /**
     * Relación: un componente pertenece a un comentario
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comentario_id', 'comentario_id');
>>>>>>> 02_API_de_Endpoints_de_Estructura
    }
}
