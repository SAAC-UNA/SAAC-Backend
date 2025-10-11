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
        static::addGlobalScope('byCareer', function (Builder $query) {
            /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $user */

            $user = Auth::user();
            $careerParam = Request::get('career_id');

            // Detecta el modelo actual
            $model = $query->getModel();
            $modelName = class_basename($model);
            $table = $model->getTable();

            //  Evitar recursión: si ya se está resolviendo una relación accreditationCycle, no aplicar nada
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $trace) {
                if (!empty($trace['function']) && str_contains($trace['function'], 'accreditationCycle')) {
                    return;
                }
            }

            //  Sin usuario pero con parámetro → modo Postman
            if (!$user && $careerParam) {
                static::applyFilter($query, $modelName, [$careerParam]);
                return;
            }

            //  Sin usuario ni parámetro → modo Tinker libre
            if (!$user) return;

            //  SuperUsuario → ver todo
            if ($user->hasRole('SuperUsuario')) return;

            //  Usuario normal → filtrar por carreras
            $careerIds = $user->careers->pluck('carrera_id')->toArray();
            static::applyFilter($query, $modelName, $careerIds);
        });
    }

    /**
     * Aplica el filtro de acuerdo al tipo de modelo
     */
    protected static function applyFilter(Builder $query, string $modelName, array $careerIds)
    {
        switch ($modelName) {
            case 'AccreditationCycle':
                $query->whereHas('careerCampus.career', function ($q) use ($careerIds) {
                    $q->whereIn('carrera_id', $careerIds);
                });
                break;

            default:
                $query->whereHas('accreditationCycle.careerCampus.career', function ($q) use ($careerIds) {
                    $q->whereIn('carrera_id', $careerIds);
                });
                break;
        }
    }
}
