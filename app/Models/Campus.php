<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    use HasFactory;

    protected $table = 'SEDE';          // tabla real en BD
    protected $primaryKey = 'sede_id';  // PK real
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = ['universidad_id', 'nombre'];

    // N:1 → Campus pertenece a una Universidad
    public function university()
    {
        return $this->belongsTo(University::class, 'universidad_id', 'universidad_id');
    }

    // 1:N → Campus tiene muchas Facultades
   // public function faculties()
    //{
    //    return $this->hasMany(Faculty::class, 'sede_id', 'sede_id');
   // }
}
