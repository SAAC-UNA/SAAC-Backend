<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'ARCHIVO';

    // Clave primaria
    protected $primaryKey = 'archivo_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'evidencia_id',
        'elemento_id',
        'usuario_id',
        'proceso_id',
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
    ];

    // Cast de tipos
    protected $casts = [
        'fecha_subida' => 'datetime',
        'is_publico' => 'boolean',
        'link_expira_en' => 'datetime',
    ];

    /**
     * Relación: Un archivo pertenece a una evidencia.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidencia_id', 'evidencia_id');
    }

    /**
     * Relación: Un archivo puede pertenecer directamente a un elemento (modelo flexible).
     * HU-008
     */
    public function elemento()
    {
        return $this->belongsTo(StructureElement::class, 'elemento_id', 'elemento_id');
    }

    /**
     * Relación: Un archivo pertenece a un usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Relación: Un archivo pertenece a un proceso.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }

    /**
     * Genera un token público UUID único para el archivo.
     *
     * @return string
     */
    public function generatePublicToken(): string
    {
        $this->token_publico = (string) Str::uuid();
        $this->save();
        
        return $this->token_publico;
    }

    /**
     * Hace el archivo público y genera un token con expiración de 1 año.
     *
     * @return void
     */
    public function makePublic(): void
    {
        $this->is_publico = true;
        $this->token_publico = (string) Str::uuid();
        $this->link_expira_en = now()->addYear();
        $this->save();
    }

    /**
     * Hace el archivo privado y elimina el token público.
     *
     * @return void
     */
    public function makePrivate(): void
    {
        $this->is_publico = false;
        $this->token_publico = null;
        $this->link_expira_en = null;
        $this->save();
    }

    /**
     * Verifica si el link público ha expirado.
     *
     * @return bool
     */
    public function hasExpiredLink(): bool
    {
        if (!$this->is_publico || !$this->link_expira_en) {
            return true;
        }

        return now()->isAfter($this->link_expira_en);
    }

    /**
     * Verifica si el archivo es accesible públicamente.
     *
     * @return bool
     */
    public function isPubliclyAccessible(): bool
    {
        return $this->is_publico && !$this->hasExpiredLink();
    }

    /**
     * Obtiene la URL pública del archivo.
     *
     * @return string|null
     */
    public function getPublicUrl(): ?string
    {
        if (!$this->isPubliclyAccessible()) {
            return null;
        }

        return url('/api/p/' . $this->token_publico);
    }

    /**
     * Scope para obtener solo archivos públicos válidos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublicAndValid($query)
    {
        return $query->where('is_publico', true)
                     ->where('link_expira_en', '>', now());
    }

    /**
     * Scope para filtrar por evidencia.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $evidenciaId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByEvidence($query, int $evidenciaId)
    {
        return $query->where('evidencia_id', $evidenciaId);
    }

    /**
     * Scope para filtrar por proceso.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $procesoId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProcess($query, int $procesoId)
    {
        return $query->where('proceso_id', $procesoId);
    }

    /**
     * Scope para filtrar por usuario.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $usuarioId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }
}
