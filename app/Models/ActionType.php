<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionType extends Model
{
    /** @use HasFactory<\Database\Factories\ActionTypeFactory> */
    use HasFactory;
    protected $table = 'TIPO_ACCION';
    protected $primaryKey = 'tipo_accion_id';
    protected $fillable = ['descripcion'];

    public function actionType()
    {
        return $this->hasMany(ActionType::class, 'tipo_accion_id', 'tipo_accion_id');
    }
}
