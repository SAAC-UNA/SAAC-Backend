<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    /** @use HasFactory<\Database\Factories\UniversityFactory> */
    use HasFactory;
    protected $table = 'UNIVERSIDAD';
    protected $primaryKey = 'universidad_id';
    protected $fillable = ['nombre'];
    
    public function Campuses()
    {
        return $this->hasMany(Campus::class, 'universidad_id', 'universidad_id');
    }
}
