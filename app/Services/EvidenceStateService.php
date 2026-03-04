<?php

namespace App\Services;

use App\Models\EvidenceState;
use Illuminate\Support\Facades\DB;

class EvidenceStateService
{
    public function getAll()
    {
        $rows = DB::select('CALL SP_OBTENER_ESTADOS_EVIDENCIA()');
        return EvidenceState::hydrate(array_map(fn($r) => (array) $r, $rows));
    }

    public function findById(int $id): ?EvidenceState
    {
        $rows = DB::select('CALL SP_BUSCAR_ESTADO_EVIDENCIA(?)', [$id]);
        return $rows ? EvidenceState::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    public function create(array $data): EvidenceState
    {
        $rows = DB::select('CALL SP_CREAR_ESTADO_EVIDENCIA(?)', [
            $data['nombre'],
        ]);
        return EvidenceState::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function update(EvidenceState $estado, array $data): EvidenceState
    {
        $rows = DB::select('CALL SP_ACTUALIZAR_ESTADO_EVIDENCIA(?, ?)', [
            $estado->estado_evidencia_id,
            $data['nombre'] ?? $estado->nombre,
        ]);
        return EvidenceState::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function delete(EvidenceState $estado): void
    {
        DB::statement('CALL SP_ELIMINAR_ESTADO_EVIDENCIA(?)', [$estado->estado_evidencia_id]);
    }
}