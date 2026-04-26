<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Process;
use App\Models\Criterion;
use App\Models\CriterionApproval;
use Illuminate\Support\Facades\Hash;

class AprobacionCriteriosTestSeeder extends Seeder
{
    /**
     * Seeder de datos de prueba para Aprobación de Criterios (Postman testing)
     *
     * Usa los datos existentes y solo crea:
     * - 1 usuario adicional (Encargado de prueba)
     * - Aprobaciones de criterios de ejemplo
     */
    public function run(): void
    {
        // 1. VERIFICAR DATOS EXISTENTES
        $usuarios = User::count();
        $criterios = Criterion::count();
        $procesos = Process::count();

        if ($usuarios === 0 || $criterios === 0 || $procesos === 0) {
            echo "❌ ERROR: Primero ejecuta los seeders principales (migrate:fresh --seed)\n";
            return;
        }

        echo "✓ Datos existentes: {$usuarios} usuarios, {$criterios} criterios, {$procesos} procesos\n\n";

        // 2. OBTENER O CREAR ENCARGADO DE ACREDITACIÓN
        echo "Configurando usuarios...\n";

        $encargadoRole = Role::where('name', 'Encargado de Acreditación')->first();
        $encargado = User::whereHas('roles', function($q) use ($encargadoRole) {
            $q->where('roles.id', $encargadoRole->id);
        })->first();

        if (!$encargado) {
            $encargado = User::create([
                'cedula' => '222222222',
                'nombre' => 'Encargado Acreditación Test',
                'email' => 'encargado@saac.una.ac.cr',
                'password' => Hash::make('password123'),
                'status' => 'active'
            ]);
            $encargado->assignRole($encargadoRole);
            echo "✓ Encargado de Acreditación creado\n";
        } else {
            echo "✓ Usando Encargado existente: {$encargado->nombre}\n";
        }

        // 3. OBTENER DATOS EXISTENTES
        $proceso = Process::first();
        $criterio1 = Criterion::first();
        $criterio2 = Criterion::skip(1)->first();

        if (!$proceso || !$criterio1 || !$criterio2) {
            echo "❌ ERROR: No hay suficientes criterios o procesos\n";
            return;
        }

        echo "✓ Usando proceso ID: {$proceso->proceso_id}\n";
        echo "✓ Usando criterio 1 ID: {$criterio1->criterio_id}\n";
        echo "✓ Usando criterio 2 ID: {$criterio2->criterio_id}\n\n";

        // 4. CREAR APROBACIONES DE EJEMPLO
        echo "Creando aprobaciones de criterios...\n";

        // Limpiar aprobaciones existentes de estos criterios
        CriterionApproval::whereIn('criterio_id', [$criterio1->criterio_id, $criterio2->criterio_id])
            ->where('proceso_id', $proceso->proceso_id)
            ->delete();

        // Aprobar el primer criterio
        $aprobada = CriterionApproval::create([
            'criterio_id' => $criterio1->criterio_id,
            'proceso_id' => $proceso->proceso_id,
            'usuario_id' => $encargado->usuario_id,
            'estado' => 'aprobado',
            'comentario' => 'Criterio aprobado - Todas las evidencias cumplen con los requisitos'
        ]);

        // Rechazar el segundo criterio
        $rechazada = CriterionApproval::create([
            'criterio_id' => $criterio2->criterio_id,
            'proceso_id' => $proceso->proceso_id,
            'usuario_id' => $encargado->usuario_id,
            'estado' => 'rechazado',
            'comentario' => 'Criterio rechazado - Falta documentación de soporte'
        ]);

        echo "✓ 2 aprobaciones creadas\n\n";

        // RESUMEN
        echo "═══════════════════════════════════════════════════════\n";
        echo "  DATOS DE PRUEBA LISTOS PARA POSTMAN\n";
        echo "═══════════════════════════════════════════════════════\n";
        echo "\n";
        echo "USUARIOS EXISTENTES (usar cualquiera):\n";
        $allUsers = User::with('roles')->get();
        foreach ($allUsers as $u) {
            $roles = $u->roles->pluck('name')->join(', ');
            echo "  • {$u->nombre}\n";
            echo "    cedula: {$u->cedula}  password: password123\n";
            echo "    roles: {$roles}\n\n";
        }
        echo "ENDPOINTS PARA PROBAR:\n";
        echo "  GET  /api/aprobaciones-criterios\n";
        echo "  GET  /api/aprobaciones-criterios/{$aprobada->aprobacion_criterio_id}\n";
        echo "  POST /api/criterios/{$criterio1->criterio_id}/aprobar\n";
        echo "       Body: {\"proceso_id\": {$proceso->proceso_id}, \"comentario\": \"test\"}\n";
        echo "\n";
        echo "IDS IMPORTANTES:\n";
        echo "  proceso_id: {$proceso->proceso_id}\n";
        echo "  criterio_aprobado_id: {$criterio1->criterio_id}\n";
        echo "  criterio_rechazado_id: {$criterio2->criterio_id}\n";
        echo "  aprobacion_id: {$aprobada->aprobacion_criterio_id}\n";
        echo "═══════════════════════════════════════════════════════\n";
    }
}
