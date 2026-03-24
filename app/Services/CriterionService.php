<?php

namespace App\Services;

use App\Models\Criterion;
use Illuminate\Support\Facades\Cache;

class CriterionService
{
    private const CACHE_KEY = 'criterios.all';
    private const CACHE_TTL = 300;

    public function getAll()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () =>
            Criterion::with('component.dimension')->orderBy('nomenclatura')->get()
        );
    }

    public function findById(int $id): ?Criterion
    {
        return Criterion::with('component.dimension')->find($id);
    }

    public function create(array $data): Criterion
    {
        $criterion = Criterion::create([
            'componente_id' => $data['componente_id'],
            'descripcion'   => $data['descripcion'],
            'nomenclatura'  => $data['nomenclatura'],
            'activo'        => $data['activo'] ?? true,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $criterion->load('component.dimension');
    }

    public function update(Criterion $criterion, array $data): Criterion
    {
        $criterion->update([
            'componente_id' => $data['componente_id'] ?? $criterion->componente_id,
            'descripcion'   => $data['descripcion']   ?? $criterion->descripcion,
            'nomenclatura'  => $data['nomenclatura']  ?? $criterion->nomenclatura,
            'activo'        => $data['activo']        ?? $criterion->activo,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $criterion->fresh('component.dimension');
    }

    public function delete(Criterion $criterion): void
    {
        $criterion->delete();
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Recalcula el estado del criterio en función del estado de sus evidencias activas.
     *
     * Reglas (en orden de prioridad):
     *  1. Sin evidencias, o todas 'Pendiente'                               → 'Pendiente'
     *  2. Todas en ['Completado', 'Validada', 'Aprobado']                  → 'Completado'
     *  3. Alguna en 'Vencido'                                               → 'Vencido'
     *  4. Cualquier otro mix con progreso                                   → 'En Proceso'
     */
    public function recalcularEstado(int $criterioId): void
    {
        $criterion = Criterion::find($criterioId);
        if (!$criterion) return;

        $evidencias = $criterion->evidences()->where('activo', true)->get();
        $total      = $evidencias->count();

        if ($total === 0) {
            $nuevoEstado = 'Pendiente';
        } else {
            $estados = $evidencias->pluck('estado');

            if ($estados->every(fn($e) => $e === 'Pendiente')) {
                $nuevoEstado = 'Pendiente';
            } elseif ($estados->every(fn($e) => in_array($e, ['Completado', 'Validada', 'Aprobado']))) {
                $nuevoEstado = 'Completado';
            } elseif ($estados->contains('Vencido')) {
                $nuevoEstado = 'Vencido';
            } else {
                $nuevoEstado = 'En Proceso';
            }
        }

        if ($criterion->estado !== $nuevoEstado) {
            $criterion->estado = $nuevoEstado;
            $criterion->saveQuietly();
            Cache::forget(self::CACHE_KEY);
        }
    }
}