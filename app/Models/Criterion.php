<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Criterion extends Model
{
<<<<<<< HEAD
    /** @use HasFactory<\Database\Factories\CriterionFactory> */
    use HasFactory;
    protected $table = 'CRITERIO';
    protected $primaryKey = 'criterio_id';
    protected $fillable = ['componente_id', 'comentario_id', 'descripcion', 'nomenclatura'];

=======
    use HasFactory;

    // Tabla y PK
    protected $table = 'CRITERIO';
    protected $primaryKey = 'criterio_id';
    public $incrementing = true;   // por claridad
    protected $keyType = 'int';    // por claridad

    // Asignación masiva
    protected $fillable = [
        'componente_id',
        'comentario_id',
        'descripcion',
        'nomenclatura',
    ];

    // Relaciones
>>>>>>> 02_API_de_Endpoints_de_Estructura
    public function component()
    {
        return $this->belongsTo(Component::class, 'componente_id', 'componente_id');
    }

<<<<<<< HEAD
    public function evidence()
    {
        return $this->hasMany(Evidence::class, 'criterio_id', 'criterio_id');
    }

    public function standards()
    {
        return $this->hasMany(Standard::class, 'criterio_id', 'criterio_id');
    }
=======
    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comentario_id', 'comentario_id');
    }

    public function standard()
    {
    return $this->hasMany(Standard::class, 'criterio_id', 'criterio_id');
    }

>>>>>>> 02_API_de_Endpoints_de_Estructura
}
