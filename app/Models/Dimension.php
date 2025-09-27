<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dimension extends Model
{
    /** @use HasFactory<\Database\Factories\DimensionFactory> */
    use HasFactory;
<<<<<<< HEAD
=======

>>>>>>> 02_API_de_Endpoints_de_Estructura
    protected $table = 'DIMENSION';
    protected $primaryKey = 'dimension_id';
    protected $fillable = ['comentario_id', 'nombre', 'nomenclatura'];

<<<<<<< HEAD
    public function components()
    {
        return $this->hasMany(Component::class, 'dimension_id', 'dimension_id');
    }
}
=======
    /*public function components()
    {
        return $this->hasMany(Component::class, 'dimension_id', 'dimension_id');
    }*/
}
>>>>>>> 02_API_de_Endpoints_de_Estructura
