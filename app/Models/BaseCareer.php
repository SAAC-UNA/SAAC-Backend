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

            //  SuperUsuario → sin restricciones
            if ($user->hasRole('SuperUsuario')) return;

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

            // Modelos sin relación directa con ciclo de acreditación
            // Son catálogos generales compartidos entre todos los ciclos
            case 'Criterion':
            case 'Evidence':
            case 'Component':
            case 'Dimension':
                // Estos modelos no tienen relación directa con accreditationCycle
                // No aplicar filtro
                // No aplicar filtro para estos modelos
                break;

            // Process tiene relación directa, pero para simplificar
            // permitimos que superusuario vea todos sin filtro adicional
            case 'Process':
                // No aplicar filtro adicional aquí
                break;

            default:
                $query->whereHas('accreditationCycle', function ($q) use ($careerCampusIds) {
                    $q->whereIn('carrera_sede_id', $careerCampusIds);
                });
                break;
        }
    }
} 