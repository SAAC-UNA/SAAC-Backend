<?php

namespace App\Providers;

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

    public function boot(): void
    {
        Career::observe(new AuditObserver('Carrera'));
        Campus::observe(new AuditObserver('Campus'));
        Dimension::observe(new AuditObserver('Dimensión'));
        Component::observe(new AuditObserver('Componente'));
        Criterion::observe(new AuditObserver('Criterio'));
        Standard::observe(new AuditObserver('Estándar', 'descripcion'));
        Evidence::observe(new AuditObserver('Evidencia', 'descripcion'));
    }
}