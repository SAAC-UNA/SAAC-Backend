<?php

<<<<<<< HEAD
=======

>>>>>>> 02_API_de_Endpoints_de_Estructura
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class University extends Model
{
<<<<<<< HEAD
    /** @use HasFactory<\Database\Factories\UniversityFactory> */
    use HasFactory;
    protected $table = 'UNIVERSIDAD';
    protected $primaryKey = 'universidad_id';
    protected $fillable = ['nombre'];
    
    public function campus()
    {
        return $this->hasMany(Campus::class, 'universidad_id', 'universidad_id');
    }
}
=======
    use HasFactory;

    protected $table = 'UNIVERSIDAD';          //  igual que en migración
    protected $primaryKey = 'universidad_id';  //  igual que en migración
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['nombre'];
}
>>>>>>> 02_API_de_Endpoints_de_Estructura
