<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

abstract class BaseCareer extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('byCareerCampus', function (Builder $query) {
            /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $user */
            $user = Auth::user();
            $careerParam = Request::get('career_campus_id');

            // Detectar el modelo actual
            $model = $query->getModel();
            $modelName = class_basename($model);

            // Evitar recursión en relaciones
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $trace) {
                if (!empty($trace['function']) && str_contains($trace['function'], 'accreditationCycle')) {
                    return;
                }
            }

            //  Modo Postman: sin usuario, pero con parámetro
            if (!$user && $careerParam) {
                static::applyFilter($query, $modelName, [$careerParam]);
                return;
            }

            //  Modo libre (Tinker)
            if (!$user) return;

            //  Superusuario → sin restricciones
            if ($user->hasRole('Superusuario')) return;

            //  Obtener todos los carrera_sede_id asociados al usuario
            $careerCampusIds = $user->careers()
                ->join('CARRERA_SEDE', 'CARRERA.carrera_id', '=', 'CARRERA_SEDE.carrera_id')
                ->pluck('CARRERA_SEDE.carrera_sede_id')
                ->toArray();

            static::applyFilter($query, $modelName, $careerCampusIds);
        });
    }

    protected static function applyFilter(Builder $query, string $modelName, array $careerCampusIds)
    {
        switch ($modelName) {
            case 'AccreditationCycle':
                $query->whereIn('carrera_sede_id', $careerCampusIds);
                break;

            case 'Evidence':
            case 'Criterion':
            case 'Component':
            case 'Dimension':
                // Estos modelos son del repositorio y no deben filtrarse por carrera
                // Son compartidos entre todas las carreras
                break;

            default:
                // Para modelos como Process, Autoevaluation, etc.
                // que tienen relación directa con AccreditationCycle
                $query->whereHas('accreditationCycle', function ($q) use ($careerCampusIds) {
                    $q->whereIn('carrera_sede_id', $careerCampusIds);
                });
                break;
        }
    }
} 