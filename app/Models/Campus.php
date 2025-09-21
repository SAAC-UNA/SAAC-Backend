<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    /** @use HasFactory<\Database\Factories\CampusFactory> */
    use HasFactory;
    protected $table = 'SEDE';
    protected $primaryKey = 'sede_id';
    protected $fillable = ['universidad_id', 'nombre'];

    public function University()
    {
        return $this->belongsTo(University::class, 'universidad_id', 'universidad_id');
    }

    public function Faculty()
    {
        return $this->hasMany(Faculty::class, 'sede_id', 'sede_id');
    }
}
