<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\University;
use App\Models\Campus;
use App\Models\Faculty;
use App\Models\Career;
use App\Models\CareerCampus;
use App\Models\AccreditationCycle;
use App\Models\Process;
use App\Models\Comment;
use App\Models\Dimension;
use App\Models\Component;
use App\Models\Criterion;
use App\Models\EvidenceState;
use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class EvidenceAssignmentSeederFixed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            $this->command->info('🚀 Iniciando creación de datos de prueba para asignaciones de evidencias...');
            
            // 1. Crear estructura académica básica
            $this->createAcademicStructure();
            
            // 2. Crear comentarios base (requeridos por otras tablas)
            $this->createBaseComments();
            
            // 3. Crear estructura de evaluación
            $this->createEvaluationStructure();
            
            // 4. Crear estados de evidencia
            $this->createEvidenceStates();
            
            // 5. Crear evidencias
            $this->createEvidences();
            
            // 6. Crear usuarios adicionales
            $this->createAdditionalUsers();
            
            // 7. Crear asignaciones de evidencias
            $this->createEvidenceAssignments();
            
            DB::commit();
            $this->command->info('✅ Datos de prueba creados exitosamente!');
            $this->showSummary();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error al crear datos de prueba: ' . $e->getMessage());
            throw $e;
        }
    }

    private function createAcademicStructure()
    {
        $this->command->info('🏛️ Creando estructura académica...');
        
        // Universidad
        $university = University::firstOrCreate(
            ['nombre' => 'Universidad Nacional'],
            ['activo' => true]
        );

        // Campus/Sede
        $campus = Campus::firstOrCreate(
            ['nombre' => 'Campus Omar Dengo'],
            [
                'universidad_id' => $university->universidad_id,
                'activo' => true
            ]
        );

        // Facultad
        $faculty = Faculty::firstOrCreate(
            ['nombre' => 'Facultad de Ciencias Exactas y Naturales'],
            [
                'universidad_id' => $university->universidad_id,
                'sede_id' => $campus->sede_id,
                'activo' => true
            ]
        );

        // Carrera
        $career = Career::firstOrCreate(
            ['nombre' => 'Ingeniería en Sistemas de Información'],
            [
                'facultad_id' => $faculty->facultad_id,
                'activo' => true
            ]
        );

        // Carrera-Campus
        $careerCampus = CareerCampus::firstOrCreate([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id
        ]);

        // Ciclo de acreditación
        $cycle = AccreditationCycle::firstOrCreate(
            ['nombre' => 'Ciclo 2024-2025'],
            [
                'carrera_sede_id' => $careerCampus->carrera_sede_id
            ]
        );

        // Procesos
        for ($i = 1; $i <= 3; $i++) {
            Process::firstOrCreate([
                'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id
            ]);
        }

        $this->command->info("   ✓ Universidad: {$university->nombre}");
        $this->command->info("   ✓ Campus: {$campus->nombre}");
        $this->command->info("   ✓ Facultad: {$faculty->nombre}");
        $this->command->info("   ✓ Carrera: {$career->nombre}");
        $this->command->info("   ✓ Ciclo: {$cycle->nombre}");
        $this->command->info("   ✓ Procesos: 3 procesos creados");
    }

    private function createBaseComments()
    {
        $this->command->info('💬 Creando comentarios base...');
        
        // Obtener un usuario administrador existente
        $adminUser = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$adminUser) {
            $this->command->warn('   ⚠️  No se encontró usuario admin, usando el primer usuario disponible');
            $adminUser = User::first();
        }

        if (!$adminUser) {
            throw new \Exception('No hay usuarios disponibles para crear comentarios');
        }

        // Crear comentarios base para dimensiones, componentes y criterios
        $baseComments = [
            'Comentario base para estructura de evaluación - Dimensión',
            'Comentario base para estructura de evaluación - Componente', 
            'Comentario base para estructura de evaluación - Criterio'
        ];

        foreach ($baseComments as $commentText) {
            Comment::firstOrCreate(
                ['texto' => $commentText],
                [
                    'usuario_id' => $adminUser->usuario_id,
                    'fecha_creacion' => now()->toDateString()
                ]
            );
        }

        $this->command->info("   ✓ " . count($baseComments) . " comentarios base creados");
    }

    private function createEvaluationStructure()
    {
        $this->command->info('📊 Creando estructura de evaluación...');
        
        $comments = Comment::all();
        
        if ($comments->count() < 3) {
            throw new \Exception('No hay suficientes comentarios para crear la estructura de evaluación');
        }

        // Crear dimensiones
        $dimension = Dimension::firstOrCreate(
            ['nomenclatura' => 'D1'],
            [
                'comentario_id' => $comments[0]->comentario_id,
                'nombre' => 'Dimensión de Gestión del Programa',
                'activo' => true
            ]
        );

        // Crear componente
        $component = Component::firstOrCreate(
            ['nomenclatura' => 'C1.1'],
            [
                'dimension_id' => $dimension->dimension_id,
                'comentario_id' => $comments[1]->comentario_id,
                'nombre' => 'Información y Promoción',
                'activo' => true
            ]
        );

        // Crear criterio
        $criterion = Criterion::firstOrCreate(
            ['nomenclatura' => 'CR1.1.1'],
            [
                'componente_id' => $component->componente_id,
                'comentario_id' => $comments[2]->comentario_id,
                'descripcion' => 'La carrera cuenta con mecanismos de información y promoción',
                'activo' => true
            ]
        );

        $this->command->info("   ✓ Dimensión: {$dimension->nombre}");
        $this->command->info("   ✓ Componente: {$component->nombre}");
        $this->command->info("   ✓ Criterio: {$criterion->descripcion}");
    }

    private function createEvidenceStates()
    {
        $this->command->info('📋 Creando estados de evidencia...');
        
        $states = [
            'Pendiente',
            'En Revisión',
            'Aprobada',
            'Rechazada'
        ];

        foreach ($states as $stateName) {
            EvidenceState::firstOrCreate(['nombre' => $stateName]);
        }

        $this->command->info("   ✓ " . count($states) . " estados de evidencia creados");
    }

    private function createEvidences()
    {
        $this->command->info('📄 Creando evidencias...');
        
        $criterion = Criterion::first();
        $pendingState = EvidenceState::where('nombre', 'Pendiente')->first();
        
        if (!$criterion || !$pendingState) {
            throw new \Exception('No hay criterios o estados de evidencia disponibles');
        }

        $evidences = [
            [
                'nomenclatura' => 'EV1.1.1.1',
                'descripcion' => 'Folletos informativos de la carrera'
            ],
            [
                'nomenclatura' => 'EV1.1.1.2', 
                'descripcion' => 'Material publicitario digital'
            ],
            [
                'nomenclatura' => 'EV1.1.1.3',
                'descripcion' => 'Registros de actividades promocionales'
            ]
        ];

        foreach ($evidences as $evidenceData) {
            Evidence::firstOrCreate(
                ['nomenclatura' => $evidenceData['nomenclatura']],
                [
                    'criterio_id' => $criterion->criterio_id,
                    'estado_evidencia_id' => $pendingState->estado_evidencia_id,
                    'descripcion' => $evidenceData['descripcion'],
                    'activo' => true
                ]
            );
        }

        $this->command->info("   ✓ " . count($evidences) . " evidencias creadas");
    }

    private function createAdditionalUsers()
    {
        $this->command->info('👥 Creando usuarios adicionales...');
        
        // Verificar que existan los roles
        $docenteRole = Role::where('name', 'docente')->first();
        $coordinadorRole = Role::where('name', 'coordinador')->first();
        
        if (!$docenteRole || !$coordinadorRole) {
            $this->command->warn('   ⚠️  Roles docente o coordinador no encontrados, saltando creación de usuarios');
            return;
        }

        $users = [
            [
                'cedula' => '987654321',
                'nombre' => 'María González Pérez',
                'email' => 'maria.gonzalez@una.edu.py',
                'role' => 'docente'
            ],
            [
                'cedula' => '876543210',
                'nombre' => 'Carlos Rodríguez Silva',
                'email' => 'carlos.rodriguez@una.edu.py', 
                'role' => 'coordinador'
            ],
            [
                'cedula' => '765432109',
                'nombre' => 'Ana Martínez López',
                'email' => 'ana.martinez@una.edu.py',
                'role' => 'docente'
            ]
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);
            $userData['status'] = 'active';
            
            $user = User::firstOrCreate(
                ['cedula' => $userData['cedula']], 
                $userData
            );
            
            // Asignar rol al usuario
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        $this->command->info("   ✓ " . count($users) . " usuarios adicionales creados");
    }

    private function createEvidenceAssignments()
    {
        $this->command->info('📋 Creando asignaciones de evidencias...');
        
        $processes = Process::all();
        $evidences = Evidence::all();
        $users = User::where('status', 'active')->get();

        if ($processes->isEmpty() || $evidences->isEmpty() || $users->isEmpty()) {
            $this->command->warn('⚠️  No hay suficientes datos para crear asignaciones');
            return;
        }

        $assignments = [];
        $process = $processes->first();

        foreach ($evidences as $evidence) {
            // Asignar cada evidencia a 2-3 usuarios diferentes
            $assignedUsers = $users->random(min(3, $users->count()));
            
            foreach ($assignedUsers as $user) {
                $assignment = EvidenceAssignment::firstOrCreate([
                    'proceso_id' => $process->proceso_id,
                    'evidencia_id' => $evidence->evidencia_id,
                    'usuario_id' => $user->usuario_id
                ], [
                    'estado' => 'pendiente',
                    'fecha_asignacion' => now(),
                    'fecha_limite' => now()->addDays(30)
                ]);

                if ($assignment->wasRecentlyCreated) {
                    $assignments[] = $assignment;
                }
            }
        }

        $this->command->info("   ✓ " . count($assignments) . " asignaciones de evidencias creadas");
    }

    private function showSummary()
    {
        $this->command->info('');
        $this->command->info('📊 RESUMEN DE DATOS CREADOS:');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('🏛️  Universidades: ' . University::count());
        $this->command->info('🏢 Sedes: ' . Campus::count());
        $this->command->info('🎓 Facultades: ' . Faculty::count());
        $this->command->info('📚 Carreras: ' . Career::count());
        $this->command->info('🔄 Procesos: ' . Process::count());
        $this->command->info('📊 Dimensiones: ' . Dimension::count());
        $this->command->info('🧩 Componentes: ' . Component::count());
        $this->command->info('📋 Criterios: ' . Criterion::count());
        $this->command->info('📄 Evidencias: ' . Evidence::count());
        $this->command->info('👥 Usuarios: ' . User::count());
        $this->command->info('🎯 Asignaciones: ' . EvidenceAssignment::count());
        $this->command->info('═══════════════════════════════════════');
    }
}