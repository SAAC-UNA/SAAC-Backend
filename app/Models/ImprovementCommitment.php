<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImprovementCommitment extends Model
{
    /** @use HasFactory<\Database\Factories\ImprovementCommitmentFactory> */
    use HasFactory;
    protected $table = 'COMPROMISO_MEJORA';
    protected $primaryKey = 'ID_COMPROMISO_MEJORA';
    protected $fillable = ['proceso_id', 'fecha_inicio', 'fecha_fin'];

    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }
}
