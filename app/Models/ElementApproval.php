<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElementApproval extends Model
{
    use HasFactory;

    protected $table = 'APROBACION_ELEMENTO';
    protected $primaryKey = 'aprobacion_elemento_id';

    protected $fillable = [
        'elemento_id',
        'proceso_id',
        'usuario_id',
        'estado',
        'comentario',
        'nueva_fecha_limite',// para almacenar la nueva fecha límite propuesta por el usuario en caso de rechazo
    ];

    protected $casts = [
        'nueva_fecha_limite' => 'date',// para asegurarnos de que se trate como una fecha al acceder a este campo
    ];

    /**
     * Elemento que fue aprobado/rechazado.
     */
    public function elemento()
    {
        return $this->belongsTo(StructureElement::class, 'elemento_id', 'elemento_id');
    }

    /**
     * Proceso al que pertenece la aprobación.
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }

    /**
     * Usuario que realizó la aprobación/rechazo.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }
}
