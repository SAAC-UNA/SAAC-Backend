<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Criterion extends Model
{
    /** @use HasFactory<\Database\Factories\CriterionFactory> */
    use HasFactory;
    protected $table = 'CRITERIO';
    protected $primaryKey = 'criterio_id';
    protected $fillable = ['componente_id', 'comentario_id', 'descripcion', 'nomenclatura'];

    public function component()
    {
        return $this->belongsTo(Component::class, 'componente_id', 'componente_id');
    }

    public function evidence()
    {
        return $this->hasMany(Evidence::class, 'criterio_id', 'criterio_id');
    }

    public function standards()
    {
        return $this->hasMany(Standard::class, 'criterio_id', 'criterio_id');
    }
}
