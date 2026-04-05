<?php

namespace App\Providers;

use App\Models\AccreditationCycle;
use App\Models\Career;
use App\Models\Campus;
use App\Models\Dimension;
use App\Models\Component;
use App\Models\Criterion;
use App\Models\Standard;
use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use App\Observers\AuditObserver;
use App\Observers\EvidenceObserver;
use App\Observers\EvidenceAssignmentObserver;
use App\Contracts\FileStorageContract;
use App\Services\TradicionalFileService;
use App\Services\FlexibleFileService;
use App\Services\FileStorageFactory;
use App\Services\TradicionalExtensionRequestService;
use App\Services\FlexibleExtensionRequestService;
use App\Services\TradicionalEvidenceService;
use App\Services\TradicionalEvidenceFilterService;
use App\Services\FilterElementService;
use App\Support\AccessResolver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Ligamos los servicios de almacenamiento al contenedor IoC.
        // TradicionalFileService y FlexibleFileService son instancias únicas
        // (singleton) porque no tienen estado mutable — el disco es config.
        $this->app->singleton(TradicionalFileService::class);
        $this->app->singleton(FlexibleFileService::class);
        $this->app->singleton(FileStorageFactory::class);

        $this->app->singleton(TradicionalExtensionRequestService::class);
        $this->app->singleton(FlexibleExtensionRequestService::class);

        $this->app->singleton(TradicionalEvidenceService::class);
        $this->app->singleton(TradicionalEvidenceFilterService::class);
        $this->app->singleton(FilterElementService::class);
    }
//Es para registrar los observers de auditoria en cada modelo, para que se registren las acciones de crear, actualizar y eliminar en la tabla de auditoria
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if (!is_string($ability) || !AccessResolver::isPermissionAbility($ability)) {
                return null;
            }

            if (!$user instanceof \App\Models\User) {
                return null;
            }

            return AccessResolver::userHasAnyPermission($user, [$ability]) ? true : null;
        });

        // -----------------------------------------------------------------------
        // CAMBIO: se reemplazó Model::observe(new AuditObserver(...)) por
        // registerAudit() basado en closures.
        //
        // El código original era:
        //   AccreditationCycle::observe(new AuditObserver('Ciclos de Acreditación'));
        //   Career::observe(new AuditObserver('Carrera'));
        //   Campus::observe(new AuditObserver('Campus'));
        //   Dimension::observe(new AuditObserver('Dimensión'));
        //   Component::observe(new AuditObserver('Componente'));
        //   Criterion::observe(new AuditObserver('Criterio'));
        //   Standard::observe(new AuditObserver('Estándar', 'descripcion'));
        //   Evidence::observe(new AuditObserver('Evidencia', 'descripcion'));
        //
        // PROBLEMA: Laravel 11 guarda el *nombre de la clase* del observer, no la
        // instancia. Cuando el evento Eloquent dispara (ej. al hacer $evidence->update()
        // dentro de una transacción), Laravel intenta resolver AuditObserver desde el
        // contenedor IoC. El contenedor falla con BindingResolutionException porque no
        // sabe qué valor inyectar en el parámetro string $module del constructor.
        //
        // SOLUCIÓN: registerAudit() crea la instancia manualmente y registra closures
        // que la capturan. Así el contenedor nunca tiene que resolver AuditObserver.
        // El comportamiento de auditoría es exactamente el mismo.
        // -----------------------------------------------------------------------
        $this->registerAudit(AccreditationCycle::class, 'Ciclos de Acreditación');
        $this->registerAudit(Career::class, 'Carrera');
        $this->registerAudit(Campus::class, 'Campus');
        $this->registerAudit(Dimension::class, 'Dimensión');
        $this->registerAudit(Component::class, 'Componente');
        $this->registerAudit(Criterion::class, 'Criterio');
        $this->registerAudit(Standard::class, 'Estándar', 'descripcion');
        $this->registerAudit(Evidence::class, 'Evidencia', 'descripcion');
        Evidence::observe(EvidenceObserver::class); // recalcula CRITERIO.estado al guardar una evidencia
        EvidenceAssignment::observe(EvidenceAssignmentObserver::class); // recalcula EVIDENCIA.estado al guardar una asignación
    }

    /**
     * Registra los eventos de auditoría para un modelo usando closures.
     * Esto evita que Laravel intente resolver AuditObserver desde el contenedor IoC
     * cuando disparan los eventos Eloquent (el contenedor no puede inyectar string $module).
     */
    private function registerAudit(string $modelClass, string $module, string $field = 'nombre'): void
    {
        $observer = new AuditObserver($module, $field);

        foreach (['created', 'updated', 'deleted', 'restored', 'forceDeleted'] as $event) {
            if (method_exists($observer, $event)) {
                Event::listen(
                    "eloquent.{$event}: {$modelClass}",
                    function ($model) use ($observer, $event) {
                        $observer->{$event}($model);
                    }
                );
            }
        }
    }
}
