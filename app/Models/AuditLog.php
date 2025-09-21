<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;
    protected $table = 'BITACORA';
    protected $primaryKey = 'bitacora_id';
    protected $fillable = ['usuario_id', 'tipo_accion_id', 'detalle', 'fecha_hora'];

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    public function actionType()
    {
        return $this->belongsTo(ActionType::class, 'tipo_accion_id', 'tipo_accion_id');
    }
}
