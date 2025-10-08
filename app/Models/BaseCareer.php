<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema; // importación correcta

abstract class BaseCareer extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('byCarrera', function (Builder $query) {

            //  Evita error si no hay sesión
            if (!Auth::check()) {
                return;
            }

            /** @var \App\Models\User|null $user */
            $user = Auth::user();

            //  Superadmin ve todo
            if ($user && $user->hasRole('SuperUsuario')) {
                return;
            }

            //  Administrador: filtra por carreras asignadas
            if ($user && $user->hasRole('Administrador')) {
                $ids = $user->careers->pluck('carrera_id')->toArray();

                if (!empty($ids)) {
                    $query->whereIn('carrera_id', $ids);
                }
            }

            //  Opcional: filtra solo activos si la tabla tiene esa columna
            if (Schema::hasColumn((new static)->getTable(), 'activo')) {
                $query->where('activo', true);
            }
        });
    }
}
