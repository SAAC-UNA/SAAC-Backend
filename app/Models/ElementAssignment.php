<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElementAssignment extends Model
{
    use HasFactory;

    protected $table = 'ELEMENTO_ASIGNACION';

    protected $primaryKey = 'elemento_asignacion_id';

    protected $fillable = [
        'elemento_id',
        'usuario_id',
        'proceso_id',
        'asignado_por',
        'estado',
        'fecha_limite',
        'comentario',
    ];

    protected $casts = [
        'fecha_limite' => 'date',
    ];

    // ===== RELATIONS =====

    /**
     * An assignment belongs to a flexible structure element.
     */
    public function element()
    {
        return $this->belongsTo(StructureElement::class, 'elemento_id', 'elemento_id');
    }

    /**
     * An assignment belongs to a user (the assignee).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    /**
     * An assignment belongs to a process.
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }

    /**
     * The user who created the assignment.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'asignado_por', 'usuario_id');
    }
}
