<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Criterion extends Model
{
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
    public function component()
    {
        return $this->belongsTo(Component::class, 'componente_id', 'componente_id');
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comentario_id', 'comentario_id');
    }

    public function standard()
    {
        return $this->hasMany(Standard::class, 'criterio_id', 'criterio_id');
    }
}
