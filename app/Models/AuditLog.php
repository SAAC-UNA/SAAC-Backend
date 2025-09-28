<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'BITACORA';

    // Clave primaria
    protected $primaryKey = 'bitacora_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['usuario_id', 'tipo_accion_id', 'detalle', 'fecha_hora'];

    /**
     * Relación: Un registro de bitácora pertenece a un usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Relación: Un registro de bitácora pertenece a un tipo de acción.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function actionType()
    {
        return $this->belongsTo(ActionType::class, 'tipo_accion_id', 'tipo_accion_id');
    }
}
