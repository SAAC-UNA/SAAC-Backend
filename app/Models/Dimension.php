<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dimension extends Model
{
    /** @use HasFactory<\Database\Factories\DimensionFactory> */
    use HasFactory;

    protected $table = 'DIMENSION';
    protected $primaryKey = 'dimension_id';
    protected $fillable = ['comentario_id', 'nombre', 'nomenclatura'];

    /*public function components()
    {
        return $this->hasMany(Component::class, 'dimension_id', 'dimension_id');
    }*/
}