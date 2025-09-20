<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    /** @use HasFactory<\Database\Factories\ProcessFactory> */
    use HasFactory;
    protected $table = 'PROCESO';
    protected $primaryKey = 'proceso_id';
    protected $fillable = ['ciclo_acreditacion_id'];

    public function accreditationCycle()
    {
        return $this->belongsTo(AccreditationCycle::class, 'ciclo_acreditacion_id', 'ciclo_acreditacion_id');
    }

    public function autoevaluation()
    {
        return $this->hasOne(Autoevaluation::class, 'proceso_id', 'proceso_id');
    }

    public function improvementCommitment()
    {
        return $this->hasOne(ImprovementCommitment::class, 'proceso_id', 'proceso_id');
    }
}
