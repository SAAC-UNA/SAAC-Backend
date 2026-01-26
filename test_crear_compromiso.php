<?php

// Script para probar la creación de un compromiso de mejora desde el backend

use App\Services\ImprovementCommitmentService;

$data = [
    'ciclo_acreditacion_id' => 1,
    'descripcion' => 'Test desde script',
    'fecha_inicio' => '2026-01-26',
    'fecha_fin' => '2026-03-31',
    'selecciones' => [
        [
            'entidad_tipo' => 'CRITERIO',
            'entidad_id' => 5,
        ],
    ],
    'evidencias_asignar' => [
        [
            'evidencia_id' => 10,
            'usuarios' => [1, 2, 3],
            'fecha_limite' => '2026-02-15',
            'comentario' => 'Asignación directa a coordinadores',
        ],
    ],
];

$service = app(ImprovementCommitmentService::class);
$result = $service->createCommitment($data);

dd($result);
