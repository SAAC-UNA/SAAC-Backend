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
    protected $table = 'INFORME_ARCHIVO';

    // Clave primaria
    protected $primaryKey = 'informe_archivo_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'proceso_id',
        'usuario_id',
        'usuario_publicacion_id',
        'fecha_subida',
        'tipo',
        'path',
        'url',
        'nombre_original',
        'tamanio',
        'tipo_mime',
        'is_publico',
        'token_publico',
        'link_expira_en',
        'estado',
        'fecha_publicacion',
        'observaciones',
    ];

    // Cast de tipos
    protected $casts = [
        'fecha_subida'      => 'datetime',
        'fecha_publicacion' => 'datetime',
        'is_publico'        => 'boolean',
        'link_expira_en'    => 'datetime',
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

    public function hasExpiredLink(): bool
    {
        if (!$this->link_expira_en) {
            return false;
        }

        return now()->greaterThan($this->link_expira_en);
    }

    public function isPubliclyAccessible(): bool
    {
        return $this->is_publico && !$this->hasExpiredLink() && !empty($this->token_publico);
    }

    public function getPublicUrl(): ?string
    {
        if (!$this->isPubliclyAccessible()) {
            return null;
        }

        $baseUrl = (string) config('app.frontend_url', config('app.url'));

        return rtrim($baseUrl, '/') . '/p-informes/' . $this->token_publico;
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
     * Relación: Un informe pertenece a un proceso.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
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
