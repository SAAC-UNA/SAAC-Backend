<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Standard extends Model
{
    use HasFactory;

    protected $table = 'ESTANDAR';
    protected $primaryKey = 'estandar_id';
    protected $fillable = ['criterio_id', 'descripcion'];
    public $timestamps = true;

    // Cada estándar pertenece a un criterio (owner key = criterio_id)
    public function criterio()
    {
        return $this->belongsTo(Criterion::class, 'criterio_id', 'criterio_id');
    }
}
