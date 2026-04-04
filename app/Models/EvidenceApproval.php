<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceApproval extends Model
{
    protected $table = 'APROBACION_EVIDENCIA';
    protected $primaryKey = 'aprobacion_evidencia_id';

    protected $fillable = [
        'evidencia_id',
        'proceso_id',
        'criterio_aprobacion_id',
        'usuario_id',
        'estado',
        'comentario',
        'nueva_fecha_limite',
    ];

    /**
     * Get the evidence that was approved
     */
    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidencia_id');
    }

    /**
     * Get the process this approval belongs to
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id');
    }

    /**
     * Get the criterion approval this belongs to
     */
    public function criterionApproval()
    {
        return $this->belongsTo(CriterionApproval::class, 'criterio_aprobacion_id');
    }

    /**
     * Get the user who made the approval
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}

