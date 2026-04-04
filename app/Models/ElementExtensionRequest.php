<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElementExtensionRequest extends Model
{
    use HasFactory;

    protected $table = 'SOLICITUD_AMPLIACION_ELEMENTO';

    protected $primaryKey = 'solicitud_ampliacion_elemento_id';

    protected $fillable = [
        'elemento_asignacion_id',
        'usuario_id',
        'motivo',
        'fecha_sugerida',
        'estado',
        'fecha_resolucion',
        'usuario_resolutor_id',
        'justificacion',
    ];

    protected $casts = [
        'fecha_sugerida'   => 'datetime',
        'fecha_resolucion' => 'datetime',
    ];

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADA  = 'aprobada';
    const ESTADO_RECHAZADA = 'rechazada';
    const ESTADO_CANCELADA = 'cancelada';

    public function elementAssignment()
    {
        return $this->belongsTo(ElementAssignment::class, 'elemento_asignacion_id', 'elemento_asignacion_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    public function resolutor()
    {
        return $this->belongsTo(User::class, 'usuario_resolutor_id', 'usuario_id');
    }
}
