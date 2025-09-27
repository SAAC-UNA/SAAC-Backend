<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
<<<<<<< HEAD
    /** @use HasFactory<\Database\Factories\FacultyFactory> */
    use HasFactory;
    protected $table = 'FACULTAD';
    protected $primaryKey = 'facultad_id';
    protected $fillable = ['universidad_id', 'sede_id', 'nombre'];

=======
    use HasFactory;

    protected $table = 'FACULTAD';
    protected $primaryKey = 'facultad_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = ['universidad_id', 'sede_id', 'nombre'];

    // N:1 → FACULTAD pertenece a UNIVERSIDAD
>>>>>>> 02_API_de_Endpoints_de_Estructura
    public function university()
    {
        return $this->belongsTo(University::class, 'universidad_id', 'universidad_id');
    }
<<<<<<< HEAD
=======

    // N:1 → FACULTAD pertenece a SEDE (modelo Campus apuntando a tabla SEDE)
>>>>>>> 02_API_de_Endpoints_de_Estructura
    public function campus()
    {
        return $this->belongsTo(Campus::class, 'sede_id', 'sede_id');
    }

<<<<<<< HEAD
    public function career()
    {
        return $this->hasMany(Career::class, 'facultad_id', 'facultad_id');
    }
=======
    // 1:N → FACULTAD tiene muchas carreras
    /*public function career()
    {
        return $this->hasMany(Career::class, 'facultad_id', 'facultad_id');
    }*/
>>>>>>> 02_API_de_Endpoints_de_Estructura
}
