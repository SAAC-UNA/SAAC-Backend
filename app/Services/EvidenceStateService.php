<?php

namespace App\Services;

use App\Models\EvidenceState;
use Illuminate\Support\Facades\Cache;

class EvidenceStateService
{
    private const CACHE_KEY = 'estados_evidencia.all';
    private const CACHE_TTL = 600; // catálogo muy estático

    public function getAll()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () =>
            EvidenceState::orderBy('estado_evidencia_id')->get()
        );
    }

    public function findById(int $id): ?EvidenceState
    {
        return EvidenceState::find($id);
    }

    public function create(array $data): EvidenceState
    {
        $state = EvidenceState::create(['nombre' => $data['nombre']]);
        Cache::forget(self::CACHE_KEY);
        return $state;
    }

    public function update(EvidenceState $estado, array $data): EvidenceState
    {
        $estado->update(['nombre' => $data['nombre'] ?? $estado->nombre]);
        Cache::forget(self::CACHE_KEY);
        return $estado->fresh();
    }

    public function delete(EvidenceState $estado): void
    {
        $estado->delete();
        Cache::forget(self::CACHE_KEY);
    }
}