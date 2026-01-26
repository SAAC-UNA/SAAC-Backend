<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImprovementCommitmentService;

class TestCrearCompromiso extends Command
{
    protected $signature = 'test:crear-compromiso';
    protected $description = 'Probar la creación de un compromiso de mejora desde consola';

    public function handle()
    {
        $data = [
            'ciclo_acreditacion_id' => 1,
            'descripcion' => 'Test desde comando artisan',
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

        $this->info('Resultado:');
        dump($result);
    }
}
