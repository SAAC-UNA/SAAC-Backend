<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo del informe de acreditación aprobado.
 *
 * HU-027: Publicación de informe de acreditación aprobado.
 * Representa la resolución oficial de SINAES que certifica
 * que una carrera está acreditada dentro de un ciclo dado.
 *
 * @property \Carbon\Carbon $fecha_resolucion
 * @property \Carbon\Carbon $vigencia_desde
 * @property \Carbon\Carbon $vigencia_hasta
 * @property \Carbon\Carbon $fecha_publicacion
 * @property string         $numero_resolucion
 * @property string         $estado
 * @property int            $informe_acreditacion_id
 * @property int            $ciclo_acreditacion_id
 */
class AccreditationReport extends Model
{
    /** @use HasFactory<\Database\Factories\AccreditationReportFactory> */
    use HasFactory;

    // Estados posibles del informe
    public const STATUS_PUBLISHED   = 'publicado';
    public const STATUS_UNPUBLISHED = 'despublicado';

    // Nombre de la tabla en la base de datos
    protected $table = 'INFORME_ACREDITACION';

    // Clave primaria
    protected $primaryKey = 'informe_acreditacion_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'ciclo_acreditacion_id',
        'archivo_id',
        'usuario_publicacion_id',
        'estado',
        'numero_resolucion',
        'fecha_resolucion',
        'vigencia_desde',
        'vigencia_hasta',
        'fecha_publicacion',
        'observaciones',
        'esta_acreditada',
    ];

    // Cast de tipos
    protected $casts = [
        'fecha_resolucion'  => 'date:Y-m-d',
        'vigencia_desde'    => 'date:Y-m-d',
        'vigencia_hasta'    => 'date:Y-m-d',
        'fecha_publicacion' => 'datetime',
        'esta_acreditada'   => 'boolean',
    ];

    // --- Helpers de dominio ---

    /** Retorna true si el informe está publicado y visible públicamente. */
    public function isPublished(): bool
    {
        return $this->estado === self::STATUS_PUBLISHED;
    }

    /** Retorna true si el informe fue despublicado. */
    public function isUnpublished(): bool
    {
        return $this->estado === self::STATUS_UNPUBLISHED;
    }

    /** Retorna true si la acreditación sigue vigente a la fecha actual. */
    public function isCurrentlyValid(): bool
    {
        $today = now()->toDateString();
        return $this->isPublished()
            && $this->vigencia_desde->format('Y-m-d') <= $today
            && $this->vigencia_hasta->format('Y-m-d') >= $today;
    }

    // --- Scopes ---

    /** Filtra solo informes publicados: AccreditationReport::published()->get() */
    public function scopePublished($query)
    {
        return $query->where('estado', self::STATUS_PUBLISHED);
    }

    /** Filtra solo informes despublicados. */
    public function scopeUnpublished($query)
    {
        return $query->where('estado', self::STATUS_UNPUBLISHED);
    }

    // --- Relaciones ---

    /**
     * Relación: Un informe pertenece a un ciclo de acreditación.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accreditationCycle()
    {
        return $this->belongsTo(AccreditationCycle::class, 'ciclo_acreditacion_id', 'ciclo_acreditacion_id');
    }

    /**
     * Relación: Un informe tiene un archivo PDF adjunto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'archivo_id', 'archivo_id');
    }

    /**
     * Relación: Un informe fue publicado por un usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function publishedBy()
    {
        return $this->belongsTo(User::class, 'usuario_publicacion_id', 'usuario_id');
    }
}
