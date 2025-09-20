<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccreditationCycle extends Model
{
    /** @use HasFactory<\Database\Factories\AccreditationCycleFactory> */
    use HasFactory;
    protected $table = 'CICLO_ACREDITACION';
    protected $primaryKey = 'ciclo_acreditacion_id';
    protected $fillable = ['carrera_sede_id', 'nombre'];

    public function careerCampus()
    {
        return $this->belongsTo(CareerCampus::class, 'carrera_sede_id');
    }

    public function process()
    {
        return $this->hasMany(Process::class, 'ciclo_acreditacion_id');
    }
}
