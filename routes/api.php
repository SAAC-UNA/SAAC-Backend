<?php

use App\Http\Controllers\AccreditationCycleController;
use App\Http\Controllers\ActionTypeController;
use App\Http\Controllers\AuditLogController;
// Models
use App\Http\Controllers\AuthController;
// Controllers
use App\Http\Controllers\CampusController;
use App\Http\Controllers\CareerCampusController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\CriterionApprovalController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\DevCommentController;
use App\Http\Controllers\DevUserController;
use App\Http\Controllers\DimensionController;
use App\Http\Controllers\ElementApprovalController;
use App\Http\Controllers\ElementAssignmentController;
use App\Http\Controllers\ElementCommitmentController;
use App\Http\Controllers\ElementExtensionTimeRequestController;
use App\Http\Controllers\ElementFileController;
use App\Http\Controllers\EvidenceAssignmentController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\ExtensionRequestController;
use App\Http\Controllers\ExtensionTimeRequestController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FlexibleExtensionRequestController;
use App\Http\Controllers\GlobalFilterContextController;
use App\Http\Controllers\ImprovementCommitmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StandardController;
use App\Http\Controllers\StructureElementController;
use App\Http\Controllers\StructureModelController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\UserController;
use App\Models\AccreditationCycle;
use Illuminate\Http\Request;
// Dev Controllers (solo para pruebas)
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

// ============================================
// Rutas de Autenticación
// ============================================
// Login es público (no requiere autenticación)
Route::post('auth/login', [AuthController::class, 'login']);

// Logout y Me requieren autenticación
Route::middleware(['auth:sanctum', 'refresh.session'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::get('auth/permissions', [AuthController::class, 'permissions']); // Permisos para frontend
});

// ============================================
// Rutas de Estructura (protegidas con permisos)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session'])->prefix('contexto/filtros-globales')->group(function () {
    Route::get('/', [GlobalFilterContextController::class, 'show']);
    Route::put('/', [GlobalFilterContextController::class, 'update']);
    Route::delete('/', [GlobalFilterContextController::class, 'reset']);
    Route::get('/catalogo', [GlobalFilterContextController::class, 'catalog']);
});

Route::middleware(['auth:sanctum', 'refresh.session'])->group(function () {

    // ===== UNIVERSIDADES =====
    Route::middleware(['permission:universidades.view'])->group(function () {
        Route::get('estructura/universidades', [UniversityController::class, 'index']);
        Route::get('estructura/universidades/{university}', [UniversityController::class, 'show']);
    });
    Route::post('estructura/universidades', [UniversityController::class, 'store'])
        ->middleware('permission:universidades.create');
    Route::match(['put', 'patch'], 'estructura/universidades/{university}', [UniversityController::class, 'update'])
        ->middleware('permission:universidades.edit');
    Route::delete('estructura/universidades/{university}', [UniversityController::class, 'destroy'])
        ->middleware('permission:universidades.delete');
    Route::patch('estructura/universidades/{id}/active', [UniversityController::class, 'setActive'])
        ->middleware('permission:universidades.edit');

    // ===== CAMPUSES =====
    Route::middleware(['permission:campuses.view'])->group(function () {
        Route::get('estructura/campuses', [CampusController::class, 'index']);
        Route::get('estructura/campuses/{campus}', [CampusController::class, 'show']);
    });
    Route::post('estructura/campuses', [CampusController::class, 'store'])
        ->middleware('permission:campuses.create');
    Route::match(['put', 'patch'], 'estructura/campuses/{campus}', [CampusController::class, 'update'])
        ->middleware('permission:campuses.edit');
    Route::delete('estructura/campuses/{campus}', [CampusController::class, 'destroy'])
        ->middleware('permission:campuses.delete');

    // ===== CARRERAS =====
    Route::middleware(['permission:carreras.view'])->group(function () {
        Route::get('estructura/carreras', [CareerController::class, 'index']);
        Route::get('estructura/carreras/{career}', [CareerController::class, 'show']);
    });
    Route::post('estructura/carreras', [CareerController::class, 'store'])
        ->middleware('permission:carreras.create');
    Route::match(['put', 'patch'], 'estructura/carreras/{career}', [CareerController::class, 'update'])
        ->middleware('permission:carreras.edit');
    Route::delete('estructura/carreras/{career}', [CareerController::class, 'destroy'])
        ->middleware('permission:carreras.delete');
    Route::patch('estructura/carreras/{id}/active', [CareerController::class, 'setActive'])
        ->middleware('permission:carreras.edit');

    // ===== DIMENSIONES =====
    Route::middleware(['permission:dimensiones.view'])->group(function () {
        Route::get('estructura/dimensiones', [DimensionController::class, 'index']);
        Route::get('estructura/dimensiones/{dimension}', [DimensionController::class, 'show']);
    });
    Route::post('estructura/dimensiones', [DimensionController::class, 'store'])
        ->middleware('permission:dimensiones.create');
    Route::match(['put', 'patch'], 'estructura/dimensiones/{dimension}', [DimensionController::class, 'update'])
        ->middleware('permission:dimensiones.edit');
    Route::delete('estructura/dimensiones/{dimension}', [DimensionController::class, 'destroy'])
        ->middleware('permission:dimensiones.delete');
    Route::patch('estructura/dimensiones/{id}/active', [DimensionController::class, 'setActive'])
        ->middleware('permission:dimensiones.edit');

    // ===== COMPONENTES =====
    Route::middleware(['permission:componentes.view'])->group(function () {
        Route::get('estructura/componentes', [ComponentController::class, 'index']);
        Route::get('estructura/componentes/{component}', [ComponentController::class, 'show']);
    });
    Route::post('estructura/componentes', [ComponentController::class, 'store'])
        ->middleware('permission:componentes.create');
    Route::match(['put', 'patch'], 'estructura/componentes/{component}', [ComponentController::class, 'update'])
        ->middleware('permission:componentes.edit');
    Route::delete('estructura/componentes/{component}', [ComponentController::class, 'destroy'])
        ->middleware('permission:componentes.delete');
    Route::patch('estructura/componentes/{id}/active', [ComponentController::class, 'setActive'])
        ->middleware('permission:componentes.edit');

    // ===== CRITERIOS =====
    Route::middleware(['permission:criterios.view'])->group(function () {
        Route::get('estructura/criterios', [CriterionController::class, 'index']);
        Route::get('estructura/criterios/{criterion}', [CriterionController::class, 'show']);
    });
    Route::post('estructura/criterios', [CriterionController::class, 'store'])
        ->middleware('permission:criterios.create');
    Route::match(['put', 'patch'], 'estructura/criterios/{criterion}', [CriterionController::class, 'update'])
        ->middleware('permission:criterios.edit');
    Route::delete('estructura/criterios/{criterion}', [CriterionController::class, 'destroy'])
        ->middleware('permission:criterios.delete');
    Route::patch('estructura/criterios/{id}/active', [CriterionController::class, 'setActive'])
        ->middleware('permission:criterios.edit');

    // ===== EVIDENCIAS ===== (HU-012: Filtrado avanzado DEBE ir ANTES de apiResource)
    Route::middleware(['permission:evidencias.view'])->group(function () {
        Route::get('estructura/evidencias/filter', [EvidenceController::class, 'filter'])
            ->middleware('global.filter.context');
        Route::get('estructura/evidencias', [EvidenceController::class, 'index']);
        Route::get('estructura/evidencias/{evidence}', [EvidenceController::class, 'show']);
    });
    Route::get('estructura/evidencias/export/excel', [EvidenceController::class, 'exportExcel'])
        ->middleware('global.filter.context')
        ->middleware('permission:reportes.export');
    Route::get('estructura/evidencias/export/pdf', [EvidenceController::class, 'exportPDF'])
        ->middleware('global.filter.context')
        ->middleware('permission:reportes.export');
    Route::post('estructura/evidencias', [EvidenceController::class, 'store'])
        ->middleware('permission:evidencias.create');
    Route::match(['put', 'patch'], 'estructura/evidencias/{evidence}', [EvidenceController::class, 'update'])
        ->middleware('permission:evidencias.edit');
    Route::delete('estructura/evidencias/{evidence}', [EvidenceController::class, 'destroy'])
        ->middleware('permission:evidencias.delete');
    Route::patch('estructura/evidencias/{id}/active', [EvidenceController::class, 'setActive'])
        ->middleware('permission:evidencias.edit');
    // HU-013: Retroalimentación de evidencias (observar / validar + comentario)
    // POST porque no es idempotente: cada llamada crea un nuevo comentario en COMENTARIO
    Route::post('estructura/evidencias/{id}/retroalimentacion', [EvidenceController::class, 'retroalimentar'])
        ->middleware('permission:evidencias.edit');

    // ===== ESTÁNDARES =====
    Route::middleware(['permission:estandares.view'])->group(function () {
        Route::get('estructura/estandares', [StandardController::class, 'index']);
        Route::get('estructura/estandares/{standard}', [StandardController::class, 'show']);
    });
    Route::post('estructura/estandares', [StandardController::class, 'store'])
        ->middleware('permission:estandares.create');
    Route::match(['put', 'patch'], 'estructura/estandares/{standard}', [StandardController::class, 'update'])
        ->middleware('permission:estandares.edit');
    Route::delete('estructura/estandares/{standard}', [StandardController::class, 'destroy'])
        ->middleware('permission:estandares.delete');
    Route::patch('estructura/estandares/{id}/active', [StandardController::class, 'setActive'])
        ->middleware('permission:estandares.edit');

    // ===== ELEMENTO (Tabla flexible para SINAES 2026) =====
    Route::middleware(['permission:elemento.view'])->group(function () {
        Route::get('estructura/elementos', [StructureElementController::class, 'index']);
        Route::get('estructura/elementos/filter', [StructureElementController::class, 'filter'])
            ->middleware('global.filter.context');
        Route::get('estructura/elementos/export/excel', [StructureElementController::class, 'exportExcel'])
            ->middleware('global.filter.context');
        Route::get('estructura/elementos/export/pdf', [StructureElementController::class, 'exportPDF'])
            ->middleware('global.filter.context');
        // Route::get('estructura/elementos/arbol', [StructureElementController::class, 'tree']); // TODO: Funcionalidad tree para futuro
        Route::get('estructura/elementos/{id}', [StructureElementController::class, 'show']);
    });
    Route::post('estructura/elementos', [StructureElementController::class, 'store'])
        ->middleware('permission:elemento.create');
    Route::match(['put', 'patch'], 'estructura/elementos/{id}', [StructureElementController::class, 'update'])
        ->middleware('permission:elemento.edit');
    Route::delete('estructura/elementos/{id}', [StructureElementController::class, 'destroy'])
        ->middleware('permission:elemento.delete');
    Route::patch('estructura/elementos/{id}/active', [StructureElementController::class, 'setActive'])
        ->middleware('permission:elemento.edit');

    // ===== MODELOS DE ESTRUCTURA =====
    Route::middleware(['permission:modelos.view'])->group(function () {
        Route::get('estructura/modelos', [StructureModelController::class, 'index']);
        Route::get('estructura/modelos/activos', [StructureModelController::class, 'activos']);
        Route::get('estructura/modelos/{id}', [StructureModelController::class, 'show']);
    });
    Route::post('estructura/modelos', [StructureModelController::class, 'store'])
        ->middleware('permission:modelos.create');
    Route::patch('estructura/modelos/{id}/active', [StructureModelController::class, 'setActive'])
        ->middleware('permission:modelos.edit');
    Route::put('estructura/modelos/{id}', [StructureModelController::class, 'update'])
        ->middleware('permission:modelos.edit');
    Route::patch('estructura/modelos/{id}', [StructureModelController::class, 'update'])
        ->middleware('permission:modelos.edit');
    Route::delete('estructura/modelos/{id}', [StructureModelController::class, 'destroy'])
        ->middleware('permission:modelos.delete');

    // ===== PROCESOS Y CICLOS =====
    Route::middleware(['permission:procesos.view'])->group(function () {
        Route::get('estructura/procesos', [ProcessController::class, 'index']);
        Route::get('estructura/procesos/{id}', [ProcessController::class, 'show']);
    });

    Route::middleware(['permission:ciclos.view'])->group(function () {
        Route::get('estructura/ciclos-acreditacion', [AccreditationCycleController::class, 'index']);
        Route::get('estructura/ciclos-acreditacion/{id}', [AccreditationCycleController::class, 'show']);
        Route::get('estructura/carrera-sede', [CareerCampusController::class, 'index']);
        /* Route::get('estructura/ciclos-acreditacion', function () {
             return AccreditationCycle::with('careerCampus.career', 'careerCampus.campus')->get();
         });*/

    });

    // POST, PUT, DELETE - cada uno con su propio permiso
    // Rutas protegidas para crear/editar procesos y ciclos (solo usuarios con permisos específicos)
    Route::post('estructura/ciclos-acreditacion', [AccreditationCycleController::class, 'store'])
        ->middleware('permission:ciclos.create');
    Route::match(['put', 'patch'], 'estructura/ciclos-acreditacion/{id}', [AccreditationCycleController::class, 'update'])
        ->middleware('permission:ciclos.edit');
    Route::delete('estructura/ciclos-acreditacion/{id}', [AccreditationCycleController::class, 'destroy'])
        ->middleware('permission:ciclos.delete');
    Route::patch('estructura/ciclos-acreditacion/{id}/reactivar', [AccreditationCycleController::class, 'reactivate'])
        ->middleware('permission:ciclos.reactivar');

    Route::post('estructura/procesos', [ProcessController::class, 'store'])
        ->middleware('permission:ciclos.create');
    Route::match(['put', 'patch'], 'estructura/procesos/{id}', [ProcessController::class, 'update'])
        ->middleware('permission:ciclos.edit');
    Route::patch('estructura/procesos/{id}/active', [ProcessController::class, 'setActive'])
        ->middleware('permission:ciclos.edit');

    Route::post('estructura/procesos', [ProcessController::class, 'store'])
        ->middleware('permission:procesos.create');
    Route::match(['put', 'patch'], 'estructura/procesos/{id}', [ProcessController::class, 'update'])
        ->middleware('permission:procesos.edit');
    Route::patch('estructura/procesos/{id}/active', [ProcessController::class, 'setActive'])
        ->middleware('permission:procesos.edit');
    Route::delete('estructura/procesos/{id}', [ProcessController::class, 'destroy'])
        ->middleware('permission:procesos.delete');
});

// ============================================
// Rutas para Asignaciones de Evidencias (HU-007)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session', 'global.filter.context'])->group(function () {
    Route::middleware(['permission:asignaciones.view'])->group(function () {
        Route::get('evidencias-asignaciones', [EvidenceAssignmentController::class, 'index']);
        Route::get('evidencias-asignaciones/catalogo/usuarios', [EvidenceAssignmentController::class, 'catalogUsers']);
        Route::get('evidencias-asignaciones/catalogo/roles', [EvidenceAssignmentController::class, 'catalogRoles']);
        Route::get('evidencias-asignaciones/{evidenceAssignment}', [EvidenceAssignmentController::class, 'show']);
        Route::get('usuarios/{usuarioId}/evidencias-asignadas', [EvidenceAssignmentController::class, 'getByUser']);
        Route::get('usuarios/{usuarioId}/mis-ciclos', [EvidenceAssignmentController::class, 'getUserCycles']);
        Route::get('evidencias/{evidenciaId}/asignaciones', [EvidenceAssignmentController::class, 'getByEvidence']);
        Route::get('procesos/{procesoId}/asignaciones', [EvidenceAssignmentController::class, 'getByProcess']);
    });

    Route::post('evidencias-asignaciones/validar-duplicados', [EvidenceAssignmentController::class, 'validateDuplicates']);
    Route::post('evidencias-asignaciones', [EvidenceAssignmentController::class, 'store'])
        ->middleware('permission:asignaciones.create');
    Route::match(['put', 'patch'], 'evidencias-asignaciones/{evidenceAssignment}', [EvidenceAssignmentController::class, 'update'])
        ->middleware('permission:asignaciones.view');
    Route::delete('evidencias-asignaciones/{evidenceAssignment}', [EvidenceAssignmentController::class, 'destroy'])
        ->middleware('permission:asignaciones.delete');
});

// ============================================
// Element Assignments (HU-007 flexible model)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session', 'global.filter.context'])->group(function () {
    Route::middleware(['permission:asignaciones.view'])->group(function () {
        Route::get('elementos-asignaciones', [ElementAssignmentController::class, 'index']);
        Route::get('elementos-asignaciones/filtrar', [ElementAssignmentController::class, 'filtrar']);
        Route::get('elementos-asignaciones/{id}', [ElementAssignmentController::class, 'show']);
        Route::get('elementos/{elementoId}/asignaciones', [ElementAssignmentController::class, 'byElement']);
        Route::get('procesos/{procesoId}/elementos-asignaciones', [ElementAssignmentController::class, 'byProcess']);
        Route::get('usuarios/{usuarioId}/elementos-asignados', [ElementAssignmentController::class, 'byUser']);
    });

    Route::post('elementos-asignaciones', [ElementAssignmentController::class, 'store'])
        ->middleware('permission:asignaciones.create');
    Route::match(['put', 'patch'], 'elementos-asignaciones/{id}', [ElementAssignmentController::class, 'update'])
        ->middleware('permission:asignaciones.view');
    Route::post('elementos-asignaciones/{id}/retroalimentacion', [ElementAssignmentController::class, 'retroalimentar'])
        ->middleware('permission:asignaciones.edit');
    Route::post('elementos-asignaciones/{id}/solicitud-ampliacion', [ElementAssignmentController::class, 'storeExtension'])
        ->middleware('permission:asignaciones.view');
    Route::delete('elementos-asignaciones/{id}', [ElementAssignmentController::class, 'destroy'])
        ->middleware('permission:asignaciones.delete');
});

// ============================================
// Solicitudes de Ampliación (HU-016 - ENCARGADO)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session'])->prefix('solicitudes-ampliacion')->group(function () {
    Route::get('/', [ExtensionRequestController::class, 'index']);
    Route::get('/pendientes', [ExtensionRequestController::class, 'pending']);
    Route::get('/mis-solicitudes', [ExtensionRequestController::class, 'mySolicitudes']);
    Route::get('/{id}', [ExtensionRequestController::class, 'show']);
    Route::post('/', [ExtensionRequestController::class, 'store']);
    Route::post('/{id}/aprobar', [ExtensionRequestController::class, 'approve']);
    Route::post('/{id}/rechazar', [ExtensionRequestController::class, 'reject']);
});

// ============================================
// Solicitudes de Ampliación de Tiempo (RF-15 - PROFESORES)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session', 'throttle:60,1'])
    ->prefix('solicitudes-ampliacion-tiempo')
    ->group(function () {
        Route::get('/', [ExtensionTimeRequestController::class, 'index']);

        // GET: Evidencias próximas a vencer (modelo tradicional)
        Route::get('/evidencias/proximas-vencer', [ExtensionTimeRequestController::class, 'upcomingEvidences']);

        // GET: Ver detalle de solicitud (autorización con Policy)
        Route::get('/{id}', [ExtensionTimeRequestController::class, 'show']);

        // POST: Crear solicitud (rate limit más estricto para evitar spam)
        Route::post('/', [ExtensionTimeRequestController::class, 'store'])
            ->middleware('throttle:10,1'); // Max 10 creaciones por minuto

        // PATCH: Cancelar solicitud (cambia estado a 'cancelada', no borra)
        Route::patch('/{id}/cancelar', [ExtensionTimeRequestController::class, 'cancel']);
    });

// ============================================
// Solicitudes de Ampliación — Modelo Flexible / Elemento (RF-15)
// Archivos dedicados: ElementExtensionTimeRequestController
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session', 'throttle:60,1'])
    ->prefix('solicitudes-ampliacion-elemento')
    ->group(function () {
        Route::get('/', [ElementExtensionTimeRequestController::class, 'index']);
        Route::get('/proximas-vencer', [ElementExtensionTimeRequestController::class, 'upcomingElements']);
        Route::get('/{id}', [ElementExtensionTimeRequestController::class, 'show']);
        Route::post('/', [ElementExtensionTimeRequestController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::patch('/{id}/cancelar', [ElementExtensionTimeRequestController::class, 'cancel']);
    });

// ============================================
// Aprobación de Criterios por Bloques (HU-010)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session', 'throttle:60,1'])->group(function () {
    Route::get('aprobaciones-criterios', [CriterionApprovalController::class, 'listApprovals']);
    Route::get('aprobaciones-criterios/{approvalId}', [CriterionApprovalController::class, 'showApproval']);
    Route::post('criterios/{criterionId}/aprobar', [CriterionApprovalController::class, 'approveCriterion'])->middleware('throttle:10,1');
    Route::post('criterios/{criterionId}/rechazar', [CriterionApprovalController::class, 'rejectCriterion'])->middleware('throttle:10,1');
    // Aprobación individual de evidencias dentro de un bloque de criterio
    Route::get('criterios/{criterionId}/evidencias/aprobaciones', [CriterionApprovalController::class, 'listEvidenceApprovals']);
    Route::post('criterios/{criterionId}/evidencias/{evidenceId}/aprobar', [CriterionApprovalController::class, 'approveIndividualEvidence'])->middleware('throttle:10,1');
    Route::post('criterios/{criterionId}/evidencias/{evidenceId}/rechazar', [CriterionApprovalController::class, 'rejectIndividualEvidence'])->middleware('throttle:10,1');
});

// ============================================================
// Aprobación de Elementos por Bloques (HU-010 modelo flexible)
// ============================================================
Route::middleware(['auth:sanctum', 'refresh.session', 'throttle:60,1'])->group(function () {
    Route::get('aprobaciones-elementos', [ElementApprovalController::class, 'listApprovals']);
    Route::get('aprobaciones-elementos/{aprobacionId}', [ElementApprovalController::class, 'showApproval']);
    Route::post('elementos/{elementoId}/aprobar', [ElementApprovalController::class, 'approveElement'])->middleware('throttle:10,1');
    Route::post('elementos/{elementoId}/rechazar', [ElementApprovalController::class, 'rejectElement'])->middleware('throttle:10,1');
    // Aprobación individual de hijos dentro de un bloque de elemento
    Route::post('elementos/{padreId}/hijos/{hijoId}/aprobar', [ElementApprovalController::class, 'approveIndividualChild'])->middleware('throttle:10,1');
    Route::post('elementos/{padreId}/hijos/{hijoId}/rechazar', [ElementApprovalController::class, 'rejectIndividualChild'])->middleware('throttle:10,1');
});

// ============================================
// Archivos (HU-008 - Subida de Evidencias)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session', 'global.filter.context'])->prefix('archivos')->group(function () {
    Route::get('/test-data', [FileController::class, 'getTestData']); // TEMPORAL

    Route::get('/', [FileController::class, 'index'])
        ->middleware('permission:archivos.view');
    Route::post('/', [FileController::class, 'store'])
        ->middleware(['throttle:10,1', 'permission:archivos.upload']);
    Route::get('/{archivo}', [FileController::class, 'show'])
        ->middleware('permission:archivos.view');
    Route::get('/{archivo}/download', [FileController::class, 'download'])
        ->middleware('permission:archivos.download');
    Route::delete('/{archivo}', [FileController::class, 'destroy'])
        ->middleware('permission:archivos.delete');
    Route::post('/{archivo}/make-public', [FileController::class, 'makePublic'])
        ->middleware('permission:archivos.make_public');
    Route::post('/{archivo}/revoke-public', [FileController::class, 'revokePublic'])
        ->middleware('permission:archivos.make_public');
    Route::post('/bulk-make-public', [FileController::class, 'bulkMakePublic'])
        ->middleware('permission:archivos.make_public');
});

// Acceso público mediante token (SIN autenticación - para SINAES/informes)
Route::get('/p/{token}', [FileController::class, 'publicAccess'])
    ->name('public.files.access');
Route::get('/p/{token}/carpeta', [FileController::class, 'publicFolder'])
    ->name('public.files.folder');

// ============================================
// Solicitudes de Ampliación - Modelo Flexible (HU-016b)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session'])->prefix('elemento-solicitudes-ampliacion')->group(function () {
    Route::get('/', [FlexibleExtensionRequestController::class, 'index']);
    Route::get('/pendientes', [FlexibleExtensionRequestController::class, 'pending']);
    Route::get('/mis-solicitudes', [FlexibleExtensionRequestController::class, 'mySolicitudes']);
    Route::get('/{id}', [FlexibleExtensionRequestController::class, 'show']);
    Route::post('/', [FlexibleExtensionRequestController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::post('/{id}/aprobar', [FlexibleExtensionRequestController::class, 'approve']);
    Route::post('/{id}/rechazar', [FlexibleExtensionRequestController::class, 'reject']);
});

// ============================================
// Archivos de Elementos (HU-008 modelo flexible)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session', 'global.filter.context'])->prefix('elementos-archivos')->group(function () {
    Route::get('/', [ElementFileController::class, 'index'])
        ->middleware('permission:archivos.view');
    Route::post('/', [ElementFileController::class, 'store'])
        ->middleware(['throttle:10,1', 'permission:archivos.upload']);
    Route::get('/{archivo}', [ElementFileController::class, 'show'])
        ->middleware('permission:archivos.view');
    Route::delete('/{archivo}', [ElementFileController::class, 'destroy'])
        ->middleware('permission:archivos.delete');
    Route::get('/{archivo}/download', [ElementFileController::class, 'download'])
        ->middleware('permission:archivos.download');
    Route::post('/{archivo}/make-public', [ElementFileController::class, 'makePublic'])
        ->middleware('permission:archivos.make_public');
    Route::post('/{archivo}/revoke-public', [ElementFileController::class, 'revokePublic'])
        ->middleware('permission:archivos.make_public');
});

// ============================================
// Rutas de Gestión de Usuarios (HU-002)
// ============================================
// Protegidas con:
// - auth:sanctum: Requiere usuario autenticado con token válido
// - permission:usuarios.edit: Requiere permiso específico para editar usuarios
Route::prefix('admin/users')->middleware(['auth:sanctum', 'permission:usuarios.view|usuarios.edit'])->group(function () {
    Route::get('/', [UserController::class, 'index']);
    // Activa un usuario cambiando su estado a "active"
    // Ejemplo: Patch/api/admin/users/5/activate
    Route::patch('{user}/activate', [UserController::class, 'activate'])
        ->missing(fn (Request $request) => response()->json(['error' => 'Usuario no encontrado'], 404));
    // Desactiva un usuario cambiando su estado a "inactive"
    // Ejemplo: Patch/api/admin/users/5/deactivate
    Route::patch('{user}/deactivate', [UserController::class, 'deactivate'])
        ->missing(fn (Request $request) => response()->json(['error' => 'Usuario no encontrado'], 404));
    Route::put('{user}/role', [UserController::class, 'assignRole'])
        ->missing(fn (Request $request) => response()->json(['error' => 'Usuario no encontrado'], 404));
    Route::put('{user}/permissions', [UserController::class, 'assignPermissions'])
        ->missing(fn (Request $r) => response()->json(['error' => 'Usuario no encontrado'], 404));
    Route::put('{user}/careers', [UserController::class, 'assignCareers'])
        ->middleware('permission:usuarios.assign|usuarios.approve')
        ->missing(fn (Request $r) => response()->json(['error' => 'Usuario no encontrado'], 404));
});

// ============================================
// Gestión de Roles y Permisos (Sistema Dinámico)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session'])->group(function () {

    // ===== PERMISOS =====
    // Endpoint público para frontend (cualquier usuario autenticado)
    Route::get('admin/permissions', [PermissionController::class, 'index']);

    // ===== ROLES =====
    Route::prefix('roles')->group(function () {
        // Ver roles (Administrador puede ver para asignar, Superusuario para gestionar)
        Route::get('/', [RoleController::class, 'listRoles'])
            ->middleware('permission:roles.view');

        Route::get('/grouped', [RoleController::class, 'getRolesGrouped'])
            ->middleware('permission:roles.view');

        Route::get('/modules', [RoleController::class, 'getModulesStructure'])
            ->middleware('permission:roles.view');

        Route::get('/permisos', [RoleController::class, 'listPermissions'])
            ->middleware('permission:roles.view');

        Route::get('/{id}', [RoleController::class, 'showRole'])
            ->middleware('permission:roles.view');

        // Crear roles (solo Superusuario)
        Route::post('/', [RoleController::class, 'createRole'])
            ->middleware('permission:roles.create');

        // Editar roles (solo Superusuario)
        Route::put('/{id}', [RoleController::class, 'updateRole'])
            ->middleware('permission:roles.edit');

        // Eliminar roles (solo Superusuario)
        Route::delete('/{id}', [RoleController::class, 'deleteRole'])
            ->middleware('permission:roles.delete');

        // Activar rol
        Route::patch('/{id}/activate', [RoleController::class, 'activateRole'])
            ->middleware('permission:roles.edit');

        // Desactivar rol
        Route::patch('/{id}/deactivate', [RoleController::class, 'deactivateRole'])
            ->middleware('permission:roles.edit');
    });
});

// ============================================
// Compromisos de Mejora
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session', 'global.filter.context'])->prefix('compromisos-de-mejora')->group(function () {
    Route::middleware(['permission:compromisos_mejora.view'])->group(function () {
        Route::get('/', [ImprovementCommitmentController::class, 'listCommitments']);
        Route::get('/usuario/{usuarioId}', [ImprovementCommitmentController::class, 'getByUser']);
        Route::get('/evidencia/{evidenciaId}', [ImprovementCommitmentController::class, 'getByEvidence']);
        Route::get('/{id}', [ImprovementCommitmentController::class, 'showCommitment']);
    });

    Route::post('/', [ImprovementCommitmentController::class, 'createCommitment'])
        ->middleware('permission:compromisos_mejora.create');
    Route::put('/{id}', [ImprovementCommitmentController::class, 'updateCommitment'])
        ->middleware('permission:compromisos_mejora.edit');
    Route::patch('/{id}/active', [ImprovementCommitmentController::class, 'setActive'])
        ->middleware('permission:compromisos_mejora.edit');
});

// ============================================
// Compromisos de Mejora — Modelo Flexible (ELEMENTO)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session', 'global.filter.context'])->prefix('compromisos-elementos')->group(function () {
    Route::middleware(['permission:compromisos_mejora.view'])->group(function () {
        Route::get('/', [ElementCommitmentController::class, 'listCommitments']);
        Route::get('/usuario/{usuarioId}', [ElementCommitmentController::class, 'getByUser']);
        Route::get('/elemento/{elementoId}', [ElementCommitmentController::class, 'getByElemento']);
        Route::get('/{id}', [ElementCommitmentController::class, 'showCommitment']);
    });

    Route::post('/', [ElementCommitmentController::class, 'createCommitment'])
        ->middleware('permission:compromisos_mejora.create');
    Route::put('/{id}', [ElementCommitmentController::class, 'updateCommitment'])
        ->middleware('permission:compromisos_mejora.edit');
    Route::patch('/{id}/active', [ElementCommitmentController::class, 'setActive'])
        ->middleware('permission:compromisos_mejora.edit');
});

// ============================================
Route::middleware(['auth:sanctum', 'refresh.session'])->prefix('notificaciones')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/no-leidas/contador', [NotificationController::class, 'getUnreadCount']);
    Route::post('/marcar-todas-leidas', [NotificationController::class, 'markAllAsRead']);
    Route::post('/{id}/marcar-leida', [NotificationController::class, 'markAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
});

// ============================================
// Bitácora del Sistema (HU-005) - Solo Superusuario
// ============================================
Route::prefix('bitacora')->middleware(['auth:sanctum', 'refresh.session', 'role:Superusuario'])->group(function () {
    Route::get('/', [AuditLogController::class, 'index']);
    Route::get('/modulos', [AuditLogController::class, 'getModules']);
    Route::get('/tipos-accion', [ActionTypeController::class, 'index']);
    Route::get('/export', [AuditLogController::class, 'export']);
    Route::get('/{auditLog}', [AuditLogController::class, 'show']);
});

// ============================================
// Rutas de Desarrollo (solo para ambiente local)
// ============================================
if (App::environment('local')) {
    Route::prefix('dev')->group(function () {
        Route::post('/users', [DevUserController::class, 'store'])->middleware('auth:sanctum');       // POST /api/dev/users
        Route::post('/comments', [DevCommentController::class, 'store'])->middleware('auth:sanctum'); // POST /api/dev/comments

        // Autenticación temporal para pruebas de middleware
        Route::post('/login', [\App\Http\Controllers\DevAuthController::class, 'login']);
        Route::post('/logout', [\App\Http\Controllers\DevAuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('/me', [\App\Http\Controllers\DevAuthController::class, 'me'])->middleware('auth:sanctum');

        // Ver bitácora sin autenticación (SOLO PARA PRUEBAS)
        Route::get('/bitacora', function () {
            try {
                $logs = \DB::table('BITACORA')
                    ->join('TIPO_ACCION', 'BITACORA.tipo_accion_id', '=', 'TIPO_ACCION.tipo_accion_id')
                    ->leftJoin('USUARIO', 'BITACORA.usuario_id', '=', 'USUARIO.usuario_id')
                    ->select(
                        'BITACORA.bitacora_id',
                        'USUARIO.nombre as usuario',
                        'TIPO_ACCION.descripcion as accion',
                        'BITACORA.modulo',
                        'BITACORA.detalle',
                        'BITACORA.fecha_hora'
                    )
                    ->orderBy('BITACORA.fecha_hora', 'desc')
                    ->limit(10)
                    ->get();

                return response()->json($logs);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], 500);
            }
        })->middleware('auth:sanctum');
    });
}

// ============================================
// Rutas de Prueba
// ============================================
Route::get('/ping', function () {
    return response()->json([
        'ok' => true,
        'scope' => 'root',
        'base' => base_path(),
        'mark' => 'X1',
    ]);
});
