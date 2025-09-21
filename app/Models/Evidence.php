<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    /** @use HasFactory<\Database\Factories\EvidenceFactory> */
    use HasFactory;
    protected $table = 'EVIDENCIA';
    protected $primaryKey = 'evidencia_id';
    protected $fillable = ['criterio_id', 'estado_evidencia_id', 'descripcion', 'nomenclatura'];

    public function criterion()
    {
        return $this->belongsTo(Criterion::class, 'criterio_id', 'criterio_id');
    }

    public function evidenceState()
    {
        return $this->belongsTo(EvidenceState::class, 'estado_evidencia_id', 'estado_evidencia_id');
    }
}