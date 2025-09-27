<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    use HasFactory;

    protected $table = 'UNIVERSIDAD';          //  igual que en migración
    protected $primaryKey = 'universidad_id';  //  igual que en migración
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['nombre'];
}
