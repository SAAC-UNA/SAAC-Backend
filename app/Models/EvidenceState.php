<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvidenceState extends Model
{
    /** @use HasFactory<\Database\Factories\EvidenceStateFactory> */
    use HasFactory;
    protected $table = 'ESTADO_EVIDENCIA';
    protected $primaryKey = 'estado_evidencia_id';
    protected $fillable = ['nombre'];

    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidencia_id');
    }
}
