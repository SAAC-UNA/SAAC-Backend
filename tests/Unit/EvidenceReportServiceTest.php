<?php

use App\Models\Component;
use App\Models\Criterion;
use App\Models\Dimension;
use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use App\Models\File;
use App\Models\Process;
use App\Models\User;
use App\Services\EvidenceReportService;

it('builds a grouped evidence report with links and responsables', function () {
    $dimension = Dimension::factory()->create([
        'nomenclatura' => 'D1',
        'nombre' => 'Dimensión Uno',
    ]);

    $component = Component::factory()->create([
        'dimension_id' => $dimension->getKey(),
        'nomenclatura' => 'C1',
        'nombre' => 'Componente Uno',
    ]);

    $criterion = Criterion::factory()->create([
        'componente_id' => $component->getKey(),
        'nomenclatura' => 'CR1',
        'descripcion' => 'Criterio Uno',
    ]);

    $evidence = Evidence::factory()->create([
        'criterio_id' => $criterion->getKey(),
        'nomenclatura' => 'EV-01',
        'descripcion' => 'Evidencia principal',
    ]);

    $process = Process::factory()->create();
    $responsable = User::factory()->create([
        'nombre' => 'Ana García',
    ]);

    EvidenceAssignment::factory()->create([
        'proceso_id' => $process->getKey(),
        'evidencia_id' => $evidence->getKey(),
        'usuario_id' => $responsable->getKey(),
    ]);

    File::create([
        'evidencia_id' => $evidence->getKey(),
        'usuario_id' => $responsable->getKey(),
        'proceso_id' => $process->getKey(),
        'fecha_subida' => now(),
        'tipo' => 'enlace',
        'path' => null,
        'url' => 'https://example.com/evidencia-1',
        'nombre_original' => 'evidencia-1',
        'tamanio' => null,
        'tipo_mime' => null,
        'is_publico' => false,
        'token_publico' => null,
        'link_expira_en' => null,
    ]);

    $evidence->load(['criterion.component.dimension', 'assignments.user', 'files']);

    $report = app(EvidenceReportService::class)->build(collect([$evidence]));

    expect($report['total_evidences'])->toBe(1);
    expect($report['dimensions'])->toHaveCount(1);
    expect($report['dimensions'][0]['label'])->toBe('D1 - Dimensión Uno');
    expect($report['dimensions'][0]['components'][0]['label'])->toBe('C1 - Componente Uno');
    expect($report['dimensions'][0]['components'][0]['criteria'][0]['label'])->toBe('CR1 - Criterio Uno');
    expect($report['dimensions'][0]['components'][0]['criteria'][0]['evidences'][0]['responsables'])->toContain('Ana García');
    expect($report['dimensions'][0]['components'][0]['criteria'][0]['evidences'][0]['links'][0]['url'])->toBe('https://example.com/evidencia-1');
});