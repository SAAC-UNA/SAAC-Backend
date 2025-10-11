<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvidenceAssignment extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'EVIDENCIA_ASIGNACION';

    // Clave primaria
    protected $primaryKey = 'evidencia_asignacion_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'proceso_id',
        'evidencia_id',
        'usuario_id',
        'estado',
        'fecha_asignacion',
        'fecha_limite'
    ];

    // Cast de tipos
    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'fecha_limite' => 'datetime'
    ];

    /**
     * Relación: Una asignación pertenece a un proceso.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }

    /**
     * Relación: Una asignación pertenece a una evidencia.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidencia_id', 'evidencia_id');
    }

    /**
     * Relación: Una asignación pertenece a un usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }
}
