<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionType extends Model
{
    /** @use HasFactory<\Database\Factories\ActionTypeFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'TIPO_ACCION';

    // Clave primaria
    protected $primaryKey = 'tipo_accion_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['descripcion'];

    /**
     * Relación: Un tipo de acción puede estar en muchos registros de bitácora.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'tipo_accion_id', 'tipo_accion_id');
    }
}
