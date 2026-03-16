<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jerarquia extends Model
{
    use HasFactory;

    // Tabla
    protected $table = 'JERARQUIA';

    // Primary Key
    protected $primaryKey = 'jerarquia_id';

    // Fillable
    protected $fillable = [
        'parent_id',
        'nombre',
        'tipo',
        'nomenclatura',
        'descripcion',
        'orden',
        'activo'
    ];

    // Casts
    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    // ===== RELACIONES =====

    /**
     * Relación: Un elemento tiene un padre (autorreferencia)
     */
    public function parent()
    {
        return $this->belongsTo(Jerarquia::class, 'parent_id', 'jerarquia_id');
    }

    /**
     * Relación: Un elemento tiene muchos hijos (autorreferencia)
     */
    public function children()
    {
        return $this->hasMany(Jerarquia::class, 'parent_id', 'jerarquia_id')
                    ->orderBy('orden')
                    ->orderBy('nombre');
    }

    /**
     * Relación recursiva: Obtener todos los descendientes
     * COMENTADO: No se usa actualmente (para árbol visual)
     * Descomentar si se implementa visualización de árbol completo
     */
    // public function descendants()
    // {
    //     return $this->children()->with('descendants');
    // }

    /**
     * Relación recursiva: Obtener todos los ancestros
     * COMENTADO: No se usa actualmente (para árbol visual)
     * Descomentar si se implementa breadcrumb de ruta completa
     */
    // public function ancestors()
    // {
    //     return $this->parent()->with('ancestors');
    // }

    // ===== SCOPES =====

    /**
     * Scope: Solo elementos activos
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Filtrar por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('tipo', $type);
    }

    /**
     * Scope: Solo elementos raíz (sin padre)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Hijos de un padre específico
     */
    public function scopeChildrenOf($query, int $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    // ===== MÉTODOS DE UTILIDAD =====

    /**
     * Verificar si tiene hijos
     * ÚTIL: Se usa en delete() para evitar borrar padres con hijos
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Obtener la profundidad del nodo en el árbol
     * COMENTADO: No se usa actualmente (para árbol visual con niveles)
     * Descomentar si se necesita calcular nivel de profundidad
     */
    // public function getDepth(): int
    // {
    //     $depth = 0;
    //     $current = $this;
    //     while ($current->parent) {
    //         $depth++;
    //         $current = $current->parent;
    //     }
    //     return $depth;
    // }

    /**
     * Obtener la ruta completa desde la raíz
     * COMENTADO: No se usa actualmente (para breadcrumbs de árbol)
     * Descomentar si se necesita mostrar "Raíz > Padre > Hijo"
     */
    // public function getPath(string $separator = ' > '): string
    // {
    //     $path = [$this->nombre];
    //     $current = $this;
    //     while ($current->parent) {
    //         $current = $current->parent;
    //         array_unshift($path, $current->nombre);
    //     }
    //     return implode($separator, $path);
    // }
}
