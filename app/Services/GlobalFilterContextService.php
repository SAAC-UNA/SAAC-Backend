<?php

namespace App\Services;

use App\Models\AccreditationCycle;
use App\Models\CareerCampus;
use App\Models\Process;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class GlobalFilterContextService
{
    private const TTL_DAYS = 30;

    public function get(User $user): array
    {
        $context = Cache::get($this->cacheKey($user), []);

        return [
            'career_campus_id'      => isset($context['career_campus_id']) ? (int) $context['career_campus_id'] : null,
            'ciclo_acreditacion_id' => isset($context['ciclo_acreditacion_id']) ? (int) $context['ciclo_acreditacion_id'] : null,
            'proceso_id'            => isset($context['proceso_id']) ? (int) $context['proceso_id'] : null,
        ];
    }

    public function set(User $user, array $payload): array
    {
        $context = $this->get($user);

        if (array_key_exists('career_campus_id', $payload)) {
            $context['career_campus_id'] = $this->normalizeNullableInt($payload['career_campus_id']);
            if (!array_key_exists('ciclo_acreditacion_id', $payload)) {
                $context['ciclo_acreditacion_id'] = null;
            }
            if (!array_key_exists('proceso_id', $payload)) {
                $context['proceso_id'] = null;
            }
        }

        if (array_key_exists('ciclo_acreditacion_id', $payload)) {
            $context['ciclo_acreditacion_id'] = $this->normalizeNullableInt($payload['ciclo_acreditacion_id']);
            if (!array_key_exists('proceso_id', $payload)) {
                $context['proceso_id'] = null;
            }
        }

        if (array_key_exists('proceso_id', $payload)) {
            $context['proceso_id'] = $this->normalizeNullableInt($payload['proceso_id']);
        }

        $this->validateCareerCampus($user, $context['career_campus_id']);
        $this->validateCycle($context['ciclo_acreditacion_id'], $context['career_campus_id']);
        $this->validateProcess($context['proceso_id'], $context['ciclo_acreditacion_id'], $context['career_campus_id']);

        Cache::put($this->cacheKey($user), $context, now()->addDays(self::TTL_DAYS));

        return $context;
    }

    public function clear(User $user): array
    {
        Cache::forget($this->cacheKey($user));

        return [
            'career_campus_id'      => null,
            'ciclo_acreditacion_id' => null,
            'proceso_id'            => null,
        ];
    }

    public function injectableFilters(User $user): array
    {
        return array_filter($this->get($user), static fn ($value) => $value !== null);
    }

    private function validateCareerCampus(User $user, ?int $careerCampusId): void
    {
        if ($careerCampusId === null) {
            return;
        }

        $query = CareerCampus::query()->where('carrera_sede_id', $careerCampusId);

        if (!$user->hasRole('Superusuario')) {
            $query->whereIn('carrera_sede_id', $this->userCareerCampusIds($user));
        }

        if (!$query->exists()) {
            throw ValidationException::withMessages([
                'career_campus_id' => ['La carrera-sede seleccionada no está disponible para este usuario.'],
            ]);
        }
    }

    private function validateCycle(?int $cycleId, ?int $careerCampusId): void
    {
        if ($cycleId === null) {
            return;
        }

        $query = AccreditationCycle::query()->where('ciclo_acreditacion_id', $cycleId);

        if ($careerCampusId !== null) {
            $query->where('carrera_sede_id', $careerCampusId);
        }

        if (!$query->exists()) {
            throw ValidationException::withMessages([
                'ciclo_acreditacion_id' => ['El ciclo seleccionado no existe o no pertenece a la carrera-sede activa.'],
            ]);
        }
    }

    private function validateProcess(?int $processId, ?int $cycleId, ?int $careerCampusId): void
    {
        if ($processId === null) {
            return;
        }

        $query = Process::query()->where('proceso_id', $processId);

        if ($cycleId !== null) {
            $query->where('ciclo_acreditacion_id', $cycleId);
        } elseif ($careerCampusId !== null) {
            $query->whereHas('accreditationCycle', fn ($q) =>
                $q->where('carrera_sede_id', $careerCampusId)
            );
        }

        if (!$query->exists()) {
            throw ValidationException::withMessages([
                'proceso_id' => ['El proceso seleccionado no existe o no coincide con el contexto activo.'],
            ]);
        }
    }

    private function userCareerCampusIds(User $user): array
    {
        return $user->careers()
            ->join('CARRERA_SEDE', 'CARRERA.carrera_id', '=', 'CARRERA_SEDE.carrera_id')
            ->pluck('CARRERA_SEDE.carrera_sede_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function cacheKey(User $user): string
    {
        return 'global-filter-context:' . $user->usuario_id;
    }
}
