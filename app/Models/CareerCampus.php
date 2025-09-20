<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerCampus extends Model
{
    /** @use HasFactory<\Database\Factories\CareerCampusFactory> */
    use HasFactory;
    protected $table = 'CARRERA_SEDE';
    protected $primaryKey = 'carrera_sede_id';
    protected $fillable = ['carrera_id', 'sede_id'];

    public function career()
    {
        return $this->belongsTo(Career::class, 'carrera_id');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'sede_id');
    }

    public function accreditationCycle()
    {
        return $this->hasMany(AccreditationCycle::class, 'carrera_sede_id');
    }
}
