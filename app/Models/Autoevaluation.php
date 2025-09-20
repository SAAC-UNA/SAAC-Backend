<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Autoevaluation extends Model
{
    /** @use HasFactory<\Database\Factories\AutoevaluationFactory> */
    use HasFactory;
    protected $table = 'AUTOEVALUACION';
    protected $primaryKey = 'autoevaluacion_id';
    protected $fillable = ['proceso_id', 'fecha_inicio', 'fecha_fin'];

    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }
}