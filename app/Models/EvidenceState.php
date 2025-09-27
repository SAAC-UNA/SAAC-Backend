<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvidenceState extends Model
{
    /** @use HasFactory<\Database\Factories\EvidenceStateFactory> */
    use HasFactory;
<<<<<<< HEAD
    protected $table = 'ESTADO_EVIDENCIA';
    protected $primaryKey = 'estado_evidencia_id';
    protected $fillable = ['nombre'];

    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidencia_id');
    }
}
=======

    protected $table = 'ESTADO_EVIDENCIA';
    protected $primaryKey = 'estado_evidencia_id';
    protected $fillable = ['nombre'];
    public $timestamps = true;

    // Un estado puede estar en muchas evidencias
    public function evidences()
    {
        return $this->hasMany(Evidence::class, 'estado_evidencia_id', 'estado_evidencia_id');
    }
}
>>>>>>> 02_API_de_Endpoints_de_Estructura
