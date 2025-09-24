<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Career extends Model
{
    use HasFactory;

    protected $table = 'CARRERA';
    protected $primaryKey = 'carrera_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = ['facultad_id', 'nombre'];

    // N:1 → Career pertenece a Faculty
    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'facultad_id', 'facultad_id');
    }
}
