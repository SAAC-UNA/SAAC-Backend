<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReportFile extends Model
{
    use HasFactory;

    protected $table = 'INFORME_ARCHIVO';

    protected $primaryKey = 'informe_archivo_id';

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

    protected $casts = [
        'fecha_subida'      => 'datetime',
        'is_publico'        => 'boolean',
        'link_expira_en'    => 'datetime',
        'fecha_publicacion' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    public function publishedBy()
    {
        return $this->belongsTo(User::class, 'usuario_publicacion_id', 'usuario_id');
    }

    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }

    public function generatePublicToken(): string
    {
        $this->token_publico = (string) Str::uuid();
        $this->save();

        return $this->token_publico;
    }

    public function makePublic(): void
    {
        $this->is_publico = true;
        $this->token_publico = (string) Str::uuid();
        $this->link_expira_en = now()->addYear();
        $this->save();
    }

    public function makePrivate(): void
    {
        $this->is_publico = false;
        $this->token_publico = null;
        $this->link_expira_en = null;
        $this->save();
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

    public function scopePublicAndValid($query)
    {
        return $query->where('is_publico', true)
            ->whereNotNull('token_publico')
            ->where(function ($q) {
                $q->whereNull('link_expira_en')
                    ->orWhere('link_expira_en', '>', now());
            });
    }

    public function scopeByProcess($query, int $procesoId)
    {
        return $query->where('proceso_id', $procesoId);
    }

    public function scopeByUser($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }
}
