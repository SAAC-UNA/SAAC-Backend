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
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
//Es para registrar los observers de auditoria en cada modelo, para que se registren las acciones de crear, actualizar y eliminar en la tabla de auditoria
    public function boot(): void
    {
        AccreditationCycle::observe(new AuditObserver('Ciclos de Acreditación'));
        Career::observe(new AuditObserver('Carrera'));
        Campus::observe(new AuditObserver('Campus'));
        Dimension::observe(new AuditObserver('Dimensión'));
        Component::observe(new AuditObserver('Componente'));
        Criterion::observe(new AuditObserver('Criterio'));
        Standard::observe(new AuditObserver('Estándar', 'descripcion'));
        Evidence::observe(new AuditObserver('Evidencia', 'descripcion'));
    }
}