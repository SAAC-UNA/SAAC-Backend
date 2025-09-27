<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;

    protected $table = 'FACULTAD';
    protected $primaryKey = 'facultad_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = ['universidad_id', 'sede_id', 'nombre'];

    // N:1 → FACULTAD pertenece a UNIVERSIDAD
    public function university()
    {
        return $this->belongsTo(University::class, 'universidad_id', 'universidad_id');
    }
    public function campus()
    {
        return $this->belongsTo(Campus::class, 'sede_id', 'sede_id');
    }

    public function career()
    {
        return $this->hasMany(Career::class, 'facultad_id', 'facultad_id');
    }
}
