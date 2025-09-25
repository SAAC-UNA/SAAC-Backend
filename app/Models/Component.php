<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    /** @use HasFactory<\Database\Factories\ComponentFactory> */
    use HasFactory;
    protected $table = 'COMPONENTE';
    protected $primaryKey = 'componente_id';
    protected $fillable = ['comentario_id', 'nombre', 'nomenclatura'];

    public function dimension()
    {
        return $this->belongsTo(Dimension::class, 'componente_id', 'componente_id');
    }
}
