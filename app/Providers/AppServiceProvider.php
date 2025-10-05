<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Model => Policy (si luego creas Policies, las registras aquí)
    ];

    public function boot(): void
    {
        $this->defineAdminGate();
        $this->defineModuleGates();
    }

    private function defineAdminGate(): void
    {
        // Superusuario: rol superadmin o permiso admin.super
        Gate::define('admin.super', fn ($user) =>
            $user->hasRole('superadmin') || $user->can('admin.super')
        );
    }

    private function defineModuleGates(): void
    {
        // Catálogo mínimo que ya usas en HU-02
        $catalog = [
            'evidencias' => ['view','create','edit','delete'],
            'reportes'   => ['generate'],
            'usuarios'   => ['view','create','edit','delete'],
            'ciclos'     => ['view','create','edit','delete'],
        ];

        foreach ($catalog as $module => $actions) {
            foreach ($actions as $action) {
                $perm  = "{$module}.{$action}";
                $alias = "gestion_{$module}";

                // La gate permite si tiene el permiso atómico, o el alias gestion_*,
                // o es superadmin (por rol o permiso).
                Gate::define($perm, function ($user) use ($perm, $alias) {
                    return $user->can($perm)
                        || $user->can($alias)
                        || $user->hasRole('superadmin')
                        || $user->can('admin.super');
                });
            }
        }
    }
}
